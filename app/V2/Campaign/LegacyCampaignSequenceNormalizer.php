<?php

namespace App\V2\Campaign;

/**
 * Converts legacy GoJS flat node_model + link_model into v2 nested sequence format.
 *
 * Legacy CRM sequences use 0-based keys (0 = send invite, 1 = wait, …).
 * v2 ProcessCampaignLeadJob expects 1-based keys and condition branches.
 */
class LegacyCampaignSequenceNormalizer
{
    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $links
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $nodes, array $links = []): array
    {
        $nodes = array_values(array_filter($nodes, fn ($node) => is_array($node)));

        if ($nodes === []) {
            return [];
        }

        if ($this->isV2Format($nodes)) {
            return $nodes;
        }

        if ($links !== [] && $this->hasLegacyInviteGraph($nodes, $links)) {
            return $this->convertLegacyInviteGraph($nodes, $links);
        }

        return $this->rekeyLinearLegacy($nodes);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    public function isV2Format(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') !== 'condition') {
                continue;
            }

            $branches = $node['branches'] ?? null;
            if (! is_array($branches)) {
                continue;
            }

            if (array_key_exists('accepted', $branches) || array_key_exists('not_accepted', $branches)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    public function firstExecutableNodeKey(array $nodes, array $links = []): int
    {
        $nodes = $this->normalize($nodes, $links);
        if ($nodes === []) {
            return 1;
        }

        $resolver = new CampaignSequenceResolver();
        $topLevel = $resolver->topLevelNodes($nodes);

        return (int) ($topLevel[0]['key'] ?? 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $links
     */
    private function hasLegacyInviteGraph(array $nodes, array $links): bool
    {
        $byKey = $this->indexByKey($nodes);

        return $this->findSendInviteKey($byKey) !== null
            && ($this->findConditionKey($byKey, 'Accepted') !== null
                || $this->findConditionKey($byKey, 'Still not accepted') !== null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $links
     * @return array<int, array<string, mixed>>
     */
    private function convertLegacyInviteGraph(array $nodes, array $links): array
    {
        $byKey = $this->indexByKey($nodes);
        $adj = $this->buildAdjacency($links);
        $inviteKey = (int) $this->findSendInviteKey($byKey);
        $acceptedKey = $this->findConditionKey($byKey, 'Accepted');
        $notAcceptedKey = $this->findConditionKey($byKey, 'Still not accepted');

        $inviteNode = $this->normalizeNode($byKey[$inviteKey], 1);

        $notAcceptedBranch = [];
        foreach ($adj[$inviteKey] ?? [] as $childKey) {
            $child = $byKey[$childKey] ?? null;
            if (! is_array($child) || ($child['type'] ?? '') !== 'delay') {
                continue;
            }

            if (($child['pos'] ?? '') === 'left' || $notAcceptedKey === null || $childKey < ($acceptedKey ?? PHP_INT_MAX)) {
                $notAcceptedBranch[] = $this->normalizeNode($child);
            }
        }

        if ($notAcceptedKey !== null) {
            $notAcceptedBranch = array_merge(
                $notAcceptedBranch,
                $this->collectBranchNodes($notAcceptedKey, $byKey, $adj),
            );
        }

        $acceptedBranch = $acceptedKey !== null
            ? $this->collectBranchNodes($acceptedKey, $byKey, $adj)
            : [];

        $nextKey = 3;
        $acceptedBranch = $this->assignBranchKeys($acceptedBranch, $nextKey);
        $notAcceptedBranch = $this->assignBranchKeys($notAcceptedBranch, $nextKey);

        $condition = [
            'key' => 2,
            'type' => 'condition',
            'value' => 'accepted',
            'label' => 'Invite Accepted?',
            'branches' => [
                'accepted' => $acceptedBranch,
                'not_accepted' => $notAcceptedBranch,
            ],
        ];

        $sequence = [$inviteNode, $condition];

        if ($this->findEndNode($byKey) !== null) {
            $sequence[] = [
                'key' => max(99, $nextKey),
                'type' => 'end',
                'value' => 'end',
                'label' => 'End of sequence',
            ];
        }

        return $sequence;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function rekeyLinearLegacy(array $nodes): array
    {
        $filtered = [];

        foreach ($nodes as $node) {
            if (! $this->isExecutableLegacyNode($node)) {
                continue;
            }

            $filtered[] = $node;
        }

        usort($filtered, fn ($a, $b) => ((int) ($a['key'] ?? 0)) <=> ((int) ($b['key'] ?? 0)));

        $result = [];
        $nextKey = 1;

        foreach ($filtered as $node) {
            if (($node['type'] ?? '') === 'end') {
                $result[] = $this->normalizeNode($node, 99);
                continue;
            }

            $result[] = $this->normalizeNode($node, $nextKey);
            $nextKey++;
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byKey
     * @param  array<int, array<int>>  $adj
     * @return array<int, array<string, mixed>>
     */
    private function collectBranchNodes(int $startKey, array $byKey, array $adj): array
    {
        $result = [];
        $visited = [];
        $queue = $adj[$startKey] ?? [];

        while ($queue !== []) {
            $key = (int) array_shift($queue);
            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            $node = $byKey[$key] ?? null;
            if (! is_array($node)) {
                continue;
            }

            $type = (string) ($node['type'] ?? '');
            $label = (string) ($node['label'] ?? '');

            if ($type === 'condition' && in_array($label, ['Accepted', 'Still not accepted'], true)) {
                foreach ($adj[$key] ?? [] as $next) {
                    $queue[] = (int) $next;
                }

                continue;
            }

            if ($type === 'end' || (($node['value'] ?? '') === 'end')) {
                continue;
            }

            if (in_array($type, ['action', 'delay'], true) && $this->isExecutableLegacyNode($node)) {
                $result[] = $this->normalizeNode($node);
            }

            foreach ($adj[$key] ?? [] as $next) {
                $queue[] = (int) $next;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $branch
     * @return array<int, array<string, mixed>>
     */
    private function assignBranchKeys(array $branch, int &$nextKey): array
    {
        $result = [];

        foreach ($branch as $node) {
            $result[] = $this->normalizeNode($node, $nextKey);
            $nextKey++;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function normalizeNode(array $node, ?int $key = null): array
    {
        $normalized = $node;

        if ($key !== null) {
            $normalized['key'] = $key;
        }

        if (($normalized['type'] ?? '') === 'delay') {
            $normalized['type'] = 'delay';
        }

        if (($normalized['value'] ?? '') === 'send-invites') {
            $normalized['value'] = 'send-invite';
        }

        if (isset($normalized['message']) && ! isset($normalized['config'])) {
            $normalized['config'] = ['message' => $normalized['message']];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function isExecutableLegacyNode(array $node): bool
    {
        $type = (string) ($node['type'] ?? '');
        $value = (string) ($node['value'] ?? '');
        $label = (string) ($node['label'] ?? '');

        if ($type === 'end' || $value === 'end') {
            return true;
        }

        if ($type === 'condition' && in_array($label, ['Accepted', 'Still not accepted'], true)) {
            return false;
        }

        if ($type === 'action' && in_array($value, ['add-action'], true)) {
            return false;
        }

        return in_array($type, ['action', 'delay', 'end'], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function indexByKey(array $nodes): array
    {
        $byKey = [];

        foreach ($nodes as $node) {
            $byKey[(int) ($node['key'] ?? -1)] = $node;
        }

        return $byKey;
    }

    /**
     * @param  array<int, array<string, mixed>>  $links
     * @return array<int, array<int>>
     */
    private function buildAdjacency(array $links): array
    {
        $adj = [];

        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }

            $from = (int) ($link['from'] ?? -1);
            $to = (int) ($link['to'] ?? -1);

            if ($from < 0 || $to < 0) {
                continue;
            }

            $adj[$from] ??= [];
            $adj[$from][] = $to;
        }

        return $adj;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byKey
     */
    private function findSendInviteKey(array $byKey): ?int
    {
        foreach ($byKey as $key => $node) {
            $value = (string) ($node['value'] ?? '');

            if (($node['type'] ?? '') === 'action' && in_array($value, ['send-invites', 'send-invite'], true)) {
                return (int) $key;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byKey
     */
    private function findConditionKey(array $byKey, string $label): ?int
    {
        foreach ($byKey as $key => $node) {
            if (($node['type'] ?? '') === 'condition' && ($node['label'] ?? '') === $label) {
                return (int) $key;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byKey
     */
    private function findEndNode(array $byKey): ?array
    {
        foreach ($byKey as $node) {
            if (($node['type'] ?? '') === 'end' || ($node['value'] ?? '') === 'end') {
                return $node;
            }
        }

        return null;
    }
}
