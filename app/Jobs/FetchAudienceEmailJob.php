<?php

namespace App\Jobs;

use App\Models\Audience;
use App\Models\AudienceList;
use App\Models\Integration;
use App\Models\User;
use App\Services\PhantomBusterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchAudienceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted (1 = no retries, fail fast)
     * Note: This can be overridden by queue worker --tries parameter
     */
    public $tries = 1;

    /**
     * Maximum number of exceptions allowed before failing (prevents retries)
     * This ensures the job fails immediately even if queue worker has --tries > 1
     */
    public $maxExceptions = 1;

    /**
     * The number of seconds to wait before retrying the job.
     * Set to null to prevent retries completely.
     */
    public $backoff = null;

    /**
     * The number of seconds the job can run before timing out (10 minutes)
     * This matches the lock timeout and allows enough time for PhantomBuster operations
     */
    public $timeout = 600;

    /**
     * Delete the job if it fails (don't keep failed jobs in queue)
     */
    public $deleteWhenMissingModels = true;

    public int $audienceListItemId;
    public string $publicIdentifier;

    /**
     * Create a new job instance.
     */
    public function __construct(int $audienceListItemId, string $publicIdentifier)
    {
        $this->audienceListItemId = $audienceListItemId;
        $this->publicIdentifier = $publicIdentifier;
    }

    /**
     * Determine the number of times the job may be attempted.
     * This method overrides the queue worker's --tries parameter.
     * 
     * @return int
     */
    public function tries(): int
    {
        return 1; // No retries - fail fast
    }

    /**
     * Handle a job failure.
     * This prevents the job from being retried even if Horizon has tries > 1
     */
    public function failed(\Throwable $exception): void
    {
        $audienceListItem = AudienceList::find($this->audienceListItemId);
        
        if ($audienceListItem) {
            // Reset status to null so user can retry
            $audienceListItem->update([
                'email_fetch_status' => null,
                'email_fetch_attempted_at' => null
            ]);
            
            Log::warning('FetchAudienceEmailJob: Job failed permanently - status reset for retry', [
                'audience_list_id' => $this->audienceListItemId,
                'public_identifier' => $this->publicIdentifier,
                'error' => $exception->getMessage(),
                'action' => 'Status reset to null - user can retry'
            ]);
        } else {
            Log::error('FetchAudienceEmailJob: Job failed permanently (item not found)', [
                'audience_list_id' => $this->audienceListItemId,
                'public_identifier' => $this->publicIdentifier,
                'error' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $audienceListItem = AudienceList::find($this->audienceListItemId);
            
            if (!$audienceListItem) {
                Log::warning('FetchAudienceEmailJob: Audience list item not found', [
                    'audience_list_id' => $this->audienceListItemId
                ]);
                return;
            }

            // Update status to processing when job starts
            $audienceListItem->update([
                'email_fetch_status' => 'processing'
            ]);

            // Skip if email already exists
            if (!empty($audienceListItem->con_email)) {
                $audienceListItem->update([
                    'email_fetch_status' => 'completed'
                ]);
                Log::info('FetchAudienceEmailJob: Email already exists, skipping', [
                    'audience_list_id' => $this->audienceListItemId
                ]);
                return;
            }

            // Build profile URL from public identifier
            $profileUrl = "https://www.linkedin.com/in/{$this->publicIdentifier}/";
            
            // Get user's LinkedIn integration for session cookie
            $audience = Audience::where('audience_id', $audienceListItem->audience_id)->first();
            if (!$audience) {
                Log::warning('FetchAudienceEmailJob: Audience not found', [
                    'audience_list_id' => $this->audienceListItemId,
                    'audience_id' => $audienceListItem->audience_id
                ]);
                return;
            }

            $user = User::find($audience->user_id);
            if (!$user) {
                Log::warning('FetchAudienceEmailJob: User not found', [
                    'audience_list_id' => $this->audienceListItemId,
                    'user_id' => $audience->user_id
                ]);
                return;
            }

            // Check and reset daily limit if needed
            $this->checkAndResetDailyLimit($user);
            
            // Refresh user model to get latest count after potential reset
            $user->refresh();
            
            // Check daily limit before processing
            $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
            if ($user->daily_profile_email_scraping_count >= $dailyLimit) {
                Log::warning('FetchAudienceEmailJob: Daily limit exceeded', [
                    'user_id' => $user->id,
                    'current_count' => $user->daily_profile_email_scraping_count,
                    'limit' => $dailyLimit
                ]);
                throw new \Exception("Daily email scraping limit reached ({$dailyLimit} profiles/day). Please try again tomorrow.");
            }

            $integration = Integration::where('user_id', $user->id)
                ->where('oauth_provider', 'linkedin')
                ->whereNotNull('linkedin_session_cookie')
                ->latest('linkedin_session_verified_at')
                ->first();

            if (!$integration) {
                Log::warning('FetchAudienceEmailJob: LinkedIn session cookie not found', [
                    'audience_list_id' => $this->audienceListItemId,
                    'user_id' => $user->id
                ]);
                return;
            }

            // Build identities array
            $identities = [[
                'sessionCookie' => $integration->linkedin_session_cookie,
                'userAgent' => $integration->linkedin_user_agent ?? config('services.phantombuster.linkedin_user_agent')
            ]];

            // Add identityId if available
            if (isset($integration->linkedin_identity_id) && !empty($integration->linkedin_identity_id)) {
                $identities[0]['identityId'] = $integration->linkedin_identity_id;
            }

            Log::info('FetchAudienceEmailJob: Fetching email using PhantomBuster Profile Scraper', [
                'audience_list_id' => $this->audienceListItemId,
                'profile_url' => $profileUrl,
                'public_identifier' => $this->publicIdentifier
            ]);

            $service = new PhantomBusterService();
            $profileData = $service->scrapeLinkedInProfile(
                $profileUrl,
                null, // sessionCookie - using identities instead
                null, // userAgent - using identities instead
                $identities,
                300, // maxWaitSeconds
                10   // pollIntervalSeconds
            );

            // Extract email from profile data
            // PhantomBuster Profile Scraper returns email in 'professionalEmail' field
            // Check multiple possible fields, but ensure value is non-empty (not empty string)
            $email = null;
            
            // Try professionalEmail first (most common field)
            if (isset($profileData['professionalEmail']) && 
                is_string($profileData['professionalEmail']) && 
                trim($profileData['professionalEmail']) !== '') {
                $email = trim($profileData['professionalEmail']);
            }
            // Try email field
            elseif (isset($profileData['email']) && 
                    is_string($profileData['email']) && 
                    trim($profileData['email']) !== '') {
                $email = trim($profileData['email']);
            }
            // Try emailAddress field
            elseif (isset($profileData['emailAddress']) && 
                    is_string($profileData['emailAddress']) && 
                    trim($profileData['emailAddress']) !== '') {
                $email = trim($profileData['emailAddress']);
            }
            // Try nested contactInfo fields
            elseif (isset($profileData['contactInfo']) && is_array($profileData['contactInfo'])) {
                if (isset($profileData['contactInfo']['emailAddress']) && 
                    is_string($profileData['contactInfo']['emailAddress']) && 
                    trim($profileData['contactInfo']['emailAddress']) !== '') {
                    $email = trim($profileData['contactInfo']['emailAddress']);
                } elseif (isset($profileData['contactInfo']['email']) && 
                          is_string($profileData['contactInfo']['email']) && 
                          trim($profileData['contactInfo']['email']) !== '') {
                    $email = trim($profileData['contactInfo']['email']);
                }
            }

            // Simplified log - only log if email not found for debugging
            if (empty($email)) {
                Log::info('FetchAudienceEmailJob: Email extraction result - no email found', [
                    'audience_list_id' => $this->audienceListItemId,
                    'profile_url' => $profileUrl
                ]);
            }

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $audienceListItem->update([
                    'con_email' => $email,
                    'email_fetch_status' => 'completed'
                ]);
                
                Log::info('FetchAudienceEmailJob: Successfully fetched and updated email', [
                    'audience_list_id' => $this->audienceListItemId,
                    'email' => $email,
                    'profile_url' => $profileUrl
                ]);
            } else {
                // Mark that email fetch was attempted but no email found
                $audienceListItem->update([
                    'email_fetch_attempted_at' => now(),
                    'email_fetch_status' => 'completed'
                ]);
                
                Log::warning('FetchAudienceEmailJob: Profile scraped but no valid email found', [
                    'audience_list_id' => $this->audienceListItemId,
                    'profile_url' => $profileUrl,
                    'email_attempted' => $email,
                    'email_valid' => $email ? filter_var($email, FILTER_VALIDATE_EMAIL) : false,
                    'profile_data_keys' => array_keys($profileData)
                ]);
            }

            // Update daily count (increment by 1 for this single profile scrape)
            $user->increment('daily_profile_email_scraping_count', 1);
            
            // Log removed to reduce verbosity
        } catch (\Throwable $th) {
            $audienceListItem = AudienceList::find($this->audienceListItemId);
            
            // Check if this is a lock timeout error (not a real scraping failure)
            // Includes: lock timeout, 429 rate limit (parallel execution limit), or maxParallelismReached
            $isLockTimeoutMessage = str_contains($th->getMessage(), 'LOCK_TIMEOUT') || 
                                   str_contains($th->getMessage(), 'lock could not be acquired') || 
                                   str_contains($th->getMessage(), 'lock after waiting') ||
                                   str_contains($th->getMessage(), 'Agent maximum parallel executions limit') ||
                                   str_contains($th->getMessage(), 'maxParallelismReached') ||
                                   (str_contains($th->getMessage(), '429') && str_contains($th->getMessage(), 'parallel execution'));
            
            if ($audienceListItem) {
                if ($isLockTimeoutMessage) {
                    // Lock timeout: Reset status to null so user can try again
                    $audienceListItem->update([
                        'email_fetch_status' => null,
                        'email_fetch_attempted_at' => null
                    ]);
                    
                    Log::warning('FetchAudienceEmailJob: Lock timeout - reset status for retry', [
                        'audience_list_id' => $this->audienceListItemId,
                        'public_identifier' => $this->publicIdentifier,
                        'error' => $th->getMessage(),
                        'action' => 'Status reset to null - user can retry'
                    ]);
                } else {
                    // Real scraping failure: Mark as completed (no email found)
                    $audienceListItem->update([
                        'email_fetch_attempted_at' => now(),
                        'email_fetch_status' => 'completed' // Mark as completed so it doesn't retry
                    ]);
                    
                    Log::error('FetchAudienceEmailJob: Failed to fetch email (scraping failure)', [
                        'audience_list_id' => $this->audienceListItemId,
                        'public_identifier' => $this->publicIdentifier,
                        'error' => $th->getMessage(),
                        'trace' => $th->getTraceAsString()
                    ]);
                }
            }
            
            // Don't re-throw exception - just return to complete job
            // The separate phantombuster supervisor has tries=1, so it won't retry
            // If exception escapes, it will be caught by Laravel's exception handler
            return;
        }
    }

    /**
     * Check and reset daily limit if needed
     */
    private function checkAndResetDailyLimit(User $user): void
    {
        $today = now()->toDateString();
        $resetDate = $user->daily_profile_email_scraping_reset_at 
            ? \Carbon\Carbon::parse($user->daily_profile_email_scraping_reset_at)->toDateString() 
            : null;

        // Reset if it's a new day
        if ($resetDate !== $today) {
            $user->update([
                'daily_profile_email_scraping_count' => 0,
                'daily_profile_email_scraping_reset_at' => $today
            ]);
            
            Log::info('FetchAudienceEmailJob: Daily limit reset', [
                'user_id' => $user->id,
                'reset_date' => $today
            ]);
        }
    }
}
