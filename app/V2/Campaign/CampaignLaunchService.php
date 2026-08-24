<?php

namespace App\V2\Campaign;

use App\Jobs\V2\SyncCampaignLeadsAndRunJob;
use App\Models\Campaign;
use App\Models\CampaignList;
use App\Models\User;
use App\Models\V2Campaign;
use App\V2\Services\UserBootstrapService;

class CampaignLaunchService
{
    public function __construct(
        private readonly LegacyCampaignBridgeService $bridge,
        private readonly CampaignLeadSyncService $sync,
        private readonly CampaignLinkedInGuard $guard,
        private readonly UserBootstrapService $bootstrap,
    ) {}

    /**
     * @return array{blocked: bool, queued: bool, v2_campaign_id: int|null, message?: string}
     */
    public function launchFromLegacy(Campaign $legacy, User $user): array
    {
        if ($this->guard->isUserDisconnected((int) $user->id)) {
            return [
                'blocked' => true,
                'queued' => false,
                'v2_campaign_id' => null,
                'message' => 'LinkedIn disconnected — reconnect on Integrations before launching.',
            ];
        }

        if (CampaignList::query()->where('campaign_id', $legacy->id)->count() === 0) {
            return [
                'blocked' => true,
                'queued' => false,
                'v2_campaign_id' => null,
                'message' => 'Add at least one lead list before launching.',
            ];
        }

        $organization = $this->bootstrap->ensurePersonalOrganization($user);
        $v2 = $this->bridge->sync($legacy, $user, (int) $organization->id);
        $this->sync->resetProgressToStart($v2);

        $legacy->forceFill(['status' => 'active'])->save();

        return $this->queueLeadSyncAndRun($v2, (int) $organization->id);
    }

    /**
     * @return array{blocked: bool, queued: bool, v2_campaign_id: int|null, message?: string}
     */
    public function queueLeadSyncAndRun(V2Campaign $campaign, ?int $organizationId): array
    {
        if ($this->guard->isUserDisconnected((int) $campaign->user_id)) {
            return [
                'blocked' => true,
                'queued' => false,
                'v2_campaign_id' => $campaign->id,
                'message' => 'LinkedIn disconnected — reconnect on Integrations before launching.',
            ];
        }

        $this->sync->markSyncing($campaign);
        SyncCampaignLeadsAndRunJob::dispatch($campaign->id, $organizationId);

        return [
            'blocked' => false,
            'queued' => true,
            'v2_campaign_id' => $campaign->id,
            'message' => 'Preparing leads — campaign will start via Unipile shortly.',
        ];
    }
}
