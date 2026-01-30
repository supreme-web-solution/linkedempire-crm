@extends('layout.auth')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div>
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Summary Cards -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Connections Card -->
                <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(0, 119, 181) 0%, rgb(0, 88, 133) 100%);"></div>
                    <div class="p-5 flex-1 flex flex-col justify-between relative">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Connections</p>
                                <h3 class="text-3xl font-bold text-gray-800 num-connects">-</h3>
                            </div>
                            <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, rgba(0, 119, 181, 0.1) 0%, rgba(0, 88, 133, 0.1) 100%);">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: rgb(0, 119, 181);">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center text-xs text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Active connections
                        </div>
                    </div>
                </div>
    
                <!-- Pending Invitations Card -->
                <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(59, 130, 246) 0%, rgb(37, 99, 235) 100%);"></div>
                    <div class="p-5 flex-1 flex flex-col justify-between relative">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Pending Invitations</p>
                                <h3 class="text-3xl font-bold text-gray-800 sent-invite">-</h3>
                            </div>
                            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Pending</span>
                        </div>
                    </div>
                </div>
    
                <!-- Profile Views Card -->
                <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(249, 115, 22) 0%, rgb(234, 88, 12) 100%);"></div>
                    <div class="p-5 flex-1 flex flex-col justify-between relative">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Profile Views</p>
                                <h3 class="text-3xl font-bold text-gray-800 profile-views">-</h3>
                            </div>
                            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-orange-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center text-xs text-gray-500">
                            <span id="profile-views-change-indicator" class="font-medium">Since last week</span>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Charts Section -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="min-w-0 p-6 bg-white rounded-xl shadow-md border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Activity Overview</h4>
                    <div id="pie-chart"></div>
                </div>
                <div class="min-w-0 p-6 bg-white rounded-xl shadow-md border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Trend Analysis</h4>
                    <div id="line-chart"></div>
                </div>
            </div>
    
        </div>
    
        <!-- Right Sidebar - Quick Actions -->
        <div class="space-y-6">
            <!-- Quick Actions Card -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgba(0, 119, 181, 0.1) 0%, rgba(0, 88, 133, 0.1) 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color: rgb(0, 119, 181);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">Get things done faster</p>
                
                <div class="grid grid-cols-2 gap-3">
                    <!-- Campaign Action -->
                    <a href="{{ route('campaign') }}" class="group p-4 rounded-lg border border-gray-200 hover:border-[#0077b5] hover:shadow-md transition-all cursor-pointer">
                        <div class="w-10 h-10 rounded-lg mb-2 flex items-center justify-center bg-green-50 group-hover:bg-green-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 00-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-800">Campaign</p>
                        <p class="text-xs text-gray-500 mt-1">Create new</p>
                    </a>
    
                    <!-- Leads Action -->
                    <a href="{{ route('leads.list') }}" class="group p-4 rounded-lg border border-gray-200 hover:border-[#0077b5] hover:shadow-md transition-all cursor-pointer">
                        <div class="w-10 h-10 rounded-lg mb-2 flex items-center justify-center bg-blue-50 group-hover:bg-blue-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-800">Leads</p>
                        <p class="text-xs text-gray-500 mt-1">View all</p>
                    </a>
    
                    <!-- AI Content Creator Action -->
                    <a href="{{ route('content-creator.index') }}" class="group p-4 rounded-lg border border-gray-200 hover:border-[#0077b5] hover:shadow-md transition-all cursor-pointer">
                        <div class="w-10 h-10 rounded-lg mb-2 flex items-center justify-center bg-purple-50 group-hover:bg-purple-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-purple-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-800">AI Content Creator</p>
                        <p class="text-xs text-gray-500 mt-1">Create new</p>
                    </a>
    
                    <!-- Competitor Followers Action -->
                    <a href="{{ route('competitor-followers.index') }}" class="group p-4 rounded-lg border border-gray-200 hover:border-[#0077b5] hover:shadow-md transition-all cursor-pointer">
                        <div class="w-10 h-10 rounded-lg mb-2 flex items-center justify-center bg-orange-50 group-hover:bg-orange-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-orange-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-800">Competitor Followers</p>
                        <p class="text-xs text-gray-500 mt-1">View followers</p>
                    </a>
                </div>
            </div>
    
            <!-- Additional Info Card -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 text-white">
                <div class="flex items-center gap-2 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <h3 class="text-lg font-semibold">Quick Actions</h3>
                </div>
                <p class="text-sm text-blue-100 mb-4">Create a new campaign to get started</p>
                <a href="{{ route('campaign') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition-colors text-sm">
                    Get Started
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
     <!-- Bar Chart -->
     {{-- <div class="min-w-0 p-6 bg-white rounded-xl shadow-md border border-gray-200 mt-6">
        <div id="bar-chart"></div>
    </div> --}}
</div>

<script src="{{ asset('js/dashboard-bar-chart.js') }}"></script>
<script src="{{ asset('js/dashboard-pie-chart.js') }}"></script>
<script src="{{ asset('js/dashboard-line-chart.js') }}"></script>

<script>
function ministats(){
    $.ajax({
        url: '/ministats',
        method: 'get',
        success: function(res){
            // Format profile views - handle negative values properly
            let profileViewsDisplay = res.profileViews || 0;
            let profileViewsValue = profileViewsDisplay;
            
            // Convert to number if it's a string
            if (typeof profileViewsDisplay === 'string') {
                // Remove any existing % sign or + sign
                profileViewsDisplay = profileViewsDisplay.replace('%', '').replace('+', '');
                profileViewsValue = parseFloat(profileViewsDisplay);
            }
            
            // Format the display value
            if (profileViewsValue < 0) {
                // Negative value - show with red color
                $('.profile-views').text(`${profileViewsValue}%`).css('color', '#dc3545');
                $('#profile-views-change-indicator').text('Since last week').css('color', '#dc3545');
            } else if (profileViewsValue > 0) {
                // Positive value - show with green color and + sign
                $('.profile-views').text(`+${profileViewsValue}%`).css('color', '#28a745');
                $('#profile-views-change-indicator').text('Since last week').css('color', '#28a745');
            } else {
                // Zero or no change
                $('.profile-views').text('0%').css('color', '#6c757d');
                $('#profile-views-change-indicator').text('Since last week').css('color', '#6c757d');
            }
            
            $('.sent-invite').text(`${res.sentInvites}`)
            $('.num-connects').text(`${res.numConnections}`)
        },
        error: function(error){
            console.log(error)
        }
    })
}
ministats()
</script>
@endsection