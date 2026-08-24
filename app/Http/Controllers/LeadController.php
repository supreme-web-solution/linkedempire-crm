<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Audience;
use App\Models\AudienceList;
use App\Models\SnLead;
use App\Models\SnLeadsCompany;
use App\Models\SnLeadList;
use App\Helpers\CustomQueryHelper;
use App\Jobs\FetchAudienceEmailJob;
use App\Jobs\FetchAudienceEmailBatchJob;
use App\Jobs\FetchSnEmailBatchJob;
use App\Models\User;
use App\V2\Services\EmailEnrichmentLimiter;
use App\V2\Services\LeadEnrichmentPersister;
use App\V2\Services\LeadEnrichmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    use CustomQueryHelper;

    public function index()
    {
        $userId = auth()->user()->id;
        $leadlist = $this->getLeadList($userId);

        // Calculate statistics
        $totalLists = Audience::where('user_id', $userId)->count() + 
                     \App\Models\SnLeadList::where('user_id', $userId)->count();
        
        // Count audience lists
        $audienceLists = Audience::where('user_id', $userId)->count();
        
        // Count sales navigator lists
        $snLists = \App\Models\SnLeadList::where('user_id', $userId)->count();
        
        // Calculate total leads across all lists
        $totalLeads = DB::table('audiences')
            ->where('user_id', $userId)
            ->selectRaw('COALESCE(SUM((SELECT COUNT(*) FROM audience_lists WHERE audience_id = audiences.audience_id)), 0) as total')
            ->value('total') ?? 0;
        
        $totalLeads += DB::table('sn_leads_lists')
            ->where('user_id', $userId)
            ->selectRaw('COALESCE(SUM((SELECT COUNT(*) FROM sn_leads WHERE sn_list_id = sn_leads_lists.list_hash)), 0) as total')
            ->value('total') ?? 0;

        $stats = [
            'total_lists' => $totalLists,
            'audience_lists' => $audienceLists,
            'sn_lists' => $snLists,
            'total_leads' => $totalLeads,
        ];

        return view('leads.index', compact('leadlist', 'stats'));
    }

    public function search_leadlist(Request $request)
    {
        $search = $request->query('search');

        if(isset($search)){
            $userId = auth()->user()->id;
            $leadlist = $this->searchLeadList($userId, $search);

            // Calculate statistics (same as index)
            $totalLists = Audience::where('user_id', $userId)->count() + 
                         \App\Models\SnLeadList::where('user_id', $userId)->count();
            
            $audienceLists = Audience::where('user_id', $userId)->count();
            $snLists = \App\Models\SnLeadList::where('user_id', $userId)->count();
            
            $totalLeads = DB::table('audiences')
                ->where('user_id', $userId)
                ->selectRaw('COALESCE(SUM((SELECT COUNT(*) FROM audience_lists WHERE audience_id = audiences.audience_id)), 0) as total')
                ->value('total') ?? 0;
            
            $totalLeads += DB::table('sn_leads_lists')
                ->where('user_id', $userId)
                ->selectRaw('COALESCE(SUM((SELECT COUNT(*) FROM sn_leads WHERE sn_list_id = sn_leads_lists.list_hash)), 0) as total')
                ->value('total') ?? 0;

            $stats = [
                'total_lists' => $totalLists,
                'audience_lists' => $audienceLists,
                'sn_lists' => $snLists,
                'total_leads' => $totalLeads,
            ];

            return view('leads.index', compact('leadlist', 'stats'));
        }

        return redirect()->route('leads.list');
    }

    public function search_leads(Request $request)
    {
        $search = $request->query('search');
        $src = $request->query('src');
        $list_id = $request->query('list_id');

        if($search){
            $leads = $this->searchLeads($list_id, $src, $search);

            if($src == 'aud'){
                $leadlist = Audience::select('audience_name as name')->where('audience_id', $listId)->first();
            }else{
                $leadlist = SnLeadList::select('name')->where('list_hash', $listId)->first();
            }

            return view('leads.leads', compact('leads', 'leadlist'));
        }

        return redirect()->route('leads.show', ['listId' => $list_id, 'src' => $src]);        
    }

    public function show(Request $request, $listId)
    {
        $src = $request->query('src');
        $emailFilter = $request->query('email_filter', 'all'); // all, with_email, without_email, not_found, not_fetched, pending
        $leads = [];
        $leadlist = null;

        if(isset($src)){
            $leads = $this->allLeads($listId, $src, $emailFilter, $request);

            if($src == 'aud'){
                $leadlist = Audience::select('audience_name as name')->where('audience_id', $listId)->first();
            }else{
                $leadlist = SnLeadList::select('name')->where('list_hash', $listId)->first();
            }
        }

        // Count pending email fetch jobs for this user
        $pendingEmailFetchCount = $this->getPendingEmailFetchCount(auth()->user()->id);

        return view('leads.leads', compact('leads', 'leadlist', 'pendingEmailFetchCount', 'emailFilter', 'listId'));
    }

    /**
     * Get count of pending email fetch jobs for a user
     */
    /**
     * Get count of pending email fetch jobs for a user
     * Excludes jobs that have been stuck for more than 10 minutes
     */
    private function getPendingEmailFetchCount($userId): int
    {
        return app(EmailEnrichmentLimiter::class)->pendingJobCount((int) $userId);
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

    public function update(Request $request, $listHash)
    {
        $data = $request->all();

        if($data['list_source'] == 'Audience'){
            Audience::where('id', $data['id'])
                ->update([
                    'audience_name' => $data['list_name']
                ]);
        }else {
            SnLeadList::where('id', $data['id'])
                ->update([
                    'name' => $data['list_name']
                ]);
        }

        notify()->success('List updated successfully');
        return redirect()->route('leads.list');
    }

    public function remove_leadlist(Request $request, $listId)
    {
        $src = $request->src;
        
        if($src == 'aud'){
            AudienceList::where('audience_id', $listId)->delete();
            Audience::where('audience_id', $listId)->delete();
        }else {
            SnLead::where('sn_list_id', $listId)->delete();
            SnLeadsCompany::where('sn_lead_id', $listId)->delete();
            SnLeadList::where('list_hash', $listId)->delete();
        }

        notify()->success('Lead list removed successfully');
        return redirect()->route('leads.list');
    }
    
    public function remove_lead(Request $request, $leadId)
    {
        $src = $request->src;
        $list_id = $request->list_id;

        if($src == 'aud'){
            AudienceList::where('id', $leadId)->delete();
        }else {
            SnLead::where('id', $leadId)->delete();
        }

        notify()->success('Lead removed successfully');
        return redirect()->route('leads.show', ['listId' => $list_id, 'src' => $src]);
    }

    public function remove_lead_bulk(Request $request, $listId)
    {
        $src = $request->query('src');
        $ids = $request->query('ids');

        if($src == 'aud'){
            AudienceList::whereIn('id', explode(',', $ids))->delete();
        }else {
            SnLead::whereIn('id', explode(',', $ids))->delete();
        }

        notify()->success('Lead removed successfully');
        return redirect()->route('leads.show', ['listId' => $listId, 'src' => $src]);
    }

    public function export(Request $request)
    {
        $list_id = $request->query('hash');
        $src = $request->query('src');
        $exp_format = $request->query('format');

        if($src == 'sn'){
            $leads = $this->snLeadExport($list_id);
        }else {
            $leads = $this->audienceExport($list_id);
        }
        
        return response()->json([
            'data' => $leads
        ]);
    }

    public function bulk_export(Request $request)
    {
        $src = $request->query('src');
        $ids = $request->query('ids');

        if($src == 'sn'){
            $leads = $this->snLeadExport($ids, 'bulk');
        }else {
            $leads = $this->audienceExport($ids, 'bulk');
        }
        
        return response()->json([
            'data' => $leads
        ]);
    }

    public function fetchEmail(Request $request, $listId)
    {
        $src = $request->query('src', 'aud');

        if ($src === 'sn') {
            return $this->enrichSnLead($request, $listId);
        }

        if ($src !== 'aud') {
            return response()->json([
                'status' => 'error',
                'message' => 'Email fetching is only available for audience and Sales Navigator leads.'
            ], 400);
        }

        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('audience_id', $listId)->firstOrFail();

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

        $pendingCount = app(EmailEnrichmentLimiter::class)->pendingJobCount($user->id);
        $batchSize = app(EmailEnrichmentLimiter::class)->batchSize();
        if ($pendingCount >= $batchSize) {
            return response()->json([
                'status' => 'error',
                'message' => "You have {$pendingCount} enrichment jobs in progress. Please wait before starting more.",
                'concurrent_limit_reached' => true,
                'pending_count' => $pendingCount
            ], 429);
        }

        $audienceListItem->update([
            'email_fetch_attempted_at' => now(),
            'email_fetch_status' => 'pending'
        ]);

        try {
            FetchAudienceEmailJob::dispatch($audienceListItem->id, $publicIdentifier);

            return response()->json([
                'status' => 'success',
                'message' => 'Enrichment job queued. Please wait while we fetch the email.',
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

    public function checkEmail($listId, $audienceListId)
    {
        $src = request()->query('src');
        
        // Only support audience leads (src=aud) for now
        if ($src !== 'aud') {
            return response()->json([
                'has_email' => false,
                'email' => null
            ], 400);
        }

        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('audience_id', $listId)->firstOrFail();

        $audienceListItem = AudienceList::where('id', $audienceListId)
            ->where('audience_id', $audience->audience_id)
            ->firstOrFail();

        // Check if email fetch was attempted but no email found
        $emailFetchCompleted = !empty($audienceListItem->email_fetch_attempted_at) && empty($audienceListItem->con_email);

        return response()->json([
            'has_email' => !empty($audienceListItem->con_email),
            'email' => $audienceListItem->con_email ?? null,
            'email_fetch_completed' => $emailFetchCompleted
        ]);
    }

    public function fetchEmailBatch(Request $request, $listId)
    {
        $src = request()->query('src', 'aud');

        if ($src === 'sn') {
            return $this->enrichSnBatch($request, $listId);
        }

        if ($src !== 'aud') {
            return response()->json([
                'status' => 'error',
                'message' => 'Batch email fetching only supported for audience and Sales Navigator leads'
            ], 400);
        }

        $user = Auth::user();
        $audience = Audience::where('user_id', $user->id)->where('audience_id', $listId)->firstOrFail();

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

        $profileCount = $itemsNeedingEmail->count();
        $capacity = app(EmailEnrichmentLimiter::class)->queueCapacity($user, $profileCount);

        if (! ($capacity['allowed'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => $capacity['message'],
                'daily_limit_reached' => ($capacity['remaining_daily'] ?? 0) <= 0,
                'remaining' => $capacity['remaining_daily'] ?? 0,
                'pending_count' => $capacity['pending_jobs'] ?? 0,
            ], ($capacity['pending_jobs'] ?? 0) >= app(EmailEnrichmentLimiter::class)->batchSize() ? 429 : 400);
        }

        $idsToQueue = $itemsNeedingEmail->pluck('id')->take($capacity['max_queue_now'])->values()->all();

        AudienceList::query()
            ->whereIn('id', $idsToQueue)
            ->update(['email_fetch_attempted_at' => now(), 'email_fetch_status' => 'pending']);

        try {
            FetchAudienceEmailBatchJob::dispatchChunked($idsToQueue, $user->id);

            $queued = count($idsToQueue);

            return response()->json([
                'status' => 'success',
                'message' => $queued < $profileCount
                    ? "Queued {$queued} of {$profileCount} profile(s) for enrichment (daily/batch limit)."
                    : "Enrichment queued for {$queued} profile(s).",
                'profile_count' => $queued,
                'skipped' => $profileCount - $queued,
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

    private function enrichSnLead(Request $request, string $listId)
    {
        /** @var User $user */
        $user = Auth::user();

        SnLeadList::query()
            ->where('list_hash', $listId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'lead_id' => 'required|integer|exists:sn_leads,id',
        ]);

        $lead = SnLead::query()
            ->where('id', $request->integer('lead_id'))
            ->where('sn_list_id', $listId)
            ->firstOrFail();

        if (! empty($lead->email)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email already exists',
                'email' => $lead->email,
            ]);
        }

        if (! empty($lead->email_fetch_attempted_at)) {
            if (in_array($lead->email_fetch_status, ['pending', 'processing'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Enrichment is already in progress.',
                    'already_pending' => true,
                ], 409);
            }

            if ($lead->email_fetch_status === 'completed' && empty($lead->email)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Enrichment already attempted. No work email found.',
                    'already_completed' => true,
                ], 409);
            }
        }

        $identifier = trim((string) ($lead->lid ?: $lead->sn_lid ?: ''));
        if ($identifier === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile identifier not found. Cannot enrich.',
            ], 400);
        }

        $this->checkAndResetDailyLimit($user);
        $user->refresh();

        $dailyLimit = (int) config('services.email_scraping.daily_limit_per_user', 100);
        if ($user->daily_profile_email_scraping_count >= $dailyLimit) {
            return response()->json([
                'status' => 'error',
                'message' => "Daily enrichment limit reached ({$dailyLimit} profiles/day).",
            ], 429);
        }

        $lead->update(['email_fetch_attempted_at' => now(), 'email_fetch_status' => 'processing']);

        try {
            $enrichmentService = app(LeadEnrichmentService::class);
            $persister = app(LeadEnrichmentPersister::class);
            $lead->loadMissing('company');
            $result = $enrichmentService->enrich($user, $enrichmentService->inputFromSnLead($lead));
            $persister->persistSnLead($lead, $result, $user->id);
        } catch (\Throwable $th) {
            $lead->update(['email_fetch_status' => 'failed']);

            Log::error('Failed to enrich SN lead', [
                'sn_lead_id' => $lead->id,
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to enrich: '.$th->getMessage(),
            ], 500);
        }

        if (! $result->isSoftTimeout()) {
            $user->increment('daily_profile_email_scraping_count');
        }

        $lead->refresh();

        return response()->json([
            'status' => 'success',
            'message' => $lead->email
                ? 'Email found via LinkedIn profile lookup.'
                : 'Enrichment complete. No work email was found for this profile.',
            'email' => $lead->email,
            'completed' => empty($lead->email),
        ]);
    }

    private function enrichSnBatch(Request $request, string $listId)
    {
        /** @var User $user */
        $user = Auth::user();

        SnLeadList::query()
            ->where('list_hash', $listId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'lead_ids' => 'required|array|min:1|max:50',
            'lead_ids.*' => 'required|integer|exists:sn_leads,id',
        ]);

        $items = SnLead::query()
            ->whereIn('id', $request->input('lead_ids', []))
            ->where('sn_list_id', $listId)
            ->get();

        $needing = $items->filter(function (SnLead $lead) {
            if (! empty($lead->email)) {
                return false;
            }

            if (in_array($lead->email_fetch_status, ['pending', 'processing'], true)) {
                return false;
            }

            if ($lead->email_fetch_status === 'completed') {
                return false;
            }

            return true;
        });

        if ($needing->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'All selected profiles are already enriched or in progress.',
            ], 400);
        }

        $profileCount = $needing->count();
        $capacity = app(EmailEnrichmentLimiter::class)->queueCapacity($user, $profileCount);

        if (! ($capacity['allowed'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => $capacity['message'],
                'daily_limit_reached' => ($capacity['remaining_daily'] ?? 0) <= 0,
                'remaining' => $capacity['remaining_daily'] ?? 0,
                'pending_count' => $capacity['pending_jobs'] ?? 0,
            ], ($capacity['pending_jobs'] ?? 0) >= app(EmailEnrichmentLimiter::class)->batchSize() ? 429 : 400);
        }

        $idsToQueue = $needing->pluck('id')->take($capacity['max_queue_now'])->values()->all();

        SnLead::query()
            ->whereIn('id', $idsToQueue)
            ->update(['email_fetch_attempted_at' => now(), 'email_fetch_status' => 'pending']);

        try {
            FetchSnEmailBatchJob::dispatchChunked($idsToQueue, $user->id, $listId);

            $queued = count($idsToQueue);

            return response()->json([
                'status' => 'success',
                'message' => $queued < $profileCount
                    ? "Queued {$queued} of {$profileCount} profile(s) for enrichment today."
                    : "Queued enrichment for {$queued} profile(s).",
                'profile_count' => $queued,
                'skipped' => $profileCount - $queued,
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to dispatch SN batch enrichment job', ['error' => $th->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to enrich: '.$th->getMessage(),
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
