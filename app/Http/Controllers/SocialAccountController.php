<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialAccountController extends Controller
{
    public function index()
    {
        $user = auth()->user()->id;

        $accounts = Integration::where('user_id', $user)->paginate(10);

        return view('auth.social-account', compact('accounts'));
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

        notify()->success('LinkedIn session saved. We recommend refreshing it every 30 days.');

        return redirect()->route('social-account.index');
    }
}
