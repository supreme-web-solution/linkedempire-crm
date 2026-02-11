<?php

namespace App\Helpers;

use App\Models\AudienceList;
use App\Models\SnLead;
use App\Models\CampaignList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

trait CampaignHelper
{
    protected function checkAuthorization($request)
    {
        if(!$request->hasHeader('lk-id')){
            throw new Exception("Missing LinkedIn ID header", 401);
        }
        
        $linkedinId = $request->header('lk-id');
        
        if (empty($linkedinId)) {
            throw new Exception("LinkedIn ID cannot be empty", 401);
        }
        
        $user = User::where('linkedin_id', $linkedinId)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
        if(!$user){
            // Log::warning('Unauthorized access attempt', [
            //     'linkedin_id' => $linkedinId,
            //     'ip' => $request->ip(),
            //     'user_agent' => $request->userAgent()
            // ]);
            throw new Exception("User not found or unauthorized", 401);
        }
        return $user;
    }

    protected function campaignResource($data)
    {
        return array_map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status,
                'createdAt' => $item->created_at,
                'sequenceType' => $this->renameSequenceType($item->sequence_type),
                'notProcessConditionIf' => $item->process_condition ? json_decode($item->process_condition) : [],
                'campaignList' => $this->campaignListResource($item->campaign_list->toArray())
            ];
        }, $data);
    }

    protected function campaignListResource($data)
    {
        return array_map(function($item){
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'leads' => $item['leads'],
                'listHash' => $item['list_hash'],
                'listSource' => $item['source'],
            ];
        }, $data);
    }

    protected function campaignSequenceResource($data)
    {
        return array_map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'nodeModel' => json_decode($item->node_model),
                'linkModel' => json_decode($item->link_model),
            ];
        }, $data);
    }

    protected function leadResource($data)
    {
        return array_map(function($item) {
            // Safely handle networkDistance parsing - convert DISTANCE_X format to number for ALL sources
            $networkDistance = null;
            if (isset($item->networkDistance) && $item->networkDistance !== null) {
                $rawDistance = $item->networkDistance;
                // Handle DISTANCE_X format (e.g., "DISTANCE_1", "DISTANCE_2", "DISTANCE_3")
                if (is_string($rawDistance) && strpos($rawDistance, '_') !== false) {
                    $networkParts = explode('_', $rawDistance);
                    $networkDistance = isset($networkParts[1]) ? (int)$networkParts[1] : null;
                } elseif (is_numeric($rawDistance)) {
                    $networkDistance = (int)$rawDistance;
                } else {
                    $networkDistance = null;
                }
            }

            // Safely handle name splitting
            $nameParts = explode(' ', $item->name ?? '', 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            $leads = [
                'id' => $item->id,
                'name' => $item->name ?? '',
                'firstName' => $firstName,
                'lastName' => $lastName,
                'headline' => $item->headline ?? null,
                'email' => $item->email ?? null,
                'location' => $item->location ?? null,
                'connectionId' => $item->profileid ?? null,
                'conId' => $item->conId ?? $item->profileid ?? null, // Add conId field for endorsement
                'publicIdentifier' => $item->publicIdentifier ?? null, // Add publicIdentifier for endorsement
                'source' => $item->source ?? null,
                'listId' => $item->list_hash ?? null,
                'memberUrn' => $item->member_urn ?? null,
                'trackingId' => $item->trackingId ?? null,
                'networkDistance' => $networkDistance, // PAlready converted to integer
                'createdAt' => $item->created_at ?? null,
            ];
            
            if(property_exists($item, 'current_node_key')){
                $leads['currentNodeKey'] = $item->current_node_key;
            }
            if(property_exists($item, 'next_node_key')){
                $leads['nextNodeKey'] = $item->next_node_key;
            }
            if(property_exists($item, 'accept_status')){
                if($item->accept_status == 0){
                    $item->accept_status = false;
                }else {
                    $item->accept_status = true;
                }
                $leads['acceptedStatus'] = $item->accept_status;
            }
            if(property_exists($item, 'status_last_id')){
                $leads['statusLastId'] = $item->status_last_id;
            }

            return $leads;
        }, $data);
    }

    private function renameSequenceType($sequence_type)
    {
        switch ($sequence_type) {
            case 'lead_gen':
                $sequence_type = 'Lead generation';
                break;
            case 'endorse':
                $sequence_type = 'Endorse';
                break;
            case 'profile_views':
                $sequence_type = 'Profile views';
                break;
            case 'custom':
                $sequence_type = 'Custom';
                break;
        }
        return $sequence_type;
    }

    public function sequenceTypes()
    {
        return [
            [
                'icon' => 'images/LEAD_GENERATION.595ce63f.svg',
                'title' => 'Lead Generation',
                'tooltip' => 'Pick this template to start getting more leads for your business',
                'route' => 'sequence-leadGen',
                'sequence_type' => 'lead_gen'
            ],
            [
                'icon' => 'images/ENDORSE_MY_SKILLS.0a36bd93.svg',
                'title' => 'Endorse My Skills',
                'tooltip' => 'Pick this template to get endorsements and boost your profile credibility',
                'route' => 'sequence-endorsement',
                'sequence_type' => 'endorse'
            ],
            [
                'icon' => 'images/EXTRA_PROFILE_VIEWS.33512b76.svg',
                'title' => 'Extra Profile Views',
                'tooltip' => 'Pick this template to grab some extra attention and increase your profile views',
                'route' => 'sequence-profileView',
                'sequence_type' => 'profile_views'
            ],
            [
                'icon' => 'images/CUSTOM_CAMPAIGN.70f8aedd.svg',
                'title' => 'Custom Campaign',
                'tooltip' => 'Pick this option to create your custom campaign from scratch',
                'route' => 'sequence-custom',
                'sequence_type' => 'custom'
            ]
        ];
    }

    protected function getCampaignList($cid, $userId)
    {
        $query1 = "campaign_lists.id, sn_leads_lists.name as name, campaign_lists.list_hash as list_hash, count(sn_leads.id) as leads, 'sn' as source, date(sn_leads_lists.created_at) as created_date";
        $first = CampaignList::select(DB::raw($query1))
            ->join('campaigns','campaign_lists.campaign_id','=','campaigns.id')
            ->join('sn_leads_lists','campaign_lists.list_hash','=','sn_leads_lists.list_hash')
            ->leftJoin('sn_leads','campaign_lists.list_hash','=','sn_leads.sn_list_id')
            ->where('campaigns.user_id', $userId)
            ->where('campaigns.id', $cid)
            ->groupBy('campaign_lists.id','name','list_hash','source','created_date');

        $query2 = "campaign_lists.id, audiences.audience_name as name, audiences.audience_id as list_hash, count(audience_lists.id) as leads, 'aud' as source, date(audiences.created_at) as created_date";
        $campaignlist = CampaignList::select(DB::raw($query2))
            ->join('campaigns','campaign_lists.campaign_id','=','campaigns.id')
            ->join('audiences','campaign_lists.list_hash','=','audiences.audience_id')
            ->leftJoin('audience_lists','campaign_lists.list_hash','=','audience_lists.audience_id')
            ->where('campaigns.user_id', $userId)
            ->where('campaigns.id', $cid)
            ->groupBy('campaign_lists.id','audiences.audience_name','audiences.audience_id','source','created_date')
            ->union($first)
            ->get();

        return $campaignlist;
    }

    protected function getLeads($list_id, $src)
    {
        if($src == 'aud'){
            $query = "id, audience_id as list_hash, concat(con_first_name,' ',con_last_name) as name, con_email as email, con_job_title as headline, con_location as location, con_id as profileid, con_id as conId, con_public_identifier as publicIdentifier, con_member_urn as member_urn, con_tracking_id as trackingId, con_distance as networkDistance, 'aud' as source, created_at";
            
            $leads = AudienceList::select(DB::raw($query))->where('audience_id', $list_id)->get();
        }else {
            $query = "id, sn_list_id as list_hash, concat(first_name,' ',last_name) as name, email, headline, geolocation as location, lid as profileid, lid as conId, public_id as publicIdentifier, object_urn as member_urn, null as trackingId, degree as networkDistance, 'sn' as source, created_at";

            $leads = SnLead::select(DB::raw($query))->where('sn_list_id', $list_id)->get();
        }

        return $leads;
    }

    protected function getRunningLeads($list_id, $src)
    {
        if($src == 'aud'){
            $query = "audience_lists.id, audience_lists.audience_id as list_hash, 
                concat(audience_lists.con_first_name,' ',audience_lists.con_last_name) as name, 
                audience_lists.con_email as email, audience_lists.con_job_title as headline, 
                audience_lists.con_location as location, 
                audience_lists.con_id as profileid, 
                audience_lists.con_id as conId,
                audience_lists.con_public_identifier as publicIdentifier,
                audience_lists.con_member_urn as member_urn, 
                audience_lists.con_tracking_id as trackingId, 
                audience_lists.con_distance as networkDistance, 
                'aud' as source, 
                audience_lists.created_at,
                clr.current_node_key,
                clr.next_node_key,
                clr.accept_status,
                clr.status_last_id";

            $leads = AudienceList::select(DB::raw($query))
                ->join('campaign_leadgen_running as clr', 'clr.lead_id', '=', 'audience_lists.id')
                ->where('audience_lists.audience_id', $list_id)
                ->get();
        }else {
            $query = "sn_leads.id, 
                sn_leads.sn_list_id as list_hash, 
                concat(sn_leads.first_name,' ',sn_leads.last_name) as name, 
                sn_leads.email, sn_leads.headline, 
                sn_leads.geolocation as location, 
                sn_leads.lid as profileid, 
                sn_leads.lid as conId,
                sn_leads.public_id as publicIdentifier,
                sn_leads.object_urn as member_urn, 
                null as trackingId, 
                sn_leads.degree as networkDistance, 
                'sn' as source, 
                sn_leads.created_at,
                clr.current_node_key,
                clr.next_node_key,
                clr.accept_status,
                clr.status_last_id";
                
            $leads = SnLead::select(DB::raw($query))
                ->join('campaign_leadgen_running as clr', 'clr.lead_id', '=', 'sn_leads.id')
                ->where('sn_leads.sn_list_id', $list_id)
                ->get();
        }

        return $leads;
    }
}
