<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Uri;
use Illuminate\Support\Facades\Http;
use App\Models\Post;
use Illuminate\Support\Facades\Log;

class LinkedInService
{
    public $api;
    private $client;
    private $secret;
    public $state;

    public function __construct()
    {
        $this->api = config('services.linkedin.api');
        $this->client = config('services.linkedin.client');
        $this->secret = config('services.linkedin.secret');
        $this->state = config('services.linkedin.state');
    }

    public function login(string $prompt = 'consent')
    {
        $url = 'https://www.linkedin.com/oauth/v2/authorization';

        $callback_url = URL::route('integration.callback');

        $prompt = in_array($prompt, ['none', 'login', 'consent'], true) ? $prompt : 'none';

        return "{$url}?response_type=code&client_id={$this->client}&redirect_uri={$callback_url}&state={$this->state}&scope=openid%20profile%20email%20w_member_social%20r_basicprofile%20r_organization_social%20w_organization_social%20rw_organization_admin&prompt={$prompt}";
        // return "{$url}?response_type=code&client_id={$this->client}&redirect_uri={$callback_url}&state={$this->state}&scope=openid%20profile%20email%20w_member_social%20r_liteprofile%20r_organization_social%20w_organization_social%20rw_organization_admin";
    }

    public function getAccessToken($oauth_code)
    {
        $url = 'https://www.linkedin.com/oauth/v2/accessToken';

        $callback_url = URL::route('integration.callback');

        $params = [
            'grant_type' => 'authorization_code',
            'code' => $oauth_code,
            'redirect_uri' => $callback_url,
            'client_id' => $this->client,
            'client_secret' => $this->secret
        ];

        return Http::asForm()
            ->post($url, $params)
            ->throw()
            ->json();
    }

    public function refreshAccessToken($refresh_token)
    {
        $url = 'https://www.linkedin.com/oauth/v2/accessToken';

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->client,
            'client_secret' => $this->secret
        ];

        return Http::asForm()
            ->post($url, $params)
            ->throw()
            ->json();
    }

    public function getUserProfile($access_token, bool $includeVanityName = false)
    {
        $api_url = $this->api . '/me';

        $params = [];
        if ($includeVanityName) {
            $params['projection'] = '(id,vanityName,localizedFirstName,localizedLastName)';
        }

        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
        ];

        return Http::withHeaders($headers)
            ->when(!empty($params), fn ($request) => $request->withQueryParameters($params))
            ->get($api_url)
            ->throw()
            ->json();
    }

    public function getUserProfileImg($access_token)
    {
        $api_url = $this->api . '/me';

        $params = [
            'projection' => '(id,profilePicture(displayImage~digitalmediaAsset:playableStreams))'
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
        ];

        return Http::withHeaders($headers)
            ->withQueryParameters($params)
            ->get($api_url)
            ->throw()
            ->json();
    }

    public function getOpenIDProfile($access_token)
    {
        $api_url = $this->api . '/userinfo';

        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
        ];

        return Http::withHeaders($headers)
            ->get($api_url)
            ->throw()
            ->json();
    }

    /**
     * Publish post using LinkedIn API v2 (Official /rest/posts endpoint)
     */
    public function publishPostV2($linkedInPost, $integration)
    {
        $api_url = "https://api.linkedin.com/rest/posts";
        $access_token = $integration->access_token;
        $author = "urn:li:person:" . $integration->oauth_uid;

        // Refresh token if expired
        if ($this->isTokenExpired($integration)) {
            try {
                $newTokens = $this->refreshAccessToken($integration->refresh_token);
                $integration->update([
                    'access_token' => $newTokens['access_token'],
                    'refresh_token' => $newTokens['refresh_token'] ?? $integration->refresh_token,
                    'expires_in' => $newTokens['expires_in'],
                    'updated_at' => now()
                ]);
                $access_token = $newTokens['access_token'];
            } catch (\Exception $e) {
                Log::error('Token refresh failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
            'LinkedIn-Version' => '202601',
            'X-Restli-Protocol-Version' => '2.0.0'
        ];

        $post_body = $this->buildPostBodyV2($linkedInPost, $author, $access_token);

        try {
            $httpResponse = Http::withHeaders($headers)->post($api_url, $post_body);
            $httpResponse->throw();

            // Extract post ID from x-restli-id header
            $responseHeaders = $httpResponse->headers();
            $postId = $responseHeaders['x-restli-id'][0] ?? null;
            
            $responseBody = !empty($httpResponse->body()) ? $httpResponse->json() : null;

            return [
                'id' => $postId,
                'status' => $httpResponse->status(),
                'data' => $responseBody
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn API failed', ['post_id' => $linkedInPost->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Fetch analytics for a LinkedIn post
     */
    public function fetchPostAnalytics($linkedinPostId, $accessToken)
    {
        Log::info('📊 Fetching post analytics', [
            'linkedin_post_id' => $linkedinPostId,
            'has_access_token' => !empty($accessToken)
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'LinkedIn-Version' => '202601',
            'X-Restli-Protocol-Version' => '2.0.0'
        ];

        try {
            // Try multiple LinkedIn Analytics API endpoints
            $endpoints = [
                "https://api.linkedin.com/rest/ugcPosts/{$linkedinPostId}/statistics",
                "https://api.linkedin.com/v2/ugcPosts/{$linkedinPostId}/statistics", 
                "https://api.linkedin.com/rest/socialActions/{$linkedinPostId}/statistics",
                "https://api.linkedin.com/v2/socialActions/{$linkedinPostId}/statistics"
            ];
            
            foreach ($endpoints as $analyticsUrl) {
                Log::info('📊 Trying analytics endpoint', ['url' => $analyticsUrl]);
                
                $response = Http::withHeaders($headers)->get($analyticsUrl);
                
                Log::info('📊 Analytics API Response', [
                    'endpoint' => $analyticsUrl,
                    'status_code' => $response->status(),
                    'body' => substr($response->body(), 0, 200)
                ]);

                if ($response->successful()) {
                    $analytics = $response->json();
                    
                    // Extract metrics from LinkedIn's response format
                    $metrics = [
                        'likes' => $analytics['likeCount'] ?? $analytics['likes'] ?? 0,
                        'comments' => $analytics['commentCount'] ?? $analytics['comments'] ?? 0,
                        'shares' => $analytics['shareCount'] ?? $analytics['shares'] ?? 0,
                        'views' => $analytics['impressionCount'] ?? $analytics['views'] ?? 0,
                        'clicks' => $analytics['clickCount'] ?? $analytics['clicks'] ?? 0,
                        'last_updated' => now()->toISOString()
                    ];

                    Log::info('✅ Analytics extracted', ['metrics' => $metrics]);
                    return $metrics;
                } elseif ($response->status() !== 404) {
                    // If it's not 404, log the error but continue trying other endpoints
                    Log::warning('⚠️ Analytics API returned error', [
                        'endpoint' => $analyticsUrl,
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 200)
                    ]);
                }
            }
            
            // If all endpoints failed, return null
            Log::warning('⚠️ All analytics endpoints failed for post', ['linkedin_post_id' => $linkedinPostId]);
            return null;
        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch analytics', [
                'error' => $e->getMessage(),
                'linkedin_post_id' => $linkedinPostId
            ]);
            return null;
        }
    }

    /**
     * Build post body for LinkedIn API v2
     */
    private function buildPostBodyV2($post, $author, $access_token)
    {
        $postBody = [
            "author" => $author,
            "commentary" => $post->content,
            "visibility" => "PUBLIC",
            "distribution" => [
                "feedDistribution" => "MAIN_FEED",
                "targetEntities" => [],
                "thirdPartyDistributionChannels" => []
            ],
            "lifecycleState" => "PUBLISHED",
            "isReshareDisabledByAuthor" => false
        ];

        // Add media based on post type
        if ($post->post_type === 'image' && $post->image_url) {
            // Model accessor returns array or string automatically
            $imageUrls = $post->image_url;
            
            if (is_array($imageUrls) && count($imageUrls) > 1) {
                // Multiple images - use multiImage format
                Log::info('📸 Uploading multiple images', ['count' => count($imageUrls)]);
                $postBody['content'] = $this->buildMultiImageContent($imageUrls, $author, $access_token);
            } elseif (is_array($imageUrls) && count($imageUrls) === 1) {
                // Single image from array - use media format
                Log::info('📸 Uploading single image from array');
                $imageId = $this->uploadImageV2($imageUrls[0], $author, $access_token);
                $postBody['content'] = [
                    "media" => [
                        "title" => substr($post->content, 0, 100),
                        "id" => $imageId
                    ]
                ];
            } else {
                // Single image URL as string
                Log::info('📸 Uploading single image');
                $imageId = $this->uploadImageV2($imageUrls, $author, $access_token);
                $postBody['content'] = [
                    "media" => [
                        "title" => substr($post->content, 0, 100),
                        "id" => $imageId
                    ]
                ];
            }
        } elseif ($post->post_type === 'video' && $post->video_url) {
            Log::info('🎥 Uploading video');
            $videoId = $this->uploadVideoV2($post->video_url, $author, $access_token);
            
            $postBody['content'] = [
                "media" => [
                    "title" => substr($post->content, 0, 100),
                    "id" => $videoId
                ]
            ];
        }
        // For text-only posts, no content block needed

        return $postBody;
    }

    /**
     * Upload image using new LinkedIn API v2
     */
    private function uploadImageV2($imageUrl, $author, $access_token)
    {
        Log::info('📸 Starting image upload process', [
            'image_url' => $imageUrl,
            'author' => $author
        ]);

        // Step 1: Initialize upload
        $initResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
            'LinkedIn-Version' => '202601'
        ])->post('https://api.linkedin.com/rest/images?action=initializeUpload', [
            "initializeUploadRequest" => [
                "owner" => $author
            ]
        ])->throw()->json();

        $uploadUrl = $initResponse['value']['uploadUrl'];
        $imageId = $initResponse['value']['image'];

        Log::info('✅ Image upload initialized', [
            'imageId' => $imageId,
            'uploadUrl' => substr($uploadUrl, 0, 50) . '...'
        ]);

        // Step 2: Upload image binary
        $imageContent = file_get_contents($imageUrl);
        
        Log::info('📤 Uploading image binary to LinkedIn', [
            'imageId' => $imageId,
            'size_bytes' => strlen($imageContent)
        ]);
        
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
        ])->withBody($imageContent, 'application/octet-stream')
          ->put($uploadUrl)
          ->throw();

        Log::info('✅ Image uploaded successfully', ['imageId' => $imageId]);

        return $imageId;
    }

    /**
     * Upload video using new LinkedIn API v2
     */
    private function uploadVideoV2($videoUrl, $author, $access_token)
    {
        $videoContent = file_get_contents($videoUrl);
        
        // Step 1: Initialize upload
        $initResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
            'LinkedIn-Version' => '202601'
        ])->post('https://api.linkedin.com/rest/videos?action=initializeUpload', [
            "initializeUploadRequest" => [
                "owner" => $author,
                "fileSizeBytes" => strlen($videoContent),
                "uploadCaptions" => false,
                "uploadThumbnail" => false
            ]
        ])->throw()->json();

        $uploadUrl = $initResponse['value']['uploadUrl'];
        $videoId = $initResponse['value']['video'];

        Log::info('📤 Uploading video binary', ['videoId' => $videoId]);

        // Step 2: Upload video binary
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
        ])->withBody($videoContent, 'application/octet-stream')
          ->put($uploadUrl)
          ->throw();

        Log::info('✅ Video uploaded successfully', ['videoId' => $videoId]);

        return $videoId;
    }

    /**
     * Build multi-image content for image posts (1+ images in one post)
     */
    private function buildMultiImageContent($imageUrls, $author, $access_token)
    {
        Log::info('📸 Uploading multiple images for image post', [
            'total_images' => count($imageUrls),
            'image_urls' => $imageUrls
        ]);

        $images = [];
        
        foreach ($imageUrls as $index => $imageUrl) {
            $imageNumber = $index + 1;
            $totalImages = count($imageUrls);
            
            Log::info("📸 Uploading image {$imageNumber} of {$totalImages}", [
                'image_url' => $imageUrl
            ]);
            
            $imageId = $this->uploadImageV2($imageUrl, $author, $access_token);
            
            $images[] = [
                "id" => $imageId,
                "altText" => "Image " . $imageNumber
            ];
            
            Log::info("✅ Image {$imageNumber} uploaded", [
                'image_id' => $imageId
            ]);
        }

        $content = [
            "multiImage" => [
                "images" => $images
            ]
        ];

        Log::info('✅ Multi-image content built', [
            'total_images_uploaded' => count($images)
        ]);

        return $content;
    }

    /**
     * Build carousel content from PDF/PowerPoint document
     * This creates TRUE swipeable LinkedIn carousel
     */
    private function buildCarouselDocumentContent($documentUrl, $author, $access_token, $postContent)
    {
        // Detect file type from URL
        $extension = pathinfo($documentUrl, PATHINFO_EXTENSION);
        $mimeType = 'application/pdf'; // Default to PDF
        
        if (in_array(strtolower($extension), ['ppt', 'pptx'])) {
            $mimeType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        }

        Log::info('🎠 Starting carousel document upload', [
            'document_url' => $documentUrl,
            'file_extension' => $extension,
            'mime_type' => $mimeType
        ]);

        // Step 1: Initialize document upload
        $initResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
            'LinkedIn-Version' => '202601'
        ])->post('https://api.linkedin.com/rest/documents?action=initializeUpload', [
            "initializeUploadRequest" => [
                "owner" => $author
            ]
        ])->throw()->json();

        $uploadUrl = $initResponse['value']['uploadUrl'];
        $documentUrn = $initResponse['value']['document'];

        Log::info('✅ Document upload initialized', [
            'documentUrn' => $documentUrn,
            'uploadUrl' => substr($uploadUrl, 0, 50) . '...'
        ]);

        // Step 2: Download document from Cloudinary
        $documentContent = file_get_contents($documentUrl);
        
        Log::info('📤 Uploading document binary to LinkedIn', [
            'documentUrn' => $documentUrn,
            'size_bytes' => strlen($documentContent),
            'mime_type' => $mimeType
        ]);

        // Step 3: Upload document binary to LinkedIn
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
        ])->withBody($documentContent, $mimeType)
          ->put($uploadUrl)
          ->throw();

        Log::info('✅ Carousel document uploaded successfully to LinkedIn', [
            'documentUrn' => $documentUrn
        ]);

        // Return document content structure
        return [
            "document" => [
                "id" => $documentUrn,
                "title" => substr($postContent, 0, 100)
            ]
        ];
    }

    /**
     * Check if access token is expired
     */
    private function isTokenExpired($integration)
    {
        if (!$integration->expires_in || !$integration->updated_at) {
            return false;
        }

        $expiresAt = $integration->updated_at->addSeconds($integration->expires_in);
        return now()->greaterThan($expiresAt->subMinutes(5)); // Refresh 5 minutes before expiry
    }

    /**
     * 🔥 OLD METHOD - Keep for backward compatibility
     */
    public function publishPost($data, $access_token)
    {
        $api_url = $this->api."/ugcPosts";
        $author = "urn:li:person:" . $data['oauth_uid'];
        $content = $data['content'];
        $article_link = $data['article'];
        $post_type = $data['post_type'];

        $headers = [
            'Authorization' => 'Bearer '.$access_token,
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0'
        ];

        $post = new Post;

        if ($post_type == 'text only'){
            $post_body = [
                "author" => $author,
                "lifecycleState" => "PUBLISHED",
                "specificContent" => [
                    "com.linkedin.ugc.ShareContent" => [
                        "shareCommentary" => [
                            "text" => $content
                        ],
                        "shareMediaCategory" => "NONE"
                    ]
                ],
                "visibility" => [
                    "com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC"
                ]
            ];
        }elseif ($post_type == 'text with article'){
            $post_body = [
                'author' => $author,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $content,
                        ],
                        'shareMediaCategory' => 'ARTICLE',
                        'media' => [
                            [
                                'status' => 'READY',
                                'description' => [
                                    'text' => $content,
                                ],
                                'originalUrl' => $article_link,
                            ],
                        ],
                    ]
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];
        }elseif ($post_type == 'text with image'){
            try {
                $register = $this->registerUpload($data, $access_token);
            } catch (\Throwable $th) {
                Log::debug($th);
                $post->where('id', $data['id'])->update([
                    'publish_status' => 'failed',
                    'comment' => $th->getMessage()
                ]);
            }
            
            $uploadUrl = $register['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
            $asset = $register['value']['asset'];

            try {
                $this->uploadImage($data, $uploadUrl, $access_token);
            } catch (\Throwable $th) {
                Log::debug($th);
                $post->where('id', $data['id'])->update([
                    'publish_status' => 'failed',
                    'comment' => $th->getMessage()
                ]);
            }
            
            $post_body = [
                "author" => $author,
                "lifecycleState" => "PUBLISHED",
                "specificContent" => [
                    "com.linkedin.ugc.ShareContent" => [
                        "shareCommentary" => [
                            "text" => $content
                        ],
                        "shareMediaCategory" => "IMAGE",
                        "media" => [
                            [
                                "status" => "READY",
                                "description" => [
                                    "text" => $content
                                ],
                                "media" => $asset,
                            ]
                        ]
                    ]
                ],
                "visibility" => [
                    "com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC"
                ]
            ];
        }elseif ($post_type == 'carousel'){
            try {
                $post_body = $this->carouselPost($access_token, $data);
            } catch (\Throwable $th) {
                Log::debug($th);
                $post->where('id', $data['id'])->update([
                    'publish_status' => 'failed',
                    'comment' => $th->getMessage()
                ]);
            }
        }

        return Http::withHeaders($headers)
            ->post($api_url, $post_body)
            ->throw()
            ->json();
    }

    public function registerUpload($data, $access_token)
    {
        $url = $this->api."/assets?action=registerUpload";
        $author = "urn:li:person:".$data['oauth_uid'];
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
        ];
        $payload = [
            "registerUploadRequest" => [
                "recipes" => [
                    "urn:li:digitalmediaRecipe:feedshare-image"
                ],
                "owner" => $author,
                "serviceRelationships" => [
                    [
                        "relationshipType" => "OWNER",
                        "identifier" => "urn:li:userGeneratedContent"
                    ]
                ]
            ]
        ];

        return Http::withHeaders($headers)
            ->post($url, $payload)
            ->throw()
            ->json();
    }

    public function uploadImage($data, $uploadUrl, $access_token)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
        ];
        $payload = [
            'upload-file' => URL::to($data['image'])
        ];

        return Http::withHeaders($headers)
            ->post($uploadUrl, $payload)
            ->throw()
            ->json();
    }

    public function carouselPost($access_token, $data)
    {
        $register = $this->registerUpload($data, $access_token);
        $uploadUrl = $register['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $asset = $register['value']['asset'];
        $this->uploadImage($data, $uploadUrl, $access_token);

        $payload = [
            "author" => "urn:li:person:" . $data['oauth_uid'],
            "commentary" => $data['content'],
            "visibility" => "PUBLIC",
            "distribution" => [
                "feedDistribution" => "NONE",
                "targetEntities" => [],
                "thirdPartyDistributionChannels" => []
            ],
            "lifecycleState" => "PUBLISHED",
            "isReshareDisabledByAuthor" => False,
            "content" => [
                "carousel" => [
                    "cards" => [
                        [
                            "media" => [
                                "id" => $asset,
                                "title" => $data['content']
                            ],
                            "landingPage" => "http://www.linkedin.com/"
                        ]
                    ]
                ]
            ],
            "contentLandingPage" => "http://www.linkedin.com/contentLandingPage"
        ];

        return $payload;
    }
}