<?php

namespace App\Http\Controllers;

use App\Models\LinkedInPost;
use App\Models\PostTemplate;
use App\Models\Timezone;
use App\Models\V2IntegrationAccount;
use App\Services\ChatGPT;
use App\Services\LinkedInContentService;
use App\Helpers\CampaignHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContentCreatorController extends Controller
{
    use CampaignHelper;

    /**
     * Display the content creator dashboard
     */
    public function index(Request $request)
    {
        $userId = auth()->user()->id;
        $status = $request->query('status', 'all');
        $userTimezone = $this->getUserTimezone();
        
        $query = LinkedInPost::where('user_id', $userId);
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $posts = $query->orderBy('created_at', 'desc')->paginate(12);
        
        // Get statistics
        $stats = [
            'total_posts' => LinkedInPost::where('user_id', $userId)->count(),
            'draft_posts' => LinkedInPost::where('user_id', $userId)->where('status', 'draft')->count(),
            'scheduled_posts' => LinkedInPost::where('user_id', $userId)->where('status', 'scheduled')->count(),
            'published_posts' => LinkedInPost::where('user_id', $userId)->where('status', 'published')->count(),
        ];
        
        return view('content-creator.index', compact('posts', 'stats', 'status', 'userTimezone'))
            ->with('hasLinkedIn', $this->userHasLinkedIn());
    }

    /**
     * Show the form for creating a new post
     */
    public function create()
    {
        $templates = PostTemplate::active()
            ->orderBy('engagement_score', 'desc')
            ->limit(20)
            ->get();
            
        $categories = PostTemplate::getCategories();
        $industries = PostTemplate::getIndustries();
        $userTimezone = $this->getUserTimezone();
        
        return view('content-creator.create', compact('templates', 'categories', 'industries', 'userTimezone'))
            ->with('hasLinkedIn', $this->userHasLinkedIn());
    }

    /**
     * Store a newly created post
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:3000',
            'post_type' => 'required|in:text,image,video',
            'scheduled_at' => 'nullable|date',
            'hashtags' => 'nullable|string|max:500',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240', // For multiple images (PNG, JPG, WEBP only)
            'video' => 'nullable|mimes:mp4,avi,mov,wmv|max:102400' // 100MB max for video
        ]);

        // Validate that only one media type is selected based on post_type
        if ($request->post_type === 'image' && $request->hasFile('video')) {
            return back()->withErrors(['video' => 'Cannot upload video for image post type.']);
        }
        
        if ($request->post_type === 'video' && $request->hasFile('images')) {
            return back()->withErrors(['images' => 'Cannot upload images for video post type.']);
        }

        $imageUrls = null;
        $videoUrl = null;

        // Initialize Cloudinary service only if we need to upload files
        $needsUpload = ($request->post_type === 'image' && $request->hasFile('images')) ||
                       ($request->post_type === 'video' && $request->hasFile('video'));
        
        if ($needsUpload) {
            $cloudinaryService = new LinkedInContentService();
            
            // Check if Cloudinary is configured before attempting upload
            if (!$cloudinaryService->isConfigured()) {
                \Log::error('Invalid configuration, please set up your environment', [
                    'userId' => auth()->id()
                ]);
                return back()->withErrors(['upload' => 'Media upload service is not configured. Please contact support or upload your media directly when publishing.'])->withInput();
            }

            // Handle multiple images upload (for image post type - 1 or more images)
            if ($request->post_type === 'image' && $request->hasFile('images')) {
                try {
                    $imageUrls = $cloudinaryService->uploadCarouselImages($request->file('images'));
                } catch (\Exception $e) {
                    \Log::error('Failed to upload images', ['error' => $e->getMessage()]);
                    return back()->withErrors(['images' => 'Failed to upload images: ' . $e->getMessage()])->withInput();
                }
            }

            // Handle video upload (only for video post type)
            if ($request->post_type === 'video' && $request->hasFile('video')) {
                try {
                    $videoUrl = $cloudinaryService->uploadVideo($request->file('video'));
                } catch (\Exception $e) {
                    \Log::error('Failed to upload video', ['error' => $e->getMessage()]);
                    return back()->withErrors(['video' => 'Failed to upload video: ' . $e->getMessage()])->withInput();
                }
            }
        }

        // Determine status based on publish option
        $status = 'draft';
        $scheduledAt = null;

        if ($request->publish_option === 'now') {
            $status = 'ready_to_publish';
            $scheduledAt = now();
        } elseif ($request->publish_option === 'schedule' && $request->scheduled_at) {
            $status = 'scheduled';
            $scheduledAt = $this->parseUserDatetimeLocalToUtc($request->scheduled_at);
            if ($scheduledAt->lte(Carbon::now('UTC'))) {
                return back()
                    ->withErrors(['scheduled_at' => 'Scheduled time must be in the future.'])
                    ->withInput();
            }
            
        }

        // Truncate hashtags if too long (safety measure, but column should now be TEXT)
        $hashtags = $request->hashtags;
        if ($hashtags && strlen($hashtags) > 65535) {
            $hashtags = substr($hashtags, 0, 65535);
            \Log::warning('⚠️ Hashtags truncated due to length', [
                'original_length' => strlen($request->hashtags),
                'truncated_length' => strlen($hashtags)
            ]);
        }

        $post = LinkedInPost::create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'image_url' => $imageUrls, // Model will auto-encode to JSON if array
            'video_url' => $videoUrl,
            'post_type' => $request->post_type,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'hashtags' => $hashtags,
            'word_count' => str_word_count($request->content)
        ]);

        if ($status === 'scheduled') {
            \App\Jobs\PublishLinkedInPost::dispatch($post)->delay($scheduledAt);
        } elseif ($status === 'ready_to_publish') {
            \App\Jobs\PublishLinkedInPost::dispatchSync($post);
        }

        notify()->success('Post saved successfully!');
        return redirect()->route('content-creator.index');
    }

    /**
     * Show the form for editing the specified post
     */
    public function edit($id)
    {
        $post = LinkedInPost::where('user_id', auth()->id())->findOrFail($id);
        $userTimezone = $this->getUserTimezone();
        
        // Only allow editing draft posts
        if ($post->status !== 'draft') {
            notify()->error('Only draft posts can be edited.');
            return redirect()->route('content-creator.index');
        }
        
        $templates = PostTemplate::active()
            ->orderBy('engagement_score', 'desc')
            ->limit(20)
            ->get();
            
        $categories = PostTemplate::getCategories();
        $industries = PostTemplate::getIndustries();
        
        return view('content-creator.edit', compact('post', 'templates', 'categories', 'industries', 'userTimezone'))
            ->with('hasLinkedIn', $this->userHasLinkedIn());
    }

    /**
     * Update the specified post
     */
    public function update(Request $request, $id)
    {
        $post = LinkedInPost::where('user_id', auth()->id())->findOrFail($id);
        
        // Only allow updating draft posts
        if ($post->status !== 'draft') {
            notify()->error('Only draft posts can be edited.');
            return redirect()->route('content-creator.index');
        }
        
        $request->validate([
            'content' => 'required|string|max:3000',
            'post_type' => 'required|in:text,image,video',
            'scheduled_at' => 'nullable|date',
            'hashtags' => 'nullable|string|max:500',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'video' => 'nullable|mimes:mp4,avi,mov,wmv|max:102400'
        ]);

        // Validate that only one media type is selected based on post_type
        if ($request->post_type === 'image' && $request->hasFile('video')) {
            return back()->withErrors(['video' => 'Cannot upload video for image post type.'])->withInput();
        }
        
        if ($request->post_type === 'video' && $request->hasFile('images')) {
            return back()->withErrors(['images' => 'Cannot upload images for video post type.'])->withInput();
        }

        $imageUrls = $post->image_url; // Keep existing images by default
        $videoUrl = $post->video_url; // Keep existing video by default

        // Handle new image uploads (for image post type)
        if ($request->post_type === 'image' && $request->hasFile('images')) {
            $cloudinaryService = new LinkedInContentService();
            
            if (!$cloudinaryService->isConfigured()) {
                \Log::error('Invalid configuration, please set up your environment', [
                    'userId' => auth()->id()
                ]);
                return back()->withErrors(['upload' => 'Media upload service is not configured. Please contact support or upload your media directly when publishing.'])->withInput();
            }
            
            try {
                $imageUrls = $cloudinaryService->uploadCarouselImages($request->file('images'));
            } catch (\Exception $e) {
                \Log::error('Failed to upload images', ['error' => $e->getMessage()]);
                return back()->withErrors(['images' => 'Failed to upload images: ' . $e->getMessage()])->withInput();
            }
        }

        // Handle new video upload (only for video post type)
        if ($request->post_type === 'video' && $request->hasFile('video')) {
            $cloudinaryService = new LinkedInContentService();
            
            if (!$cloudinaryService->isConfigured()) {
                \Log::error('Invalid configuration, please set up your environment', [
                    'userId' => auth()->id()
                ]);
                return back()->withErrors(['upload' => 'Media upload service is not configured. Please contact support or upload your media directly when publishing.'])->withInput();
            }
            
            try {
                $videoUrl = $cloudinaryService->uploadVideo($request->file('video'));
            } catch (\Exception $e) {
                \Log::error('Failed to upload video', ['error' => $e->getMessage()]);
                return back()->withErrors(['video' => 'Failed to upload video: ' . $e->getMessage()])->withInput();
            }
        }

        // Determine status based on publish option
        $status = 'draft';
        $scheduledAt = null;

        if ($request->publish_option === 'now') {
            $status = 'ready_to_publish';
            $scheduledAt = now();
        } elseif ($request->publish_option === 'schedule' && $request->scheduled_at) {
            $status = 'scheduled';
            $scheduledAt = $this->parseUserDatetimeLocalToUtc($request->scheduled_at);
            if ($scheduledAt->lte(Carbon::now('UTC'))) {
                return back()
                    ->withErrors(['scheduled_at' => 'Scheduled time must be in the future.'])
                    ->withInput();
            }
        }

        // Truncate hashtags if too long
        $hashtags = $request->hashtags;
        if ($hashtags && strlen($hashtags) > 65535) {
            $hashtags = substr($hashtags, 0, 65535);
            \Log::warning('⚠️ Hashtags truncated due to length', [
                'original_length' => strlen($request->hashtags),
                'truncated_length' => strlen($hashtags)
            ]);
        }

        $post->update([
            'content' => $request->content,
            'image_url' => $imageUrls,
            'video_url' => $videoUrl,
            'post_type' => $request->post_type,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'hashtags' => $hashtags,
            'word_count' => str_word_count($request->content)
        ]);

        if ($status === 'scheduled') {
            \App\Jobs\PublishLinkedInPost::dispatch($post)->delay($scheduledAt);
        } elseif ($status === 'ready_to_publish') {
            \App\Jobs\PublishLinkedInPost::dispatchSync($post);
        }

        notify()->success('Post updated successfully!');
        return redirect()->route('content-creator.index');
    }

    /**
     * Generate content using AI (now returns multiple drafts)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:500',
            'style' => 'required|in:professional,casual,motivational,educational,storytelling',
            'length' => 'required|in:short,medium,long',
            'template_id' => 'nullable|exists:post_templates,id',
            'multiple_drafts' => 'nullable|boolean'
        ]);

        try {
            $data = [
                'topic' => $request->topic,
                'style' => $request->style,
                'length' => $request->length,
                'template_id' => $request->template_id
            ];

            $chatGPT = new ChatGPT($data);
            
            // Check if multiple drafts are requested
            if ($request->multiple_drafts) {
                $drafts = $chatGPT->generateMultipleDrafts();
                $safeDrafts = collect($drafts)->map(function ($draft) {
                    if (!is_array($draft)) {
                        return $draft;
                    }
                    $draft['content'] = $this->ensureUtf8($draft['content'] ?? '');
                    $draft['hashtags'] = $this->ensureUtf8($draft['hashtags'] ?? '');
                    $draft['word_count'] = str_word_count($draft['content']);
                    return $draft;
                })->toArray();
                
                return response()->json([
                    'success' => true,
                    'drafts' => $safeDrafts
                ]);
            } else {
                // Single draft (backward compatibility)
                $result = $chatGPT->generateLinkedInPost();
                $safeContent = $this->ensureUtf8($result['content'] ?? '');
                $safeHashtags = $this->ensureUtf8($result['hashtags'] ?? '');

                return response()->json([
                    'success' => true,
                    'content' => $safeContent,
                    'hashtags' => $safeHashtags,
                    'word_count' => str_word_count($safeContent)
                ]);
            }

        } catch (\Exception $e) {
            // Check if it's a rate limit error (OpenAI specific)
            $message = $e->getMessage();
            $isRateLimit = str_contains(strtolower($message), 'rate limit') 
                        || str_contains(strtolower($message), 'rate_limit_exceeded')
                        || str_contains($message, '429')
                        || str_contains(strtolower($message), 'too many requests');
            
            if ($isRateLimit) {
                \Log::warning('AI rate limit hit', ['error' => substr($message, 0, 200)]);
                $draftMessage = $request->multiple_drafts ? ' This was a single API call requesting 2 drafts.' : '';
                return response()->json([
                    'success' => false,
                    'message' => '⏳ AI rate limit reached. Please wait 1-2 minutes before trying again.' . $draftMessage
                ], 429);
            }
            
            // Log actual error for debugging
            \Log::error('AI operation failed', [
                'error' => substr($message, 0, 500),
                'trace' => substr($e->getTraceAsString(), 0, 1000)
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }
    }

    /**
     * Improve existing post content with specific action
     */
    public function improvePost(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'action' => 'required|in:add_hook,add_cta,expand,make_viral,add_data,bullet_points,add_story,controversial,add_emoji,make_concise,repurpose'
        ]);

        try {
            $chatGPT = new ChatGPT();
            $result = $chatGPT->improvePost($request->action, $request->content);

            return response()->json([
                'success' => true,
                'content' => $result['content'],
                'word_count' => $result['word_count']
            ]);

        } catch (\Exception $e) {
            // Check if it's a rate limit error (OpenAI specific)
            $message = $e->getMessage();
            $isRateLimit = str_contains(strtolower($message), 'rate_limit_exceeded') 
                        || (str_contains($message, 'status code 429') && str_contains(strtolower($message), 'too many requests'));
            
            if ($isRateLimit) {
                \Log::warning('AI rate limit hit', ['error' => substr($message, 0, 200)]);
                return response()->json([
                    'success' => false,
                    'message' => '⏳ AI rate limit reached. Please wait 1-2 minutes before trying again.'
                ], 429);
            }
            
            // Log actual error for debugging
            \Log::error('AI operation failed', [
                'error' => substr($message, 0, 500),
                'trace' => substr($e->getTraceAsString(), 0, 1000)
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }
    }

    /**
     * Rewrite existing content
     */
    public function rewrite(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'tone' => 'nullable|in:professional,casual,motivational,educational,storytelling',
            'mode' => 'nullable|in:shorten,expand'
        ]);

        try {
            $data = [
                'content' => $request->content,
                'tone' => $request->tone ?? 'professional',
                'mode' => $request->mode
            ];

            $chatGPT = new ChatGPT($data);
            $result = $chatGPT->rewritePost();

            return response()->json([
                'success' => true,
                'content' => $result['content'],
                'word_count' => $result['word_count'] ?? 0
            ]);

        } catch (\Exception $e) {
            // Check if it's a rate limit error (OpenAI specific)
            $message = $e->getMessage();
            $isRateLimit = str_contains(strtolower($message), 'rate_limit_exceeded') 
                        || (str_contains($message, 'status code 429') && str_contains(strtolower($message), 'too many requests'));
            
            if ($isRateLimit) {
                \Log::warning('AI rate limit hit', ['error' => substr($message, 0, 200)]);
                return response()->json([
                    'success' => false,
                    'message' => '⏳ AI rate limit reached. Please wait 1-2 minutes before trying again.'
                ], 429);
            }
            
            // Log actual error for debugging
            \Log::error('AI operation failed', [
                'error' => substr($message, 0, 500),
                'trace' => substr($e->getTraceAsString(), 0, 1000)
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }
    }

    /**
     * Get templates by category, industry, or specific template by ID
     */
    public function getTemplates(Request $request)
    {
        // 🔥 FIX: Handle template_id parameter for single template fetch
        $templateId = $request->query('template_id');
        
        if ($templateId) {
            $template = PostTemplate::active()->find($templateId);
            
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'templates' => [$template]
            ]);
        }
        
        // Handle category and industry filters
        $category = $request->query('category');
        $industry = $request->query('industry');
        
        $query = PostTemplate::active();
        
        if ($category) {
            $query->where('category', $category);
        }
        
        if ($industry) {
            $query->where('industry', $industry);
        }
        
        $templates = $query->orderBy('engagement_score', 'desc')->get();
        
        return response()->json(['templates' => $templates]);
    }

    /**
     * Ensure strings are valid UTF-8 to avoid JSON encoding errors.
     */
    private function ensureUtf8($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private function getUserTimezone(): string
    {
        $user = auth()->user();
        if (!$user || !$user->time_zone_id) {
            return 'UTC';
        }

        $timezone = Timezone::select('time_zone')->find($user->time_zone_id);
        return $timezone?->time_zone ?: 'UTC';
    }

    /**
     * Convert an HTML5 datetime-local string (no timezone info) from the user's
     * profile timezone into UTC for storage/scheduling.
     */
    private function parseUserDatetimeLocalToUtc(string $datetimeLocal): Carbon
    {
        $userTimezone = $this->getUserTimezone();
        return Carbon::parse($datetimeLocal, $userTimezone)->setTimezone('UTC');
    }

    /**
     * Schedule a post
     */
    public function schedule(Request $request, $id)
    {
        $post = LinkedInPost::where('user_id', auth()->id())->findOrFail($id);
        
        $request->validate([
            'scheduled_at' => 'required|date'
        ]);

        $scheduledAt = $this->parseUserDatetimeLocalToUtc($request->scheduled_at);
        if ($scheduledAt->lte(Carbon::now('UTC'))) {
            return response()->json([
                'success' => false,
                'message' => 'Scheduled time must be in the future.'
            ], 422);
        }

        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt
        ]);

        // Dispatch job for scheduling
        \App\Jobs\PublishLinkedInPost::dispatch($post)->delay($post->scheduled_at);

        return response()->json([
            'success' => true,
            'message' => 'Post scheduled successfully!'
        ]);
    }

    /**
     * Publish a post immediately
     */
    public function publish($id)
    {
        $post = LinkedInPost::where('user_id', auth()->id())->findOrFail($id);
        
        if ($post->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft posts can be published immediately!'
            ], 400);
        }

        $post->update([
            'status' => 'ready_to_publish',
            'scheduled_at' => now()
        ]);

        \App\Jobs\PublishLinkedInPost::dispatchSync($post);

        return response()->json([
            'success' => true,
            'message' => 'Post published successfully!'
        ]);
    }

    /**
     * Delete a post
     */
    public function destroy($id)
    {
        $post = LinkedInPost::where('user_id', auth()->id())->findOrFail($id);
        
        if ($post->status === 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete published posts!'
            ], 400);
        }

        $post->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully!'
        ]);
    }

    /**
     * Bulk delete posts
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'required|integer|exists:linkedin_posts,id'
        ]);

        $userId = auth()->id();
        $postIds = $request->post_ids;
        
        // Only delete posts that belong to the user and are not published
        $deletedCount = LinkedInPost::where('user_id', $userId)
            ->whereIn('id', $postIds)
            ->where('status', '!=', 'published')
            ->delete();

        $skippedCount = count($postIds) - $deletedCount;
        
        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} post(s)." . ($skippedCount > 0 ? " {$skippedCount} published post(s) were skipped." : ''),
            'deleted_count' => $deletedCount,
            'skipped_count' => $skippedCount
        ]);
    }

    /**
     * Get analytics for a post
     */
    public function analytics($id)
    {
        $post = LinkedInPost::where('user_id', auth()->id())->findOrFail($id);
        
        return response()->json([
            'post' => $post,
            'engagement' => $post->engagement,
            'engagement_rate' => $post->engagement_rate
        ]);
    }

    /**
     * API endpoint for extension to get scheduled posts
     */
    public function getScheduledPosts(Request $request)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $lkId = $request->header('lk-id');
        $user = \App\Models\User::where('linkedin_id', $lkId)->first();

        if (!$user) {
            return response()->json([
                "message" => "User not found",
                "status" => 404
            ], 404);
        }

        $posts = LinkedInPost::where('user_id', $user->id)
            ->where(function($query) {
                $query->where('status', 'scheduled')
                      ->where('scheduled_at', '<=', now());
            })
            ->orWhere('status', 'ready_to_publish')
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json([
            'data' => $posts,
            'status' => 200
        ]);
    }

    /**
     * API endpoint for extension to update post status
     */
    public function updatePostStatus(Request $request, $id)
    {
        try {
            $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ], 400);
        }

        $lkId = $request->header('lk-id');
        $user = \App\Models\User::where('linkedin_id', $lkId)->first();

        if (!$user) {
            return response()->json([
                "message" => "User not found",
                "status" => 404
            ], 404);
        }

        $post = LinkedInPost::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:published,failed',
            'linkedin_post_id' => 'nullable|string',
            'analytics' => 'nullable|array'
        ]);

        if ($request->status === 'published') {
            $post->markAsPublished($request->linkedin_post_id);
        } else {
            $post->markAsFailed();
        }

        if ($request->analytics) {
            $post->updateAnalytics($request->analytics);
        }

        return response()->json([
            'message' => 'Post status updated successfully',
            'status' => 200
        ]);
    }

    private function userHasLinkedIn(): bool
    {
        $userId = auth()->id();
        if (! $userId) {
            return false;
        }

        return V2IntegrationAccount::query()
            ->where('user_id', $userId)
            ->where('provider', 'linkedin')
            ->where('status', 'active')
            ->exists();
    }
}
