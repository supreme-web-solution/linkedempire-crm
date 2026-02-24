<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PhantomBusterService
{
    private string $apiKey;
    private string $apiUrl;
    private ?string $sessionCookieOverride = null;
    private ?string $userAgentOverride = null;

    public function __construct()
    {
        $this->apiKey = config('services.phantombuster.api_key');
        $this->apiUrl = config('services.phantombuster.api_url', 'https://api.phantombuster.com/api/v1');

        if (!$this->apiKey) {
            Log::error("PHANTOMBUSTER_API_KEY not found in environment variables");
            throw new \Exception("PHANTOMBUSTER_API_KEY not configured");
        }
    }

    /**
     * Launch a Phantom with given parameters
     *
     * @param string $phantomId The Phantom ID to launch
     * @param array $arguments Arguments to pass to the Phantom
     * @return array Response with containerId
     */
    public function launchPhantom(string $phantomId, array $arguments = []): array
    {
        // Note: Lock management is handled at a higher level (in scrapeLinkedInProfile, etc.)
        // This method just performs the API call without locking
        $url = "{$this->apiUrl}/agent/{$phantomId}/launch";

        $payload = [];
        if (!empty($arguments)) {
            $payload['argument'] = $arguments;
        }

        // Log removed to reduce verbosity - only log errors

        try {
                $response = Http::timeout(30) // 30 seconds timeout
                    ->connectTimeout(15) // 15 seconds for DNS/connection
                    ->withHeaders([
                        'X-Phantombuster-Key-1' => $this->apiKey,
                        'Content-Type' => 'application/json'
                    ])->post($url, $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Handle network/DNS errors
                $errorMessage = $e->getMessage();
                
                if (str_contains($errorMessage, 'Resolving timed out') || str_contains($errorMessage, 'cURL error 28')) {
                    Log::error("PhantomBuster: DNS resolution timeout", [
                        'phantom_id' => $phantomId,
                        'url' => $url,
                        'error' => $errorMessage
                    ]);
                    
                    throw new \Exception(
                        'NETWORK_TIMEOUT: Cannot connect to PhantomBuster API. ' .
                        'This is usually a network connectivity issue. ' .
                        'Please check your internet connection and try again. ' .
                        'If the problem persists, your server may be blocking external API connections.'
                    );
                }
                
                if (str_contains($errorMessage, 'Connection timed out') || str_contains($errorMessage, 'cURL error 7')) {
                    Log::error("PhantomBuster: Connection timeout", [
                        'phantom_id' => $phantomId,
                        'url' => $url,
                        'error' => $errorMessage
                    ]);
                    
                    throw new \Exception(
                        'NETWORK_TIMEOUT: Connection to PhantomBuster API timed out. ' .
                        'The API may be temporarily unavailable or your network is slow. ' .
                        'Please try again in a few moments.'
                    );
                }
                
                // Re-throw other connection errors
                throw $e;
            }

            if ($response->failed()) {
                $status = $response->status();
                $errorBody = $response->json();
                $errorMessage = is_array($errorBody) ? ($errorBody['error'] ?? json_encode($errorBody)) : $response->body();
                
                // Handle rate limiting (429) - parallel execution limit
                if ($status === 429) {
                    $detailedError = is_array($errorBody) && isset($errorBody['details']['detailedErrorSlug']) 
                        ? $errorBody['details']['detailedErrorSlug'] 
                        : null;
                    
                    $helpfulError = "PhantomBuster rate limit reached (429). ";
                    if ($detailedError === 'maxParallelismReached') {
                        $helpfulError .= "Your account allows only 1 parallel execution. Wait for running phantoms to finish, or upgrade your plan for more parallel executions.";
                    } else {
                        $helpfulError .= "Too many requests. Please wait before retrying.";
                    }
                    $helpfulError .= " Original error: {$errorMessage}";
                    
                    Log::error("PhantomBuster launch failed - Rate limit", [
                        'status' => $status,
                        'body' => $errorBody,
                        'phantom_id' => $phantomId,
                        'detailed_error' => $detailedError
                    ]);
                    
                    throw new \Exception($helpfulError);
                }
                
                // Provide helpful error message for "Agent not found"
                if ($status === 400 && str_contains(strtolower($errorMessage), 'agent not found')) {
                    $helpfulError = "Phantom ID '{$phantomId}' not found in your workspace.\n\n";
                    $helpfulError .= "This usually means the agent is not added to your workspace yet.\n";
                    $helpfulError .= "1. Go to https://phantombuster.com/phantoms and add the phantom you intend to run.\n";
                    $helpfulError .= "2. Use the instance ID from your workspace URL (the template/gallery ID will not work).\n\n";
                    $helpfulError .= "Original error: {$errorMessage}";
                    
                    Log::error("PhantomBuster launch failed - Agent not found", [
                        'status' => $status,
                        'body' => $errorBody,
                        'phantom_id' => $phantomId,
                        'helpful_message' => $helpfulError
                    ]);
                    
                    throw new \Exception($helpfulError);
                }
                
                Log::error("PhantomBuster launch failed:", [
                    'status' => $status,
                    'body' => $errorBody,
                    'phantom_id' => $phantomId
                ]);
                $response->throw();
            }

            $data = $response->json();
            
            // Log removed to reduce verbosity
            
            // Try different possible field names for container ID
            // First check nested structure (most common format)
            $containerId = null;
            if (isset($data['data']) && is_array($data['data'])) {
                $containerId = $data['data']['containerId'] 
                    ?? $data['data']['container_id'] 
                    ?? $data['data']['id'] 
                    ?? null;
            }
            
            // Fallback to top-level keys
            if (!$containerId) {
                $containerId = $data['containerId'] 
                    ?? $data['container_id'] 
                    ?? $data['id'] 
                    ?? $data['outputId']
                    ?? null;
            }
            
            // Log removed to reduce verbosity - only log errors
            
            // Add containerId to response if we found it
            if ($containerId) {
                $data['containerId'] = $containerId;
            } else {
                Log::error('PhantomBuster: Could not extract containerId from launch response', [
                    'phantom_id' => $phantomId,
                    'full_response' => $data
                ]);
                throw new \Exception("Failed to extract container ID from PhantomBuster launch response");
            }

        return $data;
    }

    /**
     * Get the output/results from a completed Phantom execution
     *
     * @param string $phantomId The Phantom ID
     * @param string $containerId The container ID from launch response
     * @return array Output data
     */
    public function getPhantomOutput(string $phantomId, string $containerId): array
    {
        $url = "{$this->apiUrl}/agent/{$phantomId}/output";

        try {
            $response = Http::timeout(30) // 30 seconds timeout
                ->connectTimeout(15) // 15 seconds for DNS/connection
                ->withHeaders([
                    'X-Phantombuster-Key-1' => $this->apiKey
                ])->get($url, [
                    'containerId' => $containerId
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMessage = $e->getMessage();
            
            if (str_contains($errorMessage, 'Resolving timed out') || str_contains($errorMessage, 'cURL error 28')) {
                Log::error("PhantomBuster: DNS resolution timeout when fetching output", [
                    'phantom_id' => $phantomId,
                    'container_id' => $containerId,
                    'url' => $url,
                    'error' => $errorMessage
                ]);
                
                throw new \Exception(
                    'NETWORK_TIMEOUT: Cannot connect to PhantomBuster API. ' .
                    'Network connectivity issue detected. Please check your internet connection.'
                );
            }
            
            throw $e;
        }

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            
            Log::error("PhantomBuster output fetch failed:", [
                'status' => $status,
                'body' => $body,
                'phantom_id' => $phantomId,
                'container_id' => $containerId
            ]);
            
            // Handle 404 gracefully - container may not be ready yet or may have expired
            if ($status === 404) {
                $errorData = json_decode($body, true);
                $errorMessage = $errorData['error'] ?? 'Container not found';
                throw new \Exception("Container not found (404): {$errorMessage}. Container may not be ready yet or may have expired.");
            }
            
            $response->throw();
        }

        $data = $response->json();
        
        // PhantomBuster API returns data in different structures
        // Try to find the actual output array
        $outputData = $data['output'] ?? $data['data'] ?? $data;
        
        // If data is nested, try to extract it
        if (isset($data['data']) && is_array($data['data'])) {
            // Check if data contains output
            if (isset($data['data']['output'])) {
                $outputData = $data['data']['output'];
            } elseif (isset($data['data']['data'])) {
                $outputData = $data['data']['data'];
            } else {
                // data might be the output array itself
                $outputData = $data['data'];
            }
        }
        
        // Normalize to always have 'output' key
        if (!isset($data['output'])) {
            $data['output'] = is_array($outputData) ? $outputData : [];
        }
        
        return $data;
    }

    /**
     * Check the status of a running Phantom
     *
     * @param string $phantomId The Phantom ID
     * @param string $containerId The container ID
     * @return array Status information
     */
    public function getPhantomStatus(string $phantomId, string $containerId): array
    {
        // Try to get output - if it's ready, we'll get data; if not, we'll get status info
        try {
            $output = $this->getPhantomOutput($phantomId, $containerId);
            // If we got here, phantom might be finished or still running
            // Check if output has status field
            if (isset($output['status'])) {
                return $output;
            }
            // If we have output data, consider it finished
            if (isset($output['output'])) {
                return ['status' => 'finished', 'output' => $output['output']];
            }
            return ['status' => 'running'];
        } catch (\Exception $e) {
            // If output fetch fails, phantom might still be running
            Log::debug('PhantomBuster: Status check - output not ready yet', [
                'container_id' => $containerId,
                'error' => $e->getMessage()
            ]);
            return ['status' => 'running'];
        }
    }

    /**
     * List all available phantoms for the authenticated user
     *
     * @return array List of phantoms with their IDs and names
     */
    public function listPhantoms(): array
    {
        // Note: This endpoint may not be available in all PhantomBuster API versions
        // It's better to configure phantom IDs directly in .env instead of using this
        $url = "{$this->apiUrl}/agents/fetch-all";

        Log::info('PhantomBuster: Fetching list of phantoms');

        try {
            $response = Http::timeout(30) // 30 seconds timeout
                ->connectTimeout(15) // 15 seconds for DNS/connection
                ->withHeaders([
                    'X-Phantombuster-Key-1' => $this->apiKey
                ])->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMessage = $e->getMessage();
            
            if (str_contains($errorMessage, 'Resolving timed out') || str_contains($errorMessage, 'cURL error 28')) {
                Log::error("PhantomBuster: DNS resolution timeout when listing phantoms", [
                    'url' => $url,
                    'error' => $errorMessage
                ]);
                
                throw new \Exception(
                    'NETWORK_TIMEOUT: Cannot connect to PhantomBuster API. ' .
                    'Network connectivity issue detected. Please check your internet connection.'
                );
            }
            
            throw $e;
        }

        if ($response->failed()) {
            // If v2 fails, try v1 endpoint
            if (str_contains($this->apiUrl, '/api/v2')) {
                Log::info('PhantomBuster: v2 endpoint failed, trying v1');
                $url = str_replace('/api/v2', '/api/v1', $this->apiUrl) . '/agents/fetch-all';
                $response = Http::timeout(30)
                    ->connectTimeout(15)
                    ->withHeaders([
                        'X-Phantombuster-Key-1' => $this->apiKey
                    ])->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('PhantomBuster: Retrieved phantoms list from v1', [
                        'count' => count($data)
                    ]);
                    return $data;
                }
            }
            
            // Don't throw - just log and return empty array
            // The endpoint may not be available, so we should configure phantom IDs directly
            Log::warning("PhantomBuster list phantoms endpoint not available (this is normal)", [
                'status' => $response->status(),
                'body' => $response->body(),
                'url_tried' => $url,
                'note' => 'Please configure phantom IDs directly in .env file instead of using findPhantomByName'
            ]);
            return [];
        }

        $data = $response->json();
        Log::info('PhantomBuster: Retrieved phantoms list', [
            'count' => count($data)
        ]);

        return $data;
    }

    /**
     * Find a phantom by name or slug
     * Note: This method requires the listPhantoms endpoint which may not be available
     * It's recommended to configure phantom IDs directly in .env instead
     *
     * @param string $name Phantom name or slug to search for
     * @return string|null Phantom ID if found, null otherwise
     */
    public function findPhantomByName(string $name): ?string
    {
        try {
            $phantoms = $this->listPhantoms();
        } catch (\Exception $e) {
            Log::warning('PhantomBuster: Cannot list phantoms to find by name', [
                'search' => $name,
                'error' => $e->getMessage(),
                'note' => 'Please configure phantom IDs directly in .env file'
            ]);
            return null;
        }
        
        if (empty($phantoms)) {
            Log::warning('PhantomBuster: No phantoms returned from list', [
                'search' => $name,
                'note' => 'Please configure phantom IDs directly in .env file'
            ]);
            return null;
        }
        
        $searchName = strtolower($name);
        
        foreach ($phantoms as $phantom) {
            $phantomName = strtolower($phantom['name'] ?? '');
            $phantomSlug = strtolower($phantom['slug'] ?? '');
            
            if (str_contains($phantomName, $searchName) || str_contains($phantomSlug, $searchName)) {
                $phantomId = $phantom['id'] ?? $phantom['phantomId'] ?? null;
                Log::info('PhantomBuster: Found phantom by name', [
                    'search' => $name,
                    'found_name' => $phantom['name'] ?? 'Unknown',
                    'phantom_id' => $phantomId
                ]);
                return $phantomId;
            }
        }

        Log::warning('PhantomBuster: Phantom not found by name', [
            'search' => $name,
            'note' => 'Please configure phantom IDs directly in .env file'
        ]);
        return null;
    }

    /**
     * Fetch company post engagers (alternative to direct followers - doesn't require admin access)
     * Workflow:
     * 1. Get company posts using RapidAPI (to get post URLs)
     * 2. For each post, extract likers (using LinkedIn Post Likers Export)
     *
     * @param string $companyUrl LinkedIn company URL
     * @param string|null $phantomId Not used anymore (kept for backward compatibility)
     * @param int $maxWaitSeconds Maximum seconds to wait
     * @param int $pollIntervalSeconds Seconds between status checks
     * @return array Array of engager profiles
     */
    /**
     * Get globally scraped post URLs for a company (shared across all users)
     * Uses cache + database aggregation from all audiences for this company
     */
    private function getGlobalScrapedPosts(string $companyUrl): array
    {
        $normalizedUrl = $this->normalizeCompanyUrl($companyUrl);
        $cacheKey = "scraped_posts:{$normalizedUrl}";
        
        // Check cache first (fast, expires after 30 days)
        $cached = Cache::get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }
        
        // If not in cache, check all audiences for this company URL
        $scrapedPosts = $this->getScrapedPostsFromAllAudiences($normalizedUrl);
        
        // Cache for 30 days
        Cache::put($cacheKey, $scrapedPosts, now()->addDays(30));
        
        return $scrapedPosts;
    }
    
    /**
     * Add post URLs to global scraped list (shared across all users)
     */
    private function addToGlobalScrapedPosts(string $companyUrl, array $postUrls): void
    {
        if (empty($postUrls)) {
            return;
        }
        
        $normalizedUrl = $this->normalizeCompanyUrl($companyUrl);
        $cacheKey = "scraped_posts:{$normalizedUrl}";
        
        // Get existing scraped posts
        $existing = $this->getGlobalScrapedPosts($companyUrl);
        
        // Merge and deduplicate
        $allScraped = array_unique(array_merge($existing, $postUrls));
        
        // Update cache (30 days)
        Cache::put($cacheKey, $allScraped, now()->addDays(30));
        
        // Also update all audiences for this company URL (for persistence)
        $this->updateAllAudiencesForCompany($normalizedUrl, $allScraped);
        
        Log::info('PhantomBuster: Updated global scraped posts', [
            'company_url' => $normalizedUrl,
            'new_posts' => count($postUrls),
            'total_scraped' => count($allScraped)
        ]);
    }
    
    /**
     * Normalize company URL for consistent caching
     */
    private function normalizeCompanyUrl(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = rtrim($parsed['path'] ?? '', '/');
        
        return strtolower("{$scheme}://{$host}{$path}");
    }
    
    /**
     * Get scraped posts from all audiences for a company URL
     */
    private function getScrapedPostsFromAllAudiences(string $normalizedUrl): array
    {
        $allScraped = [];
        
        // Query all audiences for this company URL
        $audiences = \App\Models\Audience::where('source', 'linkedin_company_followers')
            ->where('tag', 'competitor_active_followers')
            ->whereNotNull('source_meta')
            ->get();
        
        foreach ($audiences as $audience) {
            $meta = json_decode($audience->source_meta, true);
            if (!is_array($meta)) {
                continue;
            }
            
            // Check if this audience is for the same company
            $audienceCompanyUrl = $meta['company_url'] ?? null;
            if ($audienceCompanyUrl && $this->normalizeCompanyUrl($audienceCompanyUrl) === $normalizedUrl) {
                $scraped = $meta['scraped_post_urls'] ?? [];
                if (is_array($scraped)) {
                    $allScraped = array_merge($allScraped, $scraped);
                }
            }
        }
        
        return array_unique($allScraped);
    }
    
    /**
     * Update all audiences for a company URL with scraped posts
     */
    private function updateAllAudiencesForCompany(string $normalizedUrl, array $scrapedPosts): void
    {
        $audiences = \App\Models\Audience::where('source', 'linkedin_company_followers')
            ->where('tag', 'competitor_active_followers')
            ->whereNotNull('source_meta')
            ->get();
        
        foreach ($audiences as $audience) {
            $meta = json_decode($audience->source_meta, true) ?? [];
            $audienceCompanyUrl = $meta['company_url'] ?? null;
            
            // Update audiences for this company
            if ($audienceCompanyUrl && $this->normalizeCompanyUrl($audienceCompanyUrl) === $normalizedUrl) {
                $existingScraped = $meta['scraped_post_urls'] ?? [];
                $allScraped = array_unique(array_merge($existingScraped, $scrapedPosts));
                $meta['scraped_post_urls'] = $allScraped;
                
                $audience->source_meta = json_encode($meta);
                $audience->save();
            }
        }
    }
    
    /**
     * Clear global scraped posts for a company URL (admin/reset function)
     */
    public function clearGlobalScrapedPosts(string $companyUrl): void
    {
        $normalizedUrl = $this->normalizeCompanyUrl($companyUrl);
        $cacheKey = "scraped_posts:{$normalizedUrl}";
        
        // Clear cache
        Cache::forget($cacheKey);
        
        // Clear from all audiences for this company
        $audiences = \App\Models\Audience::where('source', 'linkedin_company_followers')
            ->where('tag', 'competitor_active_followers')
            ->whereNotNull('source_meta')
            ->get();
        
        foreach ($audiences as $audience) {
            $meta = json_decode($audience->source_meta, true) ?? [];
            $audienceCompanyUrl = $meta['company_url'] ?? null;
            
            if ($audienceCompanyUrl && $this->normalizeCompanyUrl($audienceCompanyUrl) === $normalizedUrl) {
                unset($meta['scraped_post_urls']);
                $audience->source_meta = json_encode($meta);
                $audience->save();
            }
        }
        
        Log::info('PhantomBuster: Cleared global scraped posts', [
            'company_url' => $normalizedUrl
        ]);
    }

    public function fetchCompanyPostEngagers(
        string $companyUrl,
        ?string $phantomId = null,
        int $maxWaitSeconds = 600,
        int $pollIntervalSeconds = 15,
        ?string $sessionCookie = null,
        ?string $userAgent = null,
        array $alreadyScrapedPostUrls = [],
        $audience = null
    ): array {
        $this->sessionCookieOverride = $sessionCookie;
        $this->userAgentOverride = $userAgent;
        try {
            // Step 1: Get company posts using RapidAPI - sort by "top" for highest engagement
            $rapidApiService = new \App\Services\RapidApiService();
            // Try "top" first for highest engagement posts, fallback to "recent" if not supported
            $posts = $rapidApiService->fetch_company_posts($companyUrl, 1, 'top');
            
            if (empty($posts) || !isset($posts['data']) || empty($posts['data'])) {
                Log::warning('PhantomBuster: No posts found for company', ['company_url' => $companyUrl]);
                return [];
            }

            // Step 2: Sort posts by engagement (likes) before extracting URLs
            // This ensures we process the most engaging posts first
            $sortedPosts = $this->sortPostsByEngagement($posts['data']);
            
            // Extract post URLs from sorted posts
            $allPostUrls = $this->extractPostUrlsFromRapidApi($sortedPosts);
            
            if (empty($allPostUrls)) {
                Log::warning('PhantomBuster: No post URLs found in posts', ['company_url' => $companyUrl]);
                return ['engagers' => [], 'newly_scraped_posts' => []];
            }
            
            // Filter out ONLY posts this specific user/audience has already scraped
            // Each user tracks their own scraped posts independently
            // This allows multiple users to scrape the same posts if PhantomBuster allows it
            $postUrls = array_values(array_filter($allPostUrls, function($url) use ($alreadyScrapedPostUrls) {
                return !in_array($url, $alreadyScrapedPostUrls);
            }));
            
            if (empty($postUrls)) {
                Log::warning('PhantomBuster: All posts have already been scraped', [
                    'company_url' => $companyUrl,
                    'total_posts' => count($allPostUrls),
                    'already_scraped' => count($alreadyScrapedPostUrls)
                ]);
                return ['engagers' => [], 'newly_scraped_posts' => []];
            }

            // Step 3: Process posts dynamically - skip already-scraped ones and continue
            // Keep processing until we find unscraped posts or hit max attempts
            $maxAttempts = (int) config('services.phantombuster.company_posts_limit', 15);
            $maxSuccessfulPosts = 5; // Target: try to get data from at least 5 posts
            $minEngagersForEarlyStop = (int) config('services.phantombuster.min_engagers_for_early_stop', 1000);
            
            $allEngagers = [];
            $processedPosts = 0;
            $skippedPosts = 0;
            $successfulPosts = 0;
            $postIndex = 0;
            $newlyScrapedPostUrls = []; // Track newly scraped posts to return
            
            // Process posts until we find enough unscraped ones or hit max attempts
            while ($postIndex < count($postUrls) && $processedPosts < $maxAttempts) {
                $postUrl = $postUrls[$postIndex];
                $postIndex++;
                $processedPosts++;
                $postEngagers = 0;
                $likersFailed = false;

                try {
                    // Get likers for this post
                    $likers = $this->fetchPostLikers($postUrl, $maxWaitSeconds, $pollIntervalSeconds);
                    
                    // Ensure we only merge arrays (filter out any non-array items)
                    $validLikers = array_filter($likers, function($liker) {
                        return is_array($liker);
                    });
                    
                    $postEngagers += count($validLikers);
                    $allEngagers = array_merge($allEngagers, $validLikers);
                } catch (\Exception $e) {
                    $likersFailed = true;
                    $errorMsg = $e->getMessage();

                    // If this looks like a LinkedIn session / cookie issue, bubble it up
                    // so the job can mark the audience with a clear session-cookie error.
                    $isSessionError = stripos($errorMsg, 'session cookie') !== false
                        || stripos($errorMsg, 'li_at') !== false
                        || stripos($errorMsg, 'credentials') !== false
                        || stripos($errorMsg, 'Invalid/expired cookie') !== false
                        || stripos($errorMsg, 'cookie-invalid') !== false
                        || stripos($errorMsg, 'session expired') !== false;

                    if ($isSessionError) {
                        throw $e;
                    }

                    Log::warning('PhantomBuster: Failed to get likers for post', [
                        'post_url' => $postUrl,
                        'error' => substr($errorMsg, 0, 200)
                    ]);
                    
                    // If it's a 429 error (rate limit), wait a bit before continuing
                    if (str_contains($errorMsg, '429') || str_contains($errorMsg, 'parallel')) {
                        sleep(30); // Wait 30 seconds for rate limit to clear
                    }
                }
                
                // Track successful vs skipped posts
                $shouldMarkAsScraped = false;
                
                if ($postEngagers > 0) {
                    $successfulPosts++;
                    $shouldMarkAsScraped = true;

                    // Early stop if we have enough engagers (saves PhantomBuster credits)
                    if ($minEngagersForEarlyStop > 0 && count($allEngagers) >= $minEngagersForEarlyStop) {
                        break;
                    }
                } elseif ($likersFailed) {
                    $skippedPosts++;
                    $newlyScrapedPostUrls[] = $postUrl;
                }
                
                // Mark post as scraped if we attempted it
                if ($shouldMarkAsScraped) {
                    $newlyScrapedPostUrls[] = $postUrl;
                }
                
                // Small delay before processing next post to avoid hitting parallel limits
                sleep(2);
            }
            
            // Remove duplicates by public identifier and limit to top 500
            $uniqueEngagers = [];
            $seen = [];
            $skippedDuplicates = 0;
            $skippedNoPublicId = 0;
            
            foreach ($allEngagers as $index => $engager) {
                // Skip if not an array (shouldn't happen, but safety check)
                if (!is_array($engager)) {
                    continue;
                }
                
                // Stop if we've reached the limit of 500 engagers
                if (count($uniqueEngagers) >= 500) {
                    break;
                }
                
                $publicId = null;
                
                // Check profileLink first (PhantomBuster's format)
                $profileLink = $engager['profileLink'] 
                    ?? $engager['profileUrl'] 
                    ?? $engager['profile_url'] 
                    ?? null;
                
                // Extract ID from profileLink/profileUrl
                if ($profileLink) {
                    if (preg_match('/linkedin\.com\/in\/([^\/\?]+)/', $profileLink, $matches)) {
                        $publicId = $matches[1];
                    }
                }
                
                // Fallback to other fields
                if (!$publicId) {
                    $publicId = $engager['publicIdentifier'] 
                        ?? $engager['public_identifier'] 
                        ?? $engager['memberId'] 
                        ?? null;
                }
                
                if ($publicId && !isset($seen[$publicId])) {
                    $uniqueEngagers[] = $engager;
                    $seen[$publicId] = true;
                } elseif ($publicId && isset($seen[$publicId])) {
                    $skippedDuplicates++;
                } elseif (!$publicId) {
                    // If no public ID, still add it (might be unique by other fields)
                    $uniqueEngagers[] = $engager;
                    $skippedNoPublicId++;
                }
            }
            

            // Return both engagers and newly scraped posts for tracking
            // Note: Scraped posts are tracked per-user in the audience source_meta
            // This allows multiple users to attempt the same posts independently
            return [
                'engagers' => $uniqueEngagers,
                'newly_scraped_posts' => $newlyScrapedPostUrls ?? []
            ];
        } finally {
            $this->sessionCookieOverride = null;
            $this->userAgentOverride = null;
        }
    }

    /**
     * Fetch likers for a single LinkedIn post using PhantomBuster.
     *
     * @param string $postUrl Full LinkedIn post URL
     * @param int $maxWaitSeconds
     * @param int $pollIntervalSeconds
     * @param string|null $sessionCookie Optional override for li_at
     * @param string|null $userAgent Optional override for user agent
     * @return array
     * @throws \Exception
     */
    public function fetchPostLikersForUrl(
        string $postUrl,
        int $maxWaitSeconds = 300,
        int $pollIntervalSeconds = 10,
        ?string $sessionCookie = null,
        ?string $userAgent = null
    ): array {
        $this->sessionCookieOverride = $sessionCookie;
        $this->userAgentOverride = $userAgent;

        try {
            return $this->fetchPostLikers($postUrl, $maxWaitSeconds, $pollIntervalSeconds);
        } finally {
            $this->sessionCookieOverride = null;
            $this->userAgentOverride = null;
        }
    }

    /**
     * Fetch search results from a LinkedIn search URL using PhantomBuster's "LinkedIn Search Export".
     *
     * @param string $searchUrl A full LinkedIn search URL (people search preferred)
     * @param int $maxWaitSeconds Maximum seconds to wait for the phantom to finish
     * @param int $pollIntervalSeconds Poll interval while waiting
     * @param string|null $sessionCookie Optional override for li_at
     * @param string|null $userAgent Optional override for user agent
     * @param array|null $identities Optional identities array format: [['identityId' => string, 'sessionCookie' => string, 'userAgent' => string]]
     * @return array Array of profiles from PhantomBuster
     * @throws \Exception
     */
    public function fetchSearchExportResults(
        ?string $searchUrl = null,
        int $maxWaitSeconds = 600,
        int $pollIntervalSeconds = 15,
        ?string $sessionCookie = null,
        ?string $userAgent = null,
        ?string $keywords = null,
        array $connectionDegrees = [],
        string $category = 'People',
        ?int $resultsLimit = null,
        ?array $identities = null
    ): array {
        $this->sessionCookieOverride = $sessionCookie;
        $this->userAgentOverride = $userAgent;

        try {
            $phantomId = config('services.phantombuster.linkedin_search_export_phantom_id');
            
            if (!$phantomId) {
                Log::error('PhantomBuster: LinkedIn Search Export phantom ID not configured. Please set PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID in your .env file.');
                throw new \Exception('LinkedIn Search Export phantom ID not configured. Please set PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID in your .env file.');
            }

            $arguments = [];

            // Normalize category to ensure it's capitalized (PhantomBuster requires "People" not "people")
            $category = ucfirst(strtolower(trim($category)));

            $hasKeywords = $keywords && trim($keywords) !== '';
            $hasUrl = $searchUrl && trim($searchUrl) !== '';

            // Priority: If complete URL is provided (with filters), use it
            // Otherwise, use keywords mode and build minimal URL
            if ($hasUrl) {
                // Complete URL provided - use it (includes keywords and all filters)
                $arguments['searchUrl'] = $searchUrl;
                $arguments['linkedInSearchUrl'] = $searchUrl;
                $arguments['searchType'] = 'linkedInSearchUrl';
                
                // Also include keywords if provided (for PhantomBuster's keyword mode compatibility)
                if ($hasKeywords) {
                    $arguments['keywords'] = trim($keywords);
                    $arguments['category'] = $category;
                    if (!empty($connectionDegrees)) {
                        $arguments['connectionDegreesToScrape'] = $connectionDegrees;
                    }
                }
            } elseif ($hasKeywords) {
                // Only keywords provided - build minimal URL
                $arguments['keywords'] = trim($keywords);
                $arguments['category'] = $category;
                if (!empty($connectionDegrees)) {
                    $arguments['connectionDegreesToScrape'] = $connectionDegrees;
                }
                $arguments['searchType'] = 'keywords';
                
                // Build minimal search URL from keywords
                $encodedKeywords = urlencode(trim($keywords));
                $networkParam = urlencode(json_encode(!empty($connectionDegrees) ? $connectionDegrees : ['2','3+']));
                $builtUrl = "https://www.linkedin.com/search/results/all/?keywords={$encodedKeywords}&network={$networkParam}";
                $arguments['searchUrl'] = $builtUrl;
                $arguments['linkedInSearchUrl'] = $builtUrl;
            } else {
                throw new \Exception("Search export requires either a searchUrl or keywords.");
            }

            // Add limits to match Phantom config expectations
            $limit = $resultsLimit ?? 100;
            $perSearch = max(1, min($limit, 1000)); // cap at 1000
            // Mirror Phantom UI defaults: 100 per launch, 10 lines per launch
            $perLaunch = max(1, min($perSearch, 100));
            $linesPerLaunch = max(1, min(10, $perLaunch));

            $arguments['numberOfResultsPerSearch'] = $perSearch;
            $arguments['numberOfResultsPerLaunch'] = $perLaunch;
            $arguments['numberOfLinesPerLaunch'] = $linesPerLaunch;
            $arguments['enrichLeadsWithAdditionalInformation'] = true;

            // Use identities array format if provided, otherwise use top-level sessionCookie/userAgent
            if (!empty($identities) && is_array($identities)) {
                // Format identities array for PhantomBuster - match exact structure from PhantomBuster dashboard
                $formattedIdentities = [];
                foreach ($identities as $identity) {
                    if (is_array($identity) && isset($identity['sessionCookie'])) {
                        $formattedIdentity = [
                            'sessionCookie' => $identity['sessionCookie']
                        ];
                        
                        // Add identityId if provided (optional but recommended for reuse)
                        if (isset($identity['identityId']) && !empty($identity['identityId'])) {
                            $formattedIdentity['identityId'] = $identity['identityId'];
                        }
                        
                        // Add userAgent to identity (required in identities array)
                        if (isset($identity['userAgent']) && !empty($identity['userAgent'])) {
                            $formattedIdentity['userAgent'] = $identity['userAgent'];
                        } elseif ($userAgent) {
                            $formattedIdentity['userAgent'] = $userAgent;
                        } elseif ($this->getUserAgent()) {
                            $formattedIdentity['userAgent'] = $this->getUserAgent();
                        }
                        
                        $formattedIdentities[] = $formattedIdentity;
                    }
                }
                
                if (!empty($formattedIdentities)) {
                    $arguments['identities'] = $formattedIdentities;
                    
                    // Also add top-level userAgent (PhantomBuster structure includes both)
                    $topLevelUserAgent = $userAgent ?? $this->getUserAgent();
                    if ($topLevelUserAgent) {
                        $arguments['userAgent'] = $topLevelUserAgent;
                    }
                    
                }
            } else {
                // Fallback to top-level sessionCookie and userAgent (backward compatibility)
                $sessionCookieValue = $this->getSessionCookie();
                if ($sessionCookieValue) {
                    $arguments['sessionCookie'] = $sessionCookieValue;
                }

                $userAgentValue = $this->getUserAgent();
                if ($userAgentValue) {
                    $arguments['userAgent'] = $userAgentValue;
                }
            }

            $launchResponse = $this->launchPhantom($phantomId, $arguments);
            $containerId = $launchResponse['containerId'] ?? $launchResponse['data']['containerId'] ?? null;

            if (!$containerId) {
                throw new \Exception("Failed to get container ID from PhantomBuster launch for search export");
            }

            // Wait briefly before polling
            sleep(5);

            $startTime = time();
            $attempts = 0;
            $last404Time = null;
            $alreadyRetrievedWarning = false; // Track if we detect "already retrieved" warning

            while (time() - $startTime < $maxWaitSeconds) {
                $attempts++;

                try {
                    if ($attempts > 1) {
                        sleep($pollIntervalSeconds);
                    }

                    $output = $this->getPhantomOutput($phantomId, $containerId);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Container not found')) {
                        if ($last404Time === null) {
                            $last404Time = time();
                        }

                        if (time() - $last404Time > 30) {
                            Log::error('PhantomBuster: Search export container not found after multiple attempts', [
                                'phantom_id' => $phantomId,
                                'container_id' => $containerId,
                                'attempts' => $attempts,
                                'search_url' => $searchUrl
                            ]);
                            return [];
                        }

                        continue;
                    }

                    throw $e;
                }

                // Extract profiles - handle both JSON string and array formats
                $profiles = [];
                
                // Check for "already retrieved" warning in output
                $outputString = '';
                if (isset($output['data']['output']) && is_string($output['data']['output'])) {
                    $outputString = $output['data']['output'];
                }
                
                $alreadyRetrievedWarning = stripos($outputString, "already retrieved all results") !== false;
                
                if ($alreadyRetrievedWarning) {
                    Log::warning('PhantomBuster: Search already retrieved - results may be cached', [
                        'message' => 'PhantomBuster indicates this search was already performed. Results may be in cache.',
                        'suggestion' => 'Try changing search keywords or parameters, or check PhantomBuster dashboard for cached results'
                    ]);
                }
                
                // Try resultObject first (newer phantoms return JSON string here)
                if (!empty($output['data']['resultObject'] ?? null)) {
                    $resultObject = $output['data']['resultObject'];
                    
                    if (is_string($resultObject)) {
                        // Decode JSON string
                        $decoded = json_decode($resultObject, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $profiles = $decoded;
                        } else {
                            Log::warning('PhantomBuster: Failed to decode resultObject JSON string', [
                                'json_error' => json_last_error_msg(),
                                'resultObject_preview' => substr($resultObject, 0, 200)
                            ]);
                        }
                    } elseif (is_array($resultObject)) {
                        $profiles = $resultObject;
                    }
                }
                
                // Fallback to output if resultObject didn't yield data
                if (empty($profiles) && !empty($output['data']['output'] ?? null)) {
                    $outputData = $output['data']['output'];
                    
                    if (is_string($outputData)) {
                        // Skip if it's just log text (contains warning messages but no JSON)
                        // Check if it looks like JSON (starts with [ or {)
                        $trimmed = trim($outputData);
                        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                            $decoded = json_decode($outputData, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $profiles = $decoded;
                            } else {
                                Log::warning('PhantomBuster: Output is string but not valid JSON', [
                                    'json_error' => json_last_error_msg(),
                                    'output_preview' => substr($outputData, 0, 200),
                                    'is_log_text' => $alreadyRetrievedWarning
                                ]);
                            }
                        } else {
                            // Output is container log text, not profile data - skip logging to avoid noise
                        }
                    } elseif (is_array($outputData)) {
                        $profiles = $outputData;
                    }
                }
                
                // Final fallback to top-level output
                if (empty($profiles) && !empty($output['output'] ?? null)) {
                    $topLevelOutput = $output['output'];
                    if (is_string($topLevelOutput)) {
                        $trimmed = trim($topLevelOutput);
                        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                            $decoded = json_decode($topLevelOutput, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $profiles = $decoded;
                            }
                        }
                    } elseif (is_array($topLevelOutput)) {
                        $profiles = $topLevelOutput;
                    }
                }

                $containerStatus = $output['data']['containerStatus'] ?? null;
                $agentStatus = $output['data']['agentStatus'] ?? null;
                $messages = $output['data']['messages'] ?? [];
                $progress = $output['data']['progress'] ?? null;

                // Filter out error objects - PhantomBuster sometimes returns error objects instead of profiles
                $validProfiles = [];
                $errorObjects = [];
                foreach ($profiles as $profile) {
                    if (!is_array($profile)) {
                        continue;
                    }
                    // Skip error objects (they have 'error' key but no valid profile data)
                    if (isset($profile['error']) && empty($profile['fullName']) && empty($profile['profileUrl']) && empty($profile['firstName'])) {
                        $errorObjects[] = $profile;
                        continue;
                    }
                    // Only include profiles with at least a name or profileUrl
                    if (!empty($profile['fullName']) || !empty($profile['profileUrl']) || !empty($profile['firstName'])) {
                        $validProfiles[] = $profile;
                    }
                }
                
                // Log errors if all results were error objects
                if (!empty($errorObjects) && empty($validProfiles)) {
                    $firstError = $errorObjects[0] ?? null;
                    Log::warning('PhantomBuster: All returned items were error objects', [
                        'error_count' => count($errorObjects),
                        'first_error' => $firstError,
                        'error_message' => $firstError['error'] ?? null,
                        'error_query' => $firstError['query'] ?? null,
                        'search_url' => $searchUrl,
                        'keywords' => $keywords,
                        'suggestion' => 'Check PhantomBuster dashboard for detailed error message. This may indicate LinkedIn session issues or search parameter problems.'
                    ]);
                }
                
                $profiles = $validProfiles;

                if (!empty($profiles) && is_array($profiles) && count($profiles) > 0) {
                    return $profiles;
                }

                if ($containerStatus === 'not running' || $containerStatus === 'finished' || $containerStatus === 'completed') {
                    // Check for invalid/expired session cookie error
                    $outputString = '';
                    if (isset($output['data']['output']) && is_string($output['data']['output'])) {
                        $outputString = $output['data']['output'];
                    }
                    
                    // Check for session cookie errors
                    $sessionCookieErrorPatterns = [
                        'No valid credentials found',
                        'Invalid/expired cookie',
                        'network-cookie-invalid',
                        'No valid credentials',
                        'exit code: 87'
                    ];
                    
                    $hasSessionCookieError = false;
                    foreach ($sessionCookieErrorPatterns as $pattern) {
                        if (stripos($outputString, $pattern) !== false) {
                            $hasSessionCookieError = true;
                            break;
                        }
                    }
                    
                    if ($hasSessionCookieError && empty($profiles)) {
                        Log::error('PhantomBuster: LinkedIn session cookie invalid or expired', [
                            'container_status' => $containerStatus,
                            'agent_status' => $agentStatus,
                            'error_detected' => 'Session cookie invalid/expired',
                            'output_preview' => substr($outputString, 0, 500)
                        ]);
                        
                        throw new \Exception(
                            'LINKEDIN_SESSION_EXPIRED: Your LinkedIn session cookie has expired or is invalid. ' .
                            'Please update it in the Social Accounts page of your CRM. ' .
                            'Go to: Social Accounts → LinkedIn → Update session cookie'
                        );
                    }
                    
                    if ($alreadyRetrievedWarning && empty($profiles)) {
                        return [];
                    }
                    
                    Log::warning('PhantomBuster: Search export finished with no data', [
                        'container_status' => $containerStatus
                    ]);
                    break;
                }
            }

            // Check for "already retrieved" warning in final output if we didn't catch it earlier
            $finalOutputString = '';
            if (isset($output['data']['output']) && is_string($output['data']['output'])) {
                $finalOutputString = $output['data']['output'];
            }
            $finalAlreadyRetrieved = stripos($finalOutputString, "already retrieved all results") !== false;
            
            if ($finalAlreadyRetrieved && empty($profiles)) {
                return [];
            }

            // Final check for session cookie error before returning empty
            $finalOutputString = '';
            if (isset($output['data']['output']) && is_string($output['data']['output'])) {
                $finalOutputString = $output['data']['output'];
            }
            
            $sessionCookieErrorPatterns = [
                'No valid credentials found',
                'Invalid/expired cookie',
                'network-cookie-invalid',
                'No valid credentials',
                'exit code: 87'
            ];
            
            foreach ($sessionCookieErrorPatterns as $pattern) {
                if (stripos($finalOutputString, $pattern) !== false) {
                    Log::error('PhantomBuster: LinkedIn session cookie invalid or expired (final check)', [
                        'search_url' => $searchUrl,
                        'error_detected' => 'Session cookie invalid/expired',
                        'output_preview' => substr($finalOutputString, 0, 500)
                    ]);
                    
                    throw new \Exception(
                        'LINKEDIN_SESSION_EXPIRED: Your LinkedIn session cookie has expired or is invalid. ' .
                        'Please update it in the Social Accounts page of your CRM. ' .
                        'Go to: Social Accounts → LinkedIn → Update session cookie'
                    );
                }
            }
            
            Log::warning('PhantomBuster: Search export finished with no data', ['search_url' => $searchUrl]);

            return [];
        } finally {
            $this->sessionCookieOverride = null;
            $this->userAgentOverride = null;
        }
    }

    /**
     * Sort posts by engagement (likes only)
     * Posts with higher engagement will be processed first
     *
     * @param array $posts Posts from RapidAPI
     * @return array Sorted posts array (highest engagement first)
     */
    private function sortPostsByEngagement(array $posts): array
    {
        usort($posts, function($a, $b) {
            // Extract engagement metrics (likes only)
            $likesA = (int)($a['num_likes'] ?? $a['likes'] ?? $a['like_count'] ?? 0);
            $likesB = (int)($b['num_likes'] ?? $b['likes'] ?? $b['like_count'] ?? 0);
            
            // Sort descending (highest likes first)
            return $likesB <=> $likesA;
        });
        
        // Log top 3 posts for debugging
        if (count($posts) > 0) {
            $topPosts = array_slice($posts, 0, min(3, count($posts)));
            $topEngagement = [];
            foreach ($topPosts as $post) {
                $likes = (int)($post['num_likes'] ?? $post['likes'] ?? $post['like_count'] ?? 0);
                $topEngagement[] = [
                    'likes' => $likes
                ];
            }
        }
        
        return $posts;
    }

    /**
     * Extract post URLs from RapidAPI posts array
     *
     * @param array $posts Posts from RapidAPI (should be sorted by engagement)
     * @return array Array of post URLs
     */
    private function extractPostUrlsFromRapidApi(array $posts): array
    {
        $postUrls = [];
        
        foreach ($posts as $post) {
            // RapidAPI returns post URL in different possible fields
            $postUrl = $post['url'] 
                ?? $post['postUrl'] 
                ?? $post['post_url'] 
                ?? $post['linkedInUrl'] 
                ?? $post['linkedin_url'] 
                ?? $post['link'] 
                ?? null;
            
            // If post is a string (URL), use it directly
            if (!$postUrl && is_string($post)) {
                $postUrl = $post;
            }
            
            // Validate it's a LinkedIn URL
            if ($postUrl && (filter_var($postUrl, FILTER_VALIDATE_URL) || str_starts_with($postUrl, 'https://www.linkedin.com'))) {
                $postUrls[] = $postUrl;
            }
        }
        
        return array_unique($postUrls);
    }

    /**
     * Fetch likers for a specific post URL
     *
     * @param string $postUrl LinkedIn post URL
     * @param int $maxWaitSeconds
     * @param int $pollIntervalSeconds
     * @return array Array of liker profiles
     */
    private function fetchPostLikers(
        string $postUrl,
        int $maxWaitSeconds = 300,
        int $pollIntervalSeconds = 10
    ): array {
        $phantomId = config('services.phantombuster.linkedin_post_likers_phantom_id');
        
        if (!$phantomId) {
            Log::error('PhantomBuster: LinkedIn Post Likers phantom ID not configured. Please set PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID in your .env file.');
            throw new \Exception('LinkedIn Post Likers phantom ID not configured. Please set PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID in your .env file.');
        }

        $arguments = [
            'postUrl' => $postUrl,
        ];
        
        // Add session cookie and user agent
        $sessionCookie = $this->getSessionCookie();
        if ($sessionCookie) {
            $arguments['sessionCookie'] = $sessionCookie;
        }
        
        $userAgent = $this->getUserAgent();
        if ($userAgent) {
            $arguments['userAgent'] = $userAgent;
        }

        $launchResponse = $this->launchPhantom($phantomId, $arguments);
        $containerId = $launchResponse['containerId'] ?? $launchResponse['data']['containerId'] ?? null;
        
        if (!$containerId) {
            throw new \Exception("Failed to get container ID from PhantomBuster launch for post likers");
        }

        // Wait a bit before first poll to allow container to initialize
        // PhantomBuster containers need time to start up
        sleep(5);

        // Poll for completion
        $startTime = time();
        $attempts = 0;
        $last404Time = null;
        
        while (time() - $startTime < $maxWaitSeconds) {
            $attempts++;
            
            try {
                // Don't sleep on first attempt since we already waited 5 seconds
                if ($attempts > 1) {
                    sleep($pollIntervalSeconds);
                }

                $output = $this->getPhantomOutput($phantomId, $containerId);
            } catch (\Exception $e) {
                // Handle 404 errors gracefully - container might not be ready yet
                if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Container not found')) {
                    if ($last404Time === null) {
                        $last404Time = time();
                    }
                    
                    // If we keep getting 404s for more than 30 seconds, give up
                    if (time() - $last404Time > 30) {
                        Log::error('PhantomBuster: Container not found after multiple attempts', [
                            'phantom_id' => $phantomId,
                            'container_id' => $containerId,
                            'attempts' => $attempts,
                            'post_url' => $postUrl
                        ]);
                        return [];
                    }
                    
                    continue;
                }
                
                // For other errors, re-throw
                throw $e;
            }
            
            // Check multiple possible locations for the data
            $likers = $output['data']['output'] 
                ?? $output['data']['resultObject'] 
                ?? $output['output'] 
                ?? [];
            
            $containerStatus = $output['data']['containerStatus'] ?? null;
            $agentStatus = $output['data']['agentStatus'] ?? null;
            $messages = $output['data']['messages'] ?? [];
            $progress = $output['data']['progress'] ?? null;
            
            // Check output string for session/cookie errors
            $outputString = $output['data']['output'] ?? '';
            if (is_string($outputString)) {
                $sessionErrorPatterns = [
                    'No valid credentials found',
                    'Invalid/expired cookie',
                    'network-cookie-invalid',
                    'No valid credentials',
                    'cookie-invalid',
                    'session expired'
                ];
                
                foreach ($sessionErrorPatterns as $pattern) {
                    if (stripos($outputString, $pattern) !== false) {
                        $errorMessage = 'LinkedIn session cookie (li_at) is expired or invalid. Please update your LinkedIn session cookie from the Social Accounts page.';
                        Log::error('🔐 PhantomBuster: Session/Cookie error detected', [
                            'post_url' => $postUrl,
                            'error_pattern' => $pattern,
                            'output_sample' => substr($outputString, 0, 500)
                        ]);
                        throw new \Exception($errorMessage);
                    }
                }
            }
            
            // Avoid logging full output (container logs can be huge and fill disk)
            
            if (is_array($likers) && !empty($likers)) {
                return $likers;
            }
            
            // Check if phantom is finished (even if no data yet)
            if ($containerStatus === 'not running' || $containerStatus === 'finished' || $containerStatus === 'completed') {
                // Check for error messages first
                $errorDetected = false;
                $errorMessage = null;
                
                // Check messages array for errors
                if (!empty($messages) && is_array($messages)) {
                    $messagesText = is_array($messages) ? implode(' | ', array_filter($messages)) : (string)$messages;

                    // Check for common error patterns
                    $errorPatterns = [
                        'export limit' => 'Export limit reached - upgrade your PhantomBuster plan',
                        'slot' => 'No available slots - wait or upgrade plan',
                        'limit reached' => 'PhantomBuster limit reached',
                        'couldn\'t load' => 'Could not load post - post may be private or deleted',
                        'session' => 'LinkedIn session expired - refresh your li_at cookie',
                        'unauthorized' => 'LinkedIn authorization failed',
                        'error' => 'PhantomBuster error detected'
                    ];
                    
                    foreach ($errorPatterns as $pattern => $description) {
                        if (stripos($messagesText, $pattern) !== false) {
                            $errorDetected = true;
                            $errorMessage = $description . " (found: '$pattern')";
                            break;
                        }
                    }
                }
                
                // One final check for data in resultObject
                if (isset($output['data']['resultObject'])) {
                    $resultObject = $output['data']['resultObject'];

                    // If resultObject is a JSON string, try to decode it
                    if (is_string($resultObject)) {
                        $decoded = json_decode($resultObject, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            // Check if it's an error message
                            if (isset($decoded[0]['error'])) {
                                $errorDetected = true;
                                $errorText = $decoded[0]['error'];
                                
                                // Check if this is likely a session expiration issue
                                $sessionExpiredPatterns = [
                                    'cannot be displayed',
                                    'not available',
                                    'access denied',
                                    'unauthorized',
                                    'session expired'
                                ];
                                
                                $isSessionIssue = false;
                                foreach ($sessionExpiredPatterns as $pattern) {
                                    if (stripos($errorText, $pattern) !== false) {
                                        $isSessionIssue = true;
                                        $errorMessage = "LinkedIn session expired or invalid - refresh your li_at cookie. Error: " . $errorText;
                                        break;
                                    }
                                }
                                
                                if (!$isSessionIssue) {
                                    $errorMessage = "PhantomBuster error: " . $errorText;
                                }
                                
                                Log::error('PhantomBuster: Phantom returned error in resultObject', [
                                    'error' => $errorText,
                                    'post_url' => $decoded[0]['postUrl'] ?? $postUrl,
                                    'is_session_issue' => $isSessionIssue
                                ]);
                                break; // Don't return error data
                            }
                            
                            // Check if decoded contains CSV/JSON URLs (PhantomBuster file export format)
                            if (isset($decoded['csvURL']) || isset($decoded['jsonUrl']) || isset($decoded['csv_url']) || isset($decoded['json_url'])) {
                                $jsonUrl = $decoded['jsonUrl'] ?? $decoded['json_url'] ?? null;
                                $csvUrl = $decoded['csvURL'] ?? $decoded['csv_url'] ?? null;
                                
                                // Prefer JSON over CSV
                                if ($jsonUrl) {
                                    try {
                                        // Use cURL for better error handling
                                        $ch = curl_init($jsonUrl);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                                        $jsonContent = curl_exec($ch);
                                        $curlError = curl_error($ch);
                                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                        curl_close($ch);
                                        
                                        if ($curlError) {
                                            Log::error('❌ PhantomBuster: cURL error downloading JSON', [
                                                'json_url' => $jsonUrl,
                                                'error' => $curlError,
                                                'post_url' => $postUrl
                                            ]);
                                        } elseif ($httpCode !== 200) {
                                            Log::error('❌ PhantomBuster: HTTP error downloading JSON', [
                                                'json_url' => $jsonUrl,
                                                'http_code' => $httpCode,
                                                'post_url' => $postUrl
                                            ]);
                                        } elseif ($jsonContent) {
                                            $likersData = json_decode($jsonContent, true);
                                            $jsonError = json_last_error();
                                            
                                            if ($jsonError === JSON_ERROR_NONE && is_array($likersData) && !empty($likersData)) {
                                                return $likersData;
                                            } else {
                                                Log::warning('⚠️ PhantomBuster: JSON parsed but invalid or empty', [
                                                    'json_error' => json_last_error_msg(),
                                                    'is_array' => is_array($likersData),
                                                    'count' => is_array($likersData) ? count($likersData) : 0
                                                ]);
                                            }
                                        } else {
                                            Log::warning('⚠️ PhantomBuster: JSON content is empty', ['json_url' => $jsonUrl]);
                                        }
                                    } catch (\Throwable $e) {
                                        Log::error('❌ PhantomBuster: Failed to download/parse JSON', [
                                            'json_url' => $jsonUrl,
                                            'error' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }
                                }
                                
                                // Fallback to CSV if JSON failed
                                if ($csvUrl) {
                                    try {
                                        // Use cURL for better error handling
                                        $ch = curl_init($csvUrl);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                                        $csvContent = curl_exec($ch);
                                        $curlError = curl_error($ch);
                                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                        curl_close($ch);
                                        
                                        if ($curlError) {
                                            Log::error('❌ PhantomBuster: cURL error downloading CSV', [
                                                'csv_url' => $csvUrl,
                                                'error' => $curlError,
                                                'post_url' => $postUrl
                                            ]);
                                        } elseif ($httpCode !== 200) {
                                            Log::error('❌ PhantomBuster: HTTP error downloading CSV', [
                                                'csv_url' => $csvUrl,
                                                'http_code' => $httpCode,
                                                'post_url' => $postUrl
                                            ]);
                                        } elseif ($csvContent) {
                                            $lines = explode("\n", trim($csvContent));
                                            $headers = str_getcsv(array_shift($lines));
                                            
                                            $likersData = [];
                                            foreach ($lines as $lineNum => $line) {
                                                if (empty(trim($line))) continue;
                                                $row = str_getcsv($line);
                                                if (count($row) === count($headers)) {
                                                    $likersData[] = array_combine($headers, $row);
                                                }
                                            }
                                            
                                            if (!empty($likersData)) {
                                                return $likersData;
                                            }
                                        } else {
                                            Log::warning('⚠️ PhantomBuster: CSV content is empty', ['csv_url' => $csvUrl]);
                                        }
                                    } catch (\Throwable $e) {
                                        Log::error('❌ PhantomBuster: Failed to download/parse CSV', [
                                            'csv_url' => $csvUrl,
                                            'error' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }
                                }
                                
                                // If we couldn't download, log and continue
                                Log::error('❌ PhantomBuster: resultObject contains URLs but download/parse failed', [
                                    'has_json' => !empty($jsonUrl),
                                    'has_csv' => !empty($csvUrl),
                                    'json_url' => $jsonUrl,
                                    'csv_url' => $csvUrl,
                                    'post_url' => $postUrl
                                ]);
                            } else {
                                // It's valid data (array of likers)
                                return $decoded;
                            }
                        }
                        
                        // Check if resultObject string contains error keywords
                        $errorPatterns = [
                            'export limit' => 'Export limit reached',
                            'couldn\'t load' => 'Could not load post',
                            'error' => 'Error in result'
                        ];
                        foreach ($errorPatterns as $pattern => $description) {
                            if (stripos($resultObject, $pattern) !== false) {
                                $errorDetected = true;
                                $errorMessage = $description . " (found in resultObject)";
                                Log::error('PhantomBuster: Error detected in resultObject string', [
                                    'error_pattern' => $pattern,
                                    'resultObject_sample' => substr($resultObject, 0, 500)
                                ]);
                                break 2;
                            }
                        }
                    }
                    
                    if (is_array($resultObject) && !empty($resultObject)) {
                        // Check if it's an error message
                        if (isset($resultObject[0]['error'])) {
                            $errorDetected = true;
                            $errorText = $resultObject[0]['error'];
                            
                            // Check if this is likely a session expiration issue
                            $sessionExpiredPatterns = [
                                'cannot be displayed',
                                'not available',
                                'access denied',
                                'unauthorized',
                                'session expired'
                            ];
                            
                            $isSessionIssue = false;
                            foreach ($sessionExpiredPatterns as $pattern) {
                                if (stripos($errorText, $pattern) !== false) {
                                    $isSessionIssue = true;
                                    $errorMessage = "LinkedIn session expired or invalid - refresh your li_at cookie. Error: " . $errorText;
                                    break;
                                }
                            }
                            
                            if (!$isSessionIssue) {
                                $errorMessage = "PhantomBuster error: " . $errorText;
                            }
                            
                            Log::error('PhantomBuster: Phantom returned error in resultObject', [
                                'error' => $errorText,
                                'post_url' => $resultObject[0]['postUrl'] ?? $postUrl,
                                'is_session_issue' => $isSessionIssue
                            ]);
                            break; // Don't return error data
                        }
                        
                        // Check if resultObject contains CSV/JSON URLs (PhantomBuster file export format)
                        if (isset($resultObject['csvURL']) || isset($resultObject['jsonUrl']) || isset($resultObject['csv_url']) || isset($resultObject['json_url'])) {
                            $jsonUrl = $resultObject['jsonUrl'] ?? $resultObject['json_url'] ?? null;
                            $csvUrl = $resultObject['csvURL'] ?? $resultObject['csv_url'] ?? null;
                            
                            // Prefer JSON over CSV
                            if ($jsonUrl) {
                                try {
                                    // Use cURL for better error handling
                                    $ch = curl_init($jsonUrl);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                                    $jsonContent = curl_exec($ch);
                                    $curlError = curl_error($ch);
                                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);
                                    
                                    if ($curlError) {
                                        Log::error('❌ PhantomBuster: cURL error downloading JSON (array case)', [
                                            'json_url' => $jsonUrl,
                                            'error' => $curlError,
                                            'post_url' => $postUrl
                                        ]);
                                    } elseif ($httpCode !== 200) {
                                        Log::error('❌ PhantomBuster: HTTP error downloading JSON (array case)', [
                                            'json_url' => $jsonUrl,
                                            'http_code' => $httpCode,
                                            'post_url' => $postUrl
                                        ]);
                                    } elseif ($jsonContent) {
                                        $likersData = json_decode($jsonContent, true);
                                        $jsonError = json_last_error();
                                        
                                        if ($jsonError === JSON_ERROR_NONE && is_array($likersData) && !empty($likersData)) {
                                            return $likersData;
                                        } else {
                                            Log::warning('⚠️ PhantomBuster: JSON parsed but invalid or empty (array case)', [
                                                'json_error' => json_last_error_msg(),
                                                'is_array' => is_array($likersData),
                                                'count' => is_array($likersData) ? count($likersData) : 0
                                            ]);
                                        }
                                    } else {
                                        Log::warning('⚠️ PhantomBuster: JSON content is empty (array case)', ['json_url' => $jsonUrl]);
                                    }
                                } catch (\Throwable $e) {
                                    Log::error('❌ PhantomBuster: Failed to download/parse JSON (array case)', [
                                        'json_url' => $jsonUrl,
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }
                            }
                            
                            // Fallback to CSV if JSON failed
                            if ($csvUrl) {
                                try {
                                    // Use cURL for better error handling
                                    $ch = curl_init($csvUrl);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                                    $csvContent = curl_exec($ch);
                                    $curlError = curl_error($ch);
                                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);
                                    
                                    if ($curlError) {
                                        Log::error('❌ PhantomBuster: cURL error downloading CSV (array case)', [
                                            'csv_url' => $csvUrl,
                                            'error' => $curlError,
                                            'post_url' => $postUrl
                                        ]);
                                    } elseif ($httpCode !== 200) {
                                        Log::error('❌ PhantomBuster: HTTP error downloading CSV (array case)', [
                                            'csv_url' => $csvUrl,
                                            'http_code' => $httpCode,
                                            'post_url' => $postUrl
                                        ]);
                                    } elseif ($csvContent) {
                                        $lines = explode("\n", trim($csvContent));
                                        $headers = str_getcsv(array_shift($lines));
                                        
                                        $likersData = [];
                                        foreach ($lines as $lineNum => $line) {
                                            if (empty(trim($line))) continue;
                                            $row = str_getcsv($line);
                                            if (count($row) === count($headers)) {
                                                $likersData[] = array_combine($headers, $row);
                                            }
                                        }
                                        
                                        if (!empty($likersData)) {
                                            return $likersData;
                                        }
                                    } else {
                                        Log::warning('⚠️ PhantomBuster: CSV content is empty (array case)', ['csv_url' => $csvUrl]);
                                    }
                                } catch (\Throwable $e) {
                                    Log::error('❌ PhantomBuster: Failed to download/parse CSV (array case)', [
                                        'csv_url' => $csvUrl,
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }
                            }
                            
                            // If we couldn't download, log and continue
                            Log::error('❌ PhantomBuster: resultObject contains URLs but download/parse failed (array case)', [
                                'has_json' => !empty($jsonUrl),
                                'has_csv' => !empty($csvUrl),
                                'json_url' => $jsonUrl,
                                'csv_url' => $csvUrl,
                                'post_url' => $postUrl
                            ]);
                        } else {
                            // It's valid data (array of likers)
                            return $resultObject;
                        }
                    }
                }
                
                // Check the output array itself for errors or data
                $outputArray = $output['data']['output'] ?? null;
                if ($outputArray !== null) {
                    if (is_string($outputArray)) {
                        // Check for specific PhantomBuster messages in the log string
                        if (stripos($outputArray, 'Every post is scraped') !== false || stripos($outputArray, 'input is empty') !== false) {
                            $errorDetected = true;
                            $errorMessage = "Post already scraped or input empty - PhantomBuster may have cached this post. Try a different post URL or clear PhantomBuster cache.";
                            Log::warning('PhantomBuster: Post already scraped message detected', [
                                'output_sample' => substr($outputArray, 0, 500),
                                'post_url' => $postUrl
                            ]);
                        } elseif (stripos($outputArray, 'export limit') !== false || stripos($outputArray, 'limit reached') !== false) {
                            $errorDetected = true;
                            $errorMessage = "Export limit reached - found in output string";
                        } else {
                            // Try to decode as JSON
                            $decodedOutput = json_decode($outputArray, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOutput)) {
                                if (isset($decodedOutput[0]['error'])) {
                                    $errorDetected = true;
                                    $errorMessage = "PhantomBuster output error: " . $decodedOutput[0]['error'];
                                } elseif (!empty($decodedOutput)) {
                                    // It's actually data, not an error!
                                    return $decodedOutput;
                                }
                            }
                        }
                    } elseif (is_array($outputArray) && !empty($outputArray)) {
                        // Check if first item is an error
                        if (isset($outputArray[0]['error'])) {
                            $errorDetected = true;
                            $errorMessage = "PhantomBuster output error: " . $outputArray[0]['error'];
                        } else {
                            // It's actual data!
                            return $outputArray;
                        }
                    }
                }
                
                // Log detailed error information
                if ($errorDetected) {
                    Log::error('PhantomBuster: Phantom finished with error - no likers returned', [
                        'error_message' => $errorMessage,
                        'container_status' => $containerStatus,
                        'post_url' => $postUrl
                    ]);
                } else {
                    Log::warning('PhantomBuster: Phantom finished but no likers found (no error detected)', [
                        'container_status' => $containerStatus,
                        'post_url' => $postUrl
                    ]);
                }
                break;
            }
            
            if ($containerStatus === 'error') {
                Log::error('PhantomBuster: Phantom error', [
                    'messages' => $messages,
                    'agent_status' => $agentStatus
                ]);
                break;
            }
        }

        $errorNote = 'Phantom may have hit export limits, slot limits, or session issues. Check PhantomBuster dashboard for details.';
        if (isset($errorMessage) && stripos($errorMessage, 'session expired') !== false) {
            $errorNote = 'LinkedIn session cookie (li_at) appears to be expired. Please refresh it from the Social Accounts page.';
        }
        
        Log::error('PhantomBuster: Timeout or no data for post likers', [
            'post_url' => $postUrl,
            'waited_seconds' => time() - $startTime,
            'max_wait_seconds' => $maxWaitSeconds,
            'note' => $errorNote,
            'error_message' => $errorMessage ?? null
        ]);
        return [];
    }

    /**
     * Fetch post comments from PhantomBuster
     * Similar to fetchPostLikers but for comments
     * 
     * @param string $postUrl LinkedIn post URL
     * @param int $maxWaitSeconds Maximum seconds to wait
     * @param int $pollIntervalSeconds Poll interval
     * @return array Array of commenter profiles
     */
    private function fetchPostComments(
        string $postUrl,
        int $maxWaitSeconds = 300,
        int $pollIntervalSeconds = 10
    ): array {
        $phantomId = config('services.phantombuster.linkedin_post_comments_phantom_id');
        
        if (!$phantomId) {
            $errorMessage = 'LinkedIn Post Comments phantom ID not configured. Please set PHANTOMBUSTER_LINKEDIN_POST_COMMENTS_PHANTOM_ID in your .env file.';
            Log::error('PhantomBuster: ' . $errorMessage);
            throw new \Exception($errorMessage);
        }

        $arguments = [
            'postUrl' => $postUrl,
        ];
        
        // Add session cookie and user agent
        $sessionCookie = $this->getSessionCookie();
        if ($sessionCookie) {
            $arguments['sessionCookie'] = $sessionCookie;
        }
        
        $userAgent = $this->getUserAgent();
        if ($userAgent) {
            $arguments['userAgent'] = $userAgent;
        }

        $launchResponse = $this->launchPhantom($phantomId, $arguments);
        $containerId = $launchResponse['containerId'] ?? $launchResponse['data']['containerId'] ?? null;
        
        if (!$containerId) {
            throw new \Exception("Failed to get container ID from PhantomBuster launch for post comments");
        }

        // Wait a bit before first poll
        sleep(5);

        // Poll for completion
        $startTime = time();
        $attempts = 0;
        $last404Time = null;
        
        while (time() - $startTime < $maxWaitSeconds) {
            $attempts++;
            
            try {
                if ($attempts > 1) {
                    sleep($pollIntervalSeconds);
                }

                $output = $this->getPhantomOutput($phantomId, $containerId);
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Container not found')) {
                    if ($last404Time === null) {
                        $last404Time = time();
                    }
                    
                    if (time() - $last404Time > 30) {
                        Log::error('PhantomBuster: Container not found after multiple attempts', [
                            'phantom_id' => $phantomId,
                            'container_id' => $containerId,
                            'attempts' => $attempts,
                            'post_url' => $postUrl
                        ]);
                        return [];
                    }
                    
                    continue;
                }
                
                throw $e;
            }
            
            // Check multiple possible locations for the data
            $comments = $output['data']['output'] 
                ?? $output['data']['resultObject'] 
                ?? $output['output'] 
                ?? [];
            
            $containerStatus = $output['data']['containerStatus'] ?? null;
            $agentStatus = $output['data']['agentStatus'] ?? null;
            $messages = $output['data']['messages'] ?? [];
            $progress = $output['data']['progress'] ?? null;
            
            if (is_array($comments) && !empty($comments)) {
                return $comments;
            }
            
            // Check if phantom is finished
            if ($containerStatus === 'not running' || $containerStatus === 'finished' || $containerStatus === 'completed') {
                // Check for data in resultObject
                if (isset($output['data']['resultObject'])) {
                    $resultObject = $output['data']['resultObject'];
                    
                    // If resultObject is a JSON string, try to decode it
                    if (is_string($resultObject)) {
                        $decoded = json_decode($resultObject, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            if (isset($decoded[0]['error'])) {
                                Log::error('PhantomBuster: Phantom returned error in resultObject', [
                                    'error' => $decoded[0]['error'],
                                    'post_url' => $postUrl
                                ]);
                                break;
                            }
                            return $decoded;
                        }
                    }
                    
                    if (is_array($resultObject) && !empty($resultObject)) {
                        if (isset($resultObject[0]['error'])) {
                            Log::error('PhantomBuster: Phantom returned error in resultObject', [
                                'error' => $resultObject[0]['error'],
                                'post_url' => $postUrl
                            ]);
                            break;
                        }
                        return $resultObject;
                    }
                }
                
                // Check output array
                $outputArray = $output['data']['output'] ?? null;
                if ($outputArray !== null) {
                    if (is_string($outputArray)) {
                        $decodedOutput = json_decode($outputArray, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOutput)) {
                            if (isset($decodedOutput[0]['error'])) {
                                Log::error('PhantomBuster: Output error', ['error' => $decodedOutput[0]['error']]);
                            } elseif (!empty($decodedOutput)) {
                                return $decodedOutput;
                            }
                        }
                    } elseif (is_array($outputArray) && !empty($outputArray)) {
                        if (isset($outputArray[0]['error'])) {
                            Log::error('PhantomBuster: Output error', ['error' => $outputArray[0]['error']]);
                        } else {
                            return $outputArray;
                        }
                    }
                }
                
                break;
            }
            
            if ($containerStatus === 'error') {
                Log::error('PhantomBuster: Phantom error', [
                    'messages' => $messages,
                    'agent_status' => $agentStatus
                ]);
                break;
            }
        }

        Log::error('PhantomBuster: Timeout or no data for post comments', [
            'post_url' => $postUrl,
            'waited_seconds' => time() - $startTime,
            'max_wait_seconds' => $maxWaitSeconds
        ]);
        return [];
    }

    /**
     * Public method to fetch post comments for a URL
     * Similar to fetchPostLikersForUrl
     * 
     * @param string $postUrl LinkedIn post URL
     * @param int $maxWaitSeconds Maximum seconds to wait
     * @param int $pollIntervalSeconds Poll interval
     * @param string|null $sessionCookie Override session cookie
     * @param string|null $userAgent Override user agent
     * @return array Array of commenter profiles
     */
    public function fetchPostCommentsForUrl(
        string $postUrl,
        int $maxWaitSeconds = 600,
        int $pollIntervalSeconds = 15,
        ?string $sessionCookie = null,
        ?string $userAgent = null
    ): array {
        // Override session cookie and user agent if provided
        if ($sessionCookie) {
            $this->sessionCookieOverride = $sessionCookie;
        }
        if ($userAgent) {
            $this->userAgentOverride = $userAgent;
        }
        
        try {
            return $this->fetchPostComments($postUrl, $maxWaitSeconds, $pollIntervalSeconds);
        } finally {
            // Reset overrides
            $this->sessionCookieOverride = null;
            $this->userAgentOverride = null;
        }
    }


    private function getSessionCookie(): ?string
    {
        return $this->sessionCookieOverride ?? config('services.phantombuster.linkedin_session_cookie');
    }

    private function getUserAgent(): ?string
    {
        return $this->userAgentOverride ?? config('services.phantombuster.linkedin_user_agent');
    }

    /**
     * Scrape a LinkedIn profile to get full profile data including email
     * 
     * @param string $profileUrl LinkedIn profile URL (e.g., https://www.linkedin.com/in/username/)
     * @param string|null $sessionCookie Optional override for li_at
     * @param string|null $userAgent Optional override for user agent
     * @param array|null $identities Optional identities array format
     * @param int $maxWaitSeconds Maximum seconds to wait for the phantom to finish
     * @param int $pollIntervalSeconds Poll interval while waiting
     * @return array Profile data including email if available
     * @throws \Exception
     */
    public function scrapeLinkedInProfile(
        string $profileUrl,
        ?string $sessionCookie = null,
        ?string $userAgent = null,
        ?array $identities = null,
        int $maxWaitSeconds = 300,
        int $pollIntervalSeconds = 10
    ): array {
        $this->sessionCookieOverride = $sessionCookie;
        $this->userAgentOverride = $userAgent;

        $phantomId = config('services.phantombuster.linkedin_profile_scraper_phantom_id');
        
        if (!$phantomId) {
            Log::error('PhantomBuster: LinkedIn Profile Scraper phantom ID not configured. Please set PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID in your .env file.');
            throw new \Exception('LinkedIn Profile Scraper phantom ID not configured. Please set PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID in your .env file.');
        }

        // Per-agent lock to ensure only one instance of each agent runs at a time
        // PhantomBuster API only allows 1 parallel execution per agent
        // Lock must be held for the ENTIRE operation (launch + polling), not just the API call
        $lockKey = "phantombuster:agent:{$phantomId}:launch_lock";
        $lockTimeout = 600; // 10 minutes - maximum time to wait for lock (increased to match job timeout)
        $lockDuration = 900; // 15 minutes - how long to hold the lock if not manually released (safety net, increased to prevent stuck locks)
        
        $lock = Cache::lock($lockKey, $lockDuration);
        
        // Log removed to reduce verbosity - only log lock failures
        
        // Acquire the lock, blocking up to $lockTimeout seconds
        $acquired = $lock->block($lockTimeout);
        
        if (!$acquired) {
            Log::error('PhantomBuster: Failed to acquire per-agent lock after timeout', [
                'phantom_id' => $phantomId,
                'lock_key' => $lockKey,
                'timeout_seconds' => $lockTimeout,
                'message' => 'Another job is holding the lock for this agent. The job will fail and can be retried later.'
            ]);
            throw new \Exception(
                "LOCK_TIMEOUT: PhantomBuster agent {$phantomId} is currently processing another request and the lock could not be acquired after waiting {$lockTimeout} seconds. " .
                "This job will be reset so you can try again. " .
                "Each agent allows only 1 parallel execution at a time."
            );
        }

        try {

         
            $arguments = [
                'urls' => [$profileUrl],
                'spreadsheetUrl' => $profileUrl, // Use profile URL instead of shared spreadsheet to avoid cached data
                'emailChooser' => 'phantombuster',
                'enrichWithCompanyData' => false,
                'updateMonitoringMetadata' => false,
                'pushResultToCRM' => true, // Enable pushing results to CRM as per user's example
                'numberOfAddsPerLaunch' => 30, // Use 30 as per user's example
            ];

            // Use identities array format if provided, otherwise use top-level sessionCookie/userAgent
            if (!empty($identities) && is_array($identities)) {
                $formattedIdentities = [];
                foreach ($identities as $identity) {
                    if (is_array($identity) && isset($identity['sessionCookie'])) {
                        $formattedIdentity = [
                            'sessionCookie' => $identity['sessionCookie']
                        ];
                        
                        if (isset($identity['identityId']) && !empty($identity['identityId'])) {
                            $formattedIdentity['identityId'] = $identity['identityId'];
                        }
                        
                        if (isset($identity['userAgent']) && !empty($identity['userAgent'])) {
                            $formattedIdentity['userAgent'] = $identity['userAgent'];
                        } elseif ($userAgent) {
                            $formattedIdentity['userAgent'] = $userAgent;
                        } elseif ($this->getUserAgent()) {
                            $formattedIdentity['userAgent'] = $this->getUserAgent();
                        }
                        
                        $formattedIdentities[] = $formattedIdentity;
                    }
                }
                
                if (!empty($formattedIdentities)) {
                    $arguments['identities'] = $formattedIdentities;
                    
                }
            } else {
                $sessionCookieValue = $this->getSessionCookie();
                if ($sessionCookieValue) {
                    $arguments['sessionCookie'] = $sessionCookieValue;
                }

                $userAgentValue = $this->getUserAgent();
                if ($userAgentValue) {
                    $arguments['userAgent'] = $userAgentValue;
                }
            }


            $launchResponse = $this->launchPhantom($phantomId, $arguments);
            $containerId = $launchResponse['containerId'] ?? $launchResponse['data']['containerId'] ?? null;

            if (!$containerId) {
                throw new \Exception("Failed to get container ID from PhantomBuster launch for profile scraper");
            }

            sleep(5);
            
            // Log removed to reduce verbosity - only log errors

            $startTime = time();
            $attempts = 0;
            $profileData = null;
            $lastStatus = null;
            $sameStatusCount = 0;

            while (time() - $startTime < $maxWaitSeconds) {
                $attempts++;

                if ($attempts > 1) {
                    // Adaptive polling: if status hasn't changed, poll less frequently
                    if ($lastStatus === 'running' && $sameStatusCount > 3) {
                        sleep($pollIntervalSeconds * 2); // Poll every 20 seconds instead of 10 after 3 attempts
                    } else {
                        sleep($pollIntervalSeconds);
                    }
                }

                try {
                    $output = $this->getPhantomOutput($phantomId, $containerId);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Container not found')) {
                        continue;
                    }
                    throw $e;
                }

                $containerStatus = $output['data']['containerStatus'] ?? null;
                
                // Track status changes for adaptive polling
                if ($containerStatus === $lastStatus && $containerStatus === 'running') {
                    $sameStatusCount++;
                } else {
                    $sameStatusCount = 0;
                }
                $lastStatus = $containerStatus;

                $resultObject = $output['data']['resultObject'] ?? null;
                $outputData = $output['data']['output'] ?? null;
                $messages = $output['data']['messages'] ?? [];
                $progress = $output['data']['progress'] ?? null;
                
                if (!empty($messages)) {
                    $errorMessages = array_filter($messages, function($msg) {
                        return is_string($msg) && (
                            stripos($msg, 'error') !== false ||
                            stripos($msg, 'failed') !== false ||
                            stripos($msg, 'invalid') !== false ||
                            stripos($msg, 'expired') !== false ||
                            stripos($msg, 'Invalid argument') !== false
                        );
                    });
                    
                    if (!empty($errorMessages)) {
                        Log::warning('PhantomBuster: Error messages in output', [
                            'profile_url' => $profileUrl,
                            'messages' => $messages,
                            'error_messages' => array_values($errorMessages)
                        ]);
                    }
                }

                if ($resultObject) {
                    if (is_string($resultObject)) {
                        $decoded = json_decode($resultObject, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $profileData = is_array($decoded) && isset($decoded[0]) ? $decoded[0] : $decoded;
                        }
                    } elseif (is_array($resultObject)) {
                        $profileData = isset($resultObject[0]) ? $resultObject[0] : $resultObject;
                    }
                }

                if (!$profileData && $outputData) {
                    if (is_string($outputData)) {
                        $trimmed = trim($outputData);
                        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                            $decoded = json_decode($outputData, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $profileData = is_array($decoded) && isset($decoded[0]) ? $decoded[0] : $decoded;
                            }
                        }
                    } elseif (is_array($outputData)) {
                        $profileData = isset($outputData[0]) ? $outputData[0] : $outputData;
                    }
                }

                $agentStatus = $output['data']['agentStatus'] ?? null;

                // Exit early if we have profile data, even if container is still running
                // We'll check for email in the job itself
                if ($profileData && !empty($profileData)) {
                    return $profileData;
                }

                if ($containerStatus === 'not running' || $containerStatus === 'finished' || $containerStatus === 'completed') {
                    if ($profileData) {
                        return $profileData;
                    }
                    break;
                }
            }

            return $profileData ?? [];
        } catch (\Throwable $th) {
            Log::error('PhantomBuster: Failed to scrape profile', [
                'profile_url' => $profileUrl,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            throw $th;
        } finally {
            // Always release the lock, even if an exception was thrown
            // Lock must be held for entire operation (launch + polling)
            if ($acquired) {
                $lock->release();
                // Log removed to reduce verbosity
            }
        }
    }

    /**
     * Scrape multiple LinkedIn profiles in batch using urls array parameter
     * 
     * @param array<string> $profileUrls Array of LinkedIn profile URLs
     * @param array|null $identities Identities array for authentication
     * @param int $maxWaitSeconds Maximum wait time in seconds
     * @param int $pollIntervalSeconds Poll interval while waiting
     * @return array<string, array> Map of profile URL to profile data
     * @throws \Exception
     */
    public function scrapeLinkedInProfilesBatch(
        array $profileUrls,
        ?array $identities = null,
        int $maxWaitSeconds = 600,
        int $pollIntervalSeconds = 15
    ): array {
        try {
            $phantomId = config('services.phantombuster.linkedin_profile_scraper_phantom_id');
            
            if (!$phantomId) {
                Log::error('PhantomBuster: LinkedIn Profile Scraper phantom ID not configured');
                throw new \Exception('LinkedIn Profile Scraper phantom ID not configured');
            }

            if (empty($profileUrls)) {
                throw new \Exception('No profile URLs provided for batch processing');
            }

            // Limit to 20 profiles per batch
            $profileUrls = array_slice($profileUrls, 0, 20);
            $profileCount = count($profileUrls);

            // Build arguments with urls array (tested and confirmed working)
            // Note: PhantomBuster requires spreadsheetUrl even when using urls array
            // Use comma-separated URLs as spreadsheetUrl (PhantomBuster will use urls array if provided)
            $spreadsheetUrl = implode(',', $profileUrls);
            
            $arguments = [
                'urls' => $profileUrls, // Use urls array parameter for batch processing
                'spreadsheetUrl' => $spreadsheetUrl, // Required by PhantomBuster API even with urls array
                'emailChooser' => 'phantombuster',
                'enrichWithCompanyData' => false,
                'updateMonitoringMetadata' => false,
                'pushResultToCRM' => true,
                'numberOfAddsPerLaunch' => $profileCount, // Set to actual count
            ];

            // Add identities if provided
            if (!empty($identities) && is_array($identities)) {
                $formattedIdentities = [];
                foreach ($identities as $identity) {
                    if (is_array($identity) && isset($identity['sessionCookie'])) {
                        $formattedIdentity = [
                            'sessionCookie' => $identity['sessionCookie']
                        ];
                        
                        if (isset($identity['identityId']) && !empty($identity['identityId'])) {
                            $formattedIdentity['identityId'] = $identity['identityId'];
                        }
                        
                        if (isset($identity['userAgent']) && !empty($identity['userAgent'])) {
                            $formattedIdentity['userAgent'] = $identity['userAgent'];
                        } elseif ($this->getUserAgent()) {
                            $formattedIdentity['userAgent'] = $this->getUserAgent();
                        }
                        
                        $formattedIdentities[] = $formattedIdentity;
                    }
                }
                
                if (!empty($formattedIdentities)) {
                    $arguments['identities'] = $formattedIdentities;
                }
            } else {
                $sessionCookieValue = $this->getSessionCookie();
                if ($sessionCookieValue) {
                    $arguments['sessionCookie'] = $sessionCookieValue;
                }

                $userAgentValue = $this->getUserAgent();
                if ($userAgentValue) {
                    $arguments['userAgent'] = $userAgentValue;
                }
            }

            // Launch phantom with batch URLs
            $launchResponse = $this->launchPhantom($phantomId, $arguments);
            $containerId = $launchResponse['containerId'] ?? $launchResponse['data']['containerId'] ?? null;

            if (!$containerId) {
                throw new \Exception("Failed to get container ID from PhantomBuster launch for batch profile scraper");
            }

            // Wait longer for batch processing to start
            sleep(10);
            
            // Log removed to reduce verbosity - only log errors

            $startTime = time();
            $attempts = 0;
            $results = [];
            $lastStatus = null;

            while (time() - $startTime < $maxWaitSeconds) {
                $attempts++;

                if ($attempts > 1) {
                    sleep($pollIntervalSeconds);
                }

                try {
                    $output = $this->getPhantomOutput($phantomId, $containerId);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Container not found')) {
                        continue;
                    }
                    throw $e;
                }

                $containerStatus = $output['data']['containerStatus'] ?? null;
                $lastStatus = $containerStatus;

                $resultObject = $output['data']['resultObject'] ?? null;
                $outputData = $output['data']['output'] ?? null;
                $csvUrl = $output['data']['csvUrl'] ?? null;

                // Parse batch results - should be an array of profile data
                $batchResults = null;
                
                if ($resultObject) {
                    if (is_string($resultObject)) {
                        $decoded = json_decode($resultObject, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $batchResults = $decoded;
                        }
                    } elseif (is_array($resultObject)) {
                        $batchResults = $resultObject;
                    }
                }

                if (!$batchResults && $outputData) {
                    if (is_string($outputData)) {
                        $trimmed = trim($outputData);
                        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                            $decoded = json_decode($outputData, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $batchResults = $decoded;
                            }
                        }
                    } elseif (is_array($outputData)) {
                        $batchResults = $outputData;
                    }
                }

                // If CSV URL is available, try fetching results from CSV
                if (!$batchResults && $csvUrl && ($containerStatus === 'finished' || $containerStatus === 'completed')) {
                    try {
                        $csvResponse = file_get_contents($csvUrl);
                        if ($csvResponse) {
                            // Parse CSV and convert to array
                            $lines = explode("\n", trim($csvResponse));
                            $headers = str_getcsv(array_shift($lines));
                            $batchResults = [];
                            
                            foreach ($lines as $line) {
                                if (empty(trim($line))) continue;
                                $row = str_getcsv($line);
                                if (count($row) === count($headers)) {
                                    $batchResults[] = array_combine($headers, $row);
                                }
                            }
                            
                        }
                    } catch (\Throwable $csvError) {
                        Log::warning('PhantomBuster: Failed to fetch CSV results', [
                            'container_id' => $containerId,
                            'csv_url' => $csvUrl,
                            'error' => $csvError->getMessage()
                        ]);
                    }
                }

                // If we have batch results, map them to URLs
                if ($batchResults && is_array($batchResults) && !empty($batchResults)) {
                    // PhantomBuster returns array of profile objects
                    // Each object should have a URL or we match by index
                    foreach ($batchResults as $index => $profileData) {
                        if (is_array($profileData) && !empty($profileData)) {
                            // Try to match by profileUrl field if available
                            $profileUrl = $profileData['profileUrl'] 
                                ?? $profileData['url'] 
                                ?? $profileData['linkedinUrl']
                                ?? ($index < count($profileUrls) ? $profileUrls[$index] : null);
                            
                            if ($profileUrl && isset($profileUrls[array_search($profileUrl, $profileUrls)])) {
                                $results[$profileUrl] = $profileData;
                            } elseif ($index < count($profileUrls)) {
                                // Fallback to index matching
                                $results[$profileUrls[$index]] = $profileData;
                            }
                        }
                    }

                    // If we got results for all profiles, return early
                    if (count($results) >= $profileCount) {
                        return $results;
                    }
                }

                if ($containerStatus === 'not running' || $containerStatus === 'finished' || $containerStatus === 'completed') {
                    // Wait a bit more for results to be available
                    if (empty($batchResults) && $attempts < 3) {
                        sleep(5);
                        continue;
                    }
                    
                    if ($batchResults && !empty($batchResults)) {
                        // Process final results
                        foreach ($batchResults as $index => $profileData) {
                            if (is_array($profileData) && !empty($profileData)) {
                                $profileUrl = $profileData['profileUrl'] 
                                    ?? $profileData['url'] 
                                    ?? $profileData['linkedinUrl']
                                    ?? $profileData['Profile URL']
                                    ?? ($index < count($profileUrls) ? $profileUrls[$index] : null);
                                
                                if ($profileUrl && in_array($profileUrl, $profileUrls)) {
                                    $results[$profileUrl] = $profileData;
                                } elseif ($index < count($profileUrls)) {
                                    $results[$profileUrls[$index]] = $profileData;
                                }
                            }
                        }
                    } else {
                        Log::warning('PhantomBuster: Container finished but no batch results found', [
                            'container_id' => $containerId,
                            'container_status' => $containerStatus,
                            'has_resultObject' => !empty($resultObject),
                            'has_outputData' => !empty($outputData),
                            'has_csvUrl' => !empty($csvUrl)
                        ]);
                    }
                    break;
                }
            }

            return $results;

        } catch (\Throwable $th) {
            Log::error('PhantomBuster: Failed to scrape profiles in batch', [
                'profile_urls' => $profileUrls,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            throw $th;
        }
    }

}

