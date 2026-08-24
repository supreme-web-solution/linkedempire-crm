@extends('layout.auth') 
@seo([
        'title' => 'Integrations - ' . config('app.name'),
        'description' => 'LinkedIn Lead Generation And Sales Automation Tool',
        'image' => asset('images/site-image.png'),
        'site_name' => config('app.name'),
        'favicon' => asset('images/logo-1.png'),
    ])
@section('content')
@php
    $messaging = $messaging ?? null;
    $messagingConnected = $messagingConnected ?? false;
    $messagingConfigured = $messagingConfigured ?? false;
    $linkedinConnectedFlash = $linkedinConnectedFlash ?? false;
    $linkedinErrorFlash = $linkedinErrorFlash ?? null;
    $otherAccounts = ($accounts ?? collect())->filter(fn ($account) => $account->oauth_provider !== 'linkedin');
@endphp
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">
            Integrations
        </h2>
        <p class="text-sm text-gray-500 mt-1">Connect LinkedIn for messaging, lead search, content publishing, and outreach.</p>
    </div>
    @if(!$messagingConnected)
        <form method="POST" action="{{ route('social-account.linkedin.connect') }}">
            @csrf
            <button type="submit"
                @disabled(!$messagingConfigured)
                class="block px-4 py-3 text-sm font-medium leading-2 text-white transition-all duration-150 border border-transparent rounded-lg disabled:opacity-50 disabled:pointer-events-none"
                style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                onmouseover="if(!this.disabled){this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';}"
                onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                Connect LinkedIn
            </button>
        </form>
    @endif
</div>

<div class="mt-6 space-y-4">
    @if($linkedinConnectedFlash)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            LinkedIn connected successfully. Refresh the browser extension to sync status.
        </div>
    @endif
    @if($linkedinErrorFlash)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $linkedinErrorFlash }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <div class="rounded-full bg-gray-100 p-2 text-[#0077b5]">
                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12.51 8.796v1.697a3.738 3.738 0 0 1 3.288-1.684c3.455 0 4.202 2.16 4.202 4.97V19.5h-3.2v-5.072c0-1.21-.244-2.766-2.128-2.766-1.827 0-2.139 1.317-2.139 2.676V19.5h-3.19V8.796h3.168ZM7.2 6.106a1.61 1.61 0 0 1-.988 1.483 1.595 1.595 0 0 1-1.743-.348A1.607 1.607 0 0 1 5.6 4.5a1.601 1.601 0 0 1 1.6 1.606Z" clip-rule="evenodd"/>
                        <path d="M7.2 8.809H4V19.5h3.2V8.809Z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-900">LinkedIn</h3>
                        @if($messagingConnected)
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Connected</span>
                        @elseif(is_array($messaging))
                            <span class="bg-amber-100 text-amber-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Disconnected</span>
                        @else
                            <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-0.5 rounded-full">Not connected</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Connect your LinkedIn account for messaging, campaigns, content publishing, and lead tools.
                    </p>
                    @if(is_array($messaging) && !empty($messaging['email']))
                        <p class="mt-1 text-xs text-gray-500">{{ $messaging['email'] }}</p>
                    @endif
                    @if(is_array($messaging) && !empty($messaging['connected_at']))
                        <p class="mt-0.5 text-xs text-gray-400">Connected {{ $messaging['connected_at'] }}</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('social-account.linkedin.connect') }}">
                    @csrf
                    <button type="submit"
                        @disabled(!$messagingConfigured)
                        class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white transition-all focus:outline-none focus:ring-2 focus:ring-[#0077b5] disabled:opacity-50 disabled:pointer-events-none"
                        style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);"
                        onmouseover="if(!this.disabled){this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';}"
                        onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        {{ $messagingConnected ? 'Reconnect LinkedIn' : 'Connect LinkedIn' }}
                    </button>
                </form>
                @if(is_array($messaging))
                    <form method="POST" action="{{ route('social-account.linkedin.verify') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                            Verify
                        </button>
                    </form>
                    <form method="POST" action="{{ route('social-account.linkedin.disconnect', $messaging['id']) }}" onsubmit="return confirm('Disconnect LinkedIn?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-white bg-red-700 hover:bg-red-800">
                            Disconnect
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(!$messagingConfigured)
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                LinkedIn is not available on this server yet. Contact your administrator if this persists.
            </div>
        @endif

        @if(is_array($messaging) && ($messaging['live_status'] ?? null) === 'disconnected' && !$linkedinConnectedFlash)
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                LinkedIn needs to be reconnected. Click <strong>Reconnect LinkedIn</strong> above.
            </div>
        @endif

        <details class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4">
            <summary class="cursor-pointer text-sm font-medium text-gray-800">Advanced: LinkedIn session cookie (optional)</summary>
            <p class="mt-2 text-xs text-gray-500">
                Prefer <strong>Connect LinkedIn</strong> above. Use this only if you need to paste a
                <code class="font-mono bg-white px-1 py-0.5 rounded">li_at</code> cookie manually, or sync via the browser extension.
            </p>
            <form method="POST" action="{{ route('social-account.linkedin.cookie') }}" class="mt-3 space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">LinkedIn session cookie (li_at)</label>
                    <textarea name="li_at" rows="2" required class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0077b5] focus:border-[#0077b5]" placeholder="Paste your li_at cookie"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Browser user agent <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea name="user_agent" rows="2" class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0077b5] focus:border-[#0077b5]" placeholder="Leave blank to use this browser"></textarea>
                </div>
                <button type="submit"
                    @disabled(!$messagingConfigured)
                    class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 disabled:pointer-events-none"
                    style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">
                    Connect with cookie
                </button>
            </form>
        </details>
    </div>

    @if($otherAccounts->count() > 0)
    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Other connected accounts</h3>
        <div class="space-y-3">
            @foreach($otherAccounts as $account)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-100 px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $account->email ?: $account->first_name }}</p>
                    <p class="text-xs text-gray-500 capitalize">{{ $account->oauth_provider }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($account->connected_status == 1)
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Connected</span>
                    @else
                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Disconnected</span>
                    @endif
                    <form action="{{ route('social-account.disconnect', ['id' => $account->id]) }}" method="post">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:underline">Disconnect</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        {{ $accounts->links() }}
    </div>
    @endif
</div>
@endsection
