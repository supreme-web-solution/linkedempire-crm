@extends('layout.auth')

@section('content')
<div class="p-4 sm:p-6">
    @if (session('status'))
        <div class="mb-4 rounded border border-[#0077b5] bg-blue-50 text-[#0077b5] px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('competitor_followers.title') }}</h2>
            <a href="{{ route('social-account.index') }}" class="text-sm text-[#0077b5] hover:text-[#005885] font-medium">Manage LinkedIn session →</a>
        </div>
        @if (session('error'))
            <div class="rounded border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if (!$hasLinkedInSession)
            <div class="rounded border border-[#0077b5] bg-blue-50 text-[#005885] px-4 py-3 text-sm">
                <p class="font-medium">Add your LinkedIn session cookie first</p>
                <p class="mt-1">Visit the Social Accounts page, open your connected LinkedIn profile, and paste your <code class="font-mono bg-white/60 px-1 py-0.5 rounded">li_at</code> cookie + user agent. We'll auto-fill it for every competitor fetch.</p>
            </div>
        @endif
        <form method="POST" action="{{ route('competitor-followers.fetch') }}" class="grid grid-cols-1 gap-4">
            @csrf
            <div>
                <label class="block text-sm text-gray-700 mb-1">{{ __('competitor_followers.company_url_label') }}</label>
                <input name="company_url" type="url" required placeholder="https://www.linkedin.com/company/..." class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0077b5]" />
                @error('company_url')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-end">
                <button type="submit" class="inline-flex items-center justify-center rounded text-white px-4 py-2 w-full md:w-auto transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">{{ __('competitor_followers.fetch_button') }}</button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white rounded-lg shadow">
        <div class="p-4 sm:p-6 border-b">
            <h3 class="text-base font-semibold text-gray-900">{{ __('competitor_followers.history_title') }}</h3>
        </div>
        <div class="p-4 sm:p-6 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="text-gray-600">
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_name') }}</th>
                        <th class="py-2 pr-4">Followers</th>
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_created') }}</th>
                        <th class="py-2 pr-4">{{ __('competitor_followers.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audiences as $aud)
                        @php
                            $meta = json_decode($aud->source_meta, true) ?? [];
                            $companyUrl = $meta['company_url'] ?? null;
                            $lastError = $meta['last_error'] ?? null;
                            $lastErrorType = $meta['last_error_type'] ?? null;
                            $isSessionError = $lastErrorType === 'session_cookie';
                        @endphp
                        <tr class="border-t">
                            <td class="py-2 pr-4">
                                <div class="font-medium text-gray-900">{{ $aud->audience_name ?? 'Competitor Followers' }}</div>
                                @if($companyUrl)
                                    <a href="{{ $companyUrl }}" target="_blank" rel="noopener noreferrer" class="text-xs text-[#0077b5] hover:text-[#005885] hover:underline inline-flex items-center gap-1">
                                        {{ $companyUrl }}
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                @else
                                    <div class="text-xs text-gray-400">N/A</div>
                                @endif
                                @if($isSessionError && $lastError)
                                    <div class="mt-2 rounded border border-orange-300 bg-orange-50 text-orange-800 px-3 py-2 text-xs">
                                        <div class="font-medium flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            Session Cookie Error
                                        </div>
                                        <p class="mt-1">{{ $lastError }}</p>
                                        <a href="{{ route('social-account.index') }}" class="mt-1 inline-flex items-center gap-1 text-orange-700 hover:text-orange-900 font-medium underline">
                                            Update LinkedIn Session →
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="py-2 pr-4 text-gray-700">
                                <span class="font-semibold">{{ $aud->followers_count ?? 0 }}</span>
                                <span class="text-xs text-gray-500">people</span>
                            </td>
                            <td class="py-2 pr-4 text-gray-700">{{ $aud->created_at->format('Y-m-d H:i') }}</td>
                            <td class="py-2 pr-4">
                                <a href="{{ route('competitor-followers.show', $aud->id) }}" class="text-[#0077b5] hover:text-[#005885] hover:underline mr-3">{{ __('competitor_followers.view') }}</a>
                                <a href="{{ route('competitor-followers.export', $aud->id) }}" class="text-gray-700 hover:underline mr-3">{{ __('competitor_followers.export') }}</a>
                                <button type="button" 
                                        class="delete-audience-btn text-red-600 hover:text-red-800 hover:underline" 
                                        data-audience-id="{{ $aud->id }}"
                                        data-audience-name="{{ $aud->audience_name ?? 'Competitor Followers' }}">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">{{ __('competitor_followers.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">
                {{ $audiences->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-audience-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center h-screen">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Delete Competitor Audience</h3>
            <p class="text-gray-700 mb-4">
                Are you sure you want to delete <span id="delete-audience-name" class="font-semibold"></span>?
            </p>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" id="delete-audience-checkbox" class="rounded border-gray-300 text-[#0077b5] focus:ring-[#0077b5]">
                    <span class="ml-2 text-sm text-gray-700">Also delete the audience record</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">
                    If unchecked, only the follower data will be deleted. The audience record will remain.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 text-gray-700 bg-gray-100 rounded hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="button" id="confirm-delete-btn" class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700 transition-colors">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let currentAudienceId = null;
    
    // Open delete modal
    $(document).on('click', '.delete-audience-btn', function(e) {
        e.preventDefault();
        currentAudienceId = $(this).data('audience-id');
        const audienceName = $(this).data('audience-name');
        
        $('#delete-audience-name').text(audienceName);
        $('#delete-audience-checkbox').prop('checked', false);
        $('#delete-audience-modal').removeClass('hidden');
    });
    
    // Close modal on cancel
    $('#cancel-delete-btn').on('click', function() {
        $('#delete-audience-modal').addClass('hidden');
        currentAudienceId = null;
    });
    
    // Close modal on background click
    $('#delete-audience-modal').on('click', function(e) {
        if ($(e.target).attr('id') === 'delete-audience-modal') {
            $(this).addClass('hidden');
            currentAudienceId = null;
        }
    });
    
    // Confirm delete
    $('#confirm-delete-btn').on('click', function() {
        if (!currentAudienceId) {
            return;
        }
        
        const deleteAudience = $('#delete-audience-checkbox').is(':checked');
        const btn = $(this);
        const originalText = btn.text();
        
        btn.prop('disabled', true).text('Deleting...');
        
        $.ajax({
            url: `/competitor-followers/${currentAudienceId}/delete`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
                delete_audience: deleteAudience ? 1 : 0
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Remove the row from table
                    $(`.delete-audience-btn[data-audience-id="${currentAudienceId}"]`).closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Show success message
                    const notification = $('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">' + response.message + '</div>');
                    $('body').append(notification);
                    setTimeout(function() {
                        notification.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 3000);
                    
                    // Close modal
                    $('#delete-audience-modal').addClass('hidden');
                    currentAudienceId = null;
                } else {
                    alert(response.message || 'Failed to delete audience');
                    btn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to delete audience. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
                btn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>
@endsection


