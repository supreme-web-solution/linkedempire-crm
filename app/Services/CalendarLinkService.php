<?php

namespace App\Services;

use App\Models\CallStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalendarLinkService
{
    public function generateForCall(CallStatus $call, ?User $user = null): string
    {
        if (config('services.calendly.enabled')) {
            if (!$user) {
                $user = Auth::user();
                if (!$user && $call->user_id) {
                    $user = User::find($call->user_id);
                }
            }

            Log::info('Generating calendar link:', [
                'call_id' => $call->id,
                'call_user_id' => $call->user_id,
                'auth_user_id' => Auth::user() ? Auth::user()->id : 'not authenticated',
                'resolved_user_id' => $user ? $user->id : 'not found',
                'has_access_token' => $user && $user->calendly_access_token ? 'yes' : 'no',
                'calendly_access_token_length' => $user && $user->calendly_access_token ? strlen($user->calendly_access_token) : 0,
                'calendly_organization_uri' => $user ? $user->calendly_organization_uri : 'none'
            ]);

            if ($user && $user->calendly_access_token) {
                Log::info('Attempting to fetch user Calendly info from API');
                try {
                    if ($user->calendly_token_expires && now()->isAfter($user->calendly_token_expires)) {
                        Log::info('Calendly token expired, attempting refresh');
                        $this->refreshCalendlyToken($user);
                    }

                    $response = Http::withToken($user->calendly_access_token)
                        ->get('https://api.calendly.com/users/me');

                    Log::info('Calendly API response status:', ['status' => $response->status()]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $calendlyLink = $data['resource']['scheduling_url'] ?? null;

                        Log::info('Calendly API response:', [
                            'scheduling_url' => $calendlyLink,
                            'user_name' => $data['resource']['name'] ?? 'unknown',
                            'full_response' => $data
                        ]);

                        if ($calendlyLink) {
                            return $this->buildCalendlyLink($calendlyLink, $call);
                        }
                    } else {
                        Log::warning('Calendly API call failed:', [
                            'status' => $response->status(),
                            'body' => $response->body()
                        ]);
                    }
                } catch (\Throwable $th) {
                    Log::warning('Failed to get user Calendly scheduling URL:', [
                        'user_id' => $user->id,
                        'error' => $th->getMessage(),
                        'trace' => $th->getTraceAsString()
                    ]);
                }
            }

            $calendlyLink = config('services.calendly.link');
            Log::info('Using fallback config link:', ['link' => $calendlyLink]);

            if ($calendlyLink && strpos($calendlyLink, 'calendly.com') !== false) {
                return $this->buildCalendlyLink($calendlyLink, $call);
            }
        }

        $baseUrl = rtrim(config('app.url'), '/');
        return "{$baseUrl}/schedule-call/{$call->id}";
    }

    public function generateSimple(): string
    {
        if (config('services.calendly.enabled') && config('services.calendly.link')) {
            return config('services.calendly.link');
        }

        $baseUrl = config('app.url');
        return "{$baseUrl}/schedule-call";
    }

    private function buildCalendlyLink(string $baseLink, CallStatus $call): string
    {
        $recipientName = urlencode($call->recipient);
        $company = urlencode($call->company ?? '');
        $email = urlencode($call->recipient);
        $callId = urlencode($call->id);

        return "{$baseLink}?name={$recipientName}&email={$email}&a1={$company}&a2={$callId}&utm_campaign=call_booking&utm_source=linkdominator&utm_medium=api&utm_content={$callId}";
    }

    private function refreshCalendlyToken(User $user): bool
    {
        try {
            if (!$user->calendly_refresh_token) {
                Log::warning('No refresh token available for user', ['user_id' => $user->id]);
                return false;
            }

            $response = Http::asForm()->post('https://auth.calendly.com/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.calendly.client_id'),
                'client_secret' => config('services.calendly.client_secret'),
                'refresh_token' => $user->calendly_refresh_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $user->update([
                    'calendly_access_token' => $data['access_token'],
                    'calendly_refresh_token' => $data['refresh_token'] ?? $user->calendly_refresh_token,
                    'calendly_token_expires' => now()->addSeconds($data['expires_in']),
                ]);

                Log::info('Calendly token refreshed successfully', ['user_id' => $user->id]);
                return true;
            }

            Log::warning('Failed to refresh Calendly token', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Throwable $th) {
            Log::error('Error refreshing Calendly token', [
                'user_id' => $user->id,
                'error' => $th->getMessage()
            ]);
            return false;
        }
    }
}
