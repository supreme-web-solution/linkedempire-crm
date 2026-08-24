<?php

namespace App\Jobs;

use App\Models\SnLead;
use App\Models\User;
use App\V2\Services\FullEnrichClient;
use App\V2\Services\LeadEnrichmentPersister;
use App\V2\Services\LeadEnrichmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchSnEmailBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public bool $deleteWhenMissingModels = true;

    /**
     * @param  array<int>  $snLeadIds
     */
    public function __construct(
        public readonly array $snLeadIds,
        public readonly int $userId,
        public readonly string $listHash,
    ) {}

    /**
     * @param  array<int>  $snLeadIds
     */
    public static function dispatchChunked(array $snLeadIds, int $userId, string $listHash): void
    {
        $ids = array_values(array_unique(array_map('intval', $snLeadIds)));
        if ($ids === []) {
            return;
        }

        $chunkSize = max(1, (int) config('services.email_scraping.job_chunk_size', 5));
        $stagger = max(0, (int) config('services.email_scraping.job_chunk_stagger_seconds', 3));

        foreach (array_chunk($ids, $chunkSize) as $i => $chunk) {
            $pending = self::dispatch($chunk, $userId, $listHash);
            if ($i > 0 && $stagger > 0) {
                $pending->delay(now()->addSeconds($i * $stagger));
            }
        }
    }

    public function handle(
        LeadEnrichmentService $enrichmentService,
        LeadEnrichmentPersister $persister,
    ): void {
        $user = User::find($this->userId);
        if (! $user) {
            $this->markLeadsRetryable($this->snLeadIds, 'User not found for enrichment job.');

            return;
        }

        FullEnrichClient::resetCreditsExhausted();

        $this->checkAndResetDailyLimit($user);
        $user->refresh();

        $dailyLimit = (int) config('services.email_scraping.daily_limit_per_user', 100);
        $leads = SnLead::query()->whereIn('id', $this->snLeadIds)->get();

        if ($leads->isEmpty()) {
            $this->markLeadsRetryable($this->snLeadIds, 'No matching leads for enrichment job.');

            return;
        }

        $lookupsDone = 0;
        $startedAt = microtime(true);
        $softDeadline = $startedAt + max(60, $this->timeout - 150);
        $remainingIds = [];

        foreach ($leads as $index => $lead) {
            if (microtime(true) >= $softDeadline) {
                $remainingIds = $leads->slice($index)->pluck('id')->map(fn ($id) => (int) $id)->all();
                break;
            }

            if (! empty($lead->email)) {
                $lead->update(['email_fetch_status' => 'completed']);
                continue;
            }

            if ($lead->email_fetch_status === 'completed') {
                continue;
            }

            if ($user->daily_profile_email_scraping_count >= $dailyLimit) {
                SnLead::query()
                    ->whereIn('id', $leads->slice($index)->pluck('id'))
                    ->whereIn('email_fetch_status', ['pending', 'processing'])
                    ->update(['email_fetch_status' => null, 'email_fetch_attempted_at' => null]);
                break;
            }

            $identifier = trim((string) ($lead->lid ?: $lead->sn_lid ?: ''));
            if ($identifier === '') {
                $lead->update([
                    'email_fetch_attempted_at' => now(),
                    'email_fetch_status' => 'completed',
                ]);
                continue;
            }

            $lead->update(['email_fetch_status' => 'processing']);

            if ($lookupsDone > 0) {
                $this->humanPause();
            }
            $lookupsDone++;

            try {
                $lead->loadMissing('company');
                $result = $enrichmentService->enrich($user, $enrichmentService->inputFromSnLead($lead));
                $persister->persistSnLead($lead, $result, $user->id);
            } catch (\Throwable $e) {
                $lead->update(['email_fetch_status' => 'failed']);

                Log::error('[FetchSnEmailBatchJob] enrichment failed', [
                    'sn_lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (! $result->isSoftTimeout()) {
                $user->increment('daily_profile_email_scraping_count');
                $user->refresh();
            }
        }

        if ($remainingIds !== []) {
            SnLead::query()
                ->whereIn('id', $remainingIds)
                ->whereIn('email_fetch_status', ['pending', 'processing'])
                ->update(['email_fetch_status' => 'pending']);

            self::dispatch($remainingIds, $this->userId, $this->listHash)
                ->delay(now()->addSeconds(5));
        }
    }

    public function failed(?\Throwable $e): void
    {
        $this->markLeadsRetryable($this->snLeadIds, $e?->getMessage() ?: 'Enrichment job failed.');
    }

    /**
     * @param  array<int>  $ids
     */
    private function markLeadsRetryable(array $ids, string $reason): void
    {
        if ($ids === []) {
            return;
        }

        SnLead::query()
            ->whereIn('id', $ids)
            ->whereIn('email_fetch_status', ['pending', 'processing'])
            ->update([
                'email_fetch_status' => 'timed_out',
                'email_fetch_attempted_at' => now(),
            ]);

        Log::warning('[FetchSnEmailBatchJob] marked leads retryable', [
            'ids' => $ids,
            'reason' => $reason,
        ]);
    }

    private function humanPause(): void
    {
        $min = max(0, (int) config('services.unipile_pacing.profile_lookup_delay_min_ms', 1000));
        $max = max($min, (int) config('services.unipile_pacing.profile_lookup_delay_max_ms', 3000));

        if ($max > 0) {
            usleep(random_int($min, $max) * 1000);
        }
    }

    private function checkAndResetDailyLimit(User $user): void
    {
        $today = now()->toDateString();
        $resetDate = $user->daily_profile_email_scraping_reset_at
            ? \Carbon\Carbon::parse($user->daily_profile_email_scraping_reset_at)->toDateString()
            : null;

        if ($resetDate !== $today) {
            $user->update([
                'daily_profile_email_scraping_count' => 0,
                'daily_profile_email_scraping_reset_at' => $today,
            ]);
        }
    }
}
