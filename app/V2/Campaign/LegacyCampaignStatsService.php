<?php

namespace App\V2\Campaign;

use App\Models\Campaign;
use App\Models\V2Campaign;

/**
 * Maps Unipile-backed v2 campaign progress onto legacy CRM campaign rows.
 */
class LegacyCampaignStatsService
{
    public function __construct(
        private readonly LegacyCampaignBridgeService $bridge,
    ) {}

    public function findBridged(int $legacyCampaignId, int $userId): ?V2Campaign
    {
        return $this->bridge->findBridged($legacyCampaignId, $userId);
    }

    public function acceptRate(int $legacyCampaignId, int $userId): ?int
    {
        $v2 = $this->findBridged($legacyCampaignId, $userId);
        if (! $v2) {
            return null;
        }

        return $v2->acceptRate();
    }

    /**
     * Prefer live v2 execution status over stale legacy `campaigns.status`.
     */
    public function displayStatus(Campaign $legacy, int $userId): string
    {
        $v2 = $this->findBridged((int) $legacy->id, $userId);
        if (! $v2) {
            return $this->normalizeLegacyStatus((string) $legacy->status);
        }

        return match ($v2->status) {
            'preparing' => 'preparing',
            'running', 'active' => 'running',
            'completed' => 'completed',
            'paused' => 'paused',
            'draft' => 'draft',
            default => $this->normalizeLegacyStatus((string) $legacy->status),
        };
    }

    /**
     * Keep legacy row aligned when v2 finishes or pauses.
     */
    public function syncLegacyStatus(Campaign $legacy, V2Campaign $v2): void
    {
        $mapped = match ($v2->status) {
            'running', 'active', 'preparing' => 'active',
            'completed' => 'completed',
            'paused' => 'stop',
            default => null,
        };

        if ($mapped !== null && $legacy->status !== $mapped) {
            $legacy->forceFill(['status' => $mapped])->save();
        }
    }

    private function normalizeLegacyStatus(string $status): string
    {
        return match ($status) {
            'active' => 'running',
            'stop' => 'paused',
            default => $status,
        };
    }
}
