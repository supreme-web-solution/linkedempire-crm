@extends('layout.auth')

@section('content')
<div class="p-4 sm:p-6">
    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $audience->audience_name }}</h2>
                @php
                    $meta = json_decode($audience->source_meta, true) ?? [];
                    $companyUrl = $meta['company_url'] ?? null;
                    $lastError = $meta['last_error'] ?? null;
                    $lastErrorType = $meta['last_error_type'] ?? null;
                    $isHarvestError = in_array($lastErrorType, ['harvest_error', 'session_cookie'], true);
                @endphp
                @if($companyUrl)
                    <a href="{{ $companyUrl }}" target="_blank" rel="noopener noreferrer" class="text-sm text-[#0077b5] hover:text-[#005885] hover:underline inline-flex items-center gap-1">
                        {{ $companyUrl }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                @else
                    <p class="text-sm text-gray-400">N/A</p>
                @endif
            </div>
            <div>
                <a href="{{ route('competitor-followers.export', $audience->id) }}" class="inline-flex items-center justify-center rounded text-white px-4 py-2 transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">{{ __('competitor_followers.export') }}</a>
            </div>
        </div>
        @if($isHarvestError && $lastError)
            <div class="mt-4 rounded-lg border border-orange-300 bg-orange-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-orange-900 mb-1">Harvest failed</h3>
                        <p class="text-sm text-orange-800 mb-3">{{ $lastError }}</p>
                        <a href="{{ route('social-account.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-orange-700 hover:text-orange-900 underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            Reconnect LinkedIn
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-4 px-4">
        <div id="daily-limit-info" class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-700">
                    <strong>Daily Email Scraping Limit:</strong> 
                    <span id="daily-limit-used">0</span> / <span id="daily-limit-total">{{ config('services.email_scraping.daily_limit_per_user', 100) }}</span> profiles
                </span>
                <span id="daily-limit-status" class="text-gray-600">Loading...</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div id="daily-limit-progress" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg shadow">
        <div class="p-4 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <form action="{{route('competitor-followers.show', $audience->id)}}" method="get" class="flex items-center">
                    <label for="email-filter" class="mr-2 text-sm font-medium text-gray-700">Filter by Email Status:</label>
                    <select name="email_filter" id="email-filter" onchange="this.form.submit()" class="block px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#0077b5] focus:border-[#0077b5]" style="min-width: 180px;">
                        <option value="all" {{($emailFilter ?? 'all') == 'all' ? 'selected' : ''}}>All Emails</option>
                        <option value="with_email" {{($emailFilter ?? 'all') == 'with_email' ? 'selected' : ''}}>With Email</option>
                        <option value="without_email" {{($emailFilter ?? 'all') == 'without_email' ? 'selected' : ''}}>Without Email</option>
                        <option value="not_found" {{($emailFilter ?? 'all') == 'not_found' ? 'selected' : ''}}>Not Found</option>
                        <option value="not_fetched" {{($emailFilter ?? 'all') == 'not_fetched' ? 'selected' : ''}}>Not Yet Fetched</option>
                        <option value="pending" {{($emailFilter ?? 'all') == 'pending' ? 'selected' : ''}}>Pending</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="p-4 sm:p-6 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="text-gray-600">
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_name') }}</th>
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_job') }}</th>
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_company') }}</th>
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_location') }}</th>
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_profile') }}</th>
                        <th class="py-2 pr-4">Email</th>
                        <th class="py-2 pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($list as $row)
                        <tr class="border-t" id="row-{{ $row->id }}">
                            <td class="py-2 pr-4 text-gray-900">{{ trim(($row->con_first_name ?? '') . ' ' . ($row->con_last_name ?? '')) }}</td>
                            <td class="py-2 pr-4 text-gray-700">{{ $row->con_job_title }}</td>
                            <td class="py-2 pr-4 text-gray-700">{{ $row->con_company_name }}</td>
                            <td class="py-2 pr-4 text-gray-700">{{ $row->con_location }}</td>
                            <td class="py-2 pr-4">
                                @php
                                    // Build profile URL from public identifier (same logic as FetchAudienceEmailJob)
                                    $profileUrl = null;
                                    if (!empty($row->con_public_identifier)) {
                                        $profileUrl = 'https://www.linkedin.com/in/' . $row->con_public_identifier . '/';
                                    } elseif (!empty($row->con_profile_url)) {
                                        // Fallback to stored profile URL if public identifier is missing
                                        $profileUrl = $row->con_profile_url;
                                    }
                                @endphp
                                @if ($profileUrl)
                                    <a href="{{ $profileUrl }}" target="_blank" class="inline-flex items-center justify-center rounded text-white px-3 py-1.5 text-xs transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path>
                                            <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"></path>
                                        </svg>
                                        View Profile
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs">N/A</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4 text-gray-700 email-cell-{{ $row->id }}">
                                @if ($row->con_email)
                                    {{ $row->con_email }}
                                @elseif(!empty($row->email_fetch_status) && $row->email_fetch_status === 'pending')
                                    <span class="text-blue-500 text-xs font-medium">Pending...</span>
                                @elseif(!empty($row->email_fetch_attempted_at) && empty($row->email_fetch_status))
                                    @php
                                        // Handle old records or race condition: if attempted_at is set but status is NULL
                                        // Check if it's recent (within 15 minutes) to show pending, else show no email found
                                        $attemptedAt = \Carbon\Carbon::parse($row->email_fetch_attempted_at);
                                        $isRecent = $attemptedAt->diffInMinutes(now()) < 15;
                                    @endphp
                                    @if($isRecent)
                                        <span class="text-blue-500 text-xs font-medium">Pending...</span>
                                    @else
                                        <span class="text-gray-500 text-xs">No email found</span>
                                    @endif
                                @elseif(!empty($row->email_fetch_status) && $row->email_fetch_status === 'completed' && empty($row->con_email))
                                    <span class="text-gray-500 text-xs">No email found</span>
                                @elseif(empty($row->email_fetch_status) && empty($row->email_fetch_attempted_at))
                                    @if(isset($pendingEmailFetchCount) && $pendingEmailFetchCount >= 5)
                                        <span 
                                            class="text-gray-500 text-xs cursor-help relative group"
                                            title="You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users."
                                            data-tooltip="You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.">
                                            Other emails processing...
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-normal w-64 z-50">
                                                You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.
                                                <span class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
                                            </span>
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">Email not fetched</span>
                                    @endif
                                @else
                                    <span class="text-gray-500 text-xs">No email found</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4">
                                @php
                                    // Rebuild profile URL for actions dropdown
                                    $actionProfileUrl = null;
                                    if (!empty($row->con_public_identifier)) {
                                        $actionProfileUrl = 'https://www.linkedin.com/in/' . $row->con_public_identifier . '/';
                                    } elseif (!empty($row->con_profile_url)) {
                                        $actionProfileUrl = $row->con_profile_url;
                                    }
                                @endphp
                                <div class="hs-dropdown relative inline-flex">
                                    <button id="hs-dropdown-default-{{$row->id}}" type="button" class="hs-dropdown-toggle py-2 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg bg-white hover:bg-gray-50 focus:outline-hidden disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                                        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
                                            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                                        </svg>
                                    </button>
                                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-50 bg-white shadow-md rounded-lg mt-2 border border-gray-200 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-default-{{$row->id}}">
                                        <div class="p-1 space-y-0.5">
                                            @if ($actionProfileUrl)
                                                <a href="{{ $actionProfileUrl }}" target="_blank" class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                                    <svg class="w-5 h-5 text-[#0077b5]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M12.51 8.796v1.697a3.738 3.738 0 0 1 3.288-1.684c3.455 0 4.202 2.16 4.202 4.97V19.5h-3.2v-5.072c0-1.21-.244-2.766-2.128-2.766-1.827 0-2.139 1.317-2.139 2.676V19.5h-3.19V8.796h3.168ZM7.2 6.106a1.61 1.61 0 0 1-.988 1.483 1.595 1.595 0 0 1-1.743-.348A1.607 1.607 0 0 1 5.6 4.5a1.601 1.601 0 0 1 1.6 1.606Z" clip-rule="evenodd"/>
                                                        <path d="M7.2 8.809H4V19.5h3.2V8.809Z"/>
                                                    </svg>
                                                    View profile
                                                </a>
                                            @endif
                                            @if(empty($row->con_email) && empty($row->email_fetch_status) && empty($row->email_fetch_attempted_at))
                                                @if(isset($pendingEmailFetchCount) && $pendingEmailFetchCount >= 5)
                                                    <div 
                                                        class="flex w-full items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-500 cursor-help relative group"
                                                        title="You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users."
                                                        data-tooltip="You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                                            <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z" />
                                                            <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z" />
                                                        </svg>
                                                        <span>Other emails processing...</span>
                                                        <span class="absolute bottom-full left-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-normal w-64 z-50">
                                                            You have {{ $pendingEmailFetchCount }} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.
                                                            <span class="absolute top-full left-8 -mt-1 border-4 border-transparent border-t-gray-900"></span>
                                                        </span>
                                                    </div>
                                                @else
                                                    <button 
                                                        type="button" 
                                                        class="get-email-btn-individual flex w-full items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100"
                                                        data-audience-list-id="{{$row->id}}"
                                                        data-audience-id="{{$audience->id}}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                                            <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z" />
                                                            <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z" />
                                                        </svg>
                                                        Get Email
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500">{{ __('competitor_followers.empty_rows') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $list->links() }}
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const audienceId = {{ $audience->id }};
    
    // Load daily limit on page load
    loadDailyLimit();

    // Load daily limit
    function loadDailyLimit() {
        $.ajax({
            url: '/competitor-followers/daily-limit',
            method: 'GET',
            timeout: 10000, // 10 second timeout
            success: function(response) {
                if (response && typeof response.used !== 'undefined') {
                    $('#daily-limit-used').text(response.used || 0);
                    const percentage = ((response.used || 0) / (response.daily_limit || 100)) * 100;
                    $('#daily-limit-progress').css('width', percentage + '%');
                    
                    const remaining = response.remaining || 0;
                    
                    if (remaining <= 0) {
                        $('#daily-limit-status').html('<span class="text-red-600 font-semibold">Limit Reached</span>');
                        $('#daily-limit-progress').removeClass('bg-blue-600').addClass('bg-red-600');
                    } else if (remaining < 20) {
                        $('#daily-limit-status').html(`<span class="text-yellow-600 font-semibold">${remaining} remaining</span>`);
                        $('#daily-limit-progress').removeClass('bg-blue-600').addClass('bg-yellow-600');
                    } else {
                        $('#daily-limit-status').html(`<span class="text-green-600 font-semibold">${remaining} remaining</span>`);
                        $('#daily-limit-progress').removeClass('bg-yellow-600 bg-red-600').addClass('bg-blue-600');
                    }
                } else {
                    console.error('Invalid daily limit response:', response);
                    $('#daily-limit-used').text('0');
                    $('#daily-limit-status').html('<span class="text-gray-500">Unable to load</span>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Daily limit AJAX error:', {xhr, status, error});
                $('#daily-limit-used').text('0');
                $('#daily-limit-status').html('<span class="text-gray-500">Error loading</span>');
            }
        });
    }

    // Track pending email fetch count locally
    let pendingEmailFetchCount = {{ $pendingEmailFetchCount ?? 0 }};
    let pendingCountPollInterval = null;

    // Function to update pending count from backend
    function updatePendingCount() {
        $.ajax({
            url: '/competitor-followers/pending-count',
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const newCount = response.pending_count || 0;
                    if (newCount !== pendingEmailFetchCount) {
                        pendingEmailFetchCount = newCount;
                        updateEmailButtonStates();
                    }
                }
            },
            error: function() {
                // Silently fail, will retry on next poll
            }
        });
    }

    // Function to update all email button states based on pending count
    function updateEmailButtonStates() {
        const pendingLimitReached = pendingEmailFetchCount >= 5;
        const tooltipText = `You have ${pendingEmailFetchCount} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue. This helps distribute the load across all users.`;
        
        // Update all "Get Email" buttons in dropdown menus
        $('.get-email-btn-individual').each(function() {
            const btn = $(this);
            const audienceListId = btn.data('audience-list-id');
            const emailCell = $(`.email-cell-${audienceListId}`);
            
            // Only update buttons that are visible and not disabled
            if (btn.is(':visible') && !btn.prop('disabled')) {
                if (pendingLimitReached) {
                    // Replace button with "Other emails processing..." message
                    btn.replaceWith(`
                        <div 
                            class="flex w-full items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-500 cursor-help relative group"
                            title="${tooltipText}"
                            data-tooltip="${tooltipText}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z" />
                                <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z" />
                            </svg>
                            <span>Other emails processing...</span>
                            <span class="absolute bottom-full left-0 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-normal w-64 z-50">
                                ${tooltipText}
                                <span class="absolute top-full left-8 -mt-1 border-4 border-transparent border-t-gray-900"></span>
                            </span>
                        </div>
                    `);
                }
            }
        });
        
        // Also update cells that show "Email not fetched" to show "Other emails processing..." if limit reached
        if (pendingLimitReached) {
            $('[class*="email-cell-"]').each(function() {
                const cell = $(this);
                if (cell.find('span.text-gray-400:contains("Email not fetched")').length > 0 && 
                    cell.find('.get-email-btn-individual').length === 0) {
                    cell.html(`
                        <span 
                            class="text-gray-500 text-xs cursor-help relative group"
                            title="${tooltipText}"
                            data-tooltip="${tooltipText}">
                            Other emails processing...
                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-normal w-64 z-50">
                                ${tooltipText}
                                <span class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
                            </span>
                        </span>
                    `);
                }
            });
        }
    }

    // Start polling for pending count updates (every 5 seconds)
    function startPendingCountPolling() {
        if (pendingCountPollInterval) {
            clearInterval(pendingCountPollInterval);
        }
        pendingCountPollInterval = setInterval(updatePendingCount, 5000);
    }

    // Stop polling
    function stopPendingCountPolling() {
        if (pendingCountPollInterval) {
            clearInterval(pendingCountPollInterval);
            pendingCountPollInterval = null;
        }
    }

    // Start polling when page loads and set initial state
    $(document).ready(function() {
        updateEmailButtonStates(); // Set initial state based on backend value
        startPendingCountPolling();
    });

    // Individual "Get Email" button click handler
    $(document).on('click', '.get-email-btn-individual', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        const audienceListId = btn.data('audience-list-id');
        const audienceId = btn.data('audience-id');
        
        // Check if limit is reached before making request
        if (pendingEmailFetchCount >= 5) {
            showNotification('error', `You have ${pendingEmailFetchCount} email scraping jobs in progress. Please come back in 45 minutes to allow other users to use the queue.`);
            return;
        }
        
        // Disable button and show loading state
        btn.prop('disabled', true);
        const originalHtml = btn.html();
        btn.html('<svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="ml-2">Processing...</span>');
        
        $.ajax({
            url: `/competitor-followers/${audienceId}/fetch-email`,
            method: 'POST',
            data: {
                audience_list_id: audienceListId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Increment local pending count
                    pendingEmailFetchCount++;
                    updateEmailButtonStates();
                    
                    showNotification('success', response.message || 'Email fetch job queued. Please wait while we fetch the email.');
                    // Update the email cell to show "Pending..." state
                    $(`.email-cell-${audienceListId}`).html('<span class="text-blue-500 text-xs font-medium">Pending...</span>');
                    // Hide the button since email fetch is queued
                    btn.closest('.hs-dropdown-menu').find('.get-email-btn-individual').remove();
                    // Refresh daily limit display after successful dispatch
                    setTimeout(function() {
                        loadDailyLimit();
                    }, 1000); // Wait 1 second for the job to process and update the count
                } else {
                    showNotification('error', response.message || 'Failed to fetch email');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to fetch email. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                // Handle concurrent limit error
                if (xhr.status === 429 && xhr.responseJSON && xhr.responseJSON.concurrent_limit_reached) {
                    pendingEmailFetchCount = xhr.responseJSON.pending_count || pendingEmailFetchCount;
                    updateEmailButtonStates();
                    showNotification('error', errorMessage);
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    return;
                }
                
                // If already pending, update UI to show pending state
                if (xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.already_pending) {
                    $(`.email-cell-${audienceListId}`).html('<span class="text-blue-500 text-xs font-medium">Pending...</span>');
                    btn.closest('.hs-dropdown-menu').find('.get-email-btn-individual').remove();
                    showNotification('info', 'Email fetch is already in progress. Please wait.');
                    // Update pending count
                    updatePendingCount();
                } else {
                    showNotification('error', errorMessage);
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                }
            }
        });
    });

    // Show notification
    function showNotification(type, message) {
        const bgColor = type === 'success' ? '#10b981' : '#ef4444';
        const notification = $(`
            <div style="position: fixed; top: 20px; right: 20px; z-index: 99999; background: white; border-left: 4px solid ${bgColor}; padding: 16px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); min-width: 300px; max-width: 500px;">
                <div class="flex items-center gap-3">
                    <div style="color: ${bgColor}; font-size: 20px;">${type === 'success' ? '✓' : '✕'}</div>
                    <div class="text-gray-900 text-sm">${message}</div>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>
@endsection


