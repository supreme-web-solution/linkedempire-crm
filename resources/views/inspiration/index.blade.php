@extends('layout.auth')

@section('content')
<!-- Success/Error Messages -->
@if(session('success'))
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

@if(session('warning'))
<div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800">
    <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
</div>
@endif

@if(session('error'))
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
</div>
@endif

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">💡 Inspiration Library</h2>
        <p class="text-sm text-gray-500 mt-1">Discover viral LinkedIn posts and use them as inspiration</p>
        <!-- Fetch Status Badge -->
        <div id="fetch-status-container" class="mt-2 hidden">
            <span id="fetch-status-badge" class="inline-flex items-center gap-1 px-3 py-1 rounded text-xs font-medium">
                <span id="fetch-status-text"></span>
                <span id="fetch-progress-text" class="text-xs text-gray-600 ml-2"></span>
            </span>
        </div>
    </div>
    <a href="{{ route('content-creator.create') }}" 
       class="text-white px-4 py-2 rounded-lg font-medium transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
        <i class="fas fa-plus mr-2"></i>Create New Post
    </a>
</div>

<!-- Statistics Cards -->
<div class="grid gap-6 md:grid-cols-4 mb-8">
    <!-- Saved Posts Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(0, 119, 181) 0%, rgb(0, 88, 133) 100%);"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Saved Posts</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['total_posts'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, rgba(0, 119, 181, 0.1) 0%, rgba(0, 88, 133, 0.1) 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: rgb(0, 119, 181);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Total inspiration posts
            </div>
        </div>
    </div>

    <!-- Viral Posts Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-red-500"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Viral Posts</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['viral_posts'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-red-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">High Engagement</span>
            </div>
        </div>
    </div>

    <!-- Favorites Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-yellow-500"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Favorites</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $stats['favorites'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-yellow-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-yellow-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <span class="text-yellow-600 font-medium">Starred posts</span>
            </div>
        </div>
    </div>

    <!-- Avg Engagement Card -->
    <div class="relative flex flex-col bg-white border border-gray-200 shadow-md rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(135deg, rgb(249, 115, 22) 0%, rgb(234, 88, 12) 100%);"></div>
        <div class="p-5 flex-1 flex flex-col justify-between relative">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Avg Engagement</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ number_format($stats['avg_engagement'], 1, '.', '') }}%</h3>
                </div>
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-orange-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center text-xs text-gray-500">
                <span class="text-orange-600 font-medium">Across all posts</span>
            </div>
        </div>
    </div>
</div>

<!-- Content Preferences (Collapsible) -->
<div class="bg-white rounded-lg shadow mb-8">
    <button onclick="togglePreferences()" 
            class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors cursor-pointer">
        <div class="flex items-center">
            <i class="fas fa-sliders-h text-[#0077b5] mr-3"></i>
            <div class="text-left">
                <h3 class="text-lg font-semibold text-gray-900">Content Preferences</h3>
                <p class="text-sm text-gray-500">Customize what viral posts you want to see</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs text-gray-500 hidden sm:inline" id="preferences-text">Click to expand</span>
            <div class="w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center hover:border-[#0077b5] hover:bg-blue-50 transition-all">
                <svg id="preferences-icon" class="w-4 h-4 text-gray-600 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
        </div>
    </button>
    
    <div id="preferences-section" class="px-6 pb-6 hidden">
        <form method="POST" action="{{ route('inspiration.preferences.update') }}" class="space-y-6">
            @csrf
            
            <!-- Industries -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-industry mr-1 text-gray-400"></i>Industries (What field are you in?)
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @php
                    $industries = [
                        'Business & Entrepreneurship',
                        'Technology & Software',
                        'Marketing & Advertising',
                        'Sales & Business Development',
                        'Leadership & Management',
                        'Healthcare & Medical',
                        'Real Estate & Property',
                        'Finance & Investment',
                        'E-commerce & Retail',
                        'Education & Training',
                        'Consulting & Coaching',
                        'Legal & Law',
                        'Human Resources',
                        'Product Management',
                        'Design & Creative',
                        'Content Creation'
                    ];
                    $userIndustries = $preferences->industries ?? ['Business & Entrepreneurship', 'Marketing & Advertising'];
                    @endphp
                    
                    @foreach($industries as $industry)
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-blue-50 hover:border-[#0077b5] transition-colors
                        {{ in_array($industry, $userIndustries) ? 'bg-blue-50 border-[#0077b5]' : 'border-gray-200' }}">
                        <input type="checkbox" 
                               name="industries[]" 
                               value="{{ $industry }}"
                               {{ in_array($industry, $userIndustries) ? 'checked' : '' }}
                               class="w-4 h-4 border-gray-300 rounded focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                        <span class="ml-2 text-sm text-gray-700">{{ $industry }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            
            <!-- Topics -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-tags mr-1 text-gray-400"></i>Topics & Keywords (What interests you?)
                </label>
                <div id="topics-container" class="flex flex-wrap gap-2 mb-2">
                    @php
                    $userTopics = $preferences->topics ?? ['entrepreneurship', 'marketing', 'leadership'];
                    @endphp
                    
                    @foreach($userTopics as $topic)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-700">
                        {{ $topic }}
                        <button type="button" onclick="removeTopic(this, event)" class="ml-2 text-blue-600 hover:text-blue-800 hover:bg-blue-200 rounded-full p-0.5 transition-colors" title="Remove topic">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <input type="hidden" name="topics[]" value="{{ $topic }}">
                    </span>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <input type="text" 
                           id="new-topic" 
                           placeholder="e.g., AI, productivity, real estate tips"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                    <button type="button" 
                            onclick="addTopic()"
                            class="px-4 py-2 text-white rounded-md transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                        <i class="fas fa-plus mr-1"></i>Add
                    </button>
                </div>
            </div>
            
            <!-- Date Range (CRITICAL for high engagement) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar mr-1 text-gray-400"></i>Post Age (Older posts have more time to accumulate likes)
                </label>
                <select name="date_range" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                    <option value="past-24-hours" {{ ($preferences->date_range ?? 'past-month') == 'past-24-hours' ? 'selected' : '' }}>
                        Past 24 hours (Latest, but very fresh)
                    </option>
                    <option value="past-week" {{ ($preferences->date_range ?? 'past-month') == 'past-week' ? 'selected' : '' }}>
                        Past week (Recent posts)
                    </option>
                    <option value="past-month" {{ ($preferences->date_range ?? 'past-month') == 'past-month' ? 'selected' : '' }}>
                        Past month (Best for high engagement) ⭐
                    </option>
                    <option value="past-year" {{ ($preferences->date_range ?? 'past-month') == 'past-year' ? 'selected' : '' }}>
                        Past year (Evergreen content)
                    </option>
                    <option value="any-time" {{ ($preferences->date_range ?? 'past-month') == 'any-time' ? 'selected' : '' }}>
                        Any Time (All posts)
                    </option>
                </select>
                <p class="text-xs text-gray-600 mt-2">
                    💡 <strong>Recommended:</strong> <strong>Past month</strong> (2-4 weeks) – best balance of freshness and high engagement.
                </p>
            </div>
            
            <!-- Engagement Threshold -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-fire mr-1 text-gray-400"></i>Minimum Engagement (How many likes to be considered "viral"?)
                </label>
                <div class="flex items-center gap-4">
                    <input type="range" 
                           name="min_engagement" 
                           id="min-engagement" 
                           min="50" 
                           max="1000" 
                           step="50" 
                           value="{{ $preferences->min_engagement ?? 100 }}"
                           class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                    <span id="engagement-value" class="text-2xl font-bold text-[#0077b5] min-w-[100px] text-right">
                        {{ $preferences->min_engagement ?? 100 }}+ likes
                    </span>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>50 (Good posts)</span>
                    <span>1000+ (Very viral)</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <input 
                        type="checkbox" 
                        id="smart_fetch" 
                        name="smart_fetch" 
                        value="1"
                        {{ ($preferences->smart_fetch ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 border-gray-300 rounded focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                    <label for="smart_fetch" class="text-xs text-gray-700">
                        <span class="font-semibold">Smart fetch</span> (if enabled, the system may fetch posts with likes down to about half your slider value, e.g. 100 → 50, to avoid returning nothing)
                    </label>
                </div>
                <p class="text-xs text-gray-600 mt-2">
                    💡 <strong>Tip:</strong> 100-300 likes is ideal for quality viral content. Combine with "Past Month" date range for best results.
                </p>
            </div>
            
            <!-- Save Button -->
            <div class="flex justify-end pt-4 border-t">
                <button type="submit" 
                        id="savePreferencesBtn"
                        class="px-6 py-2 text-white rounded-md transition-all font-medium disabled:opacity-50 disabled:cursor-not-allowed" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="if(!this.disabled) { this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)'; }" onmouseout="if(!this.disabled) { this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none'; }">
                    <span id="savePreferencesText">
                        <i class="fas fa-save mr-2"></i>Save Preferences & Fetch Posts
                    </span>
                    <span id="savePreferencesLoading" class="hidden">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Fetching posts...
                    </span>
                </button>
            </div>
        </form>
        
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <form method="GET" action="{{ route('inspiration.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Search posts, authors, keywords..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
        </div>
        
        <!-- Category Filter -->
        <select name="category" 
                class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
            <option value="">All Categories</option>
            <option value="marketing" {{ request('category') == 'marketing' ? 'selected' : '' }}>Marketing</option>
            <option value="sales" {{ request('category') == 'sales' ? 'selected' : '' }}>Sales</option>
            <option value="tech" {{ request('category') == 'tech' ? 'selected' : '' }}>Tech</option>
            <option value="entrepreneurship" {{ request('category') == 'entrepreneurship' ? 'selected' : '' }}>Entrepreneurship</option>
            <option value="leadership" {{ request('category') == 'leadership' ? 'selected' : '' }}>Leadership</option>
            <option value="productivity" {{ request('category') == 'productivity' ? 'selected' : '' }}>Productivity</option>
        </select>
        
        <!-- Engagement Filter -->
        <select name="engagement" 
                class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
            <option value="">All Engagement</option>
            <option value="10" {{ request('engagement') == '10' ? 'selected' : '' }}>🔥 10%+ (Viral)</option>
            <option value="5" {{ request('engagement') == '5' ? 'selected' : '' }}>⚡ 5%+ (High)</option>
            <option value="3" {{ request('engagement') == '3' ? 'selected' : '' }}>✨ 3%+ (Good)</option>
        </select>
        
        <!-- Date Filter -->
        <select name="days" 
                class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
            <option value="">All Time</option>
            <option value="7" {{ request('days') == '7' ? 'selected' : '' }}>Last 7 days</option>
            <option value="30" {{ request('days') == '30' ? 'selected' : '' }}>Last 30 days</option>
            <option value="90" {{ request('days') == '90' ? 'selected' : '' }}>Last 90 days</option>
        </select>
        
        <div class="md:col-span-5 flex items-center justify-between">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" 
                       name="favorite" 
                       value="1"
                       {{ request('favorite') ? 'checked' : '' }}
                       class="w-4 h-4 bg-[#0077b5] border[#0077b5] rounded focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                <span class="ml-2 text-sm text-gray-700">
                    <i class="fas fa-star text-yellow-500"></i> Favorites only
                </span>
            </label>
            
            <div class="flex space-x-2">
                <button type="submit" 
                        class="px-4 py-2 text-white rounded-md transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                    <i class="fas fa-search mr-1"></i>Filter
                </button>
                <a href="{{ route('inspiration.index') }}" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-redo mr-1"></i>Clear
                </a>
            </div>
        </div>
    </form>
</div>

<!-- How to Use Guide (Show only if no posts yet) -->
@if($stats['total_posts'] == 0)
<div class="bg-gradient-to-r from-blue-50 to-blue-50 border border-[#0077b5] rounded-lg p-6 mb-8">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i class="fas fa-lightbulb text-[#0077b5] text-2xl"></i>
        </div>
        <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">How to Build Your Inspiration Library</h3>
            <div class="space-y-2 text-sm text-gray-700">
                <p><strong>Step 1:</strong> Browse LinkedIn feed and find high-engagement posts</p>
                <p><strong>Step 2:</strong> Click the <span class="px-2 py-1 text-white rounded text-xs" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">Save to LinkDominator</span> button (from Chrome extension)</p>
                <p><strong>Step 3:</strong> Posts appear here with engagement metrics</p>
                <p><strong>Step 4:</strong> Click "Use as Inspiration" to remix them in your voice</p>
            </div>
            <div class="mt-4 p-3 bg-white rounded-lg">
                <p class="text-xs text-gray-600"><strong>💡 Pro Tip:</strong> Save posts with 1000+ likes for best inspiration. The Chrome extension will analyze engagement automatically!</p>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Viral Posts Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($posts as $post)
    <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-all relative overflow-hidden" style="max-width: 100%;">
        <!-- Engagement Badge -->
        <div class="absolute top-2 right-2 z-10">
            <span class="px-2 py-0.5 rounded text-xs font-semibold shadow-sm text-white @if($post->engagement_rate >= 10) bg-red-500 @endif" @if($post->engagement_rate < 10) style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" @endif>
                @if($post->engagement_rate >= 10) 🔥 @elseif($post->engagement_rate >= 5) ⚡ @else ✨ @endif
                {{ number_format($post->engagement_rate, 1) }}%
            </span>
        </div>

        <!-- Favorite Star -->
        <div class="absolute top-2 left-2 z-10">
            <button onclick="toggleFavorite({{ $post->id }})" 
                    class="p-1.5 bg-white rounded-full shadow-sm hover:shadow-md transition-all">
                {{-- <i class="fas fa-star text-xs {{ $post->is_favorite ? 'text-yellow-500' : 'text-gray-300' }}"></i> --}}
                <svg xmlns="http://www.w3.org/2000/svg" fill="{{ $post->is_favorite ? '#ef4444' : '#9ca3af' }}"  viewBox="0 0 24 24" stroke-width="1.5" stroke="gray" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                  </svg>
                  
            </button>
        </div>

        <div class="p-4">
            <!-- Author Info - LinkedIn Style -->
            <div class="flex items-center mb-3">
                @if($post->author_image_url)
                <img src="{{ $post->author_image_url }}" 
                     alt="{{ $post->author_name }}" 
                     class="w-10 h-10 rounded-full object-cover">
                @else
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0077b5] to-[#005885] flex items-center justify-center text-white font-semibold text-sm">
                    {{ substr($post->author_name, 0, 1) }}
                </div>
                @endif
                <div class="ml-2 flex-1 min-w-0">
                    <div class="text-sm font-semibold text-gray-900 truncate">{{ $post->author_name }}</div>
                    @if($post->author_headline)
                    <div class="text-xs text-gray-500 truncate">{{ Str::limit($post->author_headline, 35) }}</div>
                    @endif
                    <div class="text-xs text-gray-400 mt-0.5">{{ $post->saved_at->diffForHumans() }}</div>
                </div>
            </div>

            <!-- Post Content Preview - LinkedIn Style -->
            <div class="mb-3">
                <p class="text-sm text-gray-900 leading-relaxed line-clamp-4 whitespace-pre-wrap">{{ $post->content }}</p>
            </div>

            <!-- Post Media - LinkedIn Style -->
            @if($post->post_type === 'image' && $post->images && count($post->images) > 0)
            <div class="mb-3 -mx-4">
                <img src="{{ $post->images[0] }}" alt="Post image" class="w-full max-h-64 object-cover">
            </div>
            @endif
            
            @if($post->post_type === 'carousel' && $post->images && count($post->images) > 0)
            <div class="mb-3 -mx-4">
                <div class="grid grid-cols-3 gap-0">
                    @foreach(array_slice($post->images, 0, 3) as $image)
                    <img src="{{ $image }}" alt="Carousel image" class="w-full h-24 object-cover">
                    @endforeach
                </div>
                @if(count($post->images) > 3)
                <div class="text-xs text-gray-500 mt-1 px-2">
                    +{{ count($post->images) - 3 }} more
                </div>
                @endif
            </div>
            @endif

            <!-- Engagement Metrics - LinkedIn Style (Small) -->
            <div class="flex items-center justify-between py-2 mb-2 text-xs text-gray-500 border-t border-gray-100">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center">
                        <i class="far fa-thumbs-up mr-1"></i>{{ $post->likes > 0 ? number_format($post->likes) : '' }}
                    </span>
                    <span>{{ $post->comments > 0 ? number_format($post->comments) . ' comments' : '' }}</span>
                    <span>{{ $post->shares > 0 ? number_format($post->shares) . ' shares' : '' }}</span>
                </div>
            </div>

            <!-- Action Buttons - Simplified & LinkedIn Style -->
            <div class="flex items-center justify-between pt-2 border-t border-gray-100 space-x-1">
                <button onclick="useAsInspiration({{ $post->id }})" 
                        class="flex-1 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 rounded transition-colors">
                    <i class="fas fa-lightbulb mr-1 text-[#0077b5]"></i>Use
                </button>
                
                <button onclick="remixPost({{ $post->id }})" 
                        class="flex-1 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 rounded transition-colors">
                    <i class="fas fa-magic mr-1 text-[#0077b5]"></i>Remix
                </button>
                
                @if($post->post_url)
                <a href="{{ $post->post_url }}" 
                   target="_blank"
                   class="flex-1 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 rounded transition-colors text-center">
                    <i class="fab fa-linkedin mr-1 text-[#0077b5]"></i>View
                </a>
                @endif
                
                <button onclick="deletePost({{ $post->id }})" 
                        class="px-2 py-1.5 text-xs font-medium text-gray-500 hover:bg-red-50 hover:text-red-600 rounded transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-16">
        <div class="text-gray-400 mb-4">
            <i class="fas fa-bookmark text-6xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No viral posts saved yet</h3>
        <p class="text-gray-500 mb-6">Start saving high-performing LinkedIn posts for inspiration</p>
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto text-left">
            <h4 class="font-semibold text-gray-900 mb-3">How to save viral posts:</h4>
            <ol class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 text-white rounded-full flex items-center justify-center text-xs mr-2" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">1</span>
                    <span>Install LinkDominator Chrome Extension</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 text-white rounded-full flex items-center justify-center text-xs mr-2" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">2</span>
                    <span>Browse LinkedIn feed</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 text-white rounded-full flex items-center justify-center text-xs mr-2" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">3</span>
                    <span>Click "Save to LinkDominator" on posts with high engagement</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 text-white rounded-full flex items-center justify-center text-xs mr-2" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">4</span>
                    <span>Posts appear here instantly!</span>
                </li>
            </ol>
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
@if($posts->hasPages())
<div class="mt-8">
    {{ $posts->appends(request()->query())->links() }}
</div>
@endif

<!-- Remix Modal -->
<div id="remixModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">AI Remix Post</h3>
                    <button onclick="closeRemixModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Rewrite in your voice:
                    </label>
                    <select id="remixTone" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                        <option value="professional">Professional</option>
                        <option value="casual">Casual</option>
                        <option value="motivational">Motivational</option>
                        <option value="educational">Educational</option>
                        <option value="storytelling">Storytelling</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Original Post:
                    </label>
                    <div id="originalContent" class="p-4 bg-gray-50 rounded-lg text-sm text-gray-700 max-h-40 overflow-y-auto">
                        <!-- Will be filled by JavaScript -->
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Remixed Version:
                    </label>
                    <textarea id="remixedContent" 
                              rows="8" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]"
                              readonly></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button onclick="closeRemixModal()" 
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button onclick="generateRemix()" 
                            id="generateRemixBtn"
                            class="px-4 py-2 text-white rounded-md transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                        <span id="generateRemixText">
                            <i class="fas fa-magic mr-2"></i>Generate Remix
                        </span>
                        <span id="generateRemixLoading" class="hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Generating...
                        </span>
                    </button>
                    <button onclick="useRemixedContent()" 
                            id="useRemixBtn"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors hidden">
                        <i class="fas fa-check mr-2"></i>Use This
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';
let currentRemixPostId = null;

// Toggle preferences section
function togglePreferences() {
    const section = document.getElementById('preferences-section');
    const icon = document.getElementById('preferences-icon');
    const text = document.getElementById('preferences-text');
    
    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        // Change icon from plus to minus (expand to collapse)
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>';
        if (text) text.textContent = 'Click to collapse';
    } else {
        section.classList.add('hidden');
        // Change icon from minus to plus (collapse to expand)
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>';
        if (text) text.textContent = 'Click to expand';
    }
}

// Add topic tag
function addTopic() {
    const input = document.getElementById('new-topic');
    const topic = input.value.trim();
    
    if (!topic) return;
    
    const container = document.getElementById('topics-container');
    const tag = document.createElement('span');
    tag.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-700';
    tag.innerHTML = `
        ${topic}
        <button type="button" onclick="removeTopic(this, event)" class="ml-2 text-blue-600 hover:text-blue-800 hover:bg-blue-200 rounded-full p-0.5 transition-colors" title="Remove topic">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <input type="hidden" name="topics[]" value="${topic}">
    `;
    
    container.appendChild(tag);
    input.value = '';
}

// Remove topic tag
function removeTopic(button, event) {
    // Prevent any form submission
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    // Remove the parent span element (which contains the topic text, button, and hidden input)
    button.parentElement.remove();
}

// Update engagement slider value display
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('min-engagement');
    const valueDisplay = document.getElementById('engagement-value');
    
    if (slider && valueDisplay) {
        slider.addEventListener('input', function() {
            valueDisplay.textContent = this.value + '+ likes';
        });
    }
    
    // Auto-open preferences if user hasn't set them
    const hasPreferences = {{ isset($preferences->id) ? 'true' : 'false' }};
    if (!hasPreferences) {
        togglePreferences();
    }
    
    // Handle preferences form submission with loading state
    const preferencesForm = document.querySelector('form[action="{{ route('inspiration.preferences.update') }}"]');
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', function(e) {
            const saveBtn = document.getElementById('savePreferencesBtn');
            const saveText = document.getElementById('savePreferencesText');
            const saveLoading = document.getElementById('savePreferencesLoading');
            
            if (saveBtn && saveText && saveLoading) {
                // Show loading state
                saveBtn.disabled = true;
                saveText.classList.add('hidden');
                saveLoading.classList.remove('hidden');
                
                // Reset after 60 seconds if something goes wrong (timeout protection)
                setTimeout(() => {
                    if (saveBtn.disabled) {
                        saveBtn.disabled = false;
                        saveText.classList.remove('hidden');
                        saveLoading.classList.add('hidden');
                    }
                }, 60000);
            }
        });
    }
    
    // Fetch status polling (similar to competitor followers)
    let fetchStatusInterval = null;
    let hasShownCompletion = false; // Flag to prevent multiple reloads
    let initialStatus = null; // Track initial status on page load
    
    function updateFetchStatus(isInitialCheck = false) {
        fetch('/inspiration/fetch/status', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const statusContainer = document.getElementById('fetch-status-container');
            const statusBadge = document.getElementById('fetch-status-badge');
            const statusText = document.getElementById('fetch-status-text');
            const progressText = document.getElementById('fetch-progress-text');
            
            // Store initial status on first check
            if (isInitialCheck) {
                initialStatus = data.status;
            }
            
            if (!data.status || data.status === null) {
                // No fetch in progress
                if (statusContainer) {
                    statusContainer.classList.add('hidden');
                }
                if (fetchStatusInterval) {
                    clearInterval(fetchStatusInterval);
                    fetchStatusInterval = null;
                }
                return;
            }
            
            // Show status container
            if (statusContainer) {
                statusContainer.classList.remove('hidden');
            }
            
            // Update badge based on status
            if (statusBadge && statusText && progressText) {
                statusBadge.className = 'inline-flex items-center gap-1 px-3 py-1 rounded text-xs font-medium';
                
                if (data.status === 'pending' || data.status === 'processing') {
                    statusBadge.classList.add('bg-blue-100', 'text-blue-800');
                    statusText.innerHTML = data.status === 'pending' 
                        ? '<svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Fetching posts...'
                        : '<svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';
                    progressText.textContent = data.progress || '';
                } else if (data.status === 'completed') {
                    statusBadge.classList.add('bg-green-100', 'text-green-800');
                    statusText.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Fetch completed';
                    progressText.textContent = data.new_posts ? `+${data.new_posts} new posts` : 'Completed';
                    
                    // Stop polling immediately
                    if (fetchStatusInterval) {
                        clearInterval(fetchStatusInterval);
                        fetchStatusInterval = null;
                    }
                    
                    // Only reload if this is a NEW completion (not already completed when page loaded)
                    // If status was already completed on page load, just show the message and clear it
                    if (!hasShownCompletion) {
                        hasShownCompletion = true;
                        
                        // If it was already completed when page loaded, just clear it and don't reload
                        if (initialStatus === 'completed') {
                            // Clear the status on server
                            fetch('/inspiration/fetch/status?clear=1', {
                                method: 'GET',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                }
                            }).catch(err => console.error('Error clearing status:', err));
                            
                            // Hide status after 5 seconds
                            setTimeout(() => {
                                if (statusContainer) {
                                    statusContainer.classList.add('hidden');
                                }
                            }, 5000);
                        } else {
                            // This is a NEW completion - reload to show new posts
                            // Clear the status on server first
                            fetch('/inspiration/fetch/status?clear=1', {
                                method: 'GET',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                }
                            }).catch(err => console.error('Error clearing status:', err));
                            
                            // Reload page after 2 seconds to show new posts (only once)
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        }
                    }
                } else if (data.status === 'failed') {
                    statusBadge.classList.add('bg-red-100', 'text-red-800');
                    statusText.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Fetch failed';
                    progressText.textContent = data.progress || 'Error occurred';
                    
                    // Stop polling
                    if (fetchStatusInterval) {
                        clearInterval(fetchStatusInterval);
                        fetchStatusInterval = null;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error checking fetch status:', error);
        });
    }
    
    // Start polling - check initial status first
    updateFetchStatus(true);
    
    // Poll every 3 seconds if status is pending or processing
    fetchStatusInterval = setInterval(() => {
        updateFetchStatus(false);
    }, 3000);
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (fetchStatusInterval) {
            clearInterval(fetchStatusInterval);
        }
    });
});

// Use viral post as inspiration (copy to Content Creator)
function useAsInspiration(postId) {
    fetch(`/inspiration/use/${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store in sessionStorage
                sessionStorage.setItem('inspiration_content', data.content);
                sessionStorage.setItem('inspiration_author', data.author);
                sessionStorage.setItem('inspiration_engagement', JSON.stringify(data.engagement));
                
                // Redirect to Content Creator
                window.location.href = '{{ route('content-creator.create') }}?from=inspiration';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading inspiration');
        });
}

// Open remix modal
function remixPost(postId) {
    currentRemixPostId = postId;
    
    // Find post data
    fetch(`/inspiration/use/${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('originalContent').textContent = data.content;
                document.getElementById('remixedContent').value = '';
                document.getElementById('useRemixBtn').classList.add('hidden');
                document.getElementById('remixModal').classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading post');
        });
}

// Generate AI remix
function generateRemix() {
    if (!currentRemixPostId) return;
    
    const tone = document.getElementById('remixTone').value;
    const btn = document.getElementById('generateRemixBtn');
    const btnText = document.getElementById('generateRemixText');
    const btnLoading = document.getElementById('generateRemixLoading');
    
    btn.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    
    fetch(`/inspiration/remix/${currentRemixPostId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ tone: tone })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
        
        if (data.success) {
            document.getElementById('remixedContent').value = data.content;
            document.getElementById('useRemixBtn').classList.remove('hidden');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
        console.error('Error:', error);
        alert('Error generating remix');
    });
}

// Use remixed content
function useRemixedContent() {
    const content = document.getElementById('remixedContent').value;
    
    // Store in sessionStorage
    sessionStorage.setItem('inspiration_content', content);
    sessionStorage.setItem('inspiration_remixed', 'true');
    
    // Redirect to Content Creator
    window.location.href = '{{ route('content-creator.create') }}?from=inspiration&remixed=true';
}

// Close remix modal
function closeRemixModal() {
    document.getElementById('remixModal').classList.add('hidden');
    currentRemixPostId = null;
}

// Toggle favorite
function toggleFavorite(postId) {
    fetch(`/inspiration/favorite/${postId}`, {
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
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Delete post
function deletePost(postId) {
    if (confirm('Remove this post from your inspiration library?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/inspiration/delete/${postId}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection

