<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInApiService
{
    protected $baseUrl = 'https://api.linkedin.com/v2';
    protected $restBaseUrl = 'https://api.linkedin.com/rest';
    protected $accessToken;

    public function __construct($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Get posts from a specific author using the Posts API
     * 
     * @param string $authorUrn LinkedIn URN of the author (e.g., urn:li:person:ABC123)
     * @param array $params Additional query parameters
     * @return array
     */
    public function getPostsByAuthor($authorUrn, $params = [])
    {
        try {
            $queryParams = array_merge([
                'q' => 'author',
                'author' => $authorUrn,
                'start' => 0,
                'count' => 10,
            ], $params);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->get("{$this->restBaseUrl}/posts", $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('LinkedIn API Error - Get Posts by Author', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return ['elements' => [], 'paging' => []];
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Get Posts by Author', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return ['elements' => [], 'paging' => []];
        }
    }

    /**
     * Get a specific post by URN
     * 
     * @param string $postUrn LinkedIn post URN (e.g., urn:li:activity:123456789)
     * @return array|null
     */
    public function getPost($postUrn)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->get("{$this->restBaseUrl}/posts/{$postUrn}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('LinkedIn API Error - Get Post', [
                'post_urn' => $postUrn,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Get Post', [
                'post_urn' => $postUrn,
                'error' => $th->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Post a comment on a LinkedIn post using Social Actions API
     * 
     * @param string $targetUrn Post URN or Activity URN (e.g., urn:li:activity:123456789)
     * @param string $actorUrn User/organization URN making the comment
     * @param string $commentText The comment text
     * @return array|null
     */
    public function postComment($targetUrn, $actorUrn, $commentText)
    {
        try {
            // Remove urn: prefix if present in targetUrn for the endpoint
            $targetUrn = str_replace('urn:li:', '', $targetUrn);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
                'X-Restli-Protocol-Version' => '2.0.0',
                'Content-Type' => 'application/json',
            ])->post("{$this->restBaseUrl}/socialActions/{$targetUrn}/comments", [
                'actor' => $actorUrn,
                'object' => $targetUrn,
                'message' => [
                    'text' => $commentText
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('LinkedIn Comment Posted Successfully', [
                    'target_urn' => $targetUrn,
                    'comment_urn' => $result['id'] ?? null
                ]);
                return $result;
            }

            Log::error('LinkedIn API Error - Post Comment', [
                'target_urn' => $targetUrn,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Post Comment', [
                'target_urn' => $targetUrn,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update/Edit a comment
     * 
     * @param string $targetUrn Post URN
     * @param string $commentId Comment ID
     * @param string $actorUrn Actor URN
     * @param string $newCommentText Updated comment text
     * @return array|null
     */
    public function updateComment($targetUrn, $commentId, $actorUrn, $newCommentText)
    {
        try {
            $targetUrn = str_replace('urn:li:', '', $targetUrn);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
                'X-Restli-Protocol-Version' => '2.0.0',
                'X-Restli-Method' => 'PARTIAL_UPDATE',
                'Content-Type' => 'application/json',
            ])->post("{$this->restBaseUrl}/socialActions/{$targetUrn}/comments/{$commentId}?actor={$actorUrn}", [
                'message' => [
                    'text' => $newCommentText
                ]
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('LinkedIn API Error - Update Comment', [
                'comment_id' => $commentId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Update Comment', [
                'comment_id' => $commentId,
                'error' => $th->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete a comment
     * 
     * @param string $targetUrn Post URN
     * @param string $commentId Comment ID
     * @param string $actorUrn Actor URN
     * @return bool
     */
    public function deleteComment($targetUrn, $commentId, $actorUrn)
    {
        try {
            $targetUrn = str_replace('urn:li:', '', $targetUrn);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->delete("{$this->restBaseUrl}/socialActions/{$targetUrn}/comments/{$commentId}?actor={$actorUrn}");

            if ($response->successful() || $response->status() === 204) {
                return true;
            }

            Log::error('LinkedIn API Error - Delete Comment', [
                'comment_id' => $commentId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return false;
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Delete Comment', [
                'comment_id' => $commentId,
                'error' => $th->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get comments on a post
     * 
     * @param string $targetUrn Post URN
     * @return array
     */
    public function getComments($targetUrn)
    {
        try {
            $targetUrn = str_replace('urn:li:', '', $targetUrn);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->get("{$this->restBaseUrl}/socialActions/{$targetUrn}/comments");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('LinkedIn API Error - Get Comments', [
                'target_urn' => $targetUrn,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return ['elements' => []];
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Get Comments', [
                'target_urn' => $targetUrn,
                'error' => $th->getMessage()
            ]);
            return ['elements' => []];
        }
    }

    /**
     * Get user's profile information to get actor URN
     * 
     * @return array|null
     */
    public function getUserProfile()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
            ])->get("{$this->baseUrl}/userinfo");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Get User Profile', [
                'error' => $th->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Search for posts by keywords using official LinkedIn API
     * Note: Official LinkedIn API doesn't support public keyword search directly.
     * This method fetches posts from user's network/feed and filters by keywords.
     * 
     * Alternative: Use RapidAPI for keyword search if official API doesn't meet needs.
     * 
     * @param array $keywords Array of keywords to search for
     * @param array $params Additional query parameters
     * @return array
     */
    public function searchPostsByKeywords($keywords, $params = [])
    {
        try {
            // Official LinkedIn API limitation: No direct keyword search endpoint
            // We can fetch from user's feed/network and filter client-side
            // Or use organization posts if user has access
            
            // For now, return empty as official API doesn't support this
            Log::warning('LinkedIn Official API does not support keyword search of public posts', [
                'keywords' => $keywords,
                'note' => 'Consider using RapidAPI or fetch from specific organizations'
            ]);

            return ['elements' => [], 'paging' => []];
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Search Posts by Keywords', [
                'keywords' => $keywords,
                'error' => $th->getMessage()
            ]);
            return ['elements' => [], 'paging' => []];
        }
    }

    /**
     * Get posts from a specific organization/company
     * 
     * @param string $organizationUrn LinkedIn organization URN
     * @param array $params Additional query parameters
     * @return array
     */
    public function getPostsByOrganization($organizationUrn, $params = [])
    {
        try {
            $queryParams = array_merge([
                'q' => 'authors',
                'authors' => 'List(' . $organizationUrn . ')',
                'start' => 0,
                'count' => 10,
            ], $params);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Linkedin-Version' => '202601',
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->get("{$this->restBaseUrl}/posts", $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('LinkedIn API Error - Get Posts by Organization', [
                'organization_urn' => $organizationUrn,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return ['elements' => [], 'paging' => []];
        } catch (\Throwable $th) {
            Log::error('LinkedIn API Exception - Get Posts by Organization', [
                'organization_urn' => $organizationUrn,
                'error' => $th->getMessage()
            ]);
            return ['elements' => [], 'paging' => []];
        }
    }

    /**
     * Filter posts by keywords (client-side filtering)
     * Use this after fetching posts to filter by keywords
     * 
     * @param array $posts Array of post data
     * @param array $keywords Array of keywords to match
     * @return array Filtered posts
     */
    public function filterPostsByKeywords($posts, $keywords)
    {
        if (empty($keywords) || empty($posts)) {
            return $posts;
        }

        $filtered = [];
        
        foreach ($posts as $post) {
            $postText = '';
            
            // Extract post text from various possible locations in API response
            if (isset($post['specificContent']['com.linkedin.ugc.ShareContent']['text']['text'])) {
                $postText = $post['specificContent']['com.linkedin.ugc.ShareContent']['text']['text'];
            } elseif (isset($post['commentary'])) {
                $postText = $post['commentary'];
            } elseif (isset($post['text'])) {
                $postText = $post['text'];
            }

            $postTextLower = strtolower($postText);
            
            // Check if any keyword matches
            foreach ($keywords as $keyword) {
                if (str_contains($postTextLower, strtolower(trim($keyword)))) {
                    $filtered[] = $post;
                    break; // Found a match, no need to check other keywords
                }
            }
        }

        return $filtered;
    }
}
