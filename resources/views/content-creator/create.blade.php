@extends('layout.auth')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Create New Post</h2>
    <a href="{{ route('content-creator.index') }}" 
       class="text-gray-600 hover:text-[#0077b5]">
        <i class="fas fa-arrow-left mr-2"></i>Back to Posts
    </a>
</div>

<!-- Error Messages -->
@if ($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-red-400"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
                Please fix the following errors:
            </h3>
            <div class="mt-2 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Success Messages -->
@if (session('success'))
<div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-check-circle text-green-400"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm text-green-800">
                {{ session('success') }}
            </p>
        </div>
    </div>
</div>
@endif

@php $hasLinkedIn = $hasLinkedIn ?? true; @endphp
@if(!$hasLinkedIn)
<div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    Connect LinkedIn on the
    <a href="{{ route('social-account.index') }}" class="font-semibold text-[#0077b5] hover:underline">Integrations</a>
    page before publishing posts.
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Sidebar - Templates & AI Tools -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">AI Assistant</h3>
            
            <!-- AI Generation Form -->
            <form id="aiGenerateForm" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Topic or Idea
                    </label>
                    <textarea id="aiTopic" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]"
                              rows="3" 
                              placeholder="What do you want to write about?"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Style
                        </label>
                        <select id="aiStyle" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                            <option value="professional">Professional</option>
                            <option value="casual">Casual</option>
                            <option value="motivational">Motivational</option>
                            <option value="educational">Educational</option>
                            <option value="storytelling">Storytelling</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Length
                        </label>
                        <select id="aiLength" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                            <option value="short">Short</option>
                            <option value="medium" selected>Medium</option>
                            <option value="long">Long</option>
                        </select>
                    </div>
                </div>
                
                <!-- 🔥 NEW: Multiple Drafts Option -->
                <div class="mb-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="multipleDrafts" 
                               class="w-4 h-4 bg-gray-100 border-gray-300 rounded focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                        <span class="ml-2 text-sm text-gray-700">
                            Generate 2 variations
                        </span>
                    </label>
                </div>
                
                <button type="submit" 
                        id="generateBtn"
                        class="w-full text-white py-2 px-4 rounded-md transition-all disabled:opacity-50 disabled:cursor-not-allowed" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                    <span id="generateText">
                        <i class="fas fa-magic mr-2"></i>Generate with AI
                    </span>
                    <span id="generateLoading" class="hidden">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Generating...
                    </span>
                </button>
            </form>
            
            <!-- 🔥 NEW: Multiple Drafts Selection -->
            <div id="draftsContainer" class="mt-4 hidden">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-copy mr-1 text-[#0077b5]"></i>Choose Your Favorite Draft
                </h4>
                <div id="draftsList" class="space-y-3 max-h-96 overflow-y-auto">
                    <!-- Drafts will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Templates Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Templates</h3>
                <span id="templateCount" class="text-xs text-gray-500">
                    {{ count($templates) }} templates
                </span>
            </div>
            
            <!-- 🔥 ENHANCED: Search & Filters -->
            <div class="space-y-3 mb-4">
                <!-- Search -->
                <div class="relative">
                    <input type="text" 
                           id="templateSearch" 
                           placeholder="Search templates..." 
                           class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                    <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
                </div>
                
                <!-- Category Filter -->
                <select id="templateCategory" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
                
                <!-- Industry Filter -->
                <select id="templateIndustry" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                    <option value="">All Industries</option>
                    @foreach($industries as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
                
                <!-- Engagement Score Filter -->
                <select id="templateEngagement" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5]">
                    <option value="">All Engagement</option>
                    <option value="90">🔥 90%+ (Viral)</option>
                    <option value="85">⚡ 85%+ (High)</option>
                    <option value="80">✨ 80%+ (Good)</option>
                </select>
                
                <!-- Clear Filters Button -->
                <button type="button" 
                        id="clearFilters"
                        class="w-full px-3 py-2 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-redo mr-1"></i>Clear Filters
                </button>
            </div>
            
            <!-- Templates List -->
            <div id="templatesList" class="space-y-3 max-h-64 overflow-y-auto">
                @foreach($templates as $template)
                <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer template-item"
                     data-template-id="{{ $template->id }}"
                     data-category="{{ $template->category }}"
                     data-industry="{{ $template->industry }}"
                     data-engagement="{{ $template->engagement_score }}"
                     data-title="{{ strtolower($template->title) }}"
                     data-description="{{ strtolower($template->description ?? '') }}">
                    <div class="flex items-start justify-between mb-1">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $template->title }}
                        </div>
                        <!-- Engagement Badge -->
                        <span class="ml-2 px-2 py-0.5 text-xs font-bold rounded
                            @if($template->engagement_score >= 90) bg-red-100 text-red-700
                            @elseif($template->engagement_score >= 85) bg-blue-50 text-[#0077b5] border border-[#0077b5]
                            @else bg-blue-100 text-blue-700 @endif">
                            {{ $template->engagement_score }}%
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mb-2">
                        {{ Str::limit($template->description ?? $template->content, 60) }}
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded">
                                {{ ucfirst($template->category) }}
                            </span>
                            <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded">
                                {{ ucfirst($template->industry) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- No Results Message -->
            <div id="noTemplatesMessage" class="hidden text-center py-8">
                <i class="fas fa-search text-4xl text-gray-300 mb-2"></i>
                <p class="text-sm text-gray-500">No templates found</p>
                <button type="button" onclick="clearAllFilters()" 
                        class="mt-2 text-xs text-[#0077b5] hover:text-[#005885]">
                    Clear filters
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <form id="postForm" action="{{ route('content-creator.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Post Type Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Post Type
                        </label>
                        <div class="grid grid-cols-3 gap-4 @error('post_type') border border-red-500 rounded-lg p-2 @enderror">
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="post_type" value="text" 
                                       {{ old('post_type', 'text') == 'text' ? 'checked' : '' }}
                                       class="focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">Text</div>
                                    <div class="text-xs text-gray-500">Text only</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="post_type" value="image" 
                                       {{ old('post_type') == 'image' ? 'checked' : '' }}
                                       class="focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">Image</div>
                                    <div class="text-xs text-gray-500">1-10 images</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="post_type" value="video" 
                                       {{ old('post_type') == 'video' ? 'checked' : '' }}
                                       class="focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">Video</div>
                                    <div class="text-xs text-gray-500">Video content</div>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Info note about multiple images -->
                        <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Tip:</strong> Image posts support multiple images (1-10) - perfect for photo collections and galleries!
                            </p>
                        </div>
                        @error('post_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Content Editor -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Post Content
                        </label>
                        <x-simple-text-editor
                            id="postContent"
                            name="content"
                            :value="old('content', '')"
                            :rows="12"
                            min-height="min-h-[280px]"
                            placeholder="Write your LinkedIn post here, or generate with AI on the left."
                            footer-hint="Plain text with line breaks — ready to paste into LinkedIn."
                        />
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        
                        <!-- 🔥 NEW: Improve Post Action Buttons (Taplio-style) -->
                        <div id="improveActions" class="mt-3 p-3 bg-gray-50 rounded-lg hidden">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-magic text-[#0077b5] mr-1"></i>Improve Your Post
                                </span>
                                <button type="button" onclick="toggleImproveActions()" 
                                        class="text-xs text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="improvePost('add_hook')" 
                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-fish mr-1"></i>Add Hook
                                </button>
                                <button type="button" onclick="improvePost('add_cta')" 
                                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-bullhorn mr-1"></i>Add CTA
                                </button>
                                <button type="button" onclick="improvePost('expand')" 
                                        class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-expand-arrows-alt mr-1"></i>Expand
                                </button>
                                <button type="button" onclick="improvePost('make_viral')" 
                                        class="px-3 py-1.5 text-white text-xs rounded-md transition-all flex items-center" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)';">
                                    <i class="fas fa-fire mr-1"></i>Make Viral
                                </button>
                                <button type="button" onclick="improvePost('add_data')" 
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-chart-line mr-1"></i>Add Data
                                </button>
                                <button type="button" onclick="improvePost('bullet_points')" 
                                        class="px-3 py-1.5 bg-pink-600 hover:bg-pink-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-list-ul mr-1"></i>Bullets
                                </button>
                                <button type="button" onclick="improvePost('add_story')" 
                                        class="px-3 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-book-open mr-1"></i>Add Story
                                </button>
                                <button type="button" onclick="improvePost('controversial')" 
                                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Controversial
                                </button>
                                <button type="button" onclick="improvePost('add_emoji')" 
                                        class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-smile mr-1"></i>Add Emoji
                                </button>
                                <button type="button" onclick="improvePost('make_concise')" 
                                        class="px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-compress mr-1"></i>Make Concise
                                </button>
                                <button type="button" onclick="improvePost('repurpose')" 
                                        class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-xs rounded-md transition-colors flex items-center">
                                    <i class="fas fa-recycle mr-1"></i>Repurposing
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                💡 Click any action to enhance your content with AI and add it backend on to repurpose a post
                            </p>
                        </div>
                        
                        <!-- Show improve actions button -->
                        <button type="button" id="showImproveBtn" onclick="toggleImproveActions()" 
                                class="mt-2 px-4 py-2 text-white text-sm rounded-md transition-all flex items-center" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                            <i class="fas fa-magic mr-2"></i>Improve This Post
                        </button>
                    </div>

                    <!-- Image Upload (for image posts - supports 1-10 images) -->
                    <div id="imageUploadSection" class="mb-6 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-images mr-1 text-[#0077b5]"></i>Upload Images (1-10 images)
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center @error('images.*') border-red-500 @enderror">
                            <input type="file" id="imageUpload" name="images[]" multiple accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden">
                            <button type="button" onclick="document.getElementById('imageUpload').click()" 
                                    class="text-[#0077b5] hover:text-[#005885]">
                                <i class="fas fa-images text-3xl mb-2"></i>
                                <div class="text-sm font-medium">Click to upload image(s)</div>
                                <div class="text-xs text-gray-500 mt-1">Select 1 or more images (PNG, JPG, WEBP only - max 10 images, 10MB each)</div>
                            </button>
                        </div>
                        @error('images.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div id="imagePreview" class="mt-4 hidden">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="imagePreviewGrid">
                                <!-- Images will be displayed here -->
                            </div>
                        </div>
                    </div>

                    <!-- Video Upload (for video posts) -->
                    <div id="videoUploadSection" class="mb-6 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Video
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center @error('video') border-red-500 @enderror">
                            <input type="file" id="videoUpload" name="video" accept="video/*" class="hidden">
                            <button type="button" onclick="document.getElementById('videoUpload').click()" 
                                    class="text-[#0077b5] hover:text-[#005885]">
                                <i class="fas fa-video text-3xl mb-2"></i>
                                <div class="text-sm">Click to upload video</div>
                            </button>
                        </div>
                        @error('video')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div id="videoPreview" class="mt-4 hidden">
                            <div class="relative group">
                                <video id="previewVideo" controls class="max-w-full h-48 rounded-lg">
                                    <source id="videoSource" src="" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                                <button type="button" onclick="clearVideo()" 
                                        class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-2 shadow-lg">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Hashtags -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Hashtags
                        </label>
                        <input type="text" 
                               name="hashtags" 
                               id="hashtags"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5] @error('hashtags') border-red-500 @enderror"
                               placeholder="#marketing #business #growth"
                               value="{{ old('hashtags') }}">
                        @error('hashtags')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-1 text-sm text-gray-500">
                            Separate hashtags with spaces. Use 3-5 hashtags for best results.
                        </div>
                    </div>

                    <!-- Scheduling -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Publishing Options
                        </label>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="radio" name="publish_option" value="draft" checked 
                                       class="focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                                <span class="ml-2 text-sm text-gray-700">Save as Draft</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="radio" name="publish_option" value="now" 
                                       class="focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                                <span class="ml-2 text-sm text-gray-700">Publish Now</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="radio" name="publish_option" value="schedule" 
                                       class="focus:ring-[#0077b5]" style="accent-color: #0077b5;">
                                <span class="ml-2 text-sm text-gray-700">Schedule for Later</span>
                            </label>
                        </div>
                        
                        <div id="scheduleSection" class="mt-4 hidden">
                            <input type="datetime-local" 
                                   name="scheduled_at" 
                                   id="scheduledAt"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0077b5] @error('scheduled_at') border-red-500 @enderror"
                                   min="{{ now($userTimezone)->format('Y-m-d\TH:i') }}"
                                   value="{{ old('scheduled_at') }}">
                            @error('scheduled_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('content-creator.index') }}" 
                           class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                id="savePostBtn"
                                class="px-6 py-2 text-white rounded-md transition-all disabled:opacity-50 disabled:cursor-not-allowed" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';">
                            <span id="savePostText">
                                <i class="fas fa-save mr-2"></i>Save Post
                            </span>
                            <span id="savePostLoading" class="hidden">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-gray-600/50 hidden z-50 h-screen">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-6 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#0077b5] mx-auto mb-4"></div>
            <p class="text-gray-900">Generating content...</p>
        </div>
    </div>
</div>

<script>
// CSRF token from Blade to avoid relying on a meta tag
const csrfToken = '{{ csrf_token() }}';

function setPostContent(value, { stripMarkdown = true } = {}) {
    if (window.SimpleTextEditor) {
        window.SimpleTextEditor.setValue('postContent', value || '', { stripMarkdown });
    } else {
        const cleaned = stripMarkdown && window.SimpleTextEditor?.stripMarkdown
            ? window.SimpleTextEditor.stripMarkdown(value || '')
            : (value || '');
        const el = document.getElementById('postContent');
        if (el) el.value = cleaned;
    }
    toggleImprovePanelFromContent();
}

function getPostContent() {
    return window.SimpleTextEditor
        ? window.SimpleTextEditor.getValue('postContent')
        : (document.getElementById('postContent')?.value || '');
}

function toggleImprovePanelFromContent() {
    const content = getPostContent().trim();
    const improveActions = document.getElementById('improveActions');
    const showImproveBtn = document.getElementById('showImproveBtn');
    if (!improveActions) return;
    if (content.length > 0) {
        improveActions.classList.remove('hidden');
        showImproveBtn?.classList.add('hidden');
    }
}

function scrollToPostContent() {
    document.querySelector('[data-simple-text-editor] textarea#postContent, #postContent')
        ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// 🔥 NEW: Load inspiration content from sessionStorage
window.addEventListener('DOMContentLoaded', function() {
    if (window.SimpleTextEditor) {
        window.SimpleTextEditor.init();
    }

    const urlParams = new URLSearchParams(window.location.search);
    const fromInspiration = urlParams.get('from') === 'inspiration';
    
    if (fromInspiration) {
        const inspirationContent = sessionStorage.getItem('inspiration_content');
        const inspirationAuthor = sessionStorage.getItem('inspiration_author');
        const wasRemixed = sessionStorage.getItem('inspiration_remixed');
        
        if (inspirationContent) {
            setPostContent(inspirationContent, { stripMarkdown: false });
            
            // Extract and load hashtags
            const hashtags = extractHashtags(inspirationContent);
            document.getElementById('hashtags').value = hashtags;
            
            // Show improve actions automatically
            toggleImprovePanelFromContent();
            
            // Show notification
            if (wasRemixed) {
                showNotification('✨ AI-remixed content loaded! Ready to customize and post.', 'success');
            } else {
                showNotification(`💡 Inspiration from ${inspirationAuthor || 'viral post'} loaded! Customize it to make it yours.`, 'success');
            }
            
            // Clear sessionStorage
            sessionStorage.removeItem('inspiration_content');
            sessionStorage.removeItem('inspiration_author');
            sessionStorage.removeItem('inspiration_engagement');
            sessionStorage.removeItem('inspiration_remixed');
            
            setTimeout(scrollToPostContent, 500);
        }
    }
});

document.getElementById('postContent')?.addEventListener('input', toggleImprovePanelFromContent);
document.getElementById('postContent')?.addEventListener('simple-text-editor:input', toggleImprovePanelFromContent);

// Post type change handler
document.querySelectorAll('input[name="post_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const imageSection = document.getElementById('imageUploadSection');
        const videoSection = document.getElementById('videoUploadSection');
        const imageInput = document.getElementById('imageUpload');
        const videoInput = document.getElementById('videoUpload');
        const imagePreview = document.getElementById('imagePreview');
        const videoPreview = document.getElementById('videoPreview');
        
        if (this.value === 'image') {
            imageSection.classList.remove('hidden');
            videoSection.classList.add('hidden');
            
            // Clear other inputs and previews
            videoInput.value = '';
            videoPreview.classList.add('hidden');
        } else if (this.value === 'video') {
            videoSection.classList.remove('hidden');
            imageSection.classList.add('hidden');
            
            // Clear other inputs and previews
            imageInput.value = '';
            imagePreview.classList.add('hidden');
        } else {
            imageSection.classList.add('hidden');
            videoSection.classList.add('hidden');
            
            // Clear all inputs and previews
            imageInput.value = '';
            videoInput.value = '';
            imagePreview.classList.add('hidden');
            videoPreview.classList.add('hidden');
        }
    });
});

// Publish option change handler
document.querySelectorAll('input[name="publish_option"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const scheduleSection = document.getElementById('scheduleSection');
        if (this.value === 'schedule') {
            scheduleSection.classList.remove('hidden');
        } else {
            scheduleSection.classList.add('hidden');
        }
    });
});

// 🔥 NEW: Toggle Improve Actions Panel
function toggleImproveActions() {
    const improveActions = document.getElementById('improveActions');
    const showBtn = document.getElementById('showImproveBtn');
    
    if (improveActions.classList.contains('hidden')) {
        improveActions.classList.remove('hidden');
        showBtn.classList.add('hidden');
    } else {
        improveActions.classList.add('hidden');
        showBtn.classList.remove('hidden');
    }
}

// 🔥 AI Cooldown System (15 seconds)
function startAICooldown(buttonId) {
    const button = document.getElementById(buttonId);
    if (button) {
        // Store original HTML before starting cooldown (ensures we capture normal state)
        if (buttonId === 'generateBtn') {
            // Store the complete button HTML with both spans in normal state
            const generateText = document.getElementById('generateText');
            const generateLoading = document.getElementById('generateLoading');
            if (generateText && generateLoading) {
                // Ensure normal state is visible before storing
                generateText.classList.remove('hidden');
                generateLoading.classList.add('hidden');
                button.dataset.originalHTML = button.innerHTML;
            }
        }
    }
    const cooldownEnd = Date.now() + 5000; // 5 seconds from now
    localStorage.setItem('aiCooldown_' + buttonId, cooldownEnd);
    updateCooldownUI(buttonId);
}

function updateCooldownUI(buttonId) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    const cooldownEnd = localStorage.getItem('aiCooldown_' + buttonId);
    if (!cooldownEnd) {
        // No cooldown, ensure button is in normal state
        if (buttonId === 'generateBtn') {
            const generateText = document.getElementById('generateText');
            const generateLoading = document.getElementById('generateLoading');
            if (generateText && generateLoading) {
                button.disabled = false;
                generateText.classList.remove('hidden');
                generateLoading.classList.add('hidden');
            }
        }
        return;
    }
    
    const timeLeft = Math.max(0, cooldownEnd - Date.now());
    
    if (timeLeft > 0) {
        const secondsLeft = Math.ceil(timeLeft / 1000);
        button.disabled = true;
        
        // Store original button HTML structure if not already stored
        if (!button.dataset.originalHTML) {
            // Store the complete button HTML with both spans
            button.dataset.originalHTML = button.innerHTML;
        }
        
        // For generateBtn, hide both spans and show cooldown
        if (buttonId === 'generateBtn') {
            const generateText = document.getElementById('generateText');
            const generateLoading = document.getElementById('generateLoading');
            if (generateText && generateLoading) {
                generateText.classList.add('hidden');
                generateLoading.classList.add('hidden');
            }
            // Show cooldown text directly in button
            button.innerHTML = `<i class="fas fa-clock mr-2"></i>Wait ${secondsLeft}s`;
        } else {
            // For other buttons, just update the text
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }
            button.innerHTML = `<i class="fas fa-clock mr-2"></i>Wait ${secondsLeft}s`;
        }
        
        setTimeout(() => updateCooldownUI(buttonId), 1000);
    } else {
        // Cooldown ended - restore to normal state
        button.disabled = false;
        
        if (buttonId === 'generateBtn') {
            // Restore the original HTML structure (with both spans)
            if (button.dataset.originalHTML) {
                button.innerHTML = button.dataset.originalHTML;
            }
            // Ensure normal state is visible
            const generateText = document.getElementById('generateText');
            const generateLoading = document.getElementById('generateLoading');
            if (generateText && generateLoading) {
                generateText.classList.remove('hidden');
                generateLoading.classList.add('hidden');
            }
        } else {
            // Restore original text for other buttons
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
        
        localStorage.removeItem('aiCooldown_' + buttonId);
    }
}

// Check cooldowns on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCooldownUI('generateBtn');
    
    // Check improve buttons cooldown
    const improveButtons = document.querySelectorAll('#improveActions button[onclick^="improvePost"]');
    if (improveButtons.length > 0) {
        updateCooldownUI('improveButtons');
    }
});

// 🔥 NEW: Improve Post Function
function improvePost(action) {
    const content = getPostContent().trim();
    
    if (!content) {
        alert('Please enter some content first.');
        return;
    }
    
    showLoading();
    
    // Disable all improve buttons
    const improveButtons = document.querySelectorAll('#improveActions button[onclick^="improvePost"]');
    improveButtons.forEach(btn => btn.disabled = true);
    
    fetch('/content-creator/improve', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            content: content,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            setPostContent(data.content);
            
            // Show success notification
            showNotification('✨ Content improved successfully!', 'success');
            
            // Start 60-second cooldown
            startAICooldown('improveButtons');
            updateImproveButtonsCooldown();
        } else {
            improveButtons.forEach(btn => btn.disabled = false);
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        improveButtons.forEach(btn => btn.disabled = false);
        console.error('Error:', error);
        alert('An error occurred while improving content.');
    });
}

// Update all improve buttons with cooldown
function updateImproveButtonsCooldown() {
    const cooldownEnd = localStorage.getItem('aiCooldown_improveButtons');
    if (!cooldownEnd) return;
    
    const timeLeft = Math.max(0, cooldownEnd - Date.now());
    const improveButtons = document.querySelectorAll('#improveActions button[onclick^="improvePost"]');
    
    if (timeLeft > 0) {
        const secondsLeft = Math.ceil(timeLeft / 1000);
        improveButtons.forEach(btn => {
            btn.disabled = true;
            if (!btn.dataset.originalText) {
                btn.dataset.originalText = btn.innerHTML;
            }
            const icon = btn.querySelector('i').className;
            btn.innerHTML = `<i class="${icon}"></i> ${secondsLeft}s`;
        });
        setTimeout(updateImproveButtonsCooldown, 1000);
    } else {
        improveButtons.forEach(btn => {
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        });
        localStorage.removeItem('aiCooldown_improveButtons');
    }
}

// 🔥 NEW: Show Notification Function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } animate-slide-in`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// 🔥 UPDATED: AI Generation with Multiple Drafts Support
document.getElementById('aiGenerateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const topic = document.getElementById('aiTopic').value.trim();
    if (!topic) {
        alert('Please enter a topic or idea.');
        return;
    }
    
    const multipleDrafts = document.getElementById('multipleDrafts').checked;
    const generateBtn = document.getElementById('generateBtn');
    const generateText = document.getElementById('generateText');
    const generateLoading = document.getElementById('generateLoading');
    
    // Show loading state
    generateBtn.disabled = true;
    generateText.classList.add('hidden');
    generateLoading.classList.remove('hidden');
    
    showLoading();
    
    fetch('/content-creator/generate', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            topic: topic,
            style: document.getElementById('aiStyle').value,
            length: document.getElementById('aiLength').value,
            multiple_drafts: multipleDrafts
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        // Reset button loading state first
        generateBtn.disabled = false;
        generateText.classList.remove('hidden');
        generateLoading.classList.add('hidden');
        
        if (data.success) {
            if (data.drafts && data.drafts.length > 0) {
                // Show multiple drafts
                displayMultipleDrafts(data.drafts);
            } else {
                // Single draft (backward compatibility)
                setPostContent(data.content);
                document.getElementById('hashtags').value = data.hashtags;
                toggleImprovePanelFromContent();
            }
            
            // Start cooldown after successful generation (this will handle button state)
            startAICooldown('generateBtn');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        // Reset button loading state on error
        generateBtn.disabled = false;
        generateText.classList.remove('hidden');
        generateLoading.classList.add('hidden');
        console.error('Error:', error);
        alert('An error occurred while generating content.');
    });
});

// 🔥 NEW: Display Multiple Drafts
function displayMultipleDrafts(drafts) {
    const draftsList = document.getElementById('draftsList');
    const draftsContainer = document.getElementById('draftsContainer');
    
    // Clear previous drafts
    draftsList.innerHTML = '';
    
    // Create draft cards
    drafts.forEach((draft, index) => {
        const draftCard = document.createElement('div');
        draftCard.className = 'p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-[#0077b5] hover:bg-blue-50 transition-all';
        draftCard.onclick = () => selectDraft(draft);
        
        draftCard.innerHTML = `
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-50 text-[#0077b5] border border-[#0077b5] rounded-full text-xs font-bold mr-2">
                        ${index + 1}
                    </span>
                    <span class="text-sm font-medium text-gray-900">
                        Draft ${index + 1}
                    </span>
                </div>
                <span class="text-xs text-gray-500">
                    ${draft.word_count} words
                </span>
            </div>
            <p class="text-sm text-gray-700 line-clamp-3 mb-2">
                ${draft.content.substring(0, 150)}${draft.content.length > 150 ? '...' : ''}
            </p>
            <div class="flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    ${draft.hashtags || 'No hashtags'}
                </div>
                <button class="text-xs text-[#0077b5] hover:text-[#005885] font-medium">
                    Use this draft →
                </button>
            </div>
        `;
        
        draftsList.appendChild(draftCard);
    });
    
    // Show drafts container
    draftsContainer.classList.remove('hidden');
    
    // Scroll to drafts
    draftsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// 🔥 NEW: Select Draft
function selectDraft(draft) {
    setPostContent(draft.content);
    document.getElementById('hashtags').value = draft.hashtags || '';
    
    // Hide drafts container
    document.getElementById('draftsContainer').classList.add('hidden');
    
    toggleImprovePanelFromContent();
    scrollToPostContent();
    
    // Show success notification
    showNotification('✅ Draft selected! Now improve it with AI actions below.', 'success');
}

// 🔥 FIXED: Template selection - now works instantly
function attachTemplateClickHandlers() {
    document.querySelectorAll('.template-item').forEach(item => {
        // Remove old listener if any
        const newItem = item.cloneNode(true);
        item.parentNode.replaceChild(newItem, item);
        
        newItem.addEventListener('click', function() {
            const templateId = this.dataset.templateId;
            
            console.log('🎯 Template clicked:', templateId);
            
            // Show loading
            showLoading();
            
            // Fetch template content
            fetch(`/content-creator/templates?template_id=${templateId}`)
                .then(response => {
                    console.log('📡 Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📦 Template data received:', data);
                    hideLoading();
                    
                    if (data.success !== false && data.templates && data.templates.length > 0) {
                        const template = data.templates[0];
                        
                        console.log('✅ Loading template:', template.title);
                        
                        // Load template content
                        setPostContent(template.content, { stripMarkdown: false });
                        document.getElementById('hashtags').value = extractHashtags(template.content);
                        toggleImprovePanelFromContent();
                        scrollToPostContent();
                        
                        // Show success notification
                        showNotification('✅ Template loaded! Now customize it with variables or improve actions.', 'success');
                        
                        console.log('🎉 Template loaded successfully');
                    } else {
                        throw new Error(data.message || 'Template not found in response');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('❌ Template loading error:', error);
                    alert('Error loading template: ' + error.message + '\n\nPlease check the console for details.');
                });
        });
    });
}

// Initialize template handlers on page load
attachTemplateClickHandlers();

// 🔥 ENHANCED: Template Filtering System
function filterTemplates() {
    const searchTerm = document.getElementById('templateSearch').value.toLowerCase();
    const category = document.getElementById('templateCategory').value;
    const industry = document.getElementById('templateIndustry').value;
    const engagement = document.getElementById('templateEngagement').value;
    
    let visibleCount = 0;
    
    document.querySelectorAll('.template-item').forEach(item => {
        const matchesSearch = !searchTerm || 
            item.dataset.title.includes(searchTerm) || 
            item.dataset.description.includes(searchTerm);
        
        const matchesCategory = !category || item.dataset.category === category;
        const matchesIndustry = !industry || item.dataset.industry === industry;
        const matchesEngagement = !engagement || parseInt(item.dataset.engagement) >= parseInt(engagement);
        
        if (matchesSearch && matchesCategory && matchesIndustry && matchesEngagement) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Update count
    document.getElementById('templateCount').textContent = `${visibleCount} template${visibleCount !== 1 ? 's' : ''}`;
    
    // Show/hide no results message
    const noResultsMsg = document.getElementById('noTemplatesMessage');
    const templatesList = document.getElementById('templatesList');
    if (visibleCount === 0) {
        noResultsMsg.classList.remove('hidden');
        templatesList.classList.add('hidden');
    } else {
        noResultsMsg.classList.add('hidden');
        templatesList.classList.remove('hidden');
    }
    
    // 🔥 FIX: Reattach click handlers after filtering
    attachTemplateClickHandlers();
}

// Clear all filters
function clearAllFilters() {
    document.getElementById('templateSearch').value = '';
    document.getElementById('templateCategory').value = '';
    document.getElementById('templateIndustry').value = '';
    document.getElementById('templateEngagement').value = '';
    filterTemplates();
}

// Attach filter event listeners
document.getElementById('templateSearch').addEventListener('input', filterTemplates);
document.getElementById('templateCategory').addEventListener('change', filterTemplates);
document.getElementById('templateIndustry').addEventListener('change', filterTemplates);
document.getElementById('templateEngagement').addEventListener('change', filterTemplates);
document.getElementById('clearFilters').addEventListener('click', clearAllFilters);

// Rewrite and Shorten are now part of improve actions (add_hook, make_concise, etc.)

// Utility functions
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function extractHashtags(text) {
    const hashtags = text.match(/#\w+/g);
    return hashtags ? hashtags.join(' ') : '';
}

function str_word_count(str) {
    return str.trim().split(/\s+/).filter(word => word.length > 0).length;
}

// Store selected files globally for individual removal
let selectedFiles = [];

// Image upload preview (supports multiple images)
document.getElementById('imageUpload').addEventListener('change', function(e) {
    selectedFiles = Array.from(e.target.files);
    
    if (selectedFiles.length === 0) return;
    
    // Validate file types (PNG, JPG, WEBP only)
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    const invalidFiles = selectedFiles.filter(file => !allowedTypes.includes(file.type));
    
    if (invalidFiles.length > 0) {
        alert('Please select only PNG, JPG, or WEBP images. Other file types are not allowed.');
        this.value = '';
        selectedFiles = [];
        return;
    }
    
    // Validate number of images (1-10)
    if (selectedFiles.length > 10) {
        alert('Please select maximum 10 images.');
        this.value = '';
        selectedFiles = [];
        return;
    }
    
    // Clear other inputs and previews when images are selected
    const videoInput = document.getElementById('videoUpload');
    const videoPreview = document.getElementById('videoPreview');
    videoInput.value = '';
    videoPreview.classList.add('hidden');
    
    updateImagePreviews();
});

// Update image previews
function updateImagePreviews() {
    const previewGrid = document.getElementById('imagePreviewGrid');
    previewGrid.innerHTML = '';
    
    if (selectedFiles.length === 0) {
        document.getElementById('imagePreview').classList.add('hidden');
        return;
    }
    
    // Display each image with individual remove button
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgContainer = document.createElement('div');
            imgContainer.className = 'relative group';
            imgContainer.innerHTML = `
                <img src="${e.target.result}" alt="Image ${index + 1}" 
                     class="w-full h-32 object-cover rounded-lg border-2 border-gray-200">
                <button type="button" onclick="removeImage(${index})" 
                        class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                    <i class="fas fa-times text-xs"></i>
                </button>
            `;
            previewGrid.appendChild(imgContainer);
        };
        reader.readAsDataURL(file);
    });
    
    document.getElementById('imagePreview').classList.remove('hidden');
}

// Remove individual image
function removeImage(index) {
    selectedFiles.splice(index, 1);
    
    // Update file input with remaining files
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    document.getElementById('imageUpload').files = dataTransfer.files;
    
    updateImagePreviews();
}

// Video upload preview
document.getElementById('videoUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Clear other inputs and previews when video is selected
        const imageInput = document.getElementById('imageUpload');
        const imagePreview = document.getElementById('imagePreview');
        imageInput.value = '';
        imagePreview.classList.add('hidden');
        
        const url = URL.createObjectURL(file);
        document.getElementById('videoSource').src = url;
        document.getElementById('previewVideo').load();
        document.getElementById('videoPreview').classList.remove('hidden');
    }
});

// Clear all images function
function clearImages() {
    selectedFiles = [];
    document.getElementById('imageUpload').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('imagePreviewGrid').innerHTML = '';
}

// Clear video function
function clearVideo() {
    document.getElementById('videoUpload').value = '';
    document.getElementById('videoPreview').classList.add('hidden');
    document.getElementById('videoSource').src = '';
}

// Note: Loading state is now handled in the main form submission handler above

// Main form submission handler with loading state
document.getElementById('postForm').addEventListener('submit', function(e) {
    const saveBtn = document.getElementById('savePostBtn');
    const saveText = document.getElementById('savePostText');
    const saveLoading = document.getElementById('savePostLoading');
    
    // Show loading state
    saveBtn.disabled = true;
    saveText.classList.add('hidden');
    saveLoading.classList.remove('hidden');
    
    // Reset loading state after 30 seconds if something goes wrong
    setTimeout(() => {
        if (saveBtn.disabled) {
            saveBtn.disabled = false;
            saveText.classList.remove('hidden');
            saveLoading.classList.add('hidden');
        }
    }, 30000);
});
</script>
@endsection
