<?php

namespace App\Jobs;

use App\Models\LinkedInPost;
use App\V2\Services\LegacyContentPublishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
     * Execute the job — publishes via Unipile (legacy bridge → v2_content_posts).
     */
    public function handle(LegacyContentPublishService $publisher): void
    {
        try {
            $publisher->publishLegacyPost($this->post->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to publish post via Unipile', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
            ]);
            $this->post->markAsFailed();
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PublishLinkedInPost job failed', [
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
        ]);

        $this->post->markAsFailed();
    }
}
