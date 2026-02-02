<?php

namespace App\Http\Controllers;

use App\Models\LinkedInPost;
use App\Models\PostTemplate;
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
        
        return view('content-creator.index', compact('posts', 'stats', 'status'));
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
        
        return view('content-creator.create', compact('templates', 'categories', 'industries'));
    }

    /**
     * Store a newly created post
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:3000',
            'post_type' => 'required|in:text,image,video',
            'scheduled_at' => 'nullable|date|after:now',
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

        // Initialize Cloudinary service
        $cloudinaryService = new LinkedInContentService();

        // Handle multiple images upload (for image post type - 1 or more images)
        if ($request->post_type === 'image' && $request->hasFile('images')) {
            $imageUrls = $cloudinaryService->uploadCarouselImages($request->file('images'));
        }

        // Handle video upload (only for video post type)
        if ($request->post_type === 'video' && $request->hasFile('video')) {
            $videoUrl = $cloudinaryService->uploadVideo($request->file('video'));
        }

        // Determine status based on publish option
        $status = 'draft';
        $scheduledAt = null;

        if ($request->publish_option === 'now') {
            $status = 'ready_to_publish';
            $scheduledAt = now();
        } elseif ($request->publish_option === 'schedule' && $request->scheduled_at) {
            $status = 'scheduled';
            // Parse the datetime and assume it's in UTC (since datetime-local doesn't include timezone)
            // If user has a timezone setting, we should convert it
            $scheduledAt = Carbon::parse($request->scheduled_at, 'UTC');
            
            \Log::info('📅 Scheduling post', [
                'input_time' => $request->scheduled_at,
                'parsed_utc' => $scheduledAt->toDateTimeString(),
                'server_time' => Carbon::now()->toDateTimeString()
            ]);
        }

        \Log::info('📝 Creating new LinkedIn post', [
            'user_id' => auth()->id(),
            'post_type' => $request->post_type,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'has_images' => !empty($imageUrls),
            'has_video' => !empty($videoUrl),
            'content_length' => strlen($request->content)
        ]);

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

        \Log::info('✅ Post created in database', [
            'post_id' => $post->id,
            'status' => $post->status,
            'scheduled_at' => $post->scheduled_at
        ]);

        if ($status === 'scheduled') {
            \Log::info('📅 Dispatching scheduled job', [
                'post_id' => $post->id,
                'delay_until' => $scheduledAt,
                'queue_driver' => config('queue.default')
            ]);
            // Dispatch job for scheduling
            \App\Jobs\PublishLinkedInPost::dispatch($post)->delay($scheduledAt);
        } elseif ($status === 'ready_to_publish') {
            \Log::info('🚀 Dispatching IMMEDIATE publish job', [
                'post_id' => $post->id,
                'user_id' => auth()->id(),
                'linkedin_id' => auth()->user()->linkedin_id ?? 'not_set',
                'queue_driver' => config('queue.default'),
                'queue_connection' => config('queue.connections.database')
            ]);
            
            // For immediate publishing, use dispatchSync to run immediately
            // This ensures the job runs right away without needing queue worker
            \Log::info('⚡ Using dispatchSync for immediate execution');
            \App\Jobs\PublishLinkedInPost::dispatchSync($post);
            
            \Log::info('✅ Job completed for post_id: ' . $post->id);
        }

        notify()->success('Post saved successfully!');
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
                
                return response()->json([
                    'success' => true,
                    'drafts' => $drafts
                ]);
            } else {
                // Single draft (backward compatibility)
                $result = $chatGPT->generateLinkedInPost();

                return response()->json([
                    'success' => true,
                    'content' => $result['content'],
                    'hashtags' => $result['hashtags'] ?? '',
                    'word_count' => $result['word_count'] ?? 0
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
     * Schedule a post
     */
    public function schedule(Request $request, $id)
    {
        $post = LinkedInPost::where('user_id', auth()->id())->findOrFail($id);
        
        $request->validate([
            'scheduled_at' => 'required|date|after:now'
        ]);

        // Parse the datetime and assume it's in UTC (since datetime-local doesn't include timezone)
        $scheduledAt = Carbon::parse($request->scheduled_at, 'UTC');
        
        \Log::info('📅 Rescheduling post', [
            'post_id' => $id,
            'input_time' => $request->scheduled_at,
            'parsed_utc' => $scheduledAt->toDateTimeString(),
            'server_time' => Carbon::now()->toDateTimeString()
        ]);

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

        // Log that a post is being published immediately
        \Log::info('🚀 Publishing draft post immediately', [
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'linkedin_id' => auth()->user()->linkedin_id,
            'content_preview' => substr($post->content, 0, 100) . '...'
        ]);

        // Dispatch job SYNCHRONOUSLY (no queue worker needed)
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

        \Log::info('🔍 Extension requested scheduled posts', [
            'user_id' => $user->id,
            'linkedin_id' => $lkId,
            'posts_found' => $posts->count(),
            'posts' => $posts->pluck('id', 'content')
        ]);

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
}
