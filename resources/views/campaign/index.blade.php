@extends('layout.auth')

@section('content')
<style>
.btn-linkedin {
    background: linear-gradient(135deg, #0077b5 0%, #005885 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-linkedin:hover {
    background: linear-gradient(135deg, #005885 0%, #004d6f 100%);
    box-shadow: 0 4px 12px rgba(0, 119, 181, 0.3);
    transform: translateY(-1px);
}
</style>
<script>
// Real-time campaign status updates
let campaignUpdateInterval;

function startCampaignStatusUpdates() {
    // Clear any existing interval
    if (campaignUpdateInterval) {
        clearInterval(campaignUpdateInterval);
    }
    
    // Check for updates every 10 seconds
    campaignUpdateInterval = setInterval(updateCampaignStatuses, 10000);
    
    // Initial update
    updateCampaignStatuses();
}

function updateCampaignStatuses() {
    // Show loading indicator
    const updateIndicator = document.getElementById('update-indicator');
    if (updateIndicator) {
        updateIndicator.classList.remove('hidden');
    }
    
    fetch('/api/campaigns/status-updates', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.campaigns) {
            data.campaigns.forEach(campaign => {
                updateCampaignRow(campaign);
            });
        }
    })
    .catch(error => {
        console.log('Campaign status update error:', error);
    })
    .finally(() => {
        // Hide loading indicator
        if (updateIndicator) {
            updateIndicator.classList.add('hidden');
        }
    });
}

function updateCampaignRow(campaign) {
    const campaignRow = document.querySelector(`[data-campaign-id="${campaign.id}"]`);
    if (campaignRow) {
        // Update status
        const statusElement = campaignRow.querySelector('.campaign-status');
        if (statusElement) {
            statusElement.textContent = campaign.status;
            statusElement.className = `campaign-status ${getStatusClass(campaign.status)}`;
        }
        
        // Update accept rate
        const acceptRateElement = campaignRow.querySelector('.campaign-accept-rate');
        if (acceptRateElement) {
            acceptRateElement.textContent = campaign.accept_rate + '%';
        }
        
        // Update total leads
        const totalLeadsElement = campaignRow.querySelector('.campaign-total-leads');
        if (totalLeadsElement) {
            totalLeadsElement.textContent = campaign.total_leads;
        }
        
        // Update total lead lists
        const totalLeadListsElement = campaignRow.querySelector('.campaign-total-lead-lists');
        if (totalLeadListsElement) {
            totalLeadListsElement.textContent = campaign.total_lead_list;
        }
        
        // Log the update
        console.log(`🔄 Updated campaign ${campaign.id}: ${campaign.status} (${campaign.accept_rate}%)`);
    }
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'active':
        case 'running':
            return 'text-green-600';
        case 'completed':
            return 'text-blue-600';
        case 'stop':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
}

// Start updates when page loads
document.addEventListener('DOMContentLoaded', function() {
    startCampaignStatusUpdates();
    
    // Stop updates when page is hidden (to save resources)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            if (campaignUpdateInterval) {
                clearInterval(campaignUpdateInterval);
                console.log('⏸️ Paused campaign status updates (page hidden)');
            }
        } else {
            startCampaignStatusUpdates();
            console.log('▶️ Resumed campaign status updates (page visible)');
        }
    });
});
</script>
<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3">
        <h2 class="text-2xl font-bold text-gray-800">
            Campaigns
        </h2>
        <div id="update-indicator" class="hidden flex items-center gap-2 text-sm text-gray-500">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-[#0077b5]"></div>
            <span>Updating...</span>
        </div>
    </div>
    <a href="{{route('campaign.create', ['step' =>'lead'])}}"
    class="btn-linkedin py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-white focus:outline-hidden disabled:opacity-50 disabled:pointer-events-none">
        New campaign
    </a>
</div>

<!-- Stat Cards -->
<div class="grid gap-6 md:grid-cols-4 mb-6">
    <!-- Total Campaigns Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(0, 119, 181) 0%, rgb(0, 88, 133) 100%);"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Campaigns</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['total_campaigns'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, rgba(0, 119, 181, 0.1) 0%, rgba(0, 88, 133, 0.1) 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: rgb(0, 119, 181);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 00-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                All campaigns
            </div>
        </div>
    </div>

    <!-- Running Campaigns Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-green-500"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Running</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['running_campaigns'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-green-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>
            </div>
        </div>
    </div>

    <!-- Completed Campaigns Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-blue-500"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Completed</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['completed_campaigns'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-blue-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <span class="text-blue-600 font-medium">Finished campaigns</span>
            </div>
        </div>
    </div>

    <!-- Total Leads Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(249, 115, 22) 0%, rgb(234, 88, 12) 100%);"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Leads</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_leads']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-orange-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <span class="text-orange-600 font-medium">Across all campaigns</span>
            </div>
        </div>
    </div>
</div>

<div class="mb-5">
    <div>
        @foreach($campaigns as $item)
        <div class="grid grid-cols-12 p-6 mb-4 bg-white border border-gray-200 rounded-xl shadow-md hover:shadow-lg transition-shadow" data-campaign-id="{{ $item->id }}">
            <div class="col-span-4">
                <div class="items-center justify-between">
                    <div class="flex gap-4 font-semibold text-sm text-gray-800">
                        <div class="mt-1" style="color: #0077b5;">
                            <a href="{{route('campaign.create', ['step' => 'lead', 'cid' => $item->id])}}" class="hover:underline">{{ $item->name }}</a>
                        </div>
                    </div>
                    @php($statusLabel = $item->status === 'active' ? 'running' : $item->status)
                    <small class="font-normal text-gray-400 uppercase campaign-status {{ in_array($item->status, ['running', 'active'], true) ? 'text-green-600' : ($item->status == 'completed' ? 'text-blue-600' : ($item->status == 'stop' ? 'text-red-600' : 'text-gray-600')) }}">{{ $statusLabel }}</small>
                </div>
            </div>
            <div class="col-span-3 flex">
                <div class="w-full">
                    <div class="flex gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color: #0077b5;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                        <span class="campaign-total-lead-lists">{{ $item->total_lead_list }}</span>
                    </div>
                    <small class="font-normal text-gray-400">Lists of Leads</small>
                </div>
                <div class="w-full">
                    <div class="flex gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color: #0077b5;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <span class="campaign-total-leads">{{ $item->total_leads }}</span>
                    </div>
                    <small class="font-normal text-gray-400">All Leads</small>
                </div>
            </div>
            <div class="col-span-3 flex">
                <div class="w-full">
                    <div class="campaign-accept-rate font-semibold text-gray-800">
                        {{ $item->accept_rate }}%
                    </div>
                    <small class="font-normal text-gray-400">Acceptance Rate</small>
                </div>
                <!-- <div class="w-full">
                    <div class="">
                        0%
                    </div>
                    <small class="font-normal text-gray-400">Reply rate</small>
                </div> -->
            </div>
            <div class="col-span-2 flex">
                <div class="w-full">
                    <div class="text-gray-800">
                        {{ date_format(date_create($item->created_at), "d M, Y") }}
                    </div>
                    <small class="font-normal text-gray-400">Created</small>
                </div>
                <div class="hs-dropdown relative inline-flex">
                    <button id="hs-dropdown-default-{{$item->id}}" type="button" class="hs-dropdown-toggle py-3 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-800 hover:bg-gray-100 focus:outline-hidden disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
                            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                        </svg>
                    </button>
                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full border border-gray-200" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-default-{{$item->id}}">
                        <div class="p-1 space-y-0.5">
                            <form action="{{route('campaign.delete', ['id' => $item->id])}}" method="POST">
                                @csrf
                                @method('DELETE') 
                                <button type="submit" class="flex w-full items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @if(count($campaigns)>0)
    {{ $campaigns->links() }}
    @endif
</div>
@endsection