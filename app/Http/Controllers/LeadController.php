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
use App\Models\User;
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
        $src = $request->query('src');
        
        // Only support audience leads (src=aud) for now
        if ($src !== 'aud') {
            return response()->json([
                'status' => 'error',
                'message' => 'Email fetching is only available for audience leads.'
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
        $src = request()->query('src');
        
        // Only support audience leads (src=aud) for now
        if ($src !== 'aud') {
            return response()->json([
                'status' => 'error',
                'message' => 'Batch email fetching only supported for audience leads'
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
