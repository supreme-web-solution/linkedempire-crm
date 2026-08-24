<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\V2InspirationPost;
use App\Services\ChatGPT;
use App\V2\Services\RapidApiLinkedinService;
use App\V2\Services\UserBootstrapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InspirationController extends Controller
{
    public function __construct(
        private readonly RapidApiLinkedinService $rapidApi,
        private readonly UserBootstrapService $bootstrap,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $orgId = $this->organizationId($user);

        $base = V2InspirationPost::query()->where('organization_id', $orgId);
        $query = (clone $base);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }
        if ($request->boolean('favorite')) {
            $query->where('is_favorite', true);
        }

        $posts = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(12)->appends($request->query());

        $avgEngagement = (clone $base)->where('engagement', '>', 0)->avg('engagement');

        $stats = [
            'total_posts' => (clone $base)->count(),
            'favorites' => (clone $base)->where('is_favorite', true)->count(),
            'viral_posts' => (clone $base)->where('engagement', '>=', 500)->count(),
            'avg_engagement' => $avgEngagement ? (int) round($avgEngagement) : 0,
        ];

        $categories = (clone $base)->whereNotNull('category')->distinct()->pluck('category')->filter()->values();

        return view('inspiration.index', [
            'posts' => $posts,
            'stats' => $stats,
            'categories' => $categories,
            'rapidConfigured' => $this->rapidApi->isConfigured(),
        ]);
    }

    public function fetch(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = $this->organizationId($user);

        $data = $request->validate([
            'keyword' => ['required', 'string', 'max:200'],
            'keep' => ['nullable', 'integer', 'min:1', 'max:30'],
            'date_posted' => ['nullable', 'in:Past 24 hours,Past week,Past month,Past year'],
        ]);

        if (! $this->rapidApi->isConfigured()) {
            return response()->json(['message' => 'RAPIDAPI_KEY is missing. Add it in your .env and refresh.'], 422);
        }

        $keep = (int) ($data['keep'] ?? 18);

        try {
            $result = $this->rapidApi->searchPosts(
                $data['keyword'],
                1,
                150,
                $data['date_posted'] ?? 'Past month',
                RapidApiLinkedinService::DISCOVERY_MAX_PAGES,
            );
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Fetch failed: '.$th->getMessage()], 422);
        }

        $candidates = [];
        foreach ($result['items'] as $item) {
            if (trim((string) $item['content']) === '') {
                continue;
            }

            $engagement = $this->engagementScore(
                (int) $item['likes'],
                (int) $item['comments'],
                (int) $item['shares'],
                (int) $item['views'],
            );

            $candidates[] = array_merge($item, ['engagement' => $engagement]);
        }

        usort($candidates, fn (array $a, array $b) => $b['engagement'] <=> $a['engagement']);
        $top = array_slice($candidates, 0, $keep);

        $saved = 0;
        foreach ($top as $item) {
            $postId = $item['post_id'] ?: Str::slug($item['author_name']).'_'.substr(md5($item['content']), 0, 10);

            V2InspirationPost::updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'source' => 'linkedin',
                    'post_id' => $postId,
                ],
                [
                    'user_id' => $user->id,
                    'content' => $item['content'],
                    'category' => $this->autoCategorize($item['content']),
                    'engagement' => $item['engagement'],
                    'meta' => [
                        'author_name' => $item['author_name'],
                        'author_headline' => $item['author_headline'],
                        'author_profile_url' => $item['author_profile_url'],
                        'post_url' => $item['post_url'],
                        'likes' => $item['likes'],
                        'comments' => $item['comments'],
                        'shares' => $item['shares'],
                        'views' => $item['views'],
                        'posted' => $item['posted'],
                        'images' => $item['images'],
                        'video' => $item['video'],
                    ],
                ]
            );
            $saved++;
        }

        $scanned = count($candidates);

        return response()->json([
            'message' => $scanned === 0
                ? 'No posts found for that keyword.'
                : "Saved {$saved} posts to your library.",
            'count' => $saved,
        ]);
    }

    /** Extension / legacy API — save a post from LinkedIn browse. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'author_name' => ['required', 'string'],
            'content' => ['required', 'string'],
            'likes' => ['nullable', 'integer'],
            'comments' => ['nullable', 'integer'],
            'shares' => ['nullable', 'integer'],
            'views' => ['nullable', 'integer'],
            'post_url' => ['nullable', 'url'],
            'linkedin_post_id' => ['nullable', 'string'],
            'author_headline' => ['nullable', 'string'],
            'author_profile_url' => ['nullable', 'url'],
            'author_image_url' => ['nullable', 'url'],
            'post_type' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'category' => ['nullable', 'string'],
        ]);

        $orgId = $this->organizationId($user);
        $likes = (int) ($data['likes'] ?? 0);
        $comments = (int) ($data['comments'] ?? 0);
        $shares = (int) ($data['shares'] ?? 0);
        $views = (int) ($data['views'] ?? 0);
        $postId = $data['linkedin_post_id'] ?? Str::slug($data['author_name']).'_'.substr(md5($data['content']), 0, 10);

        $row = V2InspirationPost::updateOrCreate(
            [
                'organization_id' => $orgId,
                'source' => 'linkedin',
                'post_id' => $postId,
            ],
            [
                'user_id' => $user->id,
                'content' => $data['content'],
                'category' => $data['category'] ?? $this->autoCategorize($data['content']),
                'engagement' => $this->engagementScore($likes, $comments, $shares, $views),
                'meta' => array_filter([
                    'author_name' => $data['author_name'],
                    'author_headline' => $data['author_headline'] ?? null,
                    'author_profile_url' => $data['author_profile_url'] ?? null,
                    'author_image_url' => $data['author_image_url'] ?? null,
                    'post_url' => $data['post_url'] ?? null,
                    'likes' => $likes,
                    'comments' => $comments,
                    'shares' => $shares,
                    'views' => $views,
                    'images' => $data['images'] ?? [],
                    'post_type' => $data['post_type'] ?? 'text',
                ]),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Post saved to your inspiration library.',
            'post' => $row,
        ]);
    }

    public function storeFromWeb(Request $request): JsonResponse
    {
        return $this->store($request);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->owned($id)->delete();
        notify()->success('Post removed from inspiration library.');

        return redirect()->route('inspiration.index');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $orgId = $this->organizationId($request->user());
        $deleted = V2InspirationPost::query()
            ->where('organization_id', $orgId)
            ->whereIn('id', $data['ids'])
            ->delete();

        return redirect()->route('inspiration.index')->with('success', "Removed {$deleted} post(s) from your library.");
    }

    public function toggleFavorite(int $id): JsonResponse
    {
        $post = $this->owned($id);
        $post->update(['is_favorite' => ! $post->is_favorite]);

        return response()->json(['success' => true, 'is_favorite' => $post->is_favorite]);
    }

    public function useAsInspiration(int $id): JsonResponse
    {
        $post = $this->owned($id);
        $meta = is_array($post->meta) ? $post->meta : [];
        $chatGPT = new ChatGPT();
        $formatted = $chatGPT->formatPost((string) $post->content);

        return response()->json([
            'success' => true,
            'content' => $formatted,
            'author' => (string) ($meta['author_name'] ?? 'Unknown'),
            'engagement' => [
                'likes' => (int) ($meta['likes'] ?? 0),
                'comments' => (int) ($meta['comments'] ?? 0),
                'shares' => (int) ($meta['shares'] ?? 0),
                'rate' => (int) $post->engagement,
            ],
        ]);
    }

    public function remix(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'tone' => ['nullable', 'in:Formal and respectful,Neutral and professional,Casual and friendly,professional,casual,motivational,educational,storytelling'],
        ]);

        $post = $this->owned($id);
        $toneMap = [
            'professional' => 'Neutral and professional',
            'casual' => 'Casual and friendly',
            'motivational' => 'Formal and respectful',
            'educational' => 'Neutral and professional',
            'storytelling' => 'Casual and friendly',
        ];
        $tone = $toneMap[$data['tone'] ?? ''] ?? ($data['tone'] ?? 'Neutral and professional');

        try {
            $gpt = new ChatGPT(['content' => (string) $post->content, 'tone' => $tone]);
            $result = $gpt->rewritePost();
            $meta = is_array($post->meta) ? $post->meta : [];

            return response()->json([
                'success' => true,
                'content' => $result['content'],
                'word_count' => $result['word_count'] ?? str_word_count($result['content']),
                'author' => (string) ($meta['author_name'] ?? 'Unknown'),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 422);
        }
    }

    private function owned(int $id): V2InspirationPost
    {
        $orgId = $this->organizationId(auth()->user());

        return V2InspirationPost::query()
            ->where('organization_id', $orgId)
            ->findOrFail($id);
    }

    private function organizationId(User $user): int
    {
        return (int) $this->bootstrap->ensurePersonalOrganization($user)->id;
    }

    private function engagementScore(int $likes, int $comments, int $shares, int $views): int
    {
        return $likes + ($comments * 3) + ($shares * 5) + (int) floor($views / 100);
    }

    private function autoCategorize(string $content): string
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
