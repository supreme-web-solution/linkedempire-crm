<?php

namespace App\V2\Services;

use App\Jobs\V2\PublishV2ContentPostJob;
use App\Models\LinkedInPost;
use App\Models\User;
use App\Models\V2ContentPost;
use App\Models\V2IntegrationAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Publishes legacy linkedin_posts rows via Unipile (v2_content_posts + PublishV2ContentPostJob).
 */
class LegacyContentPublishService
{
    public function findBridged(int $legacyPostId, int $userId): ?V2ContentPost
    {
        return V2ContentPost::query()
            ->where('user_id', $userId)
            ->where('meta->legacy_post_id', $legacyPostId)
            ->first();
    }

    public function publishLegacyPost(LinkedInPost $post): void
    {
        if (! in_array($post->status, ['ready_to_publish', 'scheduled'], true)) {
            Log::warning('[LegacyContentPublish] Post not publishable', [
                'post_id' => $post->id,
                'status' => $post->status,
            ]);

            return;
        }

        $user = User::find($post->user_id);
        if (! $user) {
            $post->markAsFailed();

            return;
        }

        if (! $this->hasActiveLinkedIn($user)) {
            Log::error('[LegacyContentPublish] No active LinkedIn account', [
                'post_id' => $post->id,
                'user_id' => $post->user_id,
            ]);
            $post->markAsFailed();

            return;
        }

        $v2 = $this->syncToV2($post, $user);
        $v2->update(['status' => 'ready_to_publish']);

        PublishV2ContentPostJob::dispatchSync($v2->id);

        $this->syncStatusFromV2($v2->fresh(), $post);
    }

    public function scheduleLegacyPost(LinkedInPost $post, Carbon $when): void
    {
        $user = User::find($post->user_id);
        if (! $user) {
            return;
        }

        $v2 = $this->syncToV2($post, $user);
        $v2->update([
            'status' => 'scheduled',
            'scheduled_at' => $when,
        ]);

        PublishV2ContentPostJob::dispatch($v2->id)->delay($when);
    }

    public function syncToV2(LinkedInPost $post, User $user): V2ContentPost
    {
        $orgId = (int) ($user->current_organization_id ?? 0);
        $content = $this->composeContent($post);
        $meta = $this->buildMeta($post);

        $existing = $this->findBridged((int) $post->id, (int) $user->id);

        $payload = [
            'user_id' => $user->id,
            'organization_id' => $orgId,
            'provider' => 'linkedin',
            'content' => $content,
            'status' => $post->status,
            'scheduled_at' => $post->scheduled_at,
            'meta' => $meta,
        ];

        if ($existing) {
            $existing->forceFill($payload)->save();

            return $existing->fresh();
        }

        return V2ContentPost::create($payload);
    }

    public function syncStatusFromV2(V2ContentPost $v2, LinkedInPost $post): void
    {
        if ($v2->status === 'published') {
            $linkedinPostId = $v2->meta['linkedin_post_id'] ?? null;
            $post->markAsPublished($linkedinPostId);

            return;
        }

        if ($v2->status === 'failed') {
            $post->markAsFailed();
        }
    }

    private function hasActiveLinkedIn(User $user): bool
    {
        return V2IntegrationAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'linkedin')
            ->where('status', 'active')
            ->exists();
    }

    private function composeContent(LinkedInPost $post): string
    {
        $content = trim((string) $post->content);
        $hashtags = trim((string) ($post->hashtags ?? ''));

        if ($hashtags === '') {
            return $content;
        }

        return $content."\n\n".$hashtags;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMeta(LinkedInPost $post): array
    {
        $imageUrls = $post->image_url;
        if (is_string($imageUrls) && $imageUrls !== '') {
            $imageUrls = [$imageUrls];
        } elseif (! is_array($imageUrls)) {
            $imageUrls = [];
        }

        return [
            'legacy_post_id' => (int) $post->id,
            'post_type' => $post->post_type,
            'image_urls' => array_values(array_filter($imageUrls)),
            'video_url' => $post->video_url,
            'hashtags' => $post->hashtags,
            'bridged_at' => now()->toIso8601String(),
        ];
    }
}
