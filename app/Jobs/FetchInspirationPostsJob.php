<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserContentPreference;
use App\Models\ViralPost;
use App\Services\RapidApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchInspirationPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'default';

    public int $userId;
    public int $limit;
    public int $keywords;

    public function __construct(int $userId, int $limit = 50, int $keywords = 5)
    {
        $this->userId = $userId;
        $this->limit = $limit;
        $this->keywords = $keywords;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            Log::warning('FetchInspirationPostsJob: User not found', ['user_id' => $this->userId]);
            return;
        }

        // Update status to processing
        $this->updateFetchStatus('processing', 'Starting fetch...');

        Log::info('🚀 FetchInspirationPostsJob: Started fetching inspiration posts', [
            'user_id' => $this->userId,
            'limit' => $this->limit,
            'keywords' => $this->keywords
        ]);

        try {
            $rapidapi_service = new RapidApiService;
            $preferences = $user->contentPreferences ?? UserContentPreference::make(UserContentPreference::getDefaults());
            
            // Get posts count before fetching
            $postsBefore = ViralPost::where('user_id', $this->userId)->count();
            
            $totalFetched = 0;
            
            // Fetch from keywords (industries + topics + custom keywords)
            if ($preferences->fetch_from_keywords ?? true) {
                $this->updateFetchStatus('processing', 'Fetching posts from keywords...');
                $totalFetched += $this->fetchFromUserKeywords($user, $preferences, $rapidapi_service);
            }
            
            // Fetch from user's favorite creators (if enabled)
            if ($preferences->fetch_from_creators && !empty($preferences->favorite_creators)) {
                $this->updateFetchStatus('processing', 'Fetching posts from favorite creators...');
                $totalFetched += $this->fetchFromUserCreators($user, $preferences, $rapidapi_service);
            }
            
            // Get posts count after fetching
            $postsAfter = ViralPost::where('user_id', $this->userId)->count();
            $newPosts = $postsAfter - $postsBefore;
            
            // Update status to completed
            $this->updateFetchStatus('completed', "Fetched {$newPosts} new posts", [
                'total_fetched' => $totalFetched,
                'new_posts' => $newPosts,
                'posts_before' => $postsBefore,
                'posts_after' => $postsAfter
            ]);
            
            Log::info('✅ FetchInspirationPostsJob: Completed successfully', [
                'user_id' => $this->userId,
                'new_posts' => $newPosts,
                'total_fetched' => $totalFetched
            ]);
            
        } catch (\Throwable $th) {
            Log::error('❌ FetchInspirationPostsJob: Failed', [
                'user_id' => $this->userId,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            
            $this->updateFetchStatus('failed', 'Fetch failed: ' . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Fetch posts from user keywords
     */
    private function fetchFromUserKeywords($user, $preferences, $rapidapi_service)
    {
        $keywords = $preferences->getAllKeywords();
        
        if (empty($keywords)) {
            // Use default keywords if none provided
            $keywords = ['entrepreneurship', 'leadership', 'marketing', 'technology', 'business'];
        }
        
        // Limit keywords to avoid too many API calls
        $keywords = array_slice($keywords, 0, $this->keywords);
        
        $effectiveMinEngagement = $preferences->min_engagement ?? 100;
        if ($preferences->smart_fetch ?? false) {
            $effectiveMinEngagement = max(50, (int) floor($effectiveMinEngagement / 2));
        }
        
        $dateRange = $preferences->date_range ?? 'past-month';
        
        $totalFetched = 0;
        $postsPerKeyword = ceil($this->limit / count($keywords));
        
        foreach ($keywords as $keyword) {
            $this->updateFetchStatus('processing', "Searching for: {$keyword}...");
            
            $fetched = $this->searchKeywordsWithMultiplePages(
                [$keyword],
                $user->id,
                $effectiveMinEngagement,
                $dateRange,
                $rapidapi_service,
                $postsPerKeyword
            );
            
            $totalFetched += $fetched;
        }
        
        return $totalFetched;
    }

    /**
     * Fetch posts from user's favorite creators
     */
    private function fetchFromUserCreators($user, $preferences, $rapidapi_service)
    {
        // This would need to be implemented based on your RapidAPI service
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Search keywords with multiple pages
     */
    private function searchKeywordsWithMultiplePages($keywords, $userId, $minEngagement, $dateRange, $rapidapi_service, $maxPosts)
    {
        $totalFetched = 0;
        $pagesPerKeyword = 2; // Search 2 pages per keyword
        
        $limitedKeywords = array_slice($keywords, 0, $this->keywords);
        
        foreach ($limitedKeywords as $keyword) {
            if ($totalFetched >= $maxPosts) {
                break;
            }
            
            try {
                for ($page = 1; $page <= $pagesPerKeyword; $page++) {
                    if ($totalFetched >= $maxPosts) break;
                    
                    $filters = [
                        'min_likes' => $minEngagement,
                        'limit' => 100
                    ];
                    
                    $response = $rapidapi_service->search_posts($keyword, $page, $dateRange, $filters);
                    
                    if (isset($response['data']) && is_array($response['data'])) {
                        foreach ($response['data'] as $postData) {
                            if ($totalFetched >= $maxPosts) break;
                            
                            $post = $postData['post'] ?? $postData;
                            
                            // Check duplicate
                            $existingPost = ViralPost::where('user_id', $userId)
                                ->where(function($query) use ($post) {
                                    $query->where('linkedin_post_id', $post['urn'] ?? null)
                                          ->orWhere('post_url', $post['post_url'] ?? null);
                                })
                                ->first();
                                
                            if ($existingPost) continue;
                            
                            // Check date range
                            if (!$this->postWithinDateRange($post, $dateRange)) {
                                continue;
                            }
                            
                            // Check threshold
                            if ($this->meetsThreshold($post, $minEngagement)) {
                                $this->saveViralPost($post, $userId);
                                $totalFetched++;
                            }
                        }
                    }
                    
                    sleep(1); // Rate limiting
                }
            } catch (\Exception $e) {
                Log::error("Error with keyword '{$keyword}': " . $e->getMessage());
            }
        }
        
        return $totalFetched;
    }
    
    private function postWithinDateRange($post, ?string $dateRange): bool
    {
        if (empty($dateRange) || $dateRange === 'any-time') {
            return true;
        }
        
        $postedAt = is_array($post) ? ($post['posted'] ?? ($post['post']['posted'] ?? null)) : ($post->posted ?? null);
        
        if (!$postedAt) {
            return false;
        }
        
        try {
            $postDate = Carbon::parse($postedAt);
        } catch (\Throwable $th) {
            return false;
        }
        
        $cutoff = match ($dateRange) {
            'past-24-hours' => now()->subDay(),
            'past-week' => now()->subWeek(),
            'past-month' => now()->subMonth(),
            'past-year' => now()->subYear(),
            default => null,
        };
        
        return $cutoff === null || $postDate->greaterThanOrEqualTo($cutoff);
    }
    
    private function meetsThreshold($post, $minEngagement = 100)
    {
        $likes = $post['num_likes'] ?? 0;
        $comments = $post['num_comments'] ?? 0;
        $shares = $post['num_shares'] ?? 0;
        $views = $post['num_views'] ?? 0;
        
        $totalEngagement = $likes + $comments + $shares;
        $engagementRate = $views > 0 ? ($totalEngagement / $views) * 100 : 0;
        
        return $likes >= $minEngagement 
            || $engagementRate >= 5.0
            || $comments >= max(20, $minEngagement / 5)
            || $shares >= max(10, $minEngagement / 10)
            || ($totalEngagement >= $minEngagement && $engagementRate >= 3.0);
    }
    
    private function saveViralPost($post, $userId)
    {
        $postType = $post['post_type'] ?? 'text';
        $allowedTypes = ['text', 'image', 'carousel', 'video', 'article'];
        
        if (!in_array($postType, $allowedTypes)) {
            if (!empty($post['video'])) {
                $postType = 'video';
            } elseif (!empty($post['images']) && count($post['images']) > 1) {
                $postType = 'carousel';
            } elseif (!empty($post['images'])) {
                $postType = 'image';
            } else {
                $postType = 'text';
            }
        }
        
        $authorName = 'Unknown';
        if (isset($post['poster_name'])) {
            $authorName = $post['poster_name'];
        } elseif (isset($post['poster']['first']) && isset($post['poster']['last'])) {
            $authorName = $post['poster']['first'] . ' ' . $post['poster']['last'];
        }
        
        try {
            ViralPost::updateOrCreate(
                [
                    'user_id' => $userId,
                    'linkedin_post_id' => $post['urn'] ?? ''
                ],
                [
                    'author_name' => $authorName,
                    'author_headline' => $post['poster_title'] ?? $post['poster']['headline'] ?? '',
                    'author_profile_url' => $post['poster_linkedin_url'] ?? '',
                    'content' => $post['text'] ?? '',
                    'post_url' => $post['post_url'] ?? '',
                    'likes' => $post['num_likes'] ?? 0,
                    'comments' => $post['num_comments'] ?? 0,
                    'shares' => $post['num_shares'] ?? 0,
                    'views' => $post['num_views'] ?? 0,
                    'engagement_rate' => $this->calculateEngagementRate($post),
                    'post_type' => $postType,
                    'post_date' => $post['posted'] ?? now(),
                    'category' => $this->autoCategorize($post['text'] ?? ''),
                    'saved_at' => now()
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return;
            }
            throw $e;
        }
    }
    
    private function calculateEngagementRate($post)
    {
        $likes = $post['num_likes'] ?? 0;
        $comments = $post['num_comments'] ?? 0;
        $shares = $post['num_shares'] ?? 0;
        $views = $post['num_views'] ?? 0;
        
        $totalEngagement = $likes + $comments + $shares;
        
        return $views > 0 ? round(($totalEngagement / $views) * 100, 2) : 0;
    }
    
    private function autoCategorize($content)
    {
        $content = strtolower($content);
        
        if (str_contains($content, 'entrepreneur') || str_contains($content, 'startup') || str_contains($content, 'business')) {
            return 'Business';
        } elseif (str_contains($content, 'leadership') || str_contains($content, 'management') || str_contains($content, 'team')) {
            return 'Leadership';
        } elseif (str_contains($content, 'marketing') || str_contains($content, 'sales') || str_contains($content, 'brand')) {
            return 'Marketing';
        } elseif (str_contains($content, 'career') || str_contains($content, 'job') || str_contains($content, 'work')) {
            return 'Career';
        } elseif (str_contains($content, 'motivation') || str_contains($content, 'inspiration') || str_contains($content, 'success')) {
            return 'Motivation';
        } else {
            return 'General';
        }
    }

    /**
     * Update fetch status in user preferences
     */
    private function updateFetchStatus($status, $progress = null, $metadata = [])
    {
        $preferences = UserContentPreference::where('user_id', $this->userId)->first();
        
        if (!$preferences) {
            // Create preferences if they don't exist
            $preferences = UserContentPreference::create([
                'user_id' => $this->userId,
                'min_engagement' => 100,
                'date_range' => 'past-month',
                'fetch_from_keywords' => true,
            ]);
        }
        
        // Store fetch status in a JSON field (we'll add this to the model)
        $fetchMeta = json_decode($preferences->fetch_meta ?? '{}', true);
        $fetchMeta['fetch_status'] = $status;
        $fetchMeta['fetch_progress'] = $progress;
        $fetchMeta['fetch_updated_at'] = now()->toIso8601String();
        
        if ($status === 'completed') {
            $fetchMeta['fetch_completed_at'] = now()->toIso8601String();
        } elseif ($status === 'failed') {
            $fetchMeta['fetch_failed_at'] = now()->toIso8601String();
        }
        
        // Merge any additional metadata
        if (!empty($metadata)) {
            $fetchMeta = array_merge($fetchMeta, $metadata);
        }
        
        $preferences->fetch_meta = json_encode($fetchMeta);
        $preferences->save();
    }
}
