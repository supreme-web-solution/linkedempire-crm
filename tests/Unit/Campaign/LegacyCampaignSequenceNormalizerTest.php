<?php

namespace Tests\Unit\Campaign;

use App\V2\Campaign\LegacyCampaignSequenceNormalizer;
use Tests\TestCase;

class LegacyCampaignSequenceNormalizerTest extends TestCase
{
    public function test_legacy_invite_graph_starts_with_send_invite_not_wait(): void
    {
        $normalizer = new LegacyCampaignSequenceNormalizer();

        $nodes = [
            ['key' => 0, 'type' => 'action', 'value' => 'send-invites', 'label' => 'Send an invite', 'message' => 'Hi'],
            ['key' => 1, 'type' => 'delay', 'value' => 5, 'time' => 'days', 'label' => '5 days', 'pos' => 'left'],
            ['key' => 2, 'type' => 'condition', 'value' => 'accepted', 'label' => 'Accepted', 'pos' => 'right'],
            ['key' => 3, 'type' => 'condition', 'value' => 'not accepted', 'label' => 'Still not accepted', 'pos' => 'left'],
            ['key' => 4, 'type' => 'delay', 'value' => 1, 'time' => 'hours', 'label' => '1 hour', 'pos' => 'right'],
            ['key' => 5, 'type' => 'action', 'value' => 'message', 'label' => 'Message', 'message' => 'Thanks for connecting'],
        ];

        $links = [
            ['from' => 0, 'to' => 1],
            ['from' => 0, 'to' => 2],
            ['from' => 1, 'to' => 3],
            ['from' => 2, 'to' => 4],
            ['from' => 4, 'to' => 5],
        ];

        $normalized = $normalizer->normalize($nodes, $links);

        $this->assertSame('action', $normalized[0]['type'] ?? null);
        $this->assertSame('send-invite', $normalized[0]['value'] ?? null);
        $this->assertSame(1, $normalized[0]['key'] ?? null);
        $this->assertSame('condition', $normalized[1]['type'] ?? null);
        $this->assertArrayHasKey('accepted', $normalized[1]['branches'] ?? []);
        $this->assertSame('message', $normalized[1]['branches']['accepted'][1]['value'] ?? null);
        $this->assertSame(1, $normalizer->firstExecutableNodeKey($nodes, $links));
    }

    public function test_zero_day_delay_normalizes_on_not_accepted_branch(): void
    {
        $normalizer = new LegacyCampaignSequenceNormalizer();

        $nodes = [
            ['key' => 0, 'type' => 'action', 'value' => 'send-invites', 'label' => 'Send an invite'],
            ['key' => 1, 'type' => 'delay', 'value' => 0, 'time' => 'days', 'label' => 'No delay', 'pos' => 'left'],
            ['key' => 2, 'type' => 'condition', 'value' => 'accepted', 'label' => 'Accepted'],
            ['key' => 3, 'type' => 'condition', 'value' => 'not accepted', 'label' => 'Still not accepted'],
        ];

        $links = [
            ['from' => 0, 'to' => 1],
            ['from' => 0, 'to' => 2],
            ['from' => 1, 'to' => 3],
        ];

        $normalized = $normalizer->normalize($nodes, $links);
        $notAccepted = $normalized[1]['branches']['not_accepted'] ?? [];

        $this->assertNotEmpty($notAccepted);
        $this->assertSame(0, $notAccepted[0]['value'] ?? null);
    }
}
