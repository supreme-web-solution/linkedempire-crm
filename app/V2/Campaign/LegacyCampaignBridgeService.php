<?php

namespace App\V2\Campaign;

use App\Models\Audience;
use App\Models\Campaign;
use App\Models\CampaignList;
use App\Models\CampaignSequenceEndorse;
use App\Models\SnLeadList;
use App\Models\User;
use App\Models\V2Campaign;
use App\Models\V2CampaignList;

/**
 * Copies legacy `campaigns` rows into `v2_campaigns` for Unipile execution.
 */
class LegacyCampaignBridgeService
{
    public function __construct(
        private readonly LegacyCampaignSequenceNormalizer $sequenceNormalizer = new LegacyCampaignSequenceNormalizer(),
    ) {}

    public function findBridged(int $legacyCampaignId, int $userId): ?V2Campaign
    {
        return V2Campaign::query()
            ->where('user_id', $userId)
            ->where('meta->legacy_campaign_id', $legacyCampaignId)
            ->first();
    }

    public function sync(Campaign $legacy, User $user, int $organizationId): V2Campaign
    {
        $sequence = CampaignSequenceEndorse::query()
            ->where('campaign_id', $legacy->id)
            ->first();

        $nodeModel = $this->decodeJson($sequence?->node_model);
        $linkModel = $this->decodeJson($sequence?->link_model);
        $nodeModel = $this->sequenceNormalizer->normalize($nodeModel, $linkModel);
        $processCondition = $this->decodeJson($legacy->process_condition);

        $meta = [
            'legacy_campaign_id' => (int) $legacy->id,
            'process_condition' => $processCondition,
            'bridged_at' => now()->toIso8601String(),
        ];

        $v2 = $this->findBridged((int) $legacy->id, (int) $user->id);

        $payload = [
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'name' => $legacy->name ?: 'Campaign',
            'sequence_type' => $legacy->sequence_type ?: 'custom',
            'node_model' => $nodeModel,
            'link_model' => $linkModel,
            'meta' => $meta,
        ];

        if ($v2) {
            $v2->forceFill($payload)->save();
        } else {
            $v2 = V2Campaign::query()->create(array_merge($payload, [
                'status' => 'draft',
            ]));
        }

        $this->syncLeadLists($v2, (int) $legacy->id);

        return $v2->fresh();
    }

    private function syncLeadLists(V2Campaign $v2, int $legacyCampaignId): void
    {
        $lists = CampaignList::query()->where('campaign_id', $legacyCampaignId)->get();
        $attachedIds = [];

        foreach ($lists as $list) {
            $listSrc = $this->normalizeListSrc((string) $list->list_source);
            $listName = $this->resolveListName((string) $list->list_hash, $listSrc);

            $entry = V2CampaignList::query()->firstOrCreate(
                [
                    'campaign_id' => $v2->id,
                    'list_hash' => (string) $list->list_hash,
                    'list_src' => $listSrc,
                ],
                ['list_name' => $listName],
            );

            if ($listName && $entry->list_name !== $listName) {
                $entry->forceFill(['list_name' => $listName])->save();
            }

            $attachedIds[] = (int) $entry->id;
        }

        if ($attachedIds !== []) {
            V2CampaignList::query()
                ->where('campaign_id', $v2->id)
                ->whereNotIn('id', $attachedIds)
                ->delete();
        }
    }

    private function normalizeListSrc(string $src): string
    {
        return in_array($src, ['sn', 'aud'], true) ? $src : 'aud';
    }

    private function resolveListName(string $listHash, string $listSrc): ?string
    {
        if ($listSrc === 'sn') {
            return SnLeadList::query()->where('list_hash', $listHash)->value('name');
        }

        return Audience::query()->where('audience_id', $listHash)->value('audience_name');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
