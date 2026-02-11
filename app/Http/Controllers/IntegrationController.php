<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Services\LinkedInService;
use Illuminate\Http\Request;
use Log;

class IntegrationController extends Controller
{
    public function login()
    {
        $linkedin = new LinkedInService;

        $prompt = request()->query('prompt', 'consent');
        return redirect()->away($linkedin->login($prompt));
    }

    public function callback(Request $request)
    {
        Log::info('🔗 ========== LinkedIn OAuth Callback Started ==========', [
            'user_id' => auth()->id(),
            'has_code' => $request->has('code'),
            'has_state' => $request->has('state'),
            'has_error' => $request->has('error')
        ]);

        $oauth_code = $request->query('code');
        $oauth_state = $request->query('state');
        $oauth_error = $request->query('error');

        // Initialize linkedin service
        $linkedin = new LinkedInService;

        // Check if params values are flagged
        if (isset($oauth_error)){
            Log::error('❌ OAuth Error: Connection cancelled', ['error' => $oauth_error]);
            if ($oauth_error === 'login_required') {
                Log::info('🔁 Silent login failed, retrying with prompt=login');
                return redirect()->route('integration.login', ['prompt' => 'login']);
            }
            notify()->error('Connection to linkedin was cancelled.');
            return redirect()->route('social-account.index');
        }elseif (isset($oauth_state) && $oauth_state != $linkedin->state){
            Log::error('❌ OAuth State Mismatch', [
                'expected' => $linkedin->state,
                'received' => $oauth_state
            ]);
            notify()->error('Unauthorized');
            return redirect()->route('social-account.index');
        }

        Log::info('Step 1: Getting access token from LinkedIn');

        // Get access token
        try {
            $access_token = $linkedin->getAccessToken($oauth_code);
            Log::info('✅ Access token received', [
                'has_access_token' => !empty($access_token['access_token']),
                'has_refresh_token' => !empty($access_token['refresh_token']),
                'expires_in' => $access_token['expires_in'] ?? 'unknown'
            ]);
        } catch (\Throwable $th) {
            Log::error('❌ Failed to get access token', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            notify()->error($th->getMessage());
            return redirect()->route('social-account.index');
        }

        Log::info('Step 2: Getting user profile');

        // Get profile info
        try {
            $profile = $linkedin->getUserProfile($access_token['access_token'], true);
            Log::info('✅ Profile received', [
                'profile_id' => $profile['id'] ?? 'unknown',
                'vanity_name' => $profile['vanityName'] ?? 'unknown',
                'first_name' => $profile['localizedFirstName'] ?? 'unknown',
                'last_name' => $profile['localizedLastName'] ?? 'unknown'
            ]);
        } catch (\Throwable $th) {
            Log::error('❌ Failed to get profile', [
                'error' => $th->getMessage()
            ]);
            notify()->error($th->getMessage());
            return redirect()->route('social-account.index');
        }

        Log::info('Step 3: Getting profile image');

        // Get profile image
        try {
            $profile_img = $linkedin->getUserProfileImg($access_token['access_token']);
            Log::info('✅ Profile image received');
        } catch (\Throwable $th) {
            Log::error('❌ Failed to get profile image', [
                'error' => $th->getMessage()
            ]);
            notify()->error($th->getMessage());
            return redirect()->route('social-account.index');
        }

        Log::info('Step 4: Getting email from OpenID');

        // Get profile email
        try {
            $openIdProfile = $linkedin->getOpenIDProfile($access_token['access_token']);
            Log::info('✅ Email received', [
                'email' => $openIdProfile['email'] ?? 'unknown'
            ]);
        } catch (\Throwable $th) {
            Log::error('❌ Failed to get email', [
                'error' => $th->getMessage()
            ]);
            notify()->error($th->getMessage());
            return redirect()->route('social-account.index');
        }

        Log::info('Step 5: Creating integration record in database');

        try {
            $integration = Integration::create([
                "oauth_provider" => "linkedin",
                'access_token' => $access_token['access_token'],
                'refresh_token' => $access_token['refresh_token'] ?? null,
                'expires_in' => $access_token['expires_in'],
                # 'refresh_token_expires_in' => $access_token['refresh_token_expires_in'],
                'oauth_uid' => $profile['id'],
                'first_name' => $profile['localizedFirstName'],
                'last_name' => $profile['localizedLastName'],
                'email' => $openIdProfile['email'],
                'picture' => $profile_img['profilePicture']['displayImage~']['elements'][0]['identifiers'][0]['identifier'],
                'connected_status' => 1,
                'user_id' => auth()->user()->id
            ]);

            $linkedinPublicId = $profile['vanityName']
                ?? $openIdProfile['preferred_username']
                ?? null;

            if ($linkedinPublicId) {
                auth()->user()->update(['linkedin_id' => $linkedinPublicId]);
            }

            Log::info('✅✅✅ LinkedIn Integration Created Successfully ✅✅✅', [
                'integration_id' => $integration->id,
                'user_id' => auth()->id(),
                'oauth_uid' => $profile['id'],
                'linkedin_id_synced' => $linkedinPublicId ?? null,
                'has_access_token' => !empty($integration->access_token),
                'has_refresh_token' => !empty($integration->refresh_token)
            ]);

            Log::info('========== OAuth Callback Completed ==========');

            notify()->success('LinkedIn account connected successfully.');
            return redirect()->route('social-account.index');
        } catch (\Throwable $th) {
            Log::error('❌ Failed to create integration record', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            notify()->error('Failed to save LinkedIn connection: ' . $th->getMessage());
            return redirect()->route('social-account.index');
        }
    }
}
