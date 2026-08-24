<?php

namespace App\V2\Campaign;

use App\Models\Campaign;
use App\Models\User;
use App\Models\V2Campaign;

class CampaignStatusService
{
    public function __construct(
        private readonly LegacyCampaignBridgeService $bridge,
        private readonly LegacyCampaignStatsService $stats,
    ) {}

    public function pause(Campaign $legacy, User $user): void
    {
        $legacy->forceFill(['status' => 'stop'])->save();

        $v2 = $this->bridge->findBridged((int) $legacy->id, (int) $user->id);
        if ($v2) {
            $v2->forceFill(['status' => 'paused'])->save();
        }
    }

    public function canPause(string $displayStatus): bool
    {
        return in_array(strtolower($displayStatus), ['running', 'active', 'preparing'], true);
    }

    public function canRun(string $displayStatus): bool
    {
        return ! in_array(strtolower($displayStatus), ['running', 'active', 'preparing', 'completed', 'draft'], true);
    }

    public function syncLegacyFromV2(Campaign $legacy, V2Campaign $v2): void
    {
        $this->stats->syncLegacyStatus($legacy, $v2);
    }
}
