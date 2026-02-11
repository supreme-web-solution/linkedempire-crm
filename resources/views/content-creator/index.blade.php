@extends('layout.auth')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Content Creator</h2>
    <a href="{{ route('content-creator.create') }}" 
       class="text-white px-4 py-2 rounded-lg font-medium transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
        <i class="fas fa-plus mr-2"></i>Create New Post
    </a>
</div>

<!-- Statistics Cards -->
<div class="grid gap-6 md:grid-cols-4 mb-8">
    <!-- Total Posts Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(0, 119, 181) 0%, rgb(0, 88, 133) 100%);"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Posts</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['total_posts'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, rgba(0, 119, 181, 0.1) 0%, rgba(0, 88, 133, 0.1) 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: rgb(0, 119, 181);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                All content posts
            </div>
        </div>
    </div>

    <!-- Draft Posts Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-yellow-500"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Drafts</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['draft_posts'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-yellow-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-yellow-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">In Progress</span>
            </div>
        </div>
    </div>

    <!-- Scheduled Posts Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-blue-500"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Scheduled</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['scheduled_posts'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-blue-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <span class="text-blue-600 font-medium">Ready to publish</span>
            </div>
        </div>
    </div>

    <!-- Published Posts Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-green-500"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Published</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['published_posts'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-green-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <span class="text-green-600 font-medium">Live posts</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div class="border-b border-gray-200 flex-1">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ route('content-creator.index', ['status' => 'all']) }}" 
                   class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'all' ? 'border-[#0077b5] text-[#0077b5]' : 'border-transparent text-gray-500 hover:text-[#0077b5] hover:border-gray-300' }}">
                    All Posts
                </a>
                <a href="{{ route('content-creator.index', ['status' => 'draft']) }}" 
                   class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'draft' ? 'border-[#0077b5] text-[#0077b5]' : 'border-transparent text-gray-500 hover:text-[#0077b5] hover:border-gray-300' }}">
                    Drafts
                </a>
                <a href="{{ route('content-creator.index', ['status' => 'scheduled']) }}" 
                   class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'scheduled' ? 'border-[#0077b5] text-[#0077b5]' : 'border-transparent text-gray-500 hover:text-[#0077b5] hover:border-gray-300' }}">
                    Scheduled
                </a>
                <a href="{{ route('content-creator.index', ['status' => 'published']) }}" 
                   class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'published' ? 'border-[#0077b5] text-[#0077b5]' : 'border-transparent text-gray-500 hover:text-[#0077b5] hover:border-gray-300' }}">
                    Published
                </a>
            </nav>
        </div>
        <!-- View Toggle Buttons -->
        <div class="flex items-center gap-2 ml-4 bg-gray-100 rounded-lg p-1">
            <button onclick="setViewMode('grid')" id="gridViewBtn" class="px-4 py-2 rounded-md transition-all font-medium text-sm view-toggle-btn active flex items-center gap-2" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%); color: white; box-shadow: 0 2px 4px rgba(0, 119, 181, 0.2);">
                <i class="fas fa-th"></i>
                <span>Cards</span>
            </button>
            <button onclick="setViewMode('table')" id="tableViewBtn" class="px-4 py-2 rounded-md transition-all font-medium text-sm view-toggle-btn flex items-center gap-2 text-gray-600 hover:text-[#0077b5] hover:bg-gray-50">
                <i class="fas fa-table"></i>
                <span>Table</span>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div id="bulkActionsBar" class="hidden mb-4 bg-blue-50 border border-[#0077b5] rounded-lg p-4 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-gray-700">
            <span id="selectedCount">0</span> post(s) selected
        </span>
        <button onclick="selectAllPosts()" class="text-sm text-[#0077b5] hover:underline">
            Select All
        </button>
        <button onclick="clearSelection()" class="text-sm text-gray-600 hover:underline">
            Clear
        </button>
    </div>
    <button onclick="bulkDeletePosts()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md transition-colors">
        <i class="fas fa-trash mr-2"></i>Delete Selected
    </button>
</div>

<!-- Posts Grid View -->
<div id="gridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($posts as $post)
    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow relative">
        <div class="absolute top-3 left-3 z-10">
            <input type="checkbox" class="post-checkbox w-5 h-5 rounded border-gray-300 text-[#0077b5] focus:ring-[#0077b5]" 
                   value="{{ $post->id }}" 
                   onchange="handleCheckboxChange(this)"
                   data-status="{{ $post->status }}">
        </div>
        <div class="p-6">
            <!-- Post Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($post->status === 'draft') bg-yellow-100 text-yellow-800
                        @elseif($post->status === 'scheduled') bg-blue-50 text-[#0077b5] border border-[#0077b5]
                        @elseif($post->status === 'published') bg-green-100 text-green-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ ucfirst($post->status) }}
                    </span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                        {{ ucfirst($post->post_type) }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">
                        {{ $post->word_count }} words
                    </span>
                </div>
            </div>

            <!-- Post Content Preview -->
            <div class="mb-4">
                <p class="text-gray-900 text-sm line-clamp-3">
                    {{ Str::limit($post->content, 150) }}
                </p>
            </div>

            <!-- Post Media -->
            @if($post->image_url)
            <div class="mb-4">
                @php
                    // Model accessor handles JSON decoding automatically
                    $imageUrls = $post->image_url;
                    $isMultipleImages = is_array($imageUrls) && count($imageUrls) > 1;
                @endphp
                
                @if($isMultipleImages)
                    <!-- Multiple images -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach(array_slice($imageUrls, 0, 6) as $index => $imageUrl)
                        <div class="relative">
                            <img src="{{ $imageUrl }}" alt="Image {{ $index + 1 }}" 
                                 class="w-full h-20 object-cover rounded-lg border border-gray-200">
                            <div class="absolute top-1 right-1 text-white text-xs px-1.5 py-0.5 rounded-full" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">
                                {{ $index + 1 }}
                            </div>
                        </div>
                        @endforeach
                        @if(count($imageUrls) > 6)
                        <div class="flex items-center justify-center bg-gray-100 rounded-lg h-20">
                            <span class="text-xs text-gray-500">
                                +{{ count($imageUrls) - 6 }} more
                            </span>
                        </div>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-images mr-1"></i>{{ count($imageUrls) }} image(s)
                    </div>
                @else
                    <!-- Single image (or legacy single URL) -->
                    @php
                        $singleUrl = is_array($imageUrls) ? $imageUrls[0] : $imageUrls;
                    @endphp
                    <img src="{{ $singleUrl }}" alt="Post image" class="w-full h-32 object-cover rounded-lg">
                @endif
            </div>
            @endif
            
            @if($post->carousel_images)
            <div class="mb-4">
                <div class="bg-gradient-to-r from-blue-50 to-blue-50 border border-[#0077b5] rounded-lg p-4">
                    <div class="flex items-center">
                        @php
                            $fileName = basename($post->carousel_images);
                            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            $icon = $extension === 'pdf' ? 'fa-file-pdf text-red-500' : 'fa-file-powerpoint text-[#0077b5]';
                        @endphp
                        <i class="fas {{ $icon }} text-3xl mr-3"></i>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">
                                Carousel Document
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ strtoupper($extension) }} • Swipeable Carousel
                            </div>
                        </div>
                        <i class="fas fa-swatchbook text-[#0077b5] text-xl"></i>
                    </div>
                </div>
            </div>
            @endif
            
            @if($post->video_url)
            <div class="mb-4">
                <video controls class="w-full h-32 object-cover rounded-lg">
                    <source src="{{ $post->video_url }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            @endif

            <!-- Post Meta -->
            <div class="text-sm text-gray-500 mb-4">
                <div class="flex items-center justify-between">
                    <span>Created: {{ $post->created_at->format('M j, Y') }}</span>
                    @if($post->scheduled_at)
                    <span>Scheduled: {{ $post->scheduled_at->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
                @if($post->published_at)
                <div class="mt-1">
                    <span>Published: {{ $post->published_at->format('M j, Y g:i A') }}</span>
                </div>
                @endif
            </div>

            <!-- Analytics (for published posts) -->
            @if($post->status === 'published' && $post->analytics_data)
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <div class="grid grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-lg font-bold text-gray-900">{{ $post->engagement['likes'] }}</div>
                        <div class="text-xs text-gray-500">Likes</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-gray-900">{{ $post->engagement['comments'] }}</div>
                        <div class="text-xs text-gray-500">Comments</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-gray-900">{{ $post->engagement['shares'] }}</div>
                        <div class="text-xs text-gray-500">Shares</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-gray-900">{{ $post->engagement['views'] }}</div>
                        <div class="text-xs text-gray-500">Views</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex items-center justify-between">
                <div class="flex space-x-2">
                    @if($post->status === 'draft')
                    <button onclick="publishPost({{ $post->id }})" 
                            class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-md transition-colors">
                        Publish Now
                    </button>
                    <button onclick="schedulePost({{ $post->id }})" 
                            class="px-3 py-1 text-white text-xs rounded-md transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                        Schedule
                    </button>
                    @endif
                    
                    @if($post->status === 'scheduled')
                    <button onclick="editSchedule({{ $post->id }})" 
                            class="px-3 py-1 text-white text-xs rounded-md transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                        Edit Schedule
                    </button>
                    @endif
                </div>
                
                <div class="flex space-x-2">
                    @if($post->status !== 'published')
                    <button onclick="deletePost({{ $post->id }})" 
                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md transition-colors">
                        Delete
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12">
        <div class="text-gray-400 mb-4">
            <i class="fas fa-file-alt text-6xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No posts found</h3>
        <p class="text-gray-500 mb-6">Get started by creating your first LinkedIn post.</p>
        <a href="{{ route('content-creator.create') }}" 
           class="text-white px-6 py-3 rounded-lg font-medium transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
            Create Your First Post
        </a>
    </div>
    @endforelse
</div>

<!-- Posts Table View -->
<div id="tableView" class="hidden overflow-x-auto bg-white rounded-lg shadow-md">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" id="selectAllTable" class="w-4 h-4 rounded border-gray-300 text-[#0077b5] focus:ring-[#0077b5]" onchange="toggleSelectAllTable(this)">
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Words</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($posts as $post)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="post-checkbox w-4 h-4 rounded border-gray-300 text-[#0077b5] focus:ring-[#0077b5]" 
                           value="{{ $post->id }}" 
                           onchange="handleCheckboxChange(this)"
                           data-status="{{ $post->status }}">
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 max-w-md">
                        {{ Str::limit($post->content, 100) }}
                    </div>
                    @if($post->image_url || $post->video_url || $post->carousel_images)
                    <div class="text-xs text-gray-500 mt-1">
                        @if($post->image_url)
                            {{-- <i class="fas fa-image mr-1"></i> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.106-5.106M2.25 15.75l5.106 5.106M2.25 15.75l5.106-5.106M6.75 16.5V8.25m0 8.25h8.25M5.25 6.75h8.25M4.5 3v13.5m7.5 0V3m0 0l-1.875 1.875M21 12h-2.25m-.375 6.75h1.875M18 20.25A2.25 2.25 0 0015.75 18m-3.75 0A2.25 2.25 0 019 15.75m-.375 0h-.008v.008h.008v-.008Z" />
                            </svg>
                            @php
                                $imageUrls = $post->image_url;
                                $imgCount = is_array($imageUrls) ? count($imageUrls) : 1;
                            @endphp
                            {{ $imgCount }} image(s)
                        @elseif($post->video_url)
                            {{-- <i class="fas fa-video mr-1"></i> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" />
                            </svg>
                            Video
                        @elseif($post->carousel_images)
                            {{-- <i class="fas fa-file-powerpoint mr-1"></i> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Carousel
                        @endif
                    </div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($post->status === 'draft') bg-yellow-100 text-yellow-800
                        @elseif($post->status === 'scheduled') bg-blue-50 text-[#0077b5] border border-[#0077b5]
                        @elseif($post->status === 'published') bg-green-100 text-green-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ ucfirst($post->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ ucfirst($post->post_type) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $post->created_at->format('M j, Y') }}
                    @if($post->scheduled_at)
                    <div class="text-xs text-gray-400">Scheduled: {{ $post->scheduled_at->format('M j, g:i A') }}</div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $post->word_count }}
                </td>
                <td class="px-6 py-4 text-sm font-medium">
                    <div class="flex flex-wrap gap-2">
                        @if($post->status === 'draft')
                        <button onclick="publishPost({{ $post->id }})" 
                                class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-md transition-colors whitespace-nowrap">
                            <i class="fas fa-paper-plane mr-1"></i>Publish Now
                        </button>
                        <button onclick="schedulePost({{ $post->id }})" 
                                class="px-3 py-1 text-white text-xs rounded-md transition-all whitespace-nowrap" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                            <i class="fas fa-clock mr-1"></i>Schedule
                        </button>
                        @endif
                        
                        @if($post->status === 'scheduled')
                        <button onclick="editSchedule({{ $post->id }})" 
                                class="px-3 py-1 text-white text-xs rounded-md transition-all whitespace-nowrap" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                            <i class="fas fa-edit mr-1"></i>Edit Schedule
                        </button>
                        @endif
                        
                        @if($post->status !== 'published')
                        <button onclick="deletePost({{ $post->id }})" 
                                class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md transition-colors whitespace-nowrap">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-file-alt text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No posts found</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first LinkedIn post.</p>
                    <a href="{{ route('content-creator.create') }}" 
                       class="text-white px-6 py-3 rounded-lg font-medium transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                        Create Your First Post
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if($posts->hasPages())
<div class="mt-8">
    {{ $posts->links() }}
</div>
@endif

<!-- Schedule Modal -->
<div id="scheduleModal" class="fixed inset-0 bg-gray-600/50 hidden h-screen  z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule Post</h3>
                <form id="scheduleForm">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Schedule Date & Time
                        </label>
                        <input type="datetime-local" id="scheduleDateTime" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]"
                               min="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeScheduleModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-white rounded-md transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                            Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// CSRF token from Blade
const csrfToken = '{{ csrf_token() }}';
let currentPostId = null;

// Shared selection state - stores selected post IDs
let selectedPostIds = new Set();

// View Mode Management
function initViewMode() {
    const savedView = localStorage.getItem('contentCreatorViewMode') || 'grid';
    setViewMode(savedView);
    syncCheckboxesFromState();
}

function setViewMode(mode) {
    // Save current selections before switching
    saveCurrentSelections();
    
    const gridView = document.getElementById('gridView');
    const tableView = document.getElementById('tableView');
    const gridBtn = document.getElementById('gridViewBtn');
    const tableBtn = document.getElementById('tableViewBtn');
    
    if (mode === 'table') {
        gridView.classList.add('hidden');
        tableView.classList.remove('hidden');
        // Update button styles
        gridBtn.classList.remove('active');
        gridBtn.style.background = 'transparent';
        gridBtn.style.color = '#4B5563';
        gridBtn.style.boxShadow = 'none';
        tableBtn.classList.add('active');
        tableBtn.style.background = 'linear-gradient(135deg, #0077b5 0%, #005885 100%)';
        tableBtn.style.color = 'white';
        tableBtn.style.boxShadow = '0 2px 4px rgba(0, 119, 181, 0.2)';
        localStorage.setItem('contentCreatorViewMode', 'table');
    } else {
        gridView.classList.remove('hidden');
        tableView.classList.add('hidden');
        // Update button styles
        tableBtn.classList.remove('active');
        tableBtn.style.background = 'transparent';
        tableBtn.style.color = '#4B5563';
        tableBtn.style.boxShadow = 'none';
        gridBtn.classList.add('active');
        gridBtn.style.background = 'linear-gradient(135deg, #0077b5 0%, #005885 100%)';
        gridBtn.style.color = 'white';
        gridBtn.style.boxShadow = '0 2px 4px rgba(0, 119, 181, 0.2)';
        localStorage.setItem('contentCreatorViewMode', 'grid');
    }
    
    // Sync checkboxes after view switch
    syncCheckboxesFromState();
    updateBulkActionsBar();
}

// Save current checkbox states to shared state
function saveCurrentSelections() {
    const checkboxes = document.querySelectorAll('.post-checkbox');
    selectedPostIds.clear();
    checkboxes.forEach(cb => {
        if (cb.checked) {
            selectedPostIds.add(parseInt(cb.value));
        }
    });
}

// Sync all checkboxes from shared state
function syncCheckboxesFromState() {
    const checkboxes = document.querySelectorAll('.post-checkbox');
    checkboxes.forEach(cb => {
        const postId = parseInt(cb.value);
        cb.checked = selectedPostIds.has(postId);
    });
}

// Bulk Selection Functions
function updateBulkActionsBar() {
    // Update state from current checkboxes
    saveCurrentSelections();
    
    const bulkBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedPostIds.size > 0) {
        bulkBar.classList.remove('hidden');
        selectedCount.textContent = selectedPostIds.size;
    } else {
        bulkBar.classList.add('hidden');
    }
}

function selectAllPosts() {
    const checkboxes = document.querySelectorAll('.post-checkbox');
    checkboxes.forEach(cb => {
        if (cb.dataset.status !== 'published') {
            const postId = parseInt(cb.value);
            selectedPostIds.add(postId);
            cb.checked = true;
        }
    });
    updateBulkActionsBar();
}

function clearSelection() {
    selectedPostIds.clear();
    const checkboxes = document.querySelectorAll('.post-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    updateBulkActionsBar();
}

function toggleSelectAllTable(checkbox) {
    const allCheckboxes = document.querySelectorAll('.post-checkbox');
    const checkboxes = document.querySelectorAll('#tableView .post-checkbox');
    
    if (checkbox.checked) {
        // Select all (excluding published)
        checkboxes.forEach(cb => {
            if (cb.dataset.status !== 'published') {
                const postId = parseInt(cb.value);
                selectedPostIds.add(postId);
            }
        });
    } else {
        // Deselect all
        checkboxes.forEach(cb => {
            const postId = parseInt(cb.value);
            selectedPostIds.delete(postId);
        });
    }
    
    // Sync all checkboxes from state
    syncCheckboxesFromState();
    updateBulkActionsBar();
}

// Handle checkbox change - sync both views
function handleCheckboxChange(checkbox) {
    const postId = parseInt(checkbox.value);
    
    if (checkbox.checked) {
        selectedPostIds.add(postId);
    } else {
        selectedPostIds.delete(postId);
    }
    
    // Sync the other view's checkbox
    const allCheckboxes = document.querySelectorAll('.post-checkbox');
    allCheckboxes.forEach(cb => {
        if (parseInt(cb.value) === postId && cb !== checkbox) {
            cb.checked = checkbox.checked;
        }
    });
    
    updateBulkActionsBar();
}

// Bulk Delete Function
function bulkDeletePosts() {
    if (selectedPostIds.size === 0) {
        alert('Please select at least one post to delete.');
        return;
    }
    
    const postIds = Array.from(selectedPostIds);
    
    if (!confirm(`Are you sure you want to delete ${postIds.length} selected post(s)? This action cannot be undone.`)) {
        return;
    }
    
    fetch('/content-creator/bulk-delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ post_ids: postIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting posts.');
    });
}

// Initialize view mode on page load
document.addEventListener('DOMContentLoaded', function() {
    initViewMode();
});

function publishPost(postId) {
    if (confirm('Are you sure you want to publish this post immediately?')) {
        fetch(`/content-creator/publish/${postId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while publishing the post.');
        });
    }
}

function schedulePost(postId) {
    currentPostId = postId;
    document.getElementById('scheduleModal').classList.remove('hidden');
}

function editSchedule(postId) {
    currentPostId = postId;
    document.getElementById('scheduleModal').classList.remove('hidden');
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.add('hidden');
    currentPostId = null;
}

function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        fetch(`/content-creator/delete/${postId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the post.');
        });
    }
}

// Schedule form submission
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const scheduleDateTime = document.getElementById('scheduleDateTime').value;
    
    if (!scheduleDateTime) {
        alert('Please select a date and time.');
        return;
    }
    
    if (!currentPostId) {
        alert('No post selected for scheduling.');
        return;
    }
    
    fetch(`/content-creator/schedule/${currentPostId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            scheduled_at: scheduleDateTime
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while scheduling the post.');
    });
});
</script>
@endsection
