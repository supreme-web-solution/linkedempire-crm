<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Audience;
use App\Models\AudienceList;
use App\Models\EspIntegration;
use App\Models\AutoMessageResponse;
use App\Models\Ministat;
use App\Models\SnLead;
use App\Models\SnLeadList;
use App\Models\UserActivity;
use App\Models\Integration;
use App\Helpers\CampaignHelper;
use App\Services\EmailFinder;
use App\Services\LeadShareService;
use App\Services\PhantomBusterService;
use App\Services\LinkedInService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class ChromeApiController extends Controller
{
    use CampaignHelper;

    /**
     * Constructor
     */
    public function __construct()
    {
        // CORS headers will be handled by middleware or individual methods
    }

    /**
     * Enhanced access check with better error handling
     */
    public function accessCheck(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
            
            if (!$user->hasPermissionTo('FE')) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            return $this->successResponse([
                'accessId' => 'FE',
                'user' => [
                    'id' => $user->id,
                    'linkedin_id' => $user->linkedin_id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ], 'Access granted');
            
        } catch (Exception $e) {
            Log::error('Access check failed: ' . $e->getMessage(), [
                'linkedin_id' => $request->header('lk-id'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * Enhanced audience retrieval with error handling
     */
    public function getAudience(Request $request)
    {
        try {
            // Validate the linkedinId from query parameters
            $validator = Validator::make($request->query(), [
                'linkedinId' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                Log::warning('Audience API validation failed', [
                    'errors' => $validator->errors(),
                    'query_params' => $request->query(),
                    'all_params' => $request->all()
                ]);
                return $this->errorResponse('Invalid LinkedIn ID provided', 400, $validator->errors());
            }

            $linkedin_Id = $request->query('linkedinId');
            $user = null;

            if ($request->hasHeader('lk-id')) {
                try {
                    $user = $this->checkAuthorization($request);
                } catch (Exception $e) {
                    return $this->errorResponse($e->getMessage(), 401);
                }
            }

            if (!$user) {
                $user = User::where('linkedin_id', $linkedin_Id)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$user) {
                return $this->errorResponse('User not found for LinkedIn ID', 404);
            }
            
            Log::info('Fetching audiences for LinkedIn ID', [
                'linkedin_id' => $linkedin_Id,
                'ip' => $request->ip()
            ]);

            // Use parameterized query to prevent SQL injection
            $audiences = DB::select("
                SELECT 
                    a.id, 
                    a.audience_name, 
                    a.audience_id, 
                    a.audience_type, 
                    COUNT(al.audience_id) AS total 
                FROM audiences a
                JOIN users u ON u.id = a.user_id 
                LEFT JOIN audience_lists al ON al.audience_id = a.audience_id
                WHERE u.id = ? 
                GROUP BY a.id, a.audience_name, a.audience_id, a.audience_type
                ORDER BY DATE(a.created_at) DESC
            ", [$user->id]);

            Log::info('Audiences retrieved successfully', [
                'linkedin_id' => $user->linkedin_id,
                'user_id' => $user->id,
                'count' => count($audiences)
            ]);

            return $this->successResponse(['audience' => $audiences], 'Audiences retrieved successfully', 200);
            
        } catch (Exception $e) {
            Log::error('Failed to get audience: ' . $e->getMessage(), [
                'linkedin_id' => $request->query('linkedinId'),
                'query_params' => $request->query(),
                'all_params' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to retrieve audience data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Enhanced audience storage with validation
     */
    public function storeAudience(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'audienceName' => 'required|string|max:255',
                'audienceId' => 'required|string',
                'audienceType' => 'required|string',
                'linkedInId' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid audience data provided', 400, $validator->errors());
            }

            $user = User::where('linkedin_id', $request->linkedInId)->first();
            
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Check for existing audience
            $existingAudience = Audience::where('audience_name', $request->audienceName)
                ->where('user_id', $user->id)
                ->first();

            if ($existingAudience) {
                return $this->successResponse(['audience' => $existingAudience], 'Audience already exists');
            }

            // Create new audience
            $audience = Audience::create([
                'audience_name' => $request->audienceName,
                'audience_id' => $request->audienceId,
                'audience_type' => $request->audienceType,
                'user_id' => $user->id
            ]);

            Log::info('Audience created successfully', [
                'id' => $audience->id,
                'audience_id' => $audience->audience_id,
                'user_id' => $user->id,
                'audience_name' => $request->audienceName
            ]);

            return $this->successResponse(['audience' => $audience], 'Audience created successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to store audience: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);
            
            return $this->errorResponse('Failed to create audience', 500);
        }
    }

    public function deleteAudience(Request $request)
    {
        $audience_id = $request->query('audienceId');

        AudienceList::where('audience_id', $audience_id)->delete();
        Audience::where('audience_id', $audience_id)->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }

    public function getAudienceList(Request $request)
    {
        try {
            $audience_id = $request->query('audienceId');
            $total_count = $request->query('totalCount');

            // Validate audience_id is provided
            if (!$audience_id) {
                Log::warning('getAudienceList called without audienceId', [
                    'query_params' => $request->query(),
                    'linkedin_id' => $request->header('lk-id')
                ]);
                return response()->json([
                    'audience' => [],
                    'error' => 'audienceId parameter is required'
                ], 400);
            }

            // Ensure audience_id is cast to integer for consistent comparison
            $audience_id = is_numeric($audience_id) ? (int)$audience_id : $audience_id;

            Log::info('Fetching audience list', [
                'audience_id' => $audience_id,
                'audience_id_type' => gettype($audience_id),
                'total_count' => $total_count,
                'linkedin_id' => $request->header('lk-id'),
                'query_params' => $request->query()
            ]);

            // Debug: Check what audience_id values exist in audience_lists for this audience_id
            $debug_check = DB::select("
                SELECT DISTINCT audience_id, COUNT(*) as count 
                FROM audience_lists 
                WHERE audience_id = ? 
                GROUP BY audience_id
            ", [$audience_id]);
            
            // Also check with CAST to ensure type matching
            $debug_check_cast = DB::select("
                SELECT DISTINCT CAST(audience_id AS CHAR) as audience_id_str, COUNT(*) as count 
                FROM audience_lists 
                WHERE CAST(audience_id AS CHAR) = ? 
                GROUP BY audience_id_str
            ", [(string)$audience_id]);
            
            Log::info('Debug: Checking audience_id matches', [
                'requested_audience_id' => $audience_id,
                'requested_type' => gettype($audience_id),
                'found_matches_int' => $debug_check,
                'found_matches_str' => $debug_check_cast,
                'sample_audience_ids_in_table' => DB::select("SELECT DISTINCT audience_id FROM audience_lists ORDER BY audience_id DESC LIMIT 5")
            ]);

            // Use parameterized query to prevent SQL injection and handle type correctly
            if (isset($total_count)) {
                $audience_list = DB::select("
                    SELECT id, con_first_name, con_last_name, con_job_title, con_location, con_distance, 
                    con_public_identifier, con_id, con_member_urn, con_tracking_id, created_at
                    FROM audience_lists 
                    WHERE audience_id = ? 
                    ORDER BY DATE(created_at) DESC 
                    LIMIT ?
                ", [$audience_id, (int)$total_count]);
            } else {
                $audience_list = DB::select("
                    SELECT id, con_first_name, con_last_name, con_job_title, con_location, con_distance, 
                    con_public_identifier, con_id, con_member_urn, con_tracking_id, created_at
                    FROM audience_lists 
                    WHERE audience_id = ? 
                    ORDER BY DATE(created_at) DESC
                ", [$audience_id]);
            }

            Log::info('Audience list fetched', [
                'audience_id' => $audience_id,
                'audience_id_type' => gettype($audience_id),
                'count' => count($audience_list),
                'sample_ids' => array_slice(array_column($audience_list, 'id'), 0, 3),
                'sample_connection_ids' => array_slice(array_column($audience_list, 'con_id'), 0, 3)
            ]);

            return response()->json([
                'audience' => $audience_list
            ])->header('Content-Type', 'application/json');

        } catch (Exception $e) {
            Log::error('Failed to get audience list: ' . $e->getMessage(), [
                'audience_id' => $request->query('audienceId'),
                'linkedin_id' => $request->header('lk-id'),
                'query_params' => $request->query(),
                'exception' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'audience' => [],
                'error' => 'Failed to retrieve audience list: ' . $e->getMessage()
            ], 500)->header('Content-Type', 'application/json');
        }
    }

    public function storeAudienceList(Request $request)
    {
        try {
            Log::info('Storing audience list item', [
                'request_data' => $request->all(),
                'linkedin_id' => $request->header('lk-id'),
                'audience_id' => $request->audienceId,
                'connection_id' => $request->connectionId
            ]);

            // Ensure audience_id is cast to integer for consistent comparison with bigInteger column
            $audience_id = is_numeric($request->audienceId) ? (int)$request->audienceId : $request->audienceId;
            $first_name = $request->firstName;
            $last_name = $request->lastName;
            $email = $request->email;
            $title = null;
            $locationName = null;

            if ($request->has('title')) {
                $title = $request->title;
            }
            // Check both locationName and location (frontend might send either)
            if ($request->has('locationName') && !empty($request->locationName)) {
                $locationName = $request->locationName;
            } elseif ($request->has('location') && !empty($request->location)) {
                $locationName = $request->location;
            }

            $public_identifier = $request->publicIdentifier;
            $connection_id = $request->connectionId;
            $tracking_id = $request->trackingId;
            $member_urn = $request->memberUrn;
            
            // Extract network distance from request and convert to string format
            $network_distance = null;
            // Use input() instead of has() to properly handle 0 values
            if ($request->input('networkDistance') !== null) {
                $rawDistance = $request->input('networkDistance');
                // Convert to string format: "DISTANCE_1", "DISTANCE_2", "DISTANCE_3"
                if (is_numeric($rawDistance)) {
                    $network_distance = 'DISTANCE_' . (int)$rawDistance;
                } elseif (is_string($rawDistance) && $rawDistance !== '') {
                    // If already in format like "2nd", "3rd", extract number
                    if (preg_match('/(\d+)/', $rawDistance, $matches)) {
                        $network_distance = 'DISTANCE_' . $matches[1];
                    } elseif (str_starts_with(strtoupper($rawDistance), 'DISTANCE_')) {
                        $network_distance = strtoupper($rawDistance);
                    } else {
                        $network_distance = $rawDistance; // Use as-is
                    }
                }
            } elseif ($request->input('distance') !== null) {
                $rawDistance = $request->input('distance');
                if (is_numeric($rawDistance)) {
                    $network_distance = 'DISTANCE_' . (int)$rawDistance;
                } elseif (is_string($rawDistance) && $rawDistance !== '') {
                    $network_distance = $rawDistance;
                }
            } elseif ($request->input('connectionDegree') !== null) {
                $rawDistance = $request->input('connectionDegree');
                if (is_numeric($rawDistance)) {
                    $network_distance = 'DISTANCE_' . (int)$rawDistance;
                } elseif (is_string($rawDistance) && $rawDistance !== '') {
                    $network_distance = $rawDistance;
                }
            }

            // Log all received data including network distance
            Log::info('📊 POST-SCRAPING AUDIENCE: Received data from frontend/PhantomJS', [
                'audience_id' => $audience_id,
                'connection_id' => $connection_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'public_identifier' => $public_identifier,
                'networkDistance' => $network_distance,
                'networkDistance_source' => $request->has('networkDistance') ? 'networkDistance' : ($request->has('distance') ? 'distance' : ($request->has('connectionDegree') ? 'connectionDegree' : 'not_provided')),
                'all_request_keys' => array_keys($request->all()),
                'full_request_data' => $request->all()
            ]);

            Log::info('Checking for existing audience list item', [
                'audience_id' => $audience_id,
                'audience_id_type' => gettype($audience_id),
                'connection_id' => $connection_id
            ]);

            $check_exists = AudienceList::where('con_id', $connection_id)
                ->where('audience_id', $audience_id)
                ->get();

            if ($check_exists->count() == 0) {
                Log::info('Creating new audience list item', [
                    'audience_id' => $audience_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'connection_id' => $connection_id
                ]);

                // Prepare data array for creation
                $createData = [
                    'audience_id' => $audience_id,
                    'con_first_name' => $first_name,
                    'con_last_name' => $last_name,
                    'con_email' => $email,
                    'con_job_title' => $title,
                    'con_location' => $locationName,
                    'con_public_identifier' => $public_identifier,
                    'con_id' => $connection_id,
                    'con_tracking_id' => $tracking_id,
                    'con_member_urn' => $member_urn,
                ];
                
                // Only add con_distance if it's not null
                if ($network_distance !== null) {
                    $createData['con_distance'] = (string)$network_distance; // Ensure it's a string
                }
                
                // Log what we're about to save
                Log::info('💾 POST-SCRAPING AUDIENCE: About to save to database', [
                    'create_data' => $createData,
                    'network_distance_raw' => $network_distance,
                    'network_distance_type' => gettype($network_distance),
                    'locationName' => $locationName,
                    'locationName_type' => gettype($locationName),
                ]);

                $audienceListItem = AudienceList::create($createData);
                
                // Refresh from database to ensure we have the actual saved values
                $audienceListItem->refresh();

                // Verify what's actually in the database with a direct query
                $dbRecord = \DB::table('audience_lists')
                    ->where('id', $audienceListItem->id)
                    ->first(['id', 'con_distance', 'con_location', 'con_first_name', 'con_last_name']);

                // Log what was actually saved to database
                Log::info('💾 POST-SCRAPING AUDIENCE: Saved to audience_lists table', [
                    'id' => $audienceListItem->id,
                    'audience_id' => $audience_id,
                    'connection_id' => $connection_id,
                    'con_distance_input' => $network_distance,
                    'con_distance_input_type' => gettype($network_distance),
                    'con_distance_from_model' => $audienceListItem->con_distance,
                    'con_distance_from_db_query' => $dbRecord->con_distance ?? 'NOT_FOUND',
                    'con_location_input' => $locationName,
                    'con_location_from_model' => $audienceListItem->con_location,
                    'con_location_from_db_query' => $dbRecord->con_location ?? 'NOT_FOUND',
                    'all_saved_fields' => [
                        'con_first_name' => $audienceListItem->con_first_name,
                        'con_last_name' => $audienceListItem->con_last_name,
                        'con_public_identifier' => $audienceListItem->con_public_identifier,
                        'con_id' => $audienceListItem->con_id,
                        'con_distance' => $audienceListItem->con_distance,
                        'con_location' => $audienceListItem->con_location
                    ],
                    'database_record' => [
                        'con_distance' => $dbRecord->con_distance ?? null,
                        'con_location' => $dbRecord->con_location ?? null,
                    ]
                ]);

                Log::info('Audience list item created successfully', [
                    'id' => $audienceListItem->id,
                    'audience_id' => $audience_id
                ]);

                // If email is missing, dispatch individual email fetch job
                if (empty($email) && !empty($public_identifier)) {
                    try {
                        // Get user ID from audience
                        $audience = Audience::where('audience_id', $audience_id)->first();
                        if (!$audience) {
                            Log::warning('Failed to find audience for email fetch', [
                                'audience_id' => $audience_id
                            ]);
                            return response()->json(['message' => 'success'], 201);
                        }

                        $userId = $audience->user_id;
                        
                        // Check daily limit
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $this->checkAndResetDailyLimit($user);
                            $user->refresh();
                            
                            $dailyLimit = config('services.email_scraping.daily_limit_per_user', 100);
                            if ($user->daily_profile_email_scraping_count >= $dailyLimit) {
                                Log::info('Daily email scraping limit reached for user', [
                                    'user_id' => $userId,
                                    'count' => $user->daily_profile_email_scraping_count
                                ]);
                                return response()->json(['message' => 'success'], 201);
                            }
                        }

                        // Dispatch individual email fetch job (one by one)
                        FetchAudienceEmailJob::dispatch($audienceListItem->id, $public_identifier);
                        
                        Log::info('Dispatched individual email fetch job from extension', [
                            'audience_list_id' => $audienceListItem->id,
                            'public_identifier' => $public_identifier,
                            'user_id' => $userId
                        ]);
                    } catch (\Throwable $th) {
                        // Log but don't fail the request - email fetching is optional
                        Log::warning('Failed to dispatch email fetch job', [
                            'audience_list_id' => $audienceListItem->id,
                            'public_identifier' => $public_identifier,
                            'error' => $th->getMessage()
                        ]);
                    }
                }

                return response()->json([
                    'message' => 'success'
                ], 201);
            } else {
                Log::info('Audience list item already exists', [
                    'audience_id' => $audience_id,
                    'connection_id' => $connection_id
                ]);

                return response()->json([
                    'message' => 'User already added to audience list'
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to store audience list item: ' . $e->getMessage(), [
                'request' => $request->all(),
                'linkedin_id' => $request->header('lk-id')
            ]);
            
            return response()->json([
                'message' => 'Failed to store audience list item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAudienceList(Request $request)
    {
        $audience_id = $request->audienceId;
        $connection_id = $request->connectionId;
        $ndistance = $request->networkDistance;
        $premium = $request->premium;
        $influencer = $request->influencer;
        $jobSeeker = $request->jobSeeker;
        $companyUrl = $request->companyUrl;

        $data = [];
        
        // Only update con_distance if it's provided and not null
        if ($ndistance !== null) {
            // Convert to string format
            if (is_numeric($ndistance)) {
                $data['con_distance'] = 'DISTANCE_' . (int)$ndistance;
            } else {
                $data['con_distance'] = (string)$ndistance;
            }
        }
        
        // Only add other fields if they're provided
        if ($premium !== null) {
            $data['con_premium'] = $premium;
        }
        if ($influencer !== null) {
            $data['con_influencer'] = $influencer;
        }
        if ($jobSeeker !== null) {
            $data['con_jobseeker'] = $jobSeeker;
        }
        if ($companyUrl !== null) {
            $data['con_company_url'] = $companyUrl;
        }

        $lead = AudienceList::where('audience_id', $audience_id)
            ->where('con_id', $connection_id);

        $lead->update($data);

        // get audience data from db
        if ($lead->first()->con_first_name && $lead->first()->con_last_name && $lead->first()->con_company_url && !$lead->first()->con_email) {
            $domain = $this->trimDomain($lead->first()->con_company_url);

            // initiate email finder
            try {
                $getEmail = new EmailFinder([
                    'firstName' => $lead->first()->con_first_name,
                    'lastName' => $lead->first()->con_last_name,
                    'website' => $domain
                ]);

                $getEmail = $getEmail->findEmail();

                AudienceList::where('con_id', $connection_id)->update(['con_email' => $getEmail['email']]);
            } catch (\Throwable $th) {
                // throw $th;
                Log::info($th);
            }
        }

        return response()->json([
            'message' => 'success'
        ]);
    }

    public function deleteAudienceList(Request $request)
    {
        $row_id = $request->query('rowId');

        AudienceList::where('id', $row_id)->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }

    public function audienceListExport(Request $request)
    {
        try {
            $audience_id = $request->query('audienceId');
            
            Log::info('[BACKEND] audienceListExport called', [
                'audience_id' => $audience_id,
                'request_params' => $request->all(),
                'export_type' => 'CSV',
                'timestamp' => now()->toISOString()
            ]);

            if (!$audience_id) {
                Log::warning('[BACKEND] audienceListExport: Missing audienceId parameter');
                return response()->json([
                    'error' => 'audienceId parameter is required'
                ], 400);
            }

            $audience_list = sprintf("
                SELECT con_first_name as firstName, con_last_name as lastName, con_job_title as occupation, 
                concat('https://www.linkedin.com/in/',con_public_identifier) as Link, con_location as location, 
                con_id as id, case when con_premium = 0 then 'false' else 'true' end as premium, 
                case when con_influencer = 0 then 'false' else 'true' end as influencer, 
                case when con_jobseeker = 0 then 'false' else 'true' end as jobSeeker,
                created_at as createdAt, con_distance as memberDistance, con_company_url as companyURL 
                from audience_lists where audience_id = %s
            ", $audience_id);

            $audience_list = DB::select($audience_list);
            
            Log::info('[BACKEND] audienceListExport: Query executed successfully', [
                'audience_id' => $audience_id,
                'record_count' => count($audience_list),
                'export_type' => 'CSV'
            ]);

            return response()->json([
                'audience' => $audience_list
            ]);
        } catch (\Exception $e) {
            Log::error('[BACKEND] audienceListExport failed', [
                'audience_id' => $request->query('audienceId'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to export audience data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request, $audience_id)
    {
        try {
            $user = $this->checkAuthorization($request);
            $exportType = $request->type ?? 'csv';
            $espType = $request->espType;
            
            Log::info('[BACKEND] export method called', [
                'audience_id' => $audience_id,
                'user_id' => $user->id,
                'export_type' => $exportType,
                'esp_type' => $espType,
                'request_params' => $request->all(),
                'timestamp' => now()->toISOString()
            ]);

            $query = sprintf("
                SELECT con_first_name as firstName, con_last_name as lastName, con_email as email, con_job_title as occupation, 
                concat('https://www.linkedin.com/in/',con_public_identifier) as Link, con_location as location,
                created_at as createdAt, con_distance as memberDistance, con_company_url as companyURL 
                from audience_lists 
                where audience_id = %s
            ", $audience_id);

            $audiences = DB::select($query);
            
            Log::info('[BACKEND] export: Query executed', [
                'audience_id' => $audience_id,
                'total_records' => count($audiences),
                'records_with_email' => count(array_filter($audiences, function($a) { return !empty($a->email); }))
            ]);

            if ($exportType == 'csv') {
                Log::info('[BACKEND] export: Returning CSV data', [
                    'audience_id' => $audience_id,
                    'record_count' => count($audiences)
                ]);
                return $this->successResponse(['data' => $audiences]);
            } else {
                Log::info('[BACKEND] export: Processing ESP export', [
                    'audience_id' => $audience_id,
                    'esp_type' => $espType,
                    'user_id' => $user->id
                ]);
                
                $esp = EspIntegration::where('user_id', $user->id)->first();

                if (!$esp) {
                    Log::warning('[BACKEND] export: No ESP integration found for user', [
                        'user_id' => $user->id,
                        'audience_id' => $audience_id
                    ]);
                    return $this->errorResponse('No ESP integration configured. Please configure your email service provider in the profile settings.', 400);
                }
                
                if (!$espType) {
                    Log::warning('[BACKEND] export: ESP type not specified', [
                        'user_id' => $user->id,
                        'audience_id' => $audience_id,
                        'available_esps' => [
                            'mailchimp' => !empty($esp->mailchimp),
                            'getresponse' => !empty($esp->getresponse),
                            'emailoctopus' => !empty($esp->emailoctopus),
                            'converterkit' => !empty($esp->converterkit),
                            'mailerlite' => !empty($esp->mailerlite),
                            'webhook' => !empty($esp->webhook)
                        ]
                    ]);
                    return $this->errorResponse('ESP type is required. Please specify which email service provider to use.', 400);
                }

                $esp = [
                    'id' => $esp->id,
                    'mailchimp' => json_decode($esp->mailchimp),
                    'getresponse' => json_decode($esp->getresponse),
                    'emailoctopus' => json_decode($esp->emailoctopus),
                    'converterkit' => json_decode($esp->converterkit),
                    'mailerlite' => json_decode($esp->mailerlite),
                    'webhook' => $esp->webhook
                ];
                
                Log::info('[BACKEND] export: ESP configuration loaded', [
                    'esp_type' => $espType,
                    'esp_config_exists' => !empty($esp[$espType]),
                    'user_id' => $user->id
                ]);

                if (empty($esp[$espType])) {
                    Log::warning('[BACKEND] export: ESP type not configured', [
                        'esp_type' => $espType,
                        'user_id' => $user->id,
                        'available_esps' => array_keys(array_filter($esp, function($v) { return !empty($v); }))
                    ]);
                    return $this->errorResponse("ESP type '{$espType}' is not configured. Please configure it in your profile settings.", 400);
                }

                $leadShare = new LeadShareService;
                $listTypes = ['listid', 'campaignId', 'formId', 'groupId'];

                if ($espType == 'mailchimp' || $espType == 'emailoctopus')
                    $listkey = $listTypes[0];
                elseif ($espType == 'getresponse')
                    $listkey = $listTypes[1];
                elseif ($espType == 'converterkit')
                    $listkey = $listTypes[2];
                elseif ($espType == 'mailerlite')
                    $listkey = $listTypes[3];
                else {
                    Log::warning('[BACKEND] export: Unknown ESP type', [
                        'esp_type' => $espType,
                        'user_id' => $user->id
                    ]);
                    return $this->errorResponse("Unknown ESP type: {$espType}", 400);
                }
                
                Log::info('[BACKEND] export: Starting lead sharing', [
                    'esp_type' => $espType,
                    'list_key' => $listkey,
                    'total_leads' => count($audiences),
                    'leads_with_email' => count(array_filter($audiences, function($a) { return !empty($a->email); }))
                ]);

                $sharedCount = 0;
                $skippedCount = 0;
                
                foreach ($audiences as $lead) {
                    if ($lead->email) {
                        Log::info('[BACKEND] export: Sharing lead to ESP', [
                            'esp_type' => $espType,
                            'email' => $lead->email,
                            'name' => $lead->firstName,
                            'audience_id' => $audience_id
                        ]);
                        
                        // Access ESP config as object (json_decode returns stdClass)
                        $espConfig = $esp[$espType];
                        $apiKey = $espConfig->apikey ?? null;
                        $listId = $espConfig->{$listkey} ?? null;
                        
                        // Log API key info (without exposing full key)
                        $keyPreview = $apiKey ? (substr($apiKey, 0, 10) . '...' . substr($apiKey, -5)) : 'null';
                        Log::info('[BACKEND] export: ESP config values', [
                            'esp_type' => $espType,
                            'has_apikey' => !empty($apiKey),
                            'apikey_preview' => $keyPreview,
                            'has_listid' => !empty($listId),
                            'listid' => $listId,
                            'email' => $lead->email
                        ]);
                        
                        $leadShare->leadShare($espType, [
                            'email' => $lead->email,
                            'name' => $lead->firstName,
                            'apikey' => $apiKey,
                            'listid' => $listId
                        ]);
                        $sharedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
                
                Log::info('[BACKEND] export: Lead sharing completed', [
                    'esp_type' => $espType,
                    'shared_count' => $sharedCount,
                    'skipped_count' => $skippedCount,
                    'audience_id' => $audience_id
                ]);

                return $this->successResponse([
                    'shared_count' => $sharedCount,
                    'skipped_count' => $skippedCount
                ], "Successfully shared {$sharedCount} leads to {$espType}");
            }
        } catch (Exception $e) {
            Log::error('[BACKEND] export failed', [
                'audience_id' => $audience_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return $this->errorResponse('Failed to export audience data: ' . $e->getMessage(), 500);
        }
    }

    public function getEspConfig(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
            
            Log::info('[BACKEND] getEspConfig called', [
                'user_id' => $user->id,
                'timestamp' => now()->toISOString()
            ]);
            
            $esp = EspIntegration::where('user_id', $user->id)->first();
            
            $configuredEsp = [];
            
            if ($esp) {
                $espData = [
                    'mailchimp' => json_decode($esp->mailchimp),
                    'getresponse' => json_decode($esp->getresponse),
                    'emailoctopus' => json_decode($esp->emailoctopus),
                    'converterkit' => json_decode($esp->converterkit),
                    'mailerlite' => json_decode($esp->mailerlite),
                    'webhook' => $esp->webhook
                ];
                
                $espDisplayNames = [
                    'mailchimp' => 'Mailchimp',
                    'getresponse' => 'GetResponse',
                    'emailoctopus' => 'EmailOctopus',
                    'converterkit' => 'ConverterKit',
                    'mailerlite' => 'MailerLite',
                    'webhook' => 'Webhook'
                ];
                
                foreach ($espData as $espType => $config) {
                    if (!empty($config)) {
                        // Check if it's a JSON object with apikey or if it's a string (webhook)
                        if (is_object($config) && !empty($config->apikey)) {
                            $configuredEsp[] = [
                                'type' => $espType,
                                'name' => $espDisplayNames[$espType] ?? ucfirst($espType)
                            ];
                        } elseif (is_string($config) && !empty($config)) {
                            // For webhook
                            $configuredEsp[] = [
                                'type' => $espType,
                                'name' => $espDisplayNames[$espType] ?? ucfirst($espType)
                            ];
                        }
                    }
                }
            }
            
            Log::info('[BACKEND] getEspConfig: Returning configured ESPs', [
                'user_id' => $user->id,
                'configured_count' => count($configuredEsp),
                'esps' => array_column($configuredEsp, 'type')
            ]);
            
            return $this->successResponse([
                'esps' => $configuredEsp,
                'has_config' => count($configuredEsp) > 0
            ], 'ESP configuration retrieved successfully');
        } catch (Exception $e) {
            Log::error('[BACKEND] getEspConfig failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to retrieve ESP configuration', 500);
        }
    }

    public function audienceRecent(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
            $audienceId = $request->query('audienceId');

            $todays_audience = AudienceList::where('audience_id', $audienceId)
                ->whereDate('created_at', Carbon::today())
                ->get();

            $audience_count = $todays_audience->count();

            return $this->successResponse([
                'total' => $audience_count,
                'audience' => $todays_audience
            ], 'Recent audience data retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to get recent audience: ' . $e->getMessage(), [
                'audience_id' => $request->query('audienceId'),
                'request' => $request->all()
            ]);
            
            return $this->errorResponse('Failed to retrieve recent audience data', 500);
        }
    }

    public function getAutoResponses(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        $normal = [];
        $endorsement = [];
        $followup = [];

        $auto_responses = AutoMessageResponse::where('user_id', $user->id)->get();

        foreach ($auto_responses as $item) {
            if ($item->message_type == 'normal') {
                array_push($normal, $item);
            } elseif ($item->message_type == 'endorsement') {
                array_push($endorsement, $item);
            } elseif ($item->message_type == 'followup') {
                array_push($followup, $item);
            }
        }

        return response()->json([
            'normal' => $normal,
            'endorsement' => (object)$endorsement,
            'followup' => (object)$followup
        ]);
    }

    public function storeAutoResponse(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        AutoMessageResponse::create([
            'message_type' => $request->message_type,
            'message_keywords' => $request->message_keywords,
            'total_endorse_skills' => $request->total_endorse_skills,
            'message_body' => $request->message_body,
            'attachement' => $request->attachement,
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'message created!'
        ], 201);
    }

    public function showAutoResponse(Request $request, string $id)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        $autoresponse = AutoMessageResponse::findOrFail($id);

        return response()->json([
            'data' => $autoresponse
        ]);
    }

    public function updateAutoResponse(Request $request, string $id)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        $autoresponse = AutoMessageResponse::findOrFail($id);

        $autoresponse->update([
            'message_type' => $request->message_type,
            'message_keywords' => $request->message_keywords,
            'total_endorse_skills' => $request->total_endorse_skills,
            'message_body' => $request->message_body,
            'attachement' => $request->attachement,
        ]);

        return response()->json([
            'message' => 'message updated!'
        ], 201);
    }

    public function deleteAutoResponse(Request $request, string $id)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        $autoresponse = AutoMessageResponse::findOrFail($id);

        $autoresponse->delete();

        return response()->json([
            'message' => 'message deleted!'
        ]);
    }

    public function langFilter(Request $request)
    {
        $lang = $request->query('lang', '');
        $searchTerm = strtolower(trim($lang));
        
        // LinkedIn's supported profile languages
        $allLanguages = [
            ['language_code' => 'en', 'name' => 'English'],
            ['language_code' => 'es', 'name' => 'Spanish'],
            ['language_code' => 'fr', 'name' => 'French'],
            ['language_code' => 'de', 'name' => 'German'],
            ['language_code' => 'pt', 'name' => 'Portuguese'],
            ['language_code' => 'it', 'name' => 'Italian'],
            ['language_code' => 'nl', 'name' => 'Dutch'],
            ['language_code' => 'pl', 'name' => 'Polish'],
            ['language_code' => 'ru', 'name' => 'Russian'],
            ['language_code' => 'ja', 'name' => 'Japanese'],
            ['language_code' => 'ko', 'name' => 'Korean'],
            ['language_code' => 'zh', 'name' => 'Chinese'],
            ['language_code' => 'ar', 'name' => 'Arabic'],
            ['language_code' => 'hi', 'name' => 'Hindi'],
            ['language_code' => 'tr', 'name' => 'Turkish'],
            ['language_code' => 'sv', 'name' => 'Swedish'],
            ['language_code' => 'da', 'name' => 'Danish'],
            ['language_code' => 'no', 'name' => 'Norwegian'],
            ['language_code' => 'fi', 'name' => 'Finnish'],
            ['language_code' => 'cs', 'name' => 'Czech'],
            ['language_code' => 'hu', 'name' => 'Hungarian'],
            ['language_code' => 'ro', 'name' => 'Romanian'],
            ['language_code' => 'th', 'name' => 'Thai'],
            ['language_code' => 'vi', 'name' => 'Vietnamese'],
            ['language_code' => 'id', 'name' => 'Indonesian'],
            ['language_code' => 'ms', 'name' => 'Malay'],
            ['language_code' => 'he', 'name' => 'Hebrew'],
            ['language_code' => 'uk', 'name' => 'Ukrainian'],
            ['language_code' => 'el', 'name' => 'Greek'],
            ['language_code' => 'bg', 'name' => 'Bulgarian'],
            ['language_code' => 'hr', 'name' => 'Croatian'],
            ['language_code' => 'sk', 'name' => 'Slovak'],
            ['language_code' => 'sl', 'name' => 'Slovenian'],
            ['language_code' => 'sr', 'name' => 'Serbian'],
            ['language_code' => 'et', 'name' => 'Estonian'],
            ['language_code' => 'lv', 'name' => 'Latvian'],
            ['language_code' => 'lt', 'name' => 'Lithuanian'],
        ];
        
        $languages = [];
        
        if (!empty($searchTerm)) {
            // Filter languages by search term (case-insensitive)
            $languages = array_filter($allLanguages, function($langItem) use ($searchTerm) {
                return stripos(strtolower($langItem['name']), $searchTerm) !== false ||
                       stripos(strtolower($langItem['language_code']), $searchTerm) !== false;
            });
            $languages = array_values($languages); // Re-index array
        } else {
            // Return all languages if no search term
            $languages = $allLanguages;
        }

        return response()->json([
            'languages' => $languages
        ]);
    }

    public function LinkedInConfig(Request $request)
    {
        $connection = $request->query('connection');
        $sentInvite = $request->query('sentInvite');
        $profileView = $request->query('profileView');
        $linkedin_id = $request->query('profileId');

        if (isset($linkedin_id)) {
            $user = User::where('linkedin_id', $linkedin_id)->first();

            if ($user) {
                $ministat = new Ministat;

                $stats = $ministat->where('user_id', $user->id);

                if ($stats->first()) {
                    $stats->update([
                        'connections' => $connection ?? 0,
                        'pending_invites' => $sentInvite ?? 0,
                        'profile_views' => $profileView ?? 0
                    ]);
                } else {
                    $ministat->create([
                        'connections' => $connection ?? 0,
                        'pending_invites' => $sentInvite ?? 0,
                        'profile_views' => $profileView ?? 0,
                        'user_id' => $user->id
                    ]);
                }

                return response()->json([
                    'message' => 'report save.',
                    'status_code' => 200
                ], 201);
            } else {
                return response()->json([
                    'message' => 'link id not available.',
                    'status_code' => 401
                ], 401);
            }
        } else {
            \Log::warning('⚠️ [Backend] LinkedInConfig called without profileId');
        }
    }

    public function fetchPostLikersFromPhantom(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'status' => 401
            ], 401);
        }

        $validated = $request->validate([
            'post_url' => ['required', 'url'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500']
        ]);

        // Normalize the post URL to handle various LinkedIn URL formats
        $postUrl = $this->normalizeLinkedInPostUrl($validated['post_url']);

        $integration = Integration::where('user_id', $user->id)
            ->where('oauth_provider', 'linkedin')
            ->whereNotNull('linkedin_session_cookie')
            ->latest('linkedin_session_verified_at')
            ->first();

        if (!$integration) {
            return $this->errorResponse(
                'LinkedIn session cookie not found. Please update it from the Social Accounts page.',
                422
            );
        }

        try {
            $service = new PhantomBusterService();
            $likers = $service->fetchPostLikersForUrl(
                $postUrl,
                600,
                15,
                $integration->linkedin_session_cookie,
                $integration->linkedin_user_agent ?? config('services.phantombuster.linkedin_user_agent')
            );

            // Log sample of raw PhantomBuster response to see what fields are available
            if (count($likers) > 0) {
                $sampleProfile = $likers[0];
                Log::info('📊 POST-SCRAPING AUDIENCE: PhantomBuster raw response sample', [
                    'total_from_phantom' => count($likers),
                    'requested_limit' => $validated['limit'] ?? 'not set',
                    'post_url' => $postUrl,
                    'original_post_url' => $validated['post_url'],
                    'sample_profile_keys' => array_keys($sampleProfile),
                    'sample_profile_data' => $sampleProfile,
                    'has_connectionDegree' => isset($sampleProfile['connectionDegree']),
                    'has_connection_degree' => isset($sampleProfile['connection_degree']),
                    'has_degree' => isset($sampleProfile['degree']),
                    'has_networkDistance' => isset($sampleProfile['networkDistance']),
                ]);
            } else {
                Log::info('Post likers received from PhantomBuster', [
                    'total_from_phantom' => 0,
                    'requested_limit' => $validated['limit'] ?? 'not set',
                    'post_url' => $postUrl,
                    'original_post_url' => $validated['post_url'],
                ]);
            }

            $likersBeforeLimit = count($likers);
            if (isset($validated['limit'])) {
                $likers = array_slice($likers, 0, (int) $validated['limit']);
                Log::info('Post likers limited', [
                    'before_limit' => $likersBeforeLimit,
                    'after_limit' => count($likers),
                    'limit_value' => (int) $validated['limit'],
                ]);
            }

            // Filter out company entries (they don't have profileLink) and transform valid profiles
            $normalized = [];
            $skippedCompanies = 0;
            $skippedNoProfileLink = 0;
            $transformationErrors = 0;
            
            foreach ($likers as $index => $profile) {
                try {
                    // Log what PhantomBuster returned for this profile
                    if ($index < 3) { // Log first 3 profiles in detail
                        Log::info('📊 POST-SCRAPING AUDIENCE: PhantomBuster raw profile data', [
                            'index' => $index,
                            'profile_keys' => array_keys($profile),
                            'profile_data' => $profile,
                            'has_connectionDegree' => isset($profile['connectionDegree']),
                            'has_connection_degree' => isset($profile['connection_degree']),
                            'has_degree' => isset($profile['degree']),
                            'has_networkDistance' => isset($profile['networkDistance']),
                            'connectionDegree_value' => $profile['connectionDegree'] ?? $profile['connection_degree'] ?? $profile['degree'] ?? $profile['networkDistance'] ?? 'not_found',
                        ]);
                    }
                    
                    // Skip company entries (they have companyUrl but no profileLink)
                    if (isset($profile['companyUrl']) && !isset($profile['profileLink'])) {
                        $skippedCompanies++;
                        Log::debug('Skipping company entry', [
                            'index' => $index,
                            'company_name' => $profile['companyName'] ?? 'unknown',
                            'company_url' => $profile['companyUrl'] ?? null,
                        ]);
                        continue;
                    }
                    // Skip entries without profileLink (can't extract publicIdentifier)
                    if (!isset($profile['profileLink']) && !isset($profile['profile_link'])) {
                        $skippedNoProfileLink++;
                        Log::debug('Skipping entry without profileLink', [
                            'index' => $index,
                            'name' => $profile['name'] ?? 'unknown',
                            'has_companyUrl' => isset($profile['companyUrl']),
                        ]);
                        continue;
                    }
                    $transformed = $this->transformPhantomProfile($profile);
                    
                    // Log what was transformed
                    if ($index < 3) { // Log first 3 transformed profiles
                        Log::info('🔄 POST-SCRAPING AUDIENCE: Transformed profile data', [
                            'index' => $index,
                            'original_connectionDegree' => $profile['connectionDegree'] ?? $profile['connection_degree'] ?? $profile['degree'] ?? 'not_found',
                            'transformed_connectionDegree' => $transformed['connectionDegree'] ?? 'not_found',
                            'transformed_connectionDegreeValue' => $transformed['connectionDegreeValue'] ?? 'not_found',
                            'transformed_publicIdentifier' => $transformed['publicIdentifier'] ?? 'not_found',
                            'transformed_connectionId' => $transformed['connectionId'] ?? 'not_found',
                            'fullName' => $transformed['fullName'] ?? 'not_found',
                        ]);
                    }
                    
                    if (empty($transformed['publicIdentifier']) && empty($transformed['connectionId'])) {
                        Log::warning('Transformed profile missing identifiers', [
                            'index' => $index,
                            'name' => $transformed['fullName'] ?? 'unknown',
                            'profile_link' => $profile['profileLink'] ?? null,
                        ]);
                    }
                    $normalized[] = $transformed;
                } catch (\Throwable $e) {
                    $transformationErrors++;
                    Log::error('Error transforming profile', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'profile_data' => array_keys($profile),
                    ]);
                }
            }

            Log::info('Post likers filtered and transformed', [
                'total_from_phantom' => $likersBeforeLimit,
                'after_limit' => count($likers),
                'skipped_companies' => $skippedCompanies,
                'skipped_no_profile_link' => $skippedNoProfileLink,
                'transformation_errors' => $transformationErrors,
                'valid_profiles' => count($normalized),
            ]);

            Log::info('Returning post likers to frontend', [
                'profiles_count' => count($normalized),
                'total_from_phantom' => $likersBeforeLimit,
                'after_limit' => count($likers),
                'skipped_companies' => $skippedCompanies,
                'skipped_no_profile_link' => $skippedNoProfileLink,
                'transformation_errors' => $transformationErrors,
                'requested_limit' => $validated['limit'] ?? 'not set',
            ]);

            // Log sample of first few profiles for debugging
            if (count($normalized) > 0) {
                Log::debug('Sample profiles being returned', [
                    'sample_count' => min(3, count($normalized)),
                    'samples' => array_slice($normalized, 0, 3),
                ]);
            }

            return $this->successResponse([
                'profiles' => $normalized,
                'total' => count($normalized),
                'total_from_phantom' => $likersBeforeLimit,
                'after_limit' => count($likers),
                'skipped_companies' => $skippedCompanies,
                'skipped_no_profile_link' => $skippedNoProfileLink,
                'post_url' => $postUrl,
                'original_post_url' => $validated['post_url'],
                'fetched_at' => now()->toISOString()
            ], 'Fetched post likers successfully');
        } catch (\Throwable $th) {
            Log::error('Chrome API: Failed to fetch post likers', [
                'user_id' => $user->id,
                'post_url' => $postUrl ?? ($validated['post_url'] ?? 'unknown'),
                'original_post_url' => $validated['post_url'] ?? 'unknown',
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch post likers: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Fetch post commenters from PhantomBuster
     * Similar to fetchPostLikersFromPhantom but for comments
     */
    public function fetchPostCommentsFromPhantom(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'status' => 401
            ], 401);
        }

        $validated = $request->validate([
            'post_url' => ['required', 'url'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500']
        ]);

        // Normalize the post URL to handle various LinkedIn URL formats
        $postUrl = $this->normalizeLinkedInPostUrl($validated['post_url']);

        $integration = Integration::where('user_id', $user->id)
            ->where('oauth_provider', 'linkedin')
            ->whereNotNull('linkedin_session_cookie')
            ->latest('linkedin_session_verified_at')
            ->first();

        if (!$integration) {
            return $this->errorResponse(
                'LinkedIn session cookie not found. Please update it from the Social Accounts page.',
                422
            );
        }

        try {
            $service = new PhantomBusterService();
            $comments = $service->fetchPostCommentsForUrl(
                $postUrl,
                600,
                15,
                $integration->linkedin_session_cookie,
                $integration->linkedin_user_agent ?? config('services.phantombuster.linkedin_user_agent')
            );

            // Log sample of raw PhantomBuster response
            if (count($comments) > 0) {
                $sampleComment = $comments[0];
                Log::info('📊 POST-COMMENTS-SCRAPING: PhantomBuster raw response sample', [
                    'total_from_phantom' => count($comments),
                    'requested_limit' => $validated['limit'] ?? 'not set',
                    'post_url' => $postUrl,
                    'original_post_url' => $validated['post_url'],
                    'sample_comment_keys' => array_keys($sampleComment),
                    'sample_comment_data' => $sampleComment,
                ]);
            } else {
                Log::info('Post comments received from PhantomBuster', [
                    'total_from_phantom' => 0,
                    'requested_limit' => $validated['limit'] ?? 'not set',
                    'post_url' => $postUrl,
                    'original_post_url' => $validated['post_url'],
                ]);
            }

            $commentsBeforeLimit = count($comments);
            if (isset($validated['limit'])) {
                $comments = array_slice($comments, 0, (int) $validated['limit']);
            Log::info('Post comments limited', [
                'before_limit' => $commentsBeforeLimit,
                'after_limit' => count($comments),
                'limit_value' => (int) $validated['limit'],
                'post_url' => $postUrl,
            ]);
            }

            // Transform comments to extract commenter profiles
            // PhantomBuster Post Comments Export typically returns comments with commenter info
            $normalized = [];
            $skippedCompanies = 0;
            $skippedNoProfileLink = 0;
            $transformationErrors = 0;
            
            foreach ($comments as $index => $comment) {
                try {
                    // Log what PhantomBuster returned for this comment
                    if ($index < 3) {
            Log::info('📊 POST-COMMENTS-SCRAPING: PhantomBuster raw comment data', [
                'index' => $index,
                'comment_keys' => array_keys($comment),
                'comment_data' => $comment,
                'post_url' => $postUrl,
            ]);
                    }
                    
                    // Extract commenter profile from comment
                    // PhantomBuster format may vary, check common fields
                    $commenter = $comment['commenter'] 
                        ?? $comment['author'] 
                        ?? $comment['profile'] 
                        ?? null;
                    
                    if (!$commenter) {
                        // If commenter is not nested, the comment itself might have profile fields
                        $commenter = $comment;
                    }
                    
                    // Skip company entries
                    if (isset($commenter['companyUrl']) && !isset($commenter['profileLink']) && !isset($commenter['profile_link'])) {
                        $skippedCompanies++;
                        continue;
                    }
                    
                    // Skip entries without profileLink
                    if (!isset($commenter['profileLink']) && !isset($commenter['profile_link']) && !isset($commenter['profileUrl'])) {
                        $skippedNoProfileLink++;
                        continue;
                    }
                    
                    // Transform commenter profile (similar to likers)
                    $transformed = $this->transformPhantomProfile($commenter);
                    
                    // Add comment-specific data if available
                    if (isset($comment['commentText']) || isset($comment['text']) || isset($comment['message'])) {
                        $transformed['comment_text'] = $comment['commentText'] ?? $comment['text'] ?? $comment['message'] ?? null;
                    }
                    if (isset($comment['timestamp']) || isset($comment['createdAt'])) {
                        $transformed['comment_timestamp'] = $comment['timestamp'] ?? $comment['createdAt'] ?? null;
                    }
                    
                    if ($index < 3) {
                        Log::info('🔄 POST-COMMENTS-SCRAPING: Transformed commenter profile', [
                            'index' => $index,
                            'transformed_publicIdentifier' => $transformed['publicIdentifier'] ?? 'not_found',
                            'fullName' => $transformed['fullName'] ?? 'not_found',
                        ]);
                    }
                    
                    if (empty($transformed['publicIdentifier']) && empty($transformed['connectionId'])) {
                        Log::warning('Transformed commenter profile missing identifiers', [
                            'index' => $index,
                            'name' => $transformed['fullName'] ?? 'unknown',
                        ]);
                    }
                    $normalized[] = $transformed;
                } catch (\Throwable $e) {
                    $transformationErrors++;
                    Log::error('Error transforming commenter profile', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'comment_data' => array_keys($comment),
                    ]);
                }
            }

            Log::info('Post comments filtered and transformed', [
                'total_from_phantom' => $commentsBeforeLimit,
                'after_limit' => count($comments),
                'skipped_companies' => $skippedCompanies,
                'skipped_no_profile_link' => $skippedNoProfileLink,
                'transformation_errors' => $transformationErrors,
                'valid_profiles' => count($normalized)
            ]);

            Log::info('Returning post commenters to frontend', [
                'profiles_count' => count($normalized),
                'total_from_phantom' => $commentsBeforeLimit,
                'after_limit' => count($comments),
                'skipped_companies' => $skippedCompanies,
                'skipped_no_profile_link' => $skippedNoProfileLink,
                'transformation_errors' => $transformationErrors,
                'requested_limit' => $validated['limit'] ?? 'not set'
            ]);

            return $this->successResponse([
                'profiles' => $normalized,
                'total' => count($normalized),
                'total_from_phantom' => $commentsBeforeLimit,
                'after_limit' => count($normalized),
                'skipped_companies' => $skippedCompanies,
                'skipped_no_profile_link' => $skippedNoProfileLink,
                'transformation_errors' => $transformationErrors,
                'post_url' => $postUrl,
                'original_post_url' => $validated['post_url'],
                'fetched_at' => now()->toISOString()
            ], 'Fetched post commenters successfully');
            
        } catch (\Throwable $th) {
            $errorMessage = $th->getMessage();
            
            // Check if this is a configuration error (phantom ID not set)
            if (str_contains($errorMessage, 'phantom ID not configured')) {
            Log::error('Chrome API: PhantomBuster configuration missing', [
                'user_id' => $user->id ?? 'unknown',
                'post_url' => $postUrl ?? ($validated['post_url'] ?? 'unknown'),
                'original_post_url' => $validated['post_url'] ?? 'unknown',
                'error' => $errorMessage
            ]);
                
                return $this->errorResponse(
                    'Data extraction service not configured. Please contact support.',
                    422
                );
            }
            
            Log::error('Chrome API: Failed to fetch post comments', [
                'user_id' => $user->id ?? 'unknown',
                'post_url' => $postUrl ?? ($validated['post_url'] ?? 'unknown'),
                'original_post_url' => $validated['post_url'] ?? 'unknown',
                'error' => $errorMessage,
                'trace' => $th->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to fetch post comments: ' . $errorMessage, 500);
        }
    }

    /**
     * Fetch LinkedIn search results via PhantomBuster (Search Export) to avoid deprecated Voyager search.
     */
    public function fetchSearchResultsFromPhantom(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'status' => 401
            ], 401);
        }

        $validated = $request->validate([
            'search_url' => ['nullable', 'url'],
            'keywords' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'connection_degrees' => ['nullable', 'array'],
            'connection_degrees.*' => ['string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'identities' => ['nullable', 'array'],
            'identities.*.identityId' => ['nullable', 'string'],
            'identities.*.sessionCookie' => ['required_with:identities', 'string'],
            'identities.*.userAgent' => ['nullable', 'string'],
            'use_identities_format' => ['nullable', 'boolean']
        ]);

        $keywords = isset($validated['keywords']) ? trim((string)$validated['keywords']) : '';
        $searchUrl = isset($validated['search_url']) ? trim((string)$validated['search_url']) : '';

        if ($searchUrl === '' && $keywords === '') {
            return $this->errorResponse('Provide either a search_url or keywords', 422);
        }

        $integration = Integration::where('user_id', $user->id)
            ->where('oauth_provider', 'linkedin')
            ->whereNotNull('linkedin_session_cookie')
            ->latest('linkedin_session_verified_at')
            ->first();

        if (!$integration) {
            return $this->errorResponse(
                'LinkedIn session cookie not found. Please update it from the Social Accounts page.',
                422
            );
        }

        try {
            $service = new PhantomBusterService();
            
            // Default to using identities format (can be disabled with use_identities_format=false)
            $useIdentitiesFormat = $validated['use_identities_format'] ?? true;
            $identities = null;
            
            if ($useIdentitiesFormat || !empty($validated['identities'])) {
                // Use identities array format
                if (!empty($validated['identities'])) {
                    // Use provided identities from request
                    $identities = $validated['identities'];
                    Log::info('Chrome API: Using identities from request', [
                        'identities_count' => count($identities),
                        'has_identityId' => !empty($identities[0]['identityId'] ?? null)
                    ]);
                } else {
                    // Build identities array from integration data
                    $identities = [[
                        'sessionCookie' => $integration->linkedin_session_cookie,
                        'userAgent' => $integration->linkedin_user_agent ?? config('services.phantombuster.linkedin_user_agent')
                    ]];
                    
                    // Add identityId if available in integration
                    if (isset($integration->linkedin_identity_id) && !empty($integration->linkedin_identity_id)) {
                        $identities[0]['identityId'] = $integration->linkedin_identity_id;
                    }
                    
                }
            }
            
            // Normalize category to ensure it's capitalized (PhantomBuster requires "People" not "people")
            $category = isset($validated['category']) ? ucfirst(strtolower(trim($validated['category']))) : 'People';
            
            // If searchUrl is provided and contains filters, use it (it will include keywords if present)
            // Otherwise, use keywords only (backend will build minimal URL)
            // Priority: searchUrl with filters > keywords only
            $finalSearchUrl = null;
            $finalKeywords = null;
            
            if (!empty($searchUrl) && filter_var($searchUrl, FILTER_VALIDATE_URL)) {
                // Complete URL provided (includes all filters) - use it
                $finalSearchUrl = $searchUrl;
                // Extract keywords from URL if needed, but PhantomBuster will use the URL
                $finalKeywords = $keywords ?: null;
            } elseif (!empty($keywords)) {
                // Only keywords provided - backend will build minimal URL
                $finalSearchUrl = null;
                $finalKeywords = $keywords;
            } else {
                return $this->errorResponse('Provide either a search_url or keywords', 422);
            }
            
            $profiles = $service->fetchSearchExportResults(
                $finalSearchUrl,
                600,
                15,
                $useIdentitiesFormat ? null : $integration->linkedin_session_cookie,
                $useIdentitiesFormat ? null : ($integration->linkedin_user_agent ?? config('services.phantombuster.linkedin_user_agent')),
                $finalKeywords,
                $validated['connection_degrees'] ?? [],
                $category,
                $validated['limit'] ?? null,
                $identities
            );

            // Handle empty profiles gracefully (for pagination - return empty structure instead of error)
            // This matches post likers behavior - always return success response with empty array if no results
            $normalized = [];
            if (!empty($profiles)) {
                $normalized = $this->transformSearchExportProfiles($profiles, $validated['limit'] ?? null);
            }

            $included = [];
            if (!empty($normalized)) {
                $included = array_map(function ($profile) {
                    $trackingId = Str::uuid()->toString();
                    $publicId = $profile['publicIdentifier'] ?? null;
                    $entityUrn = $publicId
                        ? "urn:li:fsd_entityResultViewModel:(urn:li:fsd_profile:{$publicId},SEARCH_SRP,DEFAULT)"
                        : null;
                    $navigationUrl = $profile['profileUrl'] ?? ($publicId ? "https://www.linkedin.com/in/{$publicId}/" : null);
                    $distanceValue = $profile['connectionDegree'] ?? $profile['networkDistance'] ?? null;
                    $distanceValue = is_numeric($distanceValue) ? (int) $distanceValue : $distanceValue;
                    $memberDistance = $distanceValue ? "DISTANCE_{$distanceValue}" : 'DISTANCE_3';

                    return [
                        'title' => ['text' => $profile['fullName'] ?? 'LinkedIn Member'],
                        'primarySubtitle' => ['text' => $profile['occupation'] ?? ''],
                        'secondarySubtitle' => ['text' => $profile['location'] ?? ''],
                        'entityUrn' => $entityUrn,
                        'trackingId' => $trackingId,
                        'trackingUrn' => $profile['memberUrn'] ?? ($publicId ? "urn:li:member:{$publicId}" : null),
                        'entityCustomTrackingInfo' => [
                            'memberDistance' => $memberDistance
                        ],
                        'navigationUrl' => $navigationUrl,
                        // Include PhantomBuster data for client-side filtering
                        'phantomData' => [
                            'company' => $profile['company'] ?? null,
                            'companyId' => $profile['companyId'] ?? null,
                            'company2' => $profile['company2'] ?? null,
                            'industry' => $profile['industry'] ?? null,
                            'school' => $profile['school'] ?? null,
                            'school2' => $profile['school2'] ?? null,
                            'location' => $profile['location'] ?? null,
                        ]
                    ];
                }, $normalized);
            }

            // Return in LinkedIn API format that frontend expects
            // Frontend expects: res['data'].data.elements and res['data'].included
            // successResponse wraps in 'data', so we need: data.data.elements
            $response = [
                'data' => [
                    'elements' => [
                        [
                            'items' => $included
                        ]
                    ],
                    'metadata' => [
                        'totalResultCount' => count($included)
                    ]
                ],
                'included' => $included
            ];

            Log::info('Chrome API: Returning search results response', [
                'items_count' => count($included),
                'totalResultCount' => count($included),
                'response_structure' => [
                    'has_data' => isset($response['data']),
                    'has_elements' => isset($response['data']['elements']),
                    'elements_count' => isset($response['data']['elements']) ? count($response['data']['elements']) : 0,
                    'has_items' => isset($response['data']['elements'][0]['items']),
                    'items_count_in_response' => isset($response['data']['elements'][0]['items']) ? count($response['data']['elements'][0]['items']) : 0,
                    'has_included' => isset($response['included']),
                    'included_count' => isset($response['included']) ? count($response['included']) : 0,
                    'final_path' => 'res.data.data.elements[0].items (after successResponse wraps)',
                    'final_included_path' => 'res.data.included (after successResponse wraps)'
                ]
            ]);

            return $this->successResponse($response, 'Fetched search results successfully');
        } catch (\Throwable $th) {
            Log::error('Chrome API: Failed to fetch search export results', [
                'user_id' => $user->id,
                'search_url' => $validated['search_url'],
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            // Check if this is a session cookie expiration error
            if (str_contains($th->getMessage(), 'LINKEDIN_SESSION_EXPIRED')) {
                $crmBaseUrl = config('app.url', 'http://127.0.0.1:8000');
                $socialAccountUrl = rtrim($crmBaseUrl, '/') . '/social-account';
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your LinkedIn session cookie has expired or is invalid.',
                    'error_code' => 'LINKEDIN_SESSION_EXPIRED',
                    'error_type' => 'session_expired',
                    'action_required' => 'update_session_cookie',
                    'help_message' => 'Please update your LinkedIn session cookie in the Social Accounts page.',
                    'crm_url' => $socialAccountUrl,
                    'instructions' => [
                        '1. Go to Social Accounts page in your CRM',
                        '2. Find your LinkedIn account',
                        '3. Update the session cookie (li_at) and user agent',
                        '4. Save and try again'
                    ]
                ], 422);
            }

            // Check if this is a network timeout error
            if (str_contains($th->getMessage(), 'NETWORK_TIMEOUT') || 
                str_contains($th->getMessage(), 'Resolving timed out') ||
                str_contains($th->getMessage(), 'cURL error 28')) {
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Network timeout: Data extraction service unavailable.',
                    'error_code' => 'NETWORK_TIMEOUT',
                    'error_type' => 'network_timeout',
                    'help_message' => 'This is usually a temporary network issue. Please try again in a moment.',
                    'suggestions' => [
                        '1. Check your internet connection',
                        '2. Wait a few seconds and try again',
                        '3. If the problem persists, contact support'
                    ]
                ], 500);
            }

            return $this->errorResponse('Failed to fetch search results: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Normalize PhantomBuster search export profiles into a consistent shape for the extension.
     */
    protected function transformSearchExportProfiles(array $profiles, ?int $limit = null): array
    {
        $normalized = [];
        $count = 0;

        foreach ($profiles as $profile) {
            if ($limit && $count >= $limit) {
                break;
            }

            if (!is_array($profile)) {
                continue;
            }

            // Skip error objects - PhantomBuster sometimes returns error objects instead of profiles
            if (isset($profile['error']) && empty($profile['fullName']) && empty($profile['profileUrl']) && empty($profile['firstName'])) {
                Log::debug('Chrome API: Skipping error object from PhantomBuster', [
                    'error' => $profile['error'] ?? null,
                    'query' => $profile['query'] ?? null
                ]);
                continue;
            }

            $fullName = $profile['fullName']
                ?? $profile['name']
                ?? trim(($profile['firstName'] ?? '') . ' ' . ($profile['lastName'] ?? ''));

            $occupation = $profile['occupation'] ?? $profile['headline'] ?? null;
            $location = $profile['location'] ?? $profile['locationName'] ?? null;

            $profileUrl = $profile['profileUrl']
                ?? $profile['profileLink']
                ?? $profile['profile_link']
                ?? $profile['linkedinUrl']
                ?? $profile['linkedin_url']
                ?? null;

            $publicId = $profile['publicIdentifier']
                ?? $profile['public_identifier']
                ?? $profile['memberId']
                ?? null;

            if (!$publicId && $profileUrl) {
                if (preg_match('/linkedin\.com\/in\/([^\/\?]+)/', $profileUrl, $matches)) {
                    $publicId = $matches[1];
                }
            }

            $connectionDegree = $profile['connectionDegree']
                ?? $profile['connection_degree']
                ?? $profile['degree']
                ?? $profile['networkDistance']
                ?? $profile['network_distance']
                ?? null;

            $normalized[] = [
                'fullName' => $fullName ?: 'LinkedIn Member',
                'occupation' => $occupation,
                'location' => $location,
                'publicIdentifier' => $publicId,
                'profileUrl' => $profileUrl,
                'connectionDegree' => $connectionDegree,
                'memberUrn' => $profile['memberUrn'] ?? null,
                // Preserve PhantomBuster data for client-side filtering
                'company' => $profile['company'] ?? null,
                'companyId' => $profile['companyId'] ?? null,
                'company2' => $profile['company2'] ?? null,
                'industry' => $profile['industry'] ?? null,
                'school' => $profile['school'] ?? null,
                'school2' => $profile['school2'] ?? null,
            ];

            $count++;
        }

        return $normalized;
    }

    public function storeSnLeads(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        $listId = $request->listId;
        $leads = $request->leads;

        SnLead::create([
            'first_name' => $leads['firstName'],
            'last_name' => $leads['lastName'],
            'headline' => $leads['headline'],
            'email' => $leads['email'],
            'lid' => $leads['profileId'],
            'picture' => $leads['picture'],
            'geolocation' => $leads['geoLocation'],
            'degree' => $leads['degree'],
            'object_urn' => $leads['objectUrn'],
            'sn_list_id' => $listId
        ]);

        if ($leads['firstName'] && $leads['lastName'] && $leads['website'] && $leads['profileId'] && !$leads['email']) {
            $leads['website'] = $this->trimDomain($leads['website']);

            // initiate email finder
            try {
                $getEmail = new EmailFinder([
                    'firstName' => $leads['firstName'],
                    'lastName' => $leads['lastName'],
                    'website' => $leads['website']
                ]);

                $getEmail = $getEmail->findEmail();

                SnLead::where('lid', $leads['profileId'])->update(['email' => $getEmail['email']]);
            } catch (\Throwable $th) {
                // throw $th;
                Log::info($th);
            }
        }

        return response()->json([
            'message' => 'Lead added'
        ], 201);
    }

    public function getSnLeadList(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        $snlist = SnLeadList::select('name', 'list_hash')->where('user_id', $user->id)->get();

        return response()->json([
            'data' => $snlist
        ]);
    }

    public function storeSnLeadList(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 401
            ]);
        }

        $listName = $request->listName;

        $snlist = SnLeadList::create([
            'name' => $listName,
            'list_hash' => rand(1111111, 9999999),
            'user_id' => $user->id
        ]);

        return response()->json([
            "message" => 'List created',
            "listId" => $snlist->list_hash
        ], 201);
    }

    public function storeUserActivity(Request $request)
    {
        $module_name = $request->query('module');
        $stats = $request->query('stat');
        $linkedin_id = $request->query('identifier');

        \Log::info('📊 [Backend] storeUserActivity endpoint called', [
            'module_name' => $module_name,
            'stats' => $stats,
            'linkedin_id' => $linkedin_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $user = User::where('linkedin_id', $linkedin_id)->first();

        if (!$user) {
            \Log::warning('⚠️ [Backend] User not found for LinkedIn ID in storeUserActivity', [
                'linkedin_id' => $linkedin_id
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        \Log::info('✅ [Backend] User found, creating user activity', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'module_name' => $module_name,
            'stats' => $stats
        ]);

        UserActivity::create([
            'module_name' => $module_name,
            'stats' => $stats,
            'user_id' => $user->id
        ]);

        \Log::info('✅ [Backend] User activity created successfully', [
            'user_id' => $user->id,
            'module_name' => $module_name,
            'stats' => $stats
        ]);

        return response()->json([
            'status' => 'success',
            'createdAt' => Carbon::now()->toDateString()
        ], 201);
    }

    public function getWebsiteWizard() {}

    public function trimDomain($url)
    {
        if (str_contains($url, 'https://'))
            $url = str_replace('https://', '', $url);
        if (str_contains($url, 'http://'))
            $url = str_replace('http://', '', $url);
        if (str_contains($url, 'www.'))
            $url = str_replace('www.', '', $url);
        if (str_contains($url, '/'))
            $url = str_replace('/', '', $url);
        return $url;
    }

    protected function transformPhantomProfile(array $profile): array
    {
        // Handle PhantomBuster's field names (profileLink, name, occupation)
        $profileLink = $profile['profileLink'] ?? $profile['profile_link'] ?? null;
        $name = $profile['name'] ?? null;
        
        // Extract publicIdentifier from profileLink
        $publicIdentifier = $profile['publicIdentifier']
            ?? $profile['public_identifier']
            ?? null;
        
        if (!$publicIdentifier && $profileLink) {
            // Extract from profileLink: https://www.linkedin.com/in/ACoAAAnfhacBqHAzP0jCagk7MHo-qefJ4d3zbUw
            // or https://www.linkedin.com/in/username/
            if (preg_match('#/in/([^/?]+)#', $profileLink, $matches)) {
                $publicIdentifier = $matches[1];
            }
        }
        
        // Split name into firstName and lastName
        $firstName = $profile['firstName'] ?? $profile['first_name'] ?? null;
        $lastName = $profile['lastName'] ?? $profile['last_name'] ?? null;
        
        if (!$firstName || !$lastName) {
            if ($name) {
                $nameParts = explode(' ', trim($name), 2);
                $firstName = $firstName ?? $nameParts[0] ?? null;
                $lastName = $lastName ?? ($nameParts[1] ?? null);
            }
        }
        
        $fullName = $profile['fullName']
            ?? $name
            ?? trim(trim($firstName ?? '') . ' ' . trim($lastName ?? ''));

        $profileUrl = $profile['profileUrl']
            ?? $profile['profile_url']
            ?? $profileLink
            ?? ($publicIdentifier ? 'https://www.linkedin.com/in/' . $publicIdentifier . '/' : null);

        $connectionId = $profile['connectionId']
            ?? $profile['connection_id']
            ?? $publicIdentifier
            ?? $profileUrl
            ?? uniqid('lnk_', true);

        // Extract connection degree from PhantomBuster response
        // PhantomBuster may provide this in different field names
        $connectionDegree = $profile['connectionDegree']
            ?? $profile['connection_degree']
            ?? $profile['degree']
            ?? $profile['networkDistance']
            ?? $profile['network_distance']
            ?? null;
        
        // Extract memberUrn - PhantomBuster typically doesn't provide this directly
        // Try multiple field names that PhantomBuster might use
        $memberUrn = $profile['memberUrn']
            ?? $profile['member_urn']
            ?? $profile['objectUrn']
            ?? $profile['object_urn']
            ?? $profile['trackingUrn']
            ?? $profile['tracking_urn']
            ?? null;
        
        // Log extraction for debugging (only for first few profiles to avoid spam)
        static $logCount = 0;
        if ($logCount < 3) {
            Log::info('🔄 POST-SCRAPING AUDIENCE: transformPhantomProfile extracting data', [
                'profile_name' => $name ?? 'unknown',
                'connectionDegree_found' => $connectionDegree !== null,
                'connectionDegree_value' => $connectionDegree,
                'connectionDegree_source' => isset($profile['connectionDegree']) ? 'connectionDegree' :
                                 (isset($profile['connection_degree']) ? 'connection_degree' :
                                 (isset($profile['degree']) ? 'degree' :
                                 (isset($profile['networkDistance']) ? 'networkDistance' :
                                 (isset($profile['network_distance']) ? 'network_distance' : 'not_found')))),
                'memberUrn_found' => $memberUrn !== null,
                'memberUrn_value' => $memberUrn,
                'memberUrn_source' => isset($profile['memberUrn']) ? 'memberUrn' :
                                     (isset($profile['member_urn']) ? 'member_urn' :
                                     (isset($profile['objectUrn']) ? 'objectUrn' :
                                     (isset($profile['object_urn']) ? 'object_urn' :
                                     (isset($profile['trackingUrn']) ? 'trackingUrn' :
                                     (isset($profile['tracking_urn']) ? 'tracking_urn' : 'not_found'))))),
                'connectionId' => $connectionId,
                'publicIdentifier' => $publicIdentifier,
                'all_profile_keys' => array_keys($profile),
                'note' => 'memberUrn is typically NOT provided by PhantomBuster - profile views will use connectionId as fallback'
            ]);
            $logCount++;
        }

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'fullName' => $fullName,
            'headline' => $profile['headline']
                ?? $profile['jobTitle']
                ?? $profile['occupation']
                ?? null,
            'location' => $profile['location']
                ?? $profile['locationName']
                ?? $profile['city']
                ?? null,
            'publicIdentifier' => $publicIdentifier,
            'profileUrl' => $profileUrl,
            'connectionId' => $connectionId,
            'memberUrn' => $memberUrn,
            'trackingId' => $profile['trackingId']
                ?? $profile['tracking_id']
                ?? null,
            'connectionDegree' => $connectionDegree,
            'connectionDegreeValue' => $connectionDegree
                ? (int) filter_var($connectionDegree, FILTER_SANITIZE_NUMBER_INT)
                : null,
            // Store the raw degree string for proper mapping (e.g., "1st", "2nd", "3rd")
            'degreeRaw' => $connectionDegree,
            'pictureUrl' => $profile['pictureUrl'] ?? $profile['picture_url'] ?? null,
            'companyName' => $profile['companyName']
                ?? $profile['company_name']
                ?? $profile['company']
                ?? null,
        ];
    }





    /**
     * Standardized success response
     */
    protected function successResponse($data = [], $message = 'Success', $status = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $status);
    }

    /**
     * Generate comment for LinkedIn post (Extension API)
     */
    public function generatePostComment(Request $request)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), 401);
        }

        $request->validate([
            'post_content' => 'required|string|max:5000',
            'tone' => 'nullable|string|in:professional,casual,engaging,thoughtful'
        ]);

        try {
            $postContent = $request->post_content;
            $tone = $request->tone ?? 'professional';

            $prompt = "Generate a thoughtful, engaging LinkedIn comment for the following post. The comment should be {$tone} in tone, add value to the conversation, and encourage engagement. Keep it concise (2-3 sentences maximum). Here is the LinkedIn post:\n\n{$postContent}";

            $chatGPT = new \App\Services\ChatGPT();
            $chatGPT->checkModeration($prompt);
            $result = $chatGPT->generateContent($prompt);

            return $this->successResponse([
                'comment' => $result['content'] ?? '',
                'word_count' => $result['words'] ?? 0
            ], 'Comment generated successfully');

        } catch (\Throwable $th) {
            Log::error('Failed to generate comment: ' . $th->getMessage(), [
                'linkedin_id' => $request->header('lk-id'),
                'post_length' => strlen($request->post_content ?? '')
            ]);

            // Check for rate limit
            $message = $th->getMessage();
            $isRateLimit = str_contains(strtolower($message), 'rate_limit') || str_contains($message, '429');
            
            if ($isRateLimit) {
                return $this->errorResponse('AI rate limit reached. Please wait a moment and try again.', 429);
            }

            return $this->errorResponse('Failed to generate comment: ' . $message, 500);
        }
    }


    /**
     * Normalize LinkedIn post URL to handle various formats
     * Handles:
     * - https://www.linkedin.com/feed/update/urn:li:activity:7389592155834642434/ (keep as-is)
     * - https://www.linkedin.com/posts/username_slug-7416785324715962368-XGiQ/ (keep as-is - PhantomBuster accepts this)
     * - Other LinkedIn post URL formats
     * 
     * Note: /posts/ URLs are kept as-is because some posts are only accessible via this format
     * and PhantomBuster accepts this format directly.
     */
    protected function normalizeLinkedInPostUrl($url)
    {
        if (empty($url)) {
            return $url;
        }

        $url = trim($url);

        // If it's already a feed URL format, return as-is (PhantomBuster accepts this)
        if (str_contains($url, '/feed/update/urn:li:activity:')) {
            return $url;
        }

        // If it's a /posts/ URL, keep it as-is - PhantomBuster accepts this format
        // and some posts are only accessible via this format
        if (str_contains($url, '/posts/')) {
            // Remove query parameters but keep the URL structure
            $urlParts = explode('?', $url);
            return $urlParts[0];
        }

        // If it's a full URL but not recognized format, try to extract ID
        if (str_starts_with($url, 'http')) {
            // Try to extract from various URL patterns
            if (preg_match('/activity[:\/](\d+)/', $url, $matches)) {
                if (isset($matches[1])) {
                    return "https://www.linkedin.com/feed/update/urn:li:activity:{$matches[1]}/";
                }
            }
            // If we can't parse it but it's a valid URL, return as-is (remove query params)
            $urlParts = explode('?', $url);
            return $urlParts[0];
        }

        // If it's just an ID (numeric string), convert to feed format
        $numericId = preg_replace('/^activity:/', '', $url);
        $numericId = preg_replace('/[^\d]/', '', $numericId);
        if (!empty($numericId) && ctype_digit($numericId)) {
            return "https://www.linkedin.com/feed/update/urn:li:activity:{$numericId}/";
        }

        // If we can't parse it, return the original (PhantomBuster might handle it)
        return $url;
    }

    /**
     * Standardized error response
     */
    protected function errorResponse($message = 'Error occurred', $status = 400, $errors = null)
    {
        $responseData = [
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if ($errors) {
            $responseData['errors'] = $errors;
        }

        return response()->json($responseData, $status);
    }

    /**
     * Sync LinkedIn ID from extension
     * When extension gets 401, it can call this to sync the LinkedIn public identifier
     * to the user's linkedin_id field in the database
     * This finds users with active LinkedIn Integrations and updates their linkedin_id
     */
    public function syncLinkedInId(Request $request)
    {
        try {
            $linkedinPublicId = $request->input('linkedin_public_id');
            $userEmail = $request->input('email'); // Optional: user's email for matching
            
            if (empty($linkedinPublicId)) {
                return $this->errorResponse('LinkedIn public identifier is required', 400);
            }

            Log::info('🔄 Syncing LinkedIn ID', [
                'linkedin_public_id' => $linkedinPublicId,
                'email' => $userEmail
            ]);

            // First, check if user already exists with this linkedin_id
            $existingUser = User::where('linkedin_id', $linkedinPublicId)->first();
            if ($existingUser) {
                Log::info('✅ User already has this LinkedIn ID', [
                    'user_id' => $existingUser->id,
                    'linkedin_id' => $linkedinPublicId
                ]);
                
                return $this->successResponse([
                    'user_id' => $existingUser->id,
                    'linkedin_id' => $linkedinPublicId,
                    'synced' => false,
                    'message' => 'LinkedIn ID already exists'
                ], 'LinkedIn ID already synced');
            }

            // Try to find user by email if provided
            $user = null;
            if ($userEmail) {
                $user = User::where('email', $userEmail)->first();
            }

            // If user found by email, check if they have LinkedIn Integration
            if ($user) {
                $integration = Integration::where('user_id', $user->id)
                    ->where('oauth_provider', 'linkedin')
                    ->where('connected_status', 1)
                    ->latest()
                    ->first();

                if ($integration) {
                    // Update user's linkedin_id
                    $oldLinkedInId = $user->linkedin_id;
                    $user->update(['linkedin_id' => $linkedinPublicId]);
                    
                    Log::info('✅ LinkedIn ID synced successfully by email', [
                        'user_id' => $user->id,
                        'email' => $userEmail,
                        'old_linkedin_id' => $oldLinkedInId,
                        'new_linkedin_id' => $linkedinPublicId
                    ]);

                    return $this->successResponse([
                        'user_id' => $user->id,
                        'linkedin_id' => $linkedinPublicId,
                        'synced' => true
                    ], 'LinkedIn ID synced successfully');
                }
            }

            // Alternative: Find all users with active LinkedIn Integrations
            // Try to match by verifying the LinkedIn profile using Integration's access token
            $integrations = Integration::where('oauth_provider', 'linkedin')
                ->where('connected_status', 1)
                ->whereNotNull('access_token')
                ->latest()
                ->get();

            if ($integrations->isEmpty()) {
                return $this->errorResponse('No active LinkedIn integrations found. Please connect your LinkedIn account in the dashboard first.', 404);
            }

            $linkedinService = new LinkedInService();
            
            // Try to verify and match by checking LinkedIn profile
            foreach ($integrations as $integration) {
                try {
                    // Get LinkedIn profile using Integration's access token
                    $profile = $linkedinService->getUserProfile($integration->access_token);
                    
                    // LinkedIn API v2 returns profile with id, but we need to get public identifier
                    // The public identifier might be in a different field or we need to construct it
                    // For now, if we have an active integration, we'll update the user
                    
                    $potentialUser = User::find($integration->user_id);
                    if ($potentialUser) {
                        $oldLinkedInId = $potentialUser->linkedin_id;
                        $potentialUser->update(['linkedin_id' => $linkedinPublicId]);
                        
                        Log::info('✅ LinkedIn ID synced using Integration access token', [
                            'user_id' => $potentialUser->id,
                            'integration_id' => $integration->id,
                            'old_linkedin_id' => $oldLinkedInId,
                            'new_linkedin_id' => $linkedinPublicId,
                            'profile_id' => $profile['id'] ?? 'unknown'
                        ]);

                        return $this->successResponse([
                            'user_id' => $potentialUser->id,
                            'linkedin_id' => $linkedinPublicId,
                            'synced' => true
                        ], 'LinkedIn ID synced successfully');
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not verify LinkedIn profile for integration', [
                        'integration_id' => $integration->id,
                        'error' => $e->getMessage()
                    ]);
                    continue; // Try next integration
                }
            }

            // Last resort: Update the most recent active integration's user
            // (User is already connected, so this should be safe)
            $latestIntegration = $integrations->first();
            $user = User::find($latestIntegration->user_id);
            
            if ($user) {
                $oldLinkedInId = $user->linkedin_id;
                $user->update(['linkedin_id' => $linkedinPublicId]);
                
                Log::info('✅ LinkedIn ID synced to most recent integration user (fallback)', [
                    'user_id' => $user->id,
                    'integration_id' => $latestIntegration->id,
                    'old_linkedin_id' => $oldLinkedInId,
                    'new_linkedin_id' => $linkedinPublicId
                ]);

                return $this->successResponse([
                    'user_id' => $user->id,
                    'linkedin_id' => $linkedinPublicId,
                    'synced' => true,
                    'message' => 'LinkedIn ID synced to most recent active integration'
                ], 'LinkedIn ID synced successfully');
            }

            return $this->errorResponse('Could not find user to sync LinkedIn ID. Please ensure your LinkedIn account is connected in the dashboard.', 404);
            
        } catch (Exception $e) {
            Log::error('Error syncing LinkedIn ID: ' . $e->getMessage(), [
                'linkedin_public_id' => $request->input('linkedin_public_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to sync LinkedIn ID: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Dispatch batch email fetch job when batch queue reaches threshold
     */
    private function dispatchEmailBatchJob($audienceId, $userId, $cacheKey)
    {
        try {
            $batchItems = Cache::get($cacheKey, []);
            
            if (empty($batchItems)) {
                return;
            }

            // Extract audience list IDs and public identifiers
            $audienceListIds = array_column($batchItems, 'audienceListItemId');
            
            // Clear the cache
            Cache::forget($cacheKey);
            
            // Dispatch batch job
            FetchAudienceEmailBatchJob::dispatchChunked($audienceListIds, $userId);
            
            Log::info('Dispatched batch email fetch job from extension', [
                'audience_id' => $audienceId,
                'user_id' => $userId,
                'batch_size' => count($audienceListIds)
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to dispatch batch email fetch job', [
                'audience_id' => $audienceId,
                'user_id' => $userId,
                'error' => $th->getMessage()
            ]);
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
        }
    }
}
