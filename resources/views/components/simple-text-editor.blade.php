@props([
    'id' => 'content',
    'name' => 'content',
    'value' => '',
    'rows' => 12,
    'minHeight' => 'min-h-[220px]',
    'placeholder' => 'Write or paste your text…',
    'required' => false,
    'footerHint' => 'Plain text with line breaks — ready to copy into LinkedIn or email.',
])

<div {{ $attributes->merge(['class' => 'simple-text-editor overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }} data-simple-text-editor>
    <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50/80 px-2 py-1.5">
        <span class="mr-1 inline-flex items-center gap-1 px-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
            </svg>
            Editor
        </span>
        <button type="button" data-insert-newline class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-gray-100" title="Insert paragraph break">
            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
            </svg>
            New line
        </button>
        <button type="button" data-insert-bullet class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-gray-100" title="Insert bullet">
            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            Bullet
        </button>
        <button type="button" data-copy-btn class="ml-auto inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-gray-100 disabled:opacity-40" title="Copy all text" disabled>
            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0a2.25 2.25 0 012.166 2.25v6.75a2.25 2.25 0 01-2.25 2.25H9.75a2.25 2.25 0 01-2.25-2.25v-6.75a2.25 2.25 0 012.166-2.25m7.332 0H8.25" />
            </svg>
            <span data-copy-label>Copy</span>
        </button>
    </div>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($required) required @endif
        placeholder="{{ $placeholder }}"
        spellcheck="true"
        class="w-full resize-y border-0 bg-transparent px-4 py-3 text-sm leading-relaxed text-gray-900 outline-none focus:ring-0 whitespace-pre-wrap break-words {{ $minHeight }}"
    >{{ $value }}</textarea>

    <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50/60 px-3 py-1.5 text-[11px] text-gray-500">
        <span>{{ $footerHint }}</span>
        <span class="tabular-nums"><span data-word-count>{{ trim($value) ? str_word_count(strip_tags($value)) : 0 }}</span> words</span>
    </div>
</div>
