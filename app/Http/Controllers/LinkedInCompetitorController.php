<?php

namespace App\Http\Controllers;

use App\Jobs\FetchCompetitorFollowersJob;
use App\Jobs\FetchAudienceEmailJob;
use App\Jobs\FetchAudienceEmailBatchJob;
use App\Models\Audience;
use App\Models\AudienceList;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LinkedInCompetitorController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Find all audience_ids that have followers for this user
        $allUserAudienceIds = Audience::where('user_id', $user->id)->pluck('audience_id')->toArray();
        $audienceIdsWithFollowers = AudienceList::whereIn('audience_id', $allUserAudienceIds)
            ->distinct()
            ->pluck('audience_id')
            ->toArray();
        
        // First, update ALL audiences that look like competitor audiences (have linkedin.com in name)
        // These are competitor audiences created before we added tag/source fields
        $audiencesToUpdate = Audience::where('user_id', $user->id)
            ->where(function($q) {
                $q->where('audience_name', 'LIKE', '%linkedin.com%')
                  ->orWhere('source', 'linkedin_company_followers');
            })
            ->get();
        
        foreach ($audiencesToUpdate as $aud) {
            $needsSave = false;
            
            // Update tag/source if missing
            if (!$aud->tag) {
                $aud->tag = 'competitor_active_followers';
                $needsSave = true;
            }
            if (!$aud->source) {
                $aud->source = 'linkedin_company_followers';
                $needsSave = true;
            }
            
            // Try to extract company name from source_meta and update audience_name
            if ($aud->source_meta) {
                $meta = json_decode($aud->source_meta, true);
                if ($meta && isset($meta['company_url'])) {
                    $url = $meta['company_url'];
                    if (preg_match('/\/company\/([^\/\?]+)/', $url, $matches)) {
                        $companySlug = $matches[1];
                        $newName = ucfirst($companySlug) . ' - Active Engagers';
                        if ($aud->audience_name !== $newName) {
                            $aud->audience_name = $newName;
                            $needsSave = true;
                        }
                    }
                }
            } elseif ($aud->audience_name && str_contains(strtolower($aud->audience_name), 'linkedin.com')) {
                // If no source_meta but has linkedin.com in name, try to guess from other data
                // Keep it as is for now, but ensure tag/source are set
            }
            
            if ($needsSave) {
                $aud->save();
                Log::info('Updated competitor audience', [
                    'audience_id' => $aud->audience_id,
                    'tag' => $aud->tag,
                    'source' => $aud->source,
                    'new_name' => $aud->audience_name
                ]);
            }
        }
        
        // Query audiences - only show audiences for the current user (per-user isolation)
        // Each user only sees their own job statuses and audiences
        $audiences = Audience::where('user_id', $user->id)
            ->where(function($query) use ($audienceIdsWithFollowers) {
                $query->where('source', 'linkedin_company_followers')
                      ->orWhere('tag', 'competitor_active_followers')
                      // Include audiences with followers that have linkedin.com in name
                      ->orWhere(function($q) use ($audienceIdsWithFollowers) {
                          $q->whereIn('audience_id', $audienceIdsWithFollowers)
                            ->where('audience_name', 'LIKE', '%linkedin.com%');
                      });
            })
            ->latest()
            ->paginate(10);

        // Add follower counts to each audience
        foreach ($audiences as $audience) {
            $count = AudienceList::where('audience_id', $audience->audience_id)->count();
            $audience->followers_count = $count;
            
        }

        $hasLinkedInSession = Integration::where('user_id', $user->id)
            ->where('oauth_provider', 'linkedin')
            ->whereNotNull('linkedin_session_cookie')
            ->exists();

        return view('competitor_followers.index', compact('audiences', 'hasLinkedInSession'));
    }

    public function fetch(Request $request)
    {
        $data = $request->validate([
            'company_url' => ['required', 'url'],
        ]);

        $user = Auth::user();

        $integration = Integration::where('user_id', $user->id)
            ->where('oauth_provider', 'linkedin')
            ->whereNotNull('linkedin_session_cookie')
            ->latest('linkedin_session_verified_at')
            ->first();

        if (!$integration) {
            return redirect()
                ->route('competitor-followers.index')
                ->with('error', __('competitor_followers.session_missing'));
        }

        // Extract company name from URL for better naming
        $companySlug = null;
        $companyName = 'Competitor Followers';
        $parsedUrl = parse_url($data['company_url']);
        if (isset($parsedUrl['path'])) {
            // Extract company name from path like /company/microsoft
            if (preg_match('/\/company\/([^\/\?]+)/', $parsedUrl['path'], $matches)) {
                $companySlug = $matches[1];
                $companyName = ucfirst($companySlug) . ' - Active Engagers';
            } elseif ($parsedUrl['host']) {
                $companyName = str_replace('www.', '', $parsedUrl['host']) . ' - Active Engagers';
            }
        }

        // Normalize company URL (remove trailing slash, query params)
        $normalizedUrl = rtrim(parse_url($data['company_url'], PHP_URL_SCHEME) . '://' . parse_url($data['company_url'], PHP_URL_HOST) . parse_url($data['company_url'], PHP_URL_PATH), '/');

        // Check if an audience already exists for this company URL
        $existingAudience = Audience::where('user_id', $user->id)
            ->where('source', 'linkedin_company_followers')
            ->where('tag', 'competitor_active_followers')
            ->get()
            ->first(function($aud) use ($normalizedUrl) {
                $meta = $aud->source_meta ? json_decode($aud->source_meta, true) : null;
                if ($meta && isset($meta['company_url'])) {
                    $existingUrl = rtrim(parse_url($meta['company_url'], PHP_URL_SCHEME) . '://' . parse_url($meta['company_url'], PHP_URL_HOST) . parse_url($meta['company_url'], PHP_URL_PATH), '/');
                    return $existingUrl === $normalizedUrl;
                }
                return false;
            });

        if ($existingAudience) {
            // Use existing audience - update name if needed
            if ($existingAudience->audience_name !== $companyName) {
                $existingAudience->audience_name = $companyName;
                $existingAudience->save();
            }
            $audience = $existingAudience;
            
            Log::info('Using existing competitor audience', [
                'audience_id' => $audience->audience_id,
                'company_url' => $data['company_url']
            ]);
        } else {
            // Create new audience
            $audience = Audience::create([
                'audience_name' => $companyName,
                'audience_id' => now()->timestamp . $user->id,
                'audience_type' => 'LI',
                'user_id' => $user->id,
                'tag' => 'competitor_active_followers',
                'source' => 'linkedin_company_followers',
                'source_meta' => json_encode([
                    'company_url' => $normalizedUrl
                ])
            ]);
            
            Log::info('Created new competitor audience', [
                'audience_id' => $audience->audience_id,
                'company_url' => $normalizedUrl
            ]);
        }

        // Set initial status to pending
        $meta = json_decode($audience->source_meta, true) ?? [];
        $meta['fetch_status'] = 'pending';
        $meta['fetch_started_at'] = now()->toIso8601String();
        $meta['fetch_progress'] = '⏳ Queued and ready to go...';
        $audience->source_meta = json_encode($meta);
        $audience->save();

        // Dispatch job to default queue (handled by supervisor-1 in Horizon)
        FetchCompetitorFollowersJob::dispatch(
            $user->id,
            $audience->id,
            $data['company_url'],
            $integration->linkedin_session_cookie,
            $integration->linkedin_user_agent ?? config('services.phantombuster.linkedin_user_agent')
        )->onQueue('default');

        return redirect()->route('competitor-followers.index')
            ->with('status', __('competitor_followers.fetch_started'));
    }

    public function show(Request $request, $audienceId)
    {
        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('id', $audienceId)->firstOrFail();

        $emailFilter = $request->query('email_filter', 'all'); // all, with_email, without_email, not_found, not_fetched, pending
        
        $query = AudienceList::where('audience_id', $audience->audience_id);
        
        // Apply email filter
        switch ($emailFilter) {
            case 'with_email':
                $query->whereNotNull('con_email')->where('con_email', '!=', '');
                break;
            case 'without_email':
                $query->where(function($q) {
                    $q->whereNull('con_email')
                      ->orWhere('con_email', '=', '');
                })->where(function($q) {
                    $q->where('email_fetch_status', 'completed')
                      ->orWhereNotNull('email_fetch_attempted_at');
                });
                break;
            case 'not_found':
                $query->where('email_fetch_status', 'completed')
                      ->where(function($q) {
                          $q->whereNull('con_email')->orWhere('con_email', '=', '');
                      });
                break;
            case 'not_fetched':
                $query->whereNull('email_fetch_status')
                      ->whereNull('email_fetch_attempted_at');
                break;
            case 'pending':
                $query->where('email_fetch_status', 'pending');
                break;
            // 'all' - no filter applied
        }
        
        $list = $query->latest()->paginate(25)->appends($request->query());

        // Count pending email fetch jobs for this user
        $pendingEmailFetchCount = $this->getPendingEmailFetchCount($user->id);

        return view('competitor_followers.show', compact('audience', 'list', 'pendingEmailFetchCount', 'emailFilter'));
    }

    /**
     * Get count of pending email fetch jobs for a user
     * Excludes jobs that have been stuck for more than 10 minutes
     */
    private function getPendingEmailFetchCount($userId)
    {
        // Get all audience_ids for this user
        $userAudienceIds = Audience::where('user_id', $userId)->pluck('audience_id')->toArray();
        
        // Reset stuck jobs (pending/processing for more than 10 minutes)
        $stuckCutoff = now()->subMinutes(10);
        AudienceList::whereIn('audience_id', $userAudienceIds)
            ->whereIn('email_fetch_status', ['pending', 'processing'])
            ->where(function($query) use ($stuckCutoff) {
                $query->where('email_fetch_attempted_at', '<', $stuckCutoff)
                      ->orWhereNull('email_fetch_attempted_at');
            })
            ->update([
                'email_fetch_status' => null,
                'email_fetch_attempted_at' => null
            ]);
        
        // Count AudienceList records with pending/processing status for this user's audiences
        // Only count jobs that started within the last 10 minutes
        return AudienceList::whereIn('audience_id', $userAudienceIds)
            ->whereIn('email_fetch_status', ['pending', 'processing'])
            ->where('email_fetch_attempted_at', '>=', $stuckCutoff)
            ->count();
    }

    /**
     * Get pending email fetch count (API endpoint)
     */
    public function getPendingCount()
    {
        $user = Auth::user();
        $count = $this->getPendingEmailFetchCount($user->id);
        
        return response()->json([
            'status' => 'success',
            'pending_count' => $count
        ]);
    }

    public function exportCsv($audienceId): StreamedResponse
    {
        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('id', $audienceId)->firstOrFail();

        $rows = AudienceList::where('audience_id', $audience->audience_id)->get([
            'con_first_name', 'con_last_name', 'con_job_title', 'con_company_name', 'con_location', 'con_profile_url', 'con_email'
        ]);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="competitor_followers_' . $audience->id . '.csv"'
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Job Title', 'Company', 'Location', 'Profile URL', 'Email']);
            foreach ($rows as $r) {
                $name = trim(($r->con_first_name ?? '') . ' ' . ($r->con_last_name ?? ''));
                fputcsv($handle, [
                    $name,
                    $r->con_job_title,
                    $r->con_company_name,
                    $r->con_location,
                    $r->con_profile_url,
                    $r->con_email ?? ''
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function fetchEmail(Request $request, $audienceId)
    {
        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('id', $audienceId)->firstOrFail();

        $request->validate([
            'audience_list_id' => 'required|integer|exists:audience_lists,id',
        ]);

        $audienceListItem = AudienceList::where('id', $request->audience_list_id)
            ->where('audience_id', $audience->audience_id)
            ->firstOrFail();

        // Check if email already exists
        if (!empty($audienceListItem->con_email)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email already exists',
                'email' => $audienceListItem->con_email
            ], 200);
        }

        // Check if email fetch is already pending or attempted
        if (!empty($audienceListItem->email_fetch_attempted_at)) {
            // If status is 'pending', it's still being processed
            if ($audienceListItem->email_fetch_status === 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email fetch is already in progress. Please wait or refresh the page.',
                    'already_pending' => true
                ], 409); // 409 Conflict
            }
            // If status is 'completed' and no email, it was already attempted
            if ($audienceListItem->email_fetch_status === 'completed' && empty($audienceListItem->con_email)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email fetch was already attempted. No email found for this profile.',
                    'already_completed' => true
                ], 409); // 409 Conflict
            }
        }

        // Check if we have public identifier or profile URL
        $publicIdentifier = $audienceListItem->con_public_identifier;
        
        if (empty($publicIdentifier) && !empty($audienceListItem->con_profile_url)) {
            // Extract public identifier from profile URL
            if (preg_match('/\/in\/([^\/\?]+)/', $audienceListItem->con_profile_url, $matches)) {
                $publicIdentifier = $matches[1];
            }
        }

        if (empty($publicIdentifier)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile identifier not found. Cannot fetch email.'
            ], 400);
        }

        // Check daily limit before dispatching
        $this->checkAndResetDailyLimit($user);
        $user->refresh();
        
        $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
        if ($user->daily_profile_email_scraping_count >= $dailyLimit) {
            return response()->json([
                'status' => 'error',
                'message' => "Daily email scraping limit reached ({$dailyLimit} profiles/day). Please try again tomorrow."
            ], 429);
        }

        // Check concurrent limit (max 5 pending jobs per user)
        $pendingCount = $this->getPendingEmailFetchCount($user->id);
        if ($pendingCount >= 5) {
            return response()->json([
                'status' => 'error',
                'message' => "You have {$pendingCount} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.",
                'concurrent_limit_reached' => true,
                'pending_count' => $pendingCount
            ], 429);
        }

        // Mark as pending immediately to prevent duplicate requests
        $audienceListItem->update([
            'email_fetch_attempted_at' => now(),
            'email_fetch_status' => 'pending'
        ]);

        // Dispatch job to fetch email
        try {
            FetchAudienceEmailJob::dispatch($audienceListItem->id, $publicIdentifier)
                ->onQueue('phantombuster');

            return response()->json([
                'status' => 'success',
                'message' => 'Email fetch job queued. Please wait while we fetch the email.',
                'pending' => true
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Failed to dispatch email fetch job', [
                'audience_list_id' => $audienceListItem->id,
                'error' => $th->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch email: ' . $th->getMessage()
            ], 500);
        }
    }

    public function checkEmail($audienceId, $audienceListId)
    {
        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('id', $audienceId)->firstOrFail();

        $audienceListItem = AudienceList::where('id', $audienceListId)
            ->where('audience_id', $audience->audience_id)
            ->firstOrFail();

        // Check if email fetch was attempted but no email found
        $emailFetchCompleted = !empty($audienceListItem->email_fetch_attempted_at) && empty($audienceListItem->con_email);

        return response()->json([
            'status' => 'success',
            'has_email' => !empty($audienceListItem->con_email),
            'email' => $audienceListItem->con_email ?? null,
            'email_fetch_completed' => $emailFetchCompleted
        ], 200);
    }

    public function fetchEmailBatch(Request $request, $audienceId)
    {
        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('id', $audienceId)->firstOrFail();

        $request->validate([
            'audience_list_ids' => 'required|array|min:1|max:20',
            'audience_list_ids.*' => 'required|integer|exists:audience_lists,id'
        ]);

        $audienceListIds = $request->input('audience_list_ids');
        
        // Verify all items belong to this audience
        $audienceListItems = AudienceList::whereIn('id', $audienceListIds)
            ->where('audience_id', $audience->audience_id)
            ->get();

        if ($audienceListItems->count() !== count($audienceListIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some selected items do not belong to this audience'
            ], 400);
        }

        // Check minimum selection requirements
        $totalItems = AudienceList::where('audience_id', $audience->audience_id)->count();
        $selectedCount = count($audienceListIds);

        // If total items < 5, allow any selection (even 1)
        // If total items >= 5 and <= 20, require minimum 5
        // If total items > 20, require minimum 20
        if ($totalItems > 20 && $selectedCount < 20) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select at least 20 profiles when there are more than 20 in the list'
            ], 400);
        } elseif ($totalItems >= 5 && $totalItems <= 20 && $selectedCount < 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select at least 5 profiles'
            ], 400);
        }
        // If totalItems < 5, no minimum requirement (allow any selection)

        // Filter out items that already have emails
        $itemsNeedingEmail = $audienceListItems->filter(function($item) {
            return empty($item->con_email) && empty($item->email_fetch_attempted_at);
        });

        if ($itemsNeedingEmail->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'All selected profiles already have emails or have been attempted'
            ], 400);
        }

        // Check daily limit
        $this->checkAndResetDailyLimit($user);
        $profileCount = $itemsNeedingEmail->count();
        
        $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
        if ($user->daily_profile_email_scraping_count + $profileCount > $dailyLimit) {
            $remaining = $dailyLimit - $user->daily_profile_email_scraping_count;
            return response()->json([
                'status' => 'error',
                'message' => "Daily limit reached. You can scrape {$remaining} more profiles today. Limit resets tomorrow.",
                'daily_limit_reached' => true,
                'remaining' => max(0, $remaining)
            ], 400);
        }

        // Dispatch batch job
        try {
            FetchAudienceEmailBatchJob::dispatch(
                $itemsNeedingEmail->pluck('id')->toArray(),
                $user->id
            )->onQueue('phantombuster');

            return response()->json([
                'status' => 'success',
                'message' => "Batch email fetch job dispatched for {$profileCount} profile(s). Please refresh the page in a few moments.",
                'profile_count' => $profileCount
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Failed to dispatch batch email fetch job', [
                'audience_list_ids' => $audienceListIds,
                'error' => $th->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch emails: ' . $th->getMessage()
            ], 500);
        }
    }

    public function getDailyLimit()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
                return response()->json([
                    'daily_limit' => $dailyLimit,
                    'used' => 0,
                    'remaining' => $dailyLimit,
                    'can_scrape' => true,
                    'reset_date' => null,
                    'error' => 'User not authenticated'
                ], 401);
            }
            
            $this->checkAndResetDailyLimit($user);
            
            // Refresh user model to get updated values
            $user->refresh();
            
            $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
            $used = (int)($user->daily_profile_email_scraping_count ?? 0);
            $remaining = max(0, $dailyLimit - $used);
            
            return response()->json([
                'daily_limit' => $dailyLimit,
                'used' => $used,
                'remaining' => $remaining,
                'can_scrape' => $remaining > 0,
                'reset_date' => $user->daily_profile_email_scraping_reset_at
            ]);
        } catch (\Throwable $th) {
            Log::error('Error getting daily limit', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            
            $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
            return response()->json([
                'daily_limit' => $dailyLimit,
                'used' => 0,
                'remaining' => $dailyLimit,
                'can_scrape' => true,
                'reset_date' => null,
                'error' => 'Failed to load daily limit'
            ], 500);
        }
    }

    public function getFetchStatus(Request $request, $audienceId)
    {
        $user = Auth::user();
        // Only allow users to check status of their own audiences (per-user isolation)
        $audience = Audience::where('user_id', $user->id)->where('id', $audienceId)->firstOrFail();
        
        $meta = json_decode($audience->source_meta, true) ?? [];
        $status = $meta['fetch_status'] ?? null;
        $progress = $meta['fetch_progress'] ?? null;
        $storedCount = (int) ($meta['stored_count'] ?? 0);
        $fetchCompletedAt = $meta['fetch_completed_at'] ?? null;

        // If status says failed but we have a completed timestamp and stored data, treat as completed (avoids UI showing failed when job actually finished successfully)
        if ($status === 'failed' && $fetchCompletedAt && $storedCount > 0) {
            $status = 'completed';
        }

        return response()->json([
            'status' => $status,
            'progress' => $progress,
            'fetch_started_at' => $meta['fetch_started_at'] ?? null,
            'fetch_completed_at' => $fetchCompletedAt,
            'fetch_failed_at' => $meta['fetch_failed_at'] ?? null,
            'stored_count' => $meta['stored_count'] ?? null,
            'total_fetched' => $meta['total_fetched'] ?? null
        ]);
    }

    public function delete(Request $request, $audienceId)
    {
        $user = Auth::user();
        
        // Find the audience
        $audience = Audience::where('id', $audienceId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$audience) {
            return response()->json([
                'status' => 'error',
                'message' => 'Audience not found or you do not have permission to delete it.'
            ], 404);
        }
        
        $deleteAudience = $request->input('delete_audience', 0) == 1;
        
        try {
            // Get the audience_id (the actual ID used in audience_lists table)
            $actualAudienceId = $audience->audience_id;
            
            // Delete all follower data (AudienceList records)
            $deletedCount = AudienceList::where('audience_id', $actualAudienceId)->delete();
            
            Log::info('LinkedInCompetitorController: Deleted follower data', [
                'user_id' => $user->id,
                'audience_id' => $audienceId,
                'actual_audience_id' => $actualAudienceId,
                'deleted_followers' => $deletedCount,
                'delete_audience_record' => $deleteAudience
            ]);
            
            // If checkbox is checked, also delete the audience record
            if ($deleteAudience) {
                $audience->delete();
                
                Log::info('LinkedInCompetitorController: Deleted audience record', [
                    'user_id' => $user->id,
                    'audience_id' => $audienceId
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Audience and all follower data have been deleted successfully.'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => "Follower data deleted successfully. The audience record has been preserved."
                ], 200);
            }
        } catch (\Throwable $th) {
            Log::error('LinkedInCompetitorController: Failed to delete audience', [
                'user_id' => $user->id,
                'audience_id' => $audienceId,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete audience: ' . $th->getMessage()
            ], 500);
        }
    }

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
        }
    }
}


