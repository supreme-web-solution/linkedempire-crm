@extends('layout.auth')

@section('content')
@if(session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Inspiration</h2>
        <p class="mt-1 text-sm text-gray-500">Discover high-performing LinkedIn posts, save favorites, and remix them into your voice.</p>
    </div>
    <a href="{{ route('content-creator.create') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-white transition-all" style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">
        Create new post
    </a>
</div>

{{-- Stats --}}
<div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500">Saved posts</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats['total_posts']) }}</p>
    </div>
    <div class="rounded-xl border border-rose-100 bg-rose-50/50 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500">Favorites</p>
        <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format($stats['favorites']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500">Viral (500+)</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats['viral_posts']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500">Avg engagement</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats['avg_engagement']) }}</p>
    </div>
</div>

{{-- Fetch --}}
<div class="mb-6 rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900">Discover viral posts</h3>
    <p class="mt-1 text-xs text-gray-500">Search a topic and save the best-performing posts to your library.</p>

    @unless($rapidConfigured)
    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
        Add <code class="rounded bg-amber-100 px-1">RAPIDAPI_KEY</code> to your <code>.env</code> to enable fetching.
    </div>
    @endunless

    <div class="mt-4 flex flex-wrap items-end gap-3">
        <label class="min-w-[220px] flex-1 text-sm">
            <span class="mb-1 block text-xs font-medium text-gray-600">Keyword / topic</span>
            <input id="fetch-keyword" type="text" placeholder="e.g. cold outreach, AI sales"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0077b5] focus:outline-none focus:ring-2 focus:ring-[#0077b5]/20" />
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-medium text-gray-600">Posted</span>
            <select id="fetch-date" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0077b5] focus:outline-none">
                <option>Past 24 hours</option>
                <option>Past week</option>
                <option selected>Past month</option>
                <option>Past year</option>
            </select>
        </label>
        <button type="button" id="fetch-btn" @disabled(!$rapidConfigured)
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition disabled:opacity-50"
            style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);">
            <span id="fetch-btn-label">Fetch posts</span>
        </button>
    </div>
    <p id="fetch-msg" class="mt-3 hidden text-sm font-medium text-emerald-600"></p>
    <p id="fetch-err" class="mt-3 hidden text-sm font-medium text-red-600"></p>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('inspiration.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search library…"
        class="min-w-[220px] flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0077b5] focus:outline-none" />
    <select name="category" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0077b5] focus:outline-none" onchange="this.form.submit()">
        <option value="">All categories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ ucfirst($cat) }}</option>
        @endforeach
    </select>
    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm">
        <input type="checkbox" name="favorite" value="1" @checked(request('favorite')) class="rounded" style="accent-color:#0077b5" onchange="this.form.submit()" />
        Favorites only
    </label>
    @if($posts->count())
    <button type="button" id="bulk-delete-btn" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-medium text-rose-700 hover:bg-rose-100">
        Delete selected (<span id="selected-count">0</span>)
    </button>
    @endif
</form>

@if($posts->isEmpty())
<div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
    <p class="text-base font-semibold text-gray-900">Your library is empty</p>
    <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">Fetch viral posts by keyword above, then remix winners into content drafts.</p>
</div>
@else
<form id="bulk-form" method="POST" action="{{ route('inspiration.bulk-destroy') }}">
    @csrf
    <div id="bulk-ids"></div>
</form>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach($posts as $post)
    @php
        $meta = is_array($post->meta) ? $post->meta : [];
        $author = $meta['author_name'] ?? 'Unknown';
        $headline = $meta['author_headline'] ?? null;
        $postUrl = $meta['post_url'] ?? null;
        $likes = (int) ($meta['likes'] ?? 0);
        $comments = (int) ($meta['comments'] ?? 0);
        $shares = (int) ($meta['shares'] ?? 0);
        $initials = strtoupper(substr($author, 0, 1));
    @endphp
    <article class="relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md {{ $post->is_favorite ? 'ring-1 ring-rose-200' : '' }}" data-post-id="{{ $post->id }}">
        <div class="flex items-start gap-3 border-b border-gray-100 px-4 py-3">
            <label class="mt-1 cursor-pointer">
                <input type="checkbox" class="post-select rounded" value="{{ $post->id }}" style="accent-color:#0077b5" />
            </label>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white" style="background: linear-gradient(135deg, #0077b5, #005885);">{{ $initials }}</div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold text-gray-900">{{ $author }}</div>
                @if($headline)<div class="truncate text-xs text-gray-500">{{ Str::limit($headline, 48) }}</div>@endif
                @if($post->category)<span class="mt-1 inline-block rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-sky-700">{{ $post->category }}</span>@endif
            </div>
            <button type="button" onclick="toggleFavorite({{ $post->id }}, this)" class="rounded-full p-1.5 hover:bg-gray-50" title="Favorite">
                <svg class="h-5 w-5 {{ $post->is_favorite ? 'text-rose-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
            </button>
        </div>

        <div class="flex-1 px-4 py-3">
            <p class="line-clamp-5 whitespace-pre-wrap text-sm leading-relaxed text-gray-800">{{ $post->content }}</p>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 px-4 py-2 text-xs text-gray-500">
            @if($likes)<span>{{ number_format($likes) }} likes</span>@endif
            @if($comments)<span>{{ number_format($comments) }} comments</span>@endif
            @if($shares)<span>{{ number_format($shares) }} shares</span>@endif
            <span class="ml-auto font-medium text-[#0077b5]">{{ number_format($post->engagement) }} pts</span>
        </div>

        <div class="flex border-t border-gray-100">
            <button type="button" onclick="useAsInspiration({{ $post->id }})" class="flex-1 px-2 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Use</button>
            <button type="button" onclick="remixPost({{ $post->id }})" class="flex-1 border-l border-gray-100 px-2 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Remix</button>
            @if($postUrl)
            <a href="{{ $postUrl }}" target="_blank" rel="noopener" class="flex-1 border-l border-gray-100 px-2 py-2 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">View</a>
            @endif
            <button type="button" onclick="deletePost({{ $post->id }})" class="flex-1 border-l border-gray-100 px-2 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50">Delete</button>
        </div>
    </article>
    @endforeach
</div>

<div class="mt-6">{{ $posts->links() }}</div>
@endif

{{-- Remix modal --}}
<div id="remix-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="max-h-[85vh] w-full max-w-lg overflow-auto rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900">Remixed draft</h3>
        <textarea id="remix-content" rows="10" class="mt-3 w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-[#0077b5] focus:outline-none"></textarea>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" onclick="closeRemix()" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Close</button>
            <button type="button" onclick="openRemixInCreator()" class="rounded-lg px-4 py-2 text-sm font-medium text-white" style="background:#0077b5">Open in Content Creator</button>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

document.getElementById('fetch-btn')?.addEventListener('click', async () => {
    const keyword = document.getElementById('fetch-keyword').value.trim();
    const datePosted = document.getElementById('fetch-date').value;
    const btn = document.getElementById('fetch-btn');
    const label = document.getElementById('fetch-btn-label');
    const msg = document.getElementById('fetch-msg');
    const err = document.getElementById('fetch-err');
    if (!keyword) return;
    btn.disabled = true;
    label.textContent = 'Fetching…';
    msg.classList.add('hidden');
    err.classList.add('hidden');
    try {
        const res = await fetch('{{ route('inspiration.fetch') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ keyword, keep: 18, date_posted: datePosted }),
        });
        const data = await res.json();
        if (!res.ok) {
            err.textContent = data.message || 'Fetch failed.';
            err.classList.remove('hidden');
        } else {
            msg.textContent = data.message;
            msg.classList.remove('hidden');
            setTimeout(() => window.location.reload(), 800);
        }
    } catch {
        err.textContent = 'Network error. Please try again.';
        err.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        label.textContent = 'Fetch posts';
    }
});

document.getElementById('fetch-keyword')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') document.getElementById('fetch-btn')?.click();
});

function toggleFavorite(id, btn) {
    fetch(`/inspiration/${id}/favorite`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    }).then(r => r.json()).then(data => {
        if (!data.success) return;
        const svg = btn.querySelector('svg');
        svg.classList.toggle('text-rose-500', data.is_favorite);
        svg.classList.toggle('text-gray-300', !data.is_favorite);
    });
}

function useAsInspiration(id) {
    fetch(`/inspiration/use/${id}`).then(r => r.json()).then(data => {
        if (!data.success) return alert(data.message || 'Error');
        sessionStorage.setItem('inspiration_content', data.content);
        sessionStorage.setItem('inspiration_author', data.author);
        window.location.href = '{{ route('content-creator.create') }}?from=inspiration';
    });
}

function remixPost(id) {
    fetch(`/inspiration/${id}/remix`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ tone: 'Neutral and professional' }),
    }).then(r => r.json()).then(data => {
        if (!data.success && !data.content) return alert(data.message || 'Remix failed');
        document.getElementById('remix-content').value = data.content;
        document.getElementById('remix-modal').classList.remove('hidden');
        document.getElementById('remix-modal').classList.add('flex');
    });
}

function closeRemix() {
    document.getElementById('remix-modal').classList.add('hidden');
    document.getElementById('remix-modal').classList.remove('flex');
}

function openRemixInCreator() {
    sessionStorage.setItem('inspiration_content', document.getElementById('remix-content').value);
    sessionStorage.setItem('inspiration_remixed', 'true');
    window.location.href = '{{ route('content-creator.create') }}?from=inspiration&remixed=true';
}

function deletePost(id) {
    if (!confirm('Remove this post from your library?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/inspiration/${id}`;
    form.innerHTML = `@csrf<input type="hidden" name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
}

const selected = new Set();
document.querySelectorAll('.post-select').forEach(cb => {
    cb.addEventListener('change', () => {
        cb.checked ? selected.add(+cb.value) : selected.delete(+cb.value);
        const btn = document.getElementById('bulk-delete-btn');
        const count = document.getElementById('selected-count');
        if (btn && count) {
            count.textContent = selected.size;
            btn.classList.toggle('hidden', selected.size === 0);
        }
    });
});

document.getElementById('bulk-delete-btn')?.addEventListener('click', () => {
    if (!selected.size || !confirm(`Delete ${selected.size} selected post(s)?`)) return;
    const wrap = document.getElementById('bulk-ids');
    wrap.innerHTML = '';
    selected.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        wrap.appendChild(input);
    });
    document.getElementById('bulk-form').submit();
});
</script>
@endsection
