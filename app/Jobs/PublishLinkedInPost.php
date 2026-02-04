<?php

namespace App\Jobs;

use App\Models\LinkedInPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PublishLinkedInPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    /**
     * Create a new job instance.
     */
    public function __construct(LinkedInPost $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job - publishes to LinkedIn using Official API
     */
    public function handle(): void
    {
        try {
            // Check if post is still ready to publish
            if ($this->post->status !== 'ready_to_publish' && $this->post->status !== 'scheduled') {
                Log::warning('Post not publishable', ['post_id' => $this->post->id, 'status' => $this->post->status]);
                return;
            }

            // Get user's LinkedIn integration
            $integration = \App\Models\Integration::where('user_id', $this->post->user_id)
                ->where('oauth_provider', 'linkedin')
                ->where('connected_status', 1)
                ->first();

            if (!$integration) {
                Log::error('No LinkedIn integration found', ['post_id' => $this->post->id, 'user_id' => $this->post->user_id]);
                $this->post->markAsFailed();
                return;
            }
            
            // Verify integration belongs to the post creator
            if ($integration->user_id !== $this->post->user_id) {
                Log::error('Integration user mismatch', ['post_user_id' => $this->post->user_id, 'integration_user_id' => $integration->user_id]);
                $this->post->markAsFailed();
                return;
            }

            if (!$integration->access_token) {
                Log::error('No access token', ['post_id' => $this->post->id]);
                $this->post->markAsFailed();
                return;
            }

            // Publish using LinkedIn Official API v2
            $linkedInService = new \App\Services\LinkedInService();
            $response = $linkedInService->publishPostV2($this->post, $integration);

            // Extract post ID from response
            $linkedinPostId = $response['id'] ?? $response['value'] ?? null;

            // Mark post as published
            $this->post->markAsPublished($linkedinPostId);

            Log::info('Post published successfully', ['post_id' => $this->post->id, 'linkedin_post_id' => $linkedinPostId]);

        } catch (\Exception $e) {
            Log::error('Failed to publish post', ['post_id' => $this->post->id, 'error' => $e->getMessage()]);
            $this->post->markAsFailed();
            throw $e;
        }
    }


    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ PublishLinkedInPost job failed', [
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Mark post as failed
        $this->post->markAsFailed();
    }
}
