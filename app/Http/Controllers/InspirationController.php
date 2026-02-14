<?php

namespace App\Http\Controllers;

use App\Models\ViralPost;
use App\Models\UserContentPreference;
use App\Services\ChatGPT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InspirationController extends Controller
{
    /**
     * Display the inspiration library
     */
    public function index(Request $request)
    {
        $userId = auth()->user()->id;
        
        $query = ViralPost::where('user_id', $userId);
        
        // Filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }
        
        if ($request->filled('engagement')) {
            $query->highEngagement($request->engagement);
        }
        
        if ($request->filled('days')) {
            $query->recentDays($request->days);
        }
        
        if ($request->filled('favorite')) {
            $query->favorites();
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('content', 'like', '%' . $request->search . '%')
                  ->orWhere('author_name', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }
        
        $posts = $query->orderBy('engagement_rate', 'desc')
                      ->orderBy('saved_at', 'desc')
                      ->paginate(12);
        
        // Get statistics - calculate from actual saved posts in database
        $avgEngagement = ViralPost::where('user_id', $userId)
            ->whereNotNull('engagement_rate')
            ->where('engagement_rate', '>', 0)
            ->avg('engagement_rate');
        
        $stats = [
            'total_posts' => ViralPost::where('user_id', $userId)->count(),
            'favorites' => ViralPost::where('user_id', $userId)->favorites()->count(),
            'viral_posts' => ViralPost::where('user_id', $userId)->viral()->count(),
            'avg_engagement' => $avgEngagement ? round($avgEngagement, 1) : 0,
        ];
        
        // Get or create default preferences
        $preferences = auth()->user()->contentPreferences;
        if (!$preferences) {
            $preferences = UserContentPreference::make(UserContentPreference::getDefaults());
        }
        
        return view('inspiration.index', compact('posts', 'stats', 'preferences'));
    }
    
    /**
     * Update user content preferences
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'industries' => 'nullable|array',
            'topics' => 'nullable|array',
            'min_engagement' => 'required|integer|min:50|max:10000',
            'date_range' => 'required|in:past-24-hours,past-week,past-month,past-year,any-time',
            'smart_fetch' => 'nullable|boolean',
        ]);

        $preferences = auth()->user()->contentPreferences()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                // Industries are now optional – default to empty array if not provided
                'industries' => $validated['industries'] ?? [],
                'topics' => $validated['topics'] ?? [],
                'min_engagement' => $validated['min_engagement'],
                'date_range' => $validated['date_range'],
                'smart_fetch' => $request->boolean('smart_fetch'),
                'fetch_from_keywords' => true,
            ]
        );
        
        // Set initial fetch status to pending
        $fetchMeta = json_decode($preferences->fetch_meta ?? '{}', true);
        $fetchMeta['fetch_status'] = 'pending';
        $fetchMeta['fetch_started_at'] = now()->toIso8601String();
        $fetchMeta['fetch_progress'] = 'Job queued...';
        $preferences->fetch_meta = json_encode($fetchMeta);
        $preferences->save();
        
        // Dispatch job to background queue
        try {
            \App\Jobs\FetchInspirationPostsJob::dispatch(
                auth()->id(),
                50, // limit
                5   // keywords
            )->onQueue('default');
            
            Log::info('Dispatched FetchInspirationPostsJob for user', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name
            ]);
            
            return redirect()->back()->with('success', 'Preferences saved! Fetching posts in the background...');
        } catch (\Exception $e) {
            Log::error('Failed to dispatch fetch job', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('success', 'Preferences saved! However, there was an issue starting the fetch. Please try again.')
                              ->with('warning', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Trigger fetch manually (API endpoint)
     */
    public function triggerFetch(Request $request)
    {
        $user = auth()->user();
        
        $preferences = $user->contentPreferences;
        if (!$preferences) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please set your preferences first'
            ], 400);
        }
        
        // Set initial fetch status to pending
        $fetchMeta = json_decode($preferences->fetch_meta ?? '{}', true);
        $fetchMeta['fetch_status'] = 'pending';
        $fetchMeta['fetch_started_at'] = now()->toIso8601String();
        $fetchMeta['fetch_progress'] = 'Job queued...';
        $preferences->fetch_meta = json_encode($fetchMeta);
        $preferences->save();
        
        // Dispatch job
        try {
            \App\Jobs\FetchInspirationPostsJob::dispatch(
                $user->id,
                50,
                5
            )->onQueue('default');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Fetch started in background'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch fetch job', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start fetch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fetch status (API endpoint)
     */
    public function getFetchStatus(Request $request)
    {
        $preferences = auth()->user()->contentPreferences;
        
        if (!$preferences) {
            return response()->json([
                'status' => null,
                'progress' => null
            ]);
        }
        
        $fetchMeta = json_decode($preferences->fetch_meta ?? '{}', true);
        
        // If request wants to clear status (after showing completion)
        if ($request->has('clear') && $request->boolean('clear')) {
            $fetchMeta['fetch_status'] = null;
            $fetchMeta['fetch_progress'] = null;
            $preferences->fetch_meta = json_encode($fetchMeta);
            $preferences->save();
            
            return response()->json([
                'status' => null,
                'progress' => null,
                'cleared' => true
            ]);
        }
        
        return response()->json([
            'status' => $fetchMeta['fetch_status'] ?? null,
            'progress' => $fetchMeta['fetch_progress'] ?? null,
            'fetch_started_at' => $fetchMeta['fetch_started_at'] ?? null,
            'fetch_completed_at' => $fetchMeta['fetch_completed_at'] ?? null,
            'fetch_failed_at' => $fetchMeta['fetch_failed_at'] ?? null,
            'new_posts' => $fetchMeta['new_posts'] ?? null,
            'total_fetched' => $fetchMeta['total_fetched'] ?? null
        ]);
    }

    /**
     * Save a viral post from Chrome extension
     */
    public function store(Request $request)
    {
        $request->validate([
            'author_name' => 'required|string',
            'content' => 'required|string',
            'likes' => 'nullable|integer',
            'comments' => 'nullable|integer',
            'shares' => 'nullable|integer',
            'views' => 'nullable|integer',
            'post_url' => 'nullable|url',
            'linkedin_post_id' => 'nullable|string',
            'author_headline' => 'nullable|string',
            'author_profile_url' => 'nullable|url',
            'author_image_url' => 'nullable|url',
            'post_type' => 'nullable|in:text,image,carousel,video,article',
            'images' => 'nullable|array',
            'category' => 'nullable|string'
        ]);

        $likes = $request->likes ?? 0;
        $comments = $request->comments ?? 0;
        $shares = $request->shares ?? 0;
        $views = $request->views ?? 0;

        // Calculate engagement rate
        $engagementRate = ViralPost::calculateEngagementRate($likes, $comments, $shares, $views);

        // Auto-categorize based on content keywords
        $category = $request->category ?? $this->autoCategorize($request->content);

        // Extract tags/hashtags
        preg_match_all('/#\w+/', $request->content, $matches);
        $tags = $matches[0] ?? [];

        $viralPost = ViralPost::create([
            'user_id' => auth()->id(),
            'author_name' => $request->author_name,
            'author_headline' => $request->author_headline,
            'author_profile_url' => $request->author_profile_url,
            'author_image_url' => $request->author_image_url,
            'content' => $request->content,
            'post_url' => $request->post_url,
            'linkedin_post_id' => $request->linkedin_post_id,
            'likes' => $likes,
            'comments' => $comments,
            'shares' => $shares,
            'views' => $views,
            'engagement_rate' => $engagementRate,
            'post_type' => $request->post_type ?? 'text',
            'images' => $request->images,
            'video_url' => $request->video_url,
            'post_date' => $request->post_date ?? now(),
            'category' => $category,
            'tags' => $tags,
            'saved_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Viral post saved to your inspiration library!',
            'post' => $viralPost
        ]);
    }

    /**
     * Save viral post from web interface
     */
    public function storeFromWeb(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Delete a viral post
     */
    public function destroy($id)
    {
        $post = ViralPost::where('user_id', auth()->id())->findOrFail($id);
        $post->delete();

        notify()->success('Post removed from inspiration library');
        return redirect()->route('inspiration.index');
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite($id)
    {
        $post = ViralPost::where('user_id', auth()->id())->findOrFail($id);
        $post->toggleFavorite();

        return response()->json([
            'success' => true,
            'is_favorite' => $post->is_favorite
        ]);
    }

    /**
     * Use viral post as inspiration (load into Content Creator)
     */
    public function useAsInspiration($id)
    {
        $post = ViralPost::where('user_id', auth()->id())->findOrFail($id);
        $chatGPT = new ChatGPT();
        $safeContent = $this->ensureUtf8($post->content);
        $formatted = $chatGPT->formatPost($safeContent);

        return response()->json([
            'success' => true,
            'content' => $this->ensureUtf8($formatted),
            'author' => $post->author_name,
            'engagement' => [
                'likes' => $post->likes,
                'comments' => $post->comments,
                'shares' => $post->shares,
                'rate' => $post->engagement_rate
            ]
        ]);
    }

    /**
     * Remix post with AI (rewrite in user's voice)
     */
    public function remix(Request $request, $id)
    {
        $request->validate([
            'tone' => 'required|in:professional,casual,motivational,educational,storytelling'
        ]);

        $post = ViralPost::where('user_id', auth()->id())->findOrFail($id);

        try {
            $data = [
                'content' => $this->ensureUtf8($post->content),
                'tone' => $request->tone
            ];

            $chatGPT = new ChatGPT($data);
            $result = $chatGPT->rewritePost();

            return response()->json([
                'success' => true,
                'content' => $this->ensureUtf8($result['content']),
                'word_count' => $result['word_count'],
                'original_author' => $post->author_name,
                'original_engagement' => $post->engagement_rate
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get categories for filtering
     */
    public function getCategories()
    {
        return ViralPost::where('user_id', auth()->id())
                       ->distinct()
                       ->pluck('category')
                       ->filter()
                       ->values();
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

    /**
     * Auto-categorize based on content
     */
    private function autoCategorize($content)
    {
        $content = strtolower($content);
        
        $keywords = [
            'marketing' => ['marketing', 'campaign', 'brand', 'advertising', 'seo', 'content'],
            'sales' => ['sales', 'revenue', 'closing', 'prospect', 'pipeline', 'deal'],
            'tech' => ['tech', 'software', 'coding', 'developer', 'ai', 'programming'],
            'entrepreneurship' => ['startup', 'founder', 'business', 'entrepreneur', 'venture'],
            'productivity' => ['productivity', 'time', 'efficient', 'organize', 'workflow'],
            'leadership' => ['leadership', 'team', 'management', 'culture', 'leader'],
        ];

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($content, $word)) {
                    return $category;
                }
            }
        }

        return 'general';
    }
}
