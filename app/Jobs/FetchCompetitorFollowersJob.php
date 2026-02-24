<?php

namespace App\Jobs;

use App\Models\Audience;
use App\Models\AudienceList;
use App\Services\PhantomBusterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchCompetitorFollowersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Set to 15 minutes (900 seconds) to allow for PhantomBuster operations
     * which can take up to 10 minutes (600 seconds) plus processing time.
     */
    public int $timeout = 900;

    public int $userId;
    public int $audiencePkId;
    public string $companyUrl;
    public string $sessionCookie;
    public string $userAgent;
    
    private int $emailDispatchCount = 0; // Track email dispatches per job instance

    public function __construct(int $userId, int $audiencePkId, string $companyUrl, string $sessionCookie, string $userAgent)
    {
        $this->userId = $userId;
        $this->audiencePkId = $audiencePkId;
        $this->companyUrl = $companyUrl;
        $this->sessionCookie = $sessionCookie;
        $this->userAgent = $userAgent;
        
        // Set the queue (using method from Queueable trait instead of property)
        $this->onQueue('default');
    }

    public function handle(): void
    {
        // Update status immediately when job starts processing (before any other operations)
        $audience = Audience::find($this->audiencePkId);
        if (!$audience) {
            Log::warning('FetchCompetitorFollowersJob: Audience not found', ['audiencePkId' => $this->audiencePkId]);
            return;
        }

        // Update status to processing IMMEDIATELY when job starts
        $this->updateFetchStatus($audience, 'processing', '🚀 Warming up the engines...');


        $service = new PhantomBusterService();
        $uniqueByPublicId = [];
        $created = 0;

        // Update status: fetching company posts
        $this->updateFetchStatus($audience, 'processing', '🔍 Scanning company activity...');

        try {
            // Get already-scraped post URLs from audience source_meta to skip them
            $scrapedPostUrls = [];
            if ($audience->source_meta) {
                $meta = json_decode($audience->source_meta, true);
                if (isset($meta['scraped_post_urls']) && is_array($meta['scraped_post_urls'])) {
                    $scrapedPostUrls = $meta['scraped_post_urls'];
                }
            }
            
            // Update status: fetching engagers
            $this->updateFetchStatus($audience, 'processing', '⚡ Extracting active engagers...');
            
            // Fetch company post engagers (people who liked company posts)
            // This doesn't require admin access and works for any company
            // Pass scraped post URLs to skip them
            $result = $service->fetchCompanyPostEngagers(
                $this->companyUrl,
                null,
                600,
                15,
                $this->sessionCookie,
                $this->userAgent,
                $scrapedPostUrls, // Pass already-scraped posts
                $audience // Pass audience to update source_meta with newly scraped posts
            );
            
            $followers = $result['engagers'] ?? $result;
            $newlyScrapedPosts = $result['newly_scraped_posts'] ?? [];
            
            // Update audience source_meta with newly scraped post URLs
            if (!empty($newlyScrapedPosts)) {
                $existingScraped = $scrapedPostUrls;
                $allScraped = array_unique(array_merge($existingScraped, $newlyScrapedPosts));
                
                $meta = json_decode($audience->source_meta, true) ?? [];
                $meta['scraped_post_urls'] = $allScraped;
                $audience->source_meta = json_encode($meta);
                $audience->save();
                
            }

            // Update status: storing followers
            $this->updateFetchStatus($audience, 'processing', '💾 Building your audience list...');
            
            foreach ($followers as $index => $follower) {
                // Skip if not an array (safety check)
                if (!is_array($follower)) {
                    continue;
                }
                
                $this->storeFollower($audience, $follower, $uniqueByPublicId, $created);
            }

            // Only mark as completed if we actually stored followers
            if ($created > 0) {
                // Update status to completed
                $this->updateFetchStatus($audience, 'completed', '✅ Done! Your audience is ready', [
                    'stored_count' => $created,
                    'total_fetched' => count($followers)
                ]);
            } else {
                // No followers were stored - decide what error to surface
                $errorMessage = 'No new profiles were added. Try again later or check if the company has recent post activity.';
                if (count($followers) === 0) {
                    $errorMessage = 'No active engagers found. The company may have limited recent activity';
                }

                // Read existing meta once so we can avoid overwriting a previous session-cookie error
                $meta = json_decode($audience->source_meta, true) ?? [];
                $existingErrorType = $meta['last_error_type'] ?? null;

                // If a previous run already detected a session cookie problem, KEEP that as the main error
                if ($existingErrorType === 'session_cookie') {
                    // Just update status to failed with a generic message; UI will still show the cookie-specific block
                    $this->updateFetchStatus($audience, 'failed', $meta['last_error'] ?? 'Failed due to LinkedIn session cookie issue.');
                } else {
                    // Otherwise, treat this as a "no data" failure and store it for the UI
                    $this->updateFetchStatus($audience, 'failed', $errorMessage);

                    $meta['last_error'] = $errorMessage;
                    $meta['last_error_type'] = 'no_data';
                    $meta['last_error_at'] = now()->toIso8601String();
                    $audience->source_meta = json_encode($meta);
                    $audience->save();
                }

                Log::warning('⚠️ FetchCompetitorFollowersJob: Completed but no followers stored', [
                    'audience_id' => $audience->audience_id,
                    'total_fetched' => count($followers),
                    'stored' => $created,
                    'existing_error_type' => $existingErrorType
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ FetchCompetitorFollowersJob: PhantomBuster failed', [
                'audience_id' => $audience->audience_id,
                'company_url' => $this->companyUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if it's a session/cookie error
            $isSessionError = stripos($e->getMessage(), 'session cookie') !== false || 
                             stripos($e->getMessage(), 'li_at') !== false ||
                             stripos($e->getMessage(), 'credentials') !== false ||
                             stripos($e->getMessage(), 'expired') !== false;
            
            if ($isSessionError) {
                // Store error message in audience source_meta for UI display
                $meta = json_decode($audience->source_meta, true) ?? [];
                $meta['last_error'] = $e->getMessage();
                $meta['last_error_type'] = 'session_cookie';
                $meta['last_error_at'] = now()->toIso8601String();
                $audience->source_meta = json_encode($meta);
                $audience->save();
                
            }
            
            // Update status to failed
            $this->updateFetchStatus($audience, 'failed', 'Failed: ' . $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Store a follower from PhantomBuster response
     *
     * @param Audience $audience
     * @param array $follower Follower data from PhantomBuster
     * @param array $seen Track seen public IDs to avoid duplicates
     * @param int $created Counter for created records
     * @return void
     */
    private function storeFollower(Audience $audience, array $follower, array &$seen, int &$created): void
    {
        if (empty($follower)) {
            return;
        }

        // PhantomBuster returns different field names, handle various formats
        // Check profileLink first (PhantomBuster's format)
        $publicId = null;
        $profileLink = $follower['profileLink'] 
            ?? $follower['profileUrl'] 
            ?? $follower['profile_url'] 
            ?? null;

        // Extract ID from profileLink/profileUrl
        if ($profileLink) {
            // Try to extract public identifier from URL
            // Format can be: https://www.linkedin.com/in/username/ or https://www.linkedin.com/in/ACoAA.../
            if (preg_match('/linkedin\.com\/in\/([^\/\?]+)/', $profileLink, $matches)) {
                $extractedId = $matches[1];
                // Use extracted ID (could be username or internal ID)
                $publicId = $extractedId;
            }
        }

        // Fallback to other fields
        if (!$publicId) {
            $publicId = $follower['publicIdentifier'] 
                ?? $follower['public_identifier'] 
                ?? $follower['memberId'] // Use memberId as fallback
                ?? null;
        }

        if ($publicId && isset($seen[$publicId])) {
            return; // Skip duplicates
        }

        // Extract name - PhantomBuster may use different field names
        $fullName = $follower['fullName'] 
            ?? $follower['name'] 
            ?? trim(($follower['firstName'] ?? $follower['first_name'] ?? '') . ' ' . ($follower['lastName'] ?? $follower['last_name'] ?? ''));
        
        $first = $follower['firstName'] 
            ?? $follower['first_name'] 
            ?? ($fullName ? explode(' ', $fullName, 2)[0] : null);
        
        $last = $follower['lastName'] 
            ?? $follower['last_name'] 
            ?? ($fullName && str_contains($fullName, ' ') ? explode(' ', $fullName, 2)[1] : null);

        // Extract job title and company
        // PhantomBuster uses 'occupation' field for job title
        $jobTitle = $follower['occupation'] 
            ?? $follower['headline'] 
            ?? $follower['title'] 
            ?? $follower['jobTitle'] 
            ?? null;
        
        $companyName = $follower['company'] 
            ?? $follower['companyName'] 
            ?? $follower['company_name'] 
            ?? null;

        // Extract location
        $location = $follower['location'] 
            ?? $follower['locationName'] 
            ?? null;

        // Profile URL - use profileLink if available, otherwise construct from publicId
        $profileUrl = $follower['profileLink'] 
            ?? $follower['profileUrl'] 
            ?? $follower['profile_url'] 
            ?? ($publicId ? 'https://www.linkedin.com/in/' . $publicId . '/' : null);
        
        // Log profile URL extraction for debugging
        if (!$profileUrl && isset($follower['profileLink'])) {
            Log::warning('FetchCompetitorFollowersJob: profileLink exists but is empty/null', [
                'profileLink_value' => $follower['profileLink'],
                'public_id' => $publicId,
                'follower_keys' => array_keys($follower)
            ]);
        }

        // Connection degree (1st, 2nd, 3rd) - PhantomBuster includes this
        $connectionDegree = $follower['connectionDegree'] 
            ?? $follower['connection_degree'] 
            ?? $follower['degree'] 
            ?? $follower['networkDistance']
            ?? $follower['network_distance']
            ?? null;

        // Log removed to reduce verbosity - only log errors or important issues

        // Convert connection degree to distance format (1, 2, 3 or DISTANCE_1, DISTANCE_2, DISTANCE_3)
        $con_distance = null;
        if ($connectionDegree !== null) {
            // Handle different formats: "1", "1st", "DISTANCE_1", 1, etc.
            if (is_numeric($connectionDegree)) {
                $con_distance = 'DISTANCE_' . (int)$connectionDegree;
            } elseif (is_string($connectionDegree)) {
                // Extract number from strings like "1st", "2nd", "3rd", "DISTANCE_1"
                if (preg_match('/(\d+)/', $connectionDegree, $matches)) {
                    $con_distance = 'DISTANCE_' . $matches[1];
                } elseif (str_starts_with(strtoupper($connectionDegree), 'DISTANCE_')) {
                    $con_distance = strtoupper($connectionDegree);
                } else {
                    $con_distance = $connectionDegree; // Use as-is if can't parse
                }
            }
        }

        // Store in audience_lists
        $savedItem = AudienceList::updateOrCreate(
            [
                'audience_id' => $audience->audience_id,
                'con_public_identifier' => $publicId,
            ],
            [
                'con_first_name' => $first,
                'con_last_name' => $last,
                'con_job_title' => $jobTitle,
                'con_company_name' => $companyName,
                'con_location' => $location,
                'con_profile_url' => $profileUrl,
                'con_distance' => $con_distance, // Save network distance
                'con_last_activity' => now(), // Use current time as last activity
            ]
        );


        if ($publicId) {
            $seen[$publicId] = true;
        }
        $created++;
        
        // Dispatch email fetch job if email is missing (max 5 at a time to allow other users)
        if (empty($savedItem->con_email) && !empty($publicId)) {
            // Get user to check daily limit
            $user = \App\Models\User::find($this->userId);
            if ($user) {
                // Check and reset daily limit
                $today = now()->toDateString();
                $resetDate = $user->daily_profile_email_scraping_reset_at 
                    ? \Carbon\Carbon::parse($user->daily_profile_email_scraping_reset_at)->toDateString() 
                    : null;

                if ($resetDate !== $today) {
                    $user->update([
                        'daily_profile_email_scraping_count' => 0,
                        'daily_profile_email_scraping_reset_at' => $today
                    ]);
                    $user->refresh();
                }
                
                // Only dispatch if under daily limit and we haven't dispatched 5 yet in this job
                $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
                if ($user->daily_profile_email_scraping_count < $dailyLimit) {
                    if ($this->emailDispatchCount < 5) {
                        \App\Jobs\FetchAudienceEmailJob::dispatch($savedItem->id, $publicId)
                            ->onQueue('phantombuster');
                        $this->emailDispatchCount++;
                        
                        // Log removed to reduce verbosity
                    }
                }
            }
        }
    }

    /**
     * Update fetch status in audience source_meta
     */
    private function updateFetchStatus($audience, $status, $progress = null, $metadata = [])
    {
        $meta = json_decode($audience->source_meta, true) ?? [];
        $meta['fetch_status'] = $status;
        $meta['fetch_progress'] = $progress;
        $meta['fetch_updated_at'] = now()->toIso8601String();
        
        if ($status === 'completed') {
            $meta['fetch_completed_at'] = now()->toIso8601String();
        } elseif ($status === 'failed') {
            $meta['fetch_failed_at'] = now()->toIso8601String();
        }
        
        // Merge any additional metadata
        if (!empty($metadata)) {
            $meta = array_merge($meta, $metadata);
        }
        
        $audience->source_meta = json_encode($meta);
        $audience->save();
    }
}


