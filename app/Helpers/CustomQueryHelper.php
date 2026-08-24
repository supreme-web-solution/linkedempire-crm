<?php

namespace App\Helpers;

use App\Models\Audience;
use App\Models\AudienceList;
use App\Models\SnLeadList;
use App\Models\SnLead;
use Illuminate\Support\Facades\DB;

trait CustomQueryHelper
{
    protected function getLeadList($userId)
    {
        $query1 = "id, audience_name as list_name, audience_id as list_hash, (select count(*) from audience_lists where audience_id=audiences.audience_id) as total_leads, concat(audience_id,'-aud') as type, 'Audience' as source, 'aud' as src, created_at";
        $first = Audience::select(DB::raw($query1))->where('user_id', $userId);

        $query2 = "id, name as list_name, list_hash, (select count(*) from sn_leads where sn_list_id = sn_leads_lists.list_hash) as total_leads, concat(list_hash,'-sn') as type, 'Sales Navigator' as source, 'sn' as src, created_at";
        $leadList = SnLeadList::select(DB::raw($query2))
            ->where('user_id', $userId)
            ->orderBy('list_name', 'asc')
            ->union($first)
            ->paginate(15);

        return $leadList;
    }

    protected function searchLeadList($userId, $term)
    {
        $query1 = "id, audience_name as list_name, audience_id as list_hash, (select count(*) from audience_lists where audience_id=audiences.audience_id) as total_leads, concat(audience_id,'-aud') as type, 'Audience' as source, 'aud' as src, created_at";
        $first = Audience::select(DB::raw($query1))
            ->where('user_id', $userId)
            ->where('audience_name','like','%'.$term.'%');

        $query2 = "id, name as list_name, list_hash, (select count(*) from sn_leads where sn_list_id = sn_leads_lists.list_hash) as total_leads, concat(list_hash,'-sn') as type, 'Sales Navigator' as source, 'sn' as src, created_at";
        $leadList = SnLeadList::select(DB::raw($query2))
            ->where('user_id', $userId)
            ->where('name','like','%'.$term.'%')
            ->orderBy('list_name', 'asc')
            ->union($first)
            ->paginate(15);

        return $leadList;
    }

    protected function searchLeads($list_id, $src, $term)
    {
        if($src == 'aud'){
            $query = "id, audience_id as list_hash, concat(con_first_name,' ',con_last_name) as name, con_email as email, con_job_title as headline, con_location as location, con_id as profileid, created_at";
            $leads = AudienceList::select(DB::raw($query))
                ->where('audience_id', $list_id)
                ->whereRaw('concat(con_job_title, con_location)','like','%'.$term.'%')
                ->paginate(15);
        }else {
            $query = "id, sn_list_id as list_hash, concat(first_name,' ',last_name) as name, email, headline, geolocation as location, lid as profileid, created_at ";
            $leads = SnLead::select(DB::raw($query))
                ->where('sn_list_id', $list_id)
                ->whereRaw('concat(headline, geolocation)','like','%'.$term.'%')
                ->paginate(15);
        }

        return $leads;
    }

    protected function allLeads($listId, $src, $emailFilter = 'all', $request = null)
    {
        if($src == 'aud'){
            $query = AudienceList::select(DB::raw("id, audience_id as list_hash, concat(con_first_name,' ',con_last_name) as name, con_email as email, con_job_title as headline, con_location as location, con_id as profileid, con_public_identifier as public_identifier, con_member_urn as member_urn, con_tracking_id as trackingId, con_distance as networkDistance, 'aud' as source, email_fetch_status, email_fetch_attempted_at, created_at"))
                ->where('audience_id', $listId);
            
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
            
            $leads = $query->paginate(15);
            
            // Preserve query parameters in pagination links
            if ($request) {
                $leads->appends($request->query());
            }
        }else {
            $query = SnLead::select(DB::raw("id, sn_list_id as list_hash, concat(first_name,' ',last_name) as name, email, headline, geolocation as location, lid as profileid, object_urn as member_urn, null as trackingId, degree as networkDistance, 'sn' as source, email_fetch_status, email_fetch_attempted_at, created_at"))
                ->where('sn_list_id', $listId);

            switch ($emailFilter) {
                case 'with_email':
                    $query->whereNotNull('email')->where('email', '!=', '');
                    break;
                case 'without_email':
                    $query->where(function ($q) {
                        $q->whereNull('email')->orWhere('email', '=', '');
                    })->where(function ($q) {
                        $q->where('email_fetch_status', 'completed')
                            ->orWhereNotNull('email_fetch_attempted_at');
                    });
                    break;
                case 'not_found':
                    $query->where('email_fetch_status', 'completed')
                        ->where(function ($q) {
                            $q->whereNull('email')->orWhere('email', '=', '');
                        });
                    break;
                case 'not_fetched':
                    $query->whereNull('email_fetch_status')
                        ->whereNull('email_fetch_attempted_at');
                    break;
                case 'pending':
                    $query->whereIn('email_fetch_status', ['pending', 'processing']);
                    break;
            }

            $leads = $query->paginate(15);
            
            // Preserve query parameters in pagination links
            if ($request) {
                $leads->appends($request->query());
            }
        }

        return $leads;
    }

    protected function snLeadExport($listId, $type=null)
    {
        $query = "sn_leads.first_name,sn_leads.last_name,sn_leads.email,sn_leads.headline,sn_leads.geolocation,
                concat('https://www.linkedin.com/in/',sn_leads.lid) as profile_url,ldc.company_name,
                ldc.company_description,ldc.company_website,
                ldc.company_industries,ldc.company_headquaters";

        $snLeads = SnLead::select(DB::raw($query))->leftJoin('sn_leads_companies as ldc','ldc.sn_lead_id','=','sn_leads.id');

        if($type && $type == 'bulk'){
            $leads = $snLeads->whereIn('sn_leads.id', explode(',', $listId))->get();
        }else {
            $leads = $snLeads->where('sn_leads.sn_list_id', $listId)->get();
        }

        return $leads;
    }

    protected function audienceExport($listId, $type=null)
    {
        $query = "con_first_name as firstName, con_last_name as lastName, con_job_title as occupation, 
                concat('https://www.linkedin.com/in/',con_public_identifier) as Link, con_location as location, 
                con_id as id, case when con_premium = 0 then 'false' else 'true' end as premium, 
                case when con_influencer = 0 then 'false' else 'true' end as influencer, 
                case when con_jobseeker = 0 then 'false' else 'true' end as jobSeeker,
                created_at as createdAt, con_distance as memberDistance, con_company_url as companyURL";

        $audLeads = AudienceList::select(DB::raw($query));

        if($type && $type == 'bulk'){
            $leads = $audLeads->whereIn('id', explode(',', $listId))->get();
        }else {
            $leads = $audLeads->where('audience_id', $listId)->get();
        }

        return $leads;
    }
}