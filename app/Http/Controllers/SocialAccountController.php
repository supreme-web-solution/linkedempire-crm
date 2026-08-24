<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\User;
use App\V2\Services\ChannelConnectionService;
use App\V2\Services\LinkedInConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SocialAccountController extends Controller
{
    public function __construct(
        private readonly LinkedInConnectionService $linkedin,
        private readonly ChannelConnectionService $channels,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $accountId = trim((string) $request->query('account_id', ''));

        if ($accountId !== '') {
            try {
                $this->channels->completeHostedConnection($user, $accountId, 'linkedin');

                return redirect()->route('social-account.index')
                    ->with('linkedin_connected', true);
            } catch (\Throwable $e) {
                Log::error('[SocialAccount] Hosted LinkedIn connect failed', [
                    'user_id' => $user->id,
                    'account_id' => $accountId,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('social-account.index')
                    ->with('linkedin_error', $this->friendlyError($e));
            }
        }

        $this->linkedin->consolidateProviderAccount($user->id, 'linkedin');

        try {
            $messagingAccounts = $this->linkedin->verifyUserAccounts($user);
        } catch (\Throwable) {
            $account = $this->linkedin->consolidateProviderAccount($user->id, 'linkedin');
            $messagingAccounts = $account
                ? [$this->linkedin->serializeAccount($account)]
                : [];
        }

        $messaging = $messagingAccounts[0] ?? null;
        $messagingConnected = is_array($messaging)
            && ($messaging['status'] ?? null) === 'active'
            && ($messaging['live_status'] ?? null) !== 'disconnected'
            && ! empty($messaging['unipile_account_id']);

        $accounts = Integration::where('user_id', $user->id)->paginate(10);

        return view('auth.social-account', [
            'accounts' => $accounts,
            'messaging' => $messaging,
            'messagingConnected' => $messagingConnected,
            'messagingConfigured' => $this->linkedin->isUnipileConfigured(),
            'linkedinConnectedFlash' => $request->boolean('connected') || session('linkedin_connected'),
            'linkedinErrorFlash' => $request->boolean('error')
                ? 'LinkedIn connection failed. Try Connect LinkedIn again.'
                : session('linkedin_error'),
        ]);
    }

    public function startLinkedInConnect(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $this->linkedin->isUnipileConfigured()) {
            notify()->error('LinkedIn messaging is not available on this server yet.');

            return redirect()->route('social-account.index');
        }

        try {
            $result = $this->linkedin->createHostedAuthLink(
                $user,
                $request,
                '/social-account?connected=1',
                '/social-account?error=1',
            );
            $url = $result['url'] ?? $result['link'] ?? $result['hosted_url'] ?? null;

            if (! $url) {
                notify()->error('Could not start LinkedIn login. Please try again.');

                return redirect()->route('social-account.index');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('[SocialAccount] Start LinkedIn hosted auth failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            notify()->error($this->friendlyError($e));

            return redirect()->route('social-account.index');
        }
    }

    public function connectLinkedInCookie(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'li_at' => ['required', 'string', 'max:4096'],
            'user_agent' => ['nullable', 'string', 'max:512'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $userAgent = trim((string) ($data['user_agent'] ?? $request->userAgent() ?? ''))
            ?: 'LinkedEmpire/1.0';

        try {
            $this->linkedin->connectViaCookie(
                $user,
                $data['li_at'],
                $userAgent,
                (int) ($user->current_organization_id ?? 0),
            );
        } catch (\Throwable $e) {
            Log::error('[SocialAccount] LinkedIn cookie connect failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            notify()->error($this->friendlyError($e));

            return redirect()->route('social-account.index');
        }

        notify()->success('LinkedIn connected successfully.');

        return redirect()->route('social-account.index');
    }

    public function verifyLinkedIn(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $results = $this->linkedin->verifyUserAccounts($user);
            $disconnected = collect($results)->first(
                fn ($row) => ($row['live_status'] ?? null) === 'disconnected'
                    || ($row['status'] ?? null) === 'disconnected'
            );

            if ($disconnected) {
                notify()->error('LinkedIn is disconnected. Use Reconnect LinkedIn on the Integrations page.');

                return redirect()->route('social-account.index');
            }

            notify()->success('LinkedIn connection verified.');
        } catch (\Throwable $e) {
            Log::error('[SocialAccount] LinkedIn verify failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            notify()->error($this->friendlyError($e));
        }

        return redirect()->route('social-account.index');
    }

    public function disconnectLinkedIn(int $id): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $this->linkedin->disconnect($user, $id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            notify()->error('Account not found.');

            return redirect()->route('social-account.index');
        }

        notify()->success('LinkedIn disconnected.');

        return redirect()->route('social-account.index');
    }

    public function disconnect(string $id)
    {
        $account = Integration::findOrFail($id);
        abort_if($account->user_id !== auth()->id(), 403);
        $userId = $account->user_id;
        $provider = $account->oauth_provider;

        $account->delete();

        if ($provider === 'linkedin') {
            User::where('id', $userId)->update(['linkedin_id' => null]);
        }

        notify()->success('Account disconnected');

        return redirect()->route('social-account.index');
    }

    public function storeCredentials(Request $request, Integration $integration)
    {
        abort_if($integration->user_id !== auth()->id(), 403);
        abort_if($integration->oauth_provider !== 'linkedin', 404);

        $data = $request->validate([
            'linkedin_session_cookie' => ['required', 'string'],
            'linkedin_user_agent' => ['required', 'string'],
        ]);

        $integration->update([
            'linkedin_session_cookie' => $data['linkedin_session_cookie'],
            'linkedin_user_agent' => $data['linkedin_user_agent'],
            'linkedin_session_verified_at' => now(),
        ]);

        Log::info('LinkedIn session cookie updated for integration', [
            'integration_id' => $integration->id,
            'user_id' => $integration->user_id,
        ]);

        if ($this->linkedin->isUnipileConfigured()) {
            try {
                $this->linkedin->connectViaCookie(
                    $request->user(),
                    $data['linkedin_session_cookie'],
                    $data['linkedin_user_agent'],
                    (int) ($request->user()->current_organization_id ?? 0),
                );
                notify()->success('LinkedIn session saved and connected.');

                return redirect()->route('social-account.index');
            } catch (\Throwable $e) {
                Log::warning('[SocialAccount] Session saved but connect failed', [
                    'user_id' => $integration->user_id,
                    'error' => $e->getMessage(),
                ]);
                notify()->success('LinkedIn session saved. Connect LinkedIn above if messaging still fails.');

                return redirect()->route('social-account.index');
            }
        }

        notify()->success('LinkedIn session saved.');

        return redirect()->route('social-account.index');
    }

    private function friendlyError(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'not configured') || str_contains($message, 'mock mode') || str_contains($message, 'api key')) {
            return 'LinkedIn is not available on this server yet.';
        }

        if (str_contains($message, 'cookie') || str_contains($message, 'credentials') || str_contains($message, 'disconnected') || str_contains($message, 'account id')) {
            return 'Could not connect LinkedIn. Try Connect LinkedIn again.';
        }

        return 'Could not connect LinkedIn. Please try again.';
    }
}
