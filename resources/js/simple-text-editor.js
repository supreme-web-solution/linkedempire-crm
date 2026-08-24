const instances = new Map();

export function stripMarkdownFormatting(text) {
    if (!text) return '';
    return text
        .replace(/\*\*(.+?)\*\*/gs, '$1')
        .replace(/__(.+?)__/gs, '$1')
        .replace(/\*\*/g, '')
        .replace(/^#{1,6}\s+/gm, '')
        .trim();
}

function countWords(text) {
    const trimmed = (text || '').trim();
    return trimmed ? trimmed.split(/\s+/).length : 0;
}

class SimpleTextEditorInstance {
    constructor(root) {
        this.root = root;
        this.textarea = root.querySelector('textarea');
        this.wordCountEl = root.querySelector('[data-word-count]');
        this.copyBtn = root.querySelector('[data-copy-btn]');
        this.copyLabel = root.querySelector('[data-copy-label]');

        if (!this.textarea) return;

        this.id = this.textarea.id;
        instances.set(this.id, this);

        root.querySelector('[data-insert-newline]')?.addEventListener('click', () => this.insertParagraphBreak());
        root.querySelector('[data-insert-bullet]')?.addEventListener('click', () => this.insertBullet());
        this.copyBtn?.addEventListener('click', () => this.copyAll());

        this.textarea.addEventListener('input', () => {
            this.updateWordCount();
            this.updateCopyState();
            this.textarea.dispatchEvent(new CustomEvent('simple-text-editor:input', { bubbles: true }));
        });

        this.updateWordCount();
        this.updateCopyState();
    }

    getValue() {
        return this.textarea?.value ?? '';
    }

    setValue(value) {
        if (!this.textarea) return;
        this.textarea.value = value ?? '';
        this.updateWordCount();
        this.updateCopyState();
        this.textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    getWordCount() {
        return countWords(this.getValue());
    }

    focus() {
        this.textarea?.focus();
    }

    updateWordCount() {
        if (this.wordCountEl) {
            this.wordCountEl.textContent = String(this.getWordCount());
        }
    }

    updateCopyState() {
        if (this.copyBtn) {
            this.copyBtn.disabled = !this.getValue().trim();
        }
    }

    insertAtCursor(snippet, selectInserted = false) {
        const el = this.textarea;
        if (!el) return;

        const start = el.selectionStart ?? this.getValue().length;
        const end = el.selectionEnd ?? start;
        const before = this.getValue().slice(0, start);
        const after = this.getValue().slice(end);
        this.setValue(`${before}${snippet}${after}`);

        requestAnimationFrame(() => {
            el.focus();
            const pos = start + snippet.length;
            if (selectInserted) {
                el.setSelectionRange(start, pos);
            } else {
                el.setSelectionRange(pos, pos);
            }
        });
    }

    insertParagraphBreak() {
        const el = this.textarea;
        const start = el?.selectionStart ?? this.getValue().length;
        const needsLeading = start > 0 && this.getValue()[start - 1] !== '\n';
        this.insertAtCursor(`${needsLeading ? '\n' : ''}\n`);
    }

    insertBullet() {
        const el = this.textarea;
        const start = el?.selectionStart ?? this.getValue().length;
        const atLineStart = start === 0 || this.getValue()[start - 1] === '\n';
        this.insertAtCursor(`${atLineStart ? '' : '\n'}• `);
    }

    async copyAll() {
        const text = this.getValue().trim();
        if (!text) return;

        try {
            await navigator.clipboard.writeText(this.getValue());
            if (this.copyLabel) {
                const original = this.copyLabel.textContent;
                this.copyLabel.textContent = 'Copied';
                setTimeout(() => {
                    this.copyLabel.textContent = original;
                }, 1500);
            }
        } catch {
            // ignore clipboard errors
        }
    }
}

export function initSimpleTextEditors() {
    document.querySelectorAll('[data-simple-text-editor]').forEach((root) => {
        if (root.dataset.initialized === 'true') return;
        root.dataset.initialized = 'true';
        new SimpleTextEditorInstance(root);
    });
}

export function getSimpleTextEditor(id) {
    return instances.get(id) ?? null;
}

export function setSimpleTextEditorValue(id, value, { stripMarkdown = false } = {}) {
    const editor = getSimpleTextEditor(id);
    const cleaned = stripMarkdown ? stripMarkdownFormatting(value) : (value ?? '');
    if (editor) {
        editor.setValue(cleaned);
        return cleaned;
    }

    const textarea = document.getElementById(id);
    if (textarea) {
        textarea.value = cleaned;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }
    return cleaned;
}

export function getSimpleTextEditorValue(id) {
    const editor = getSimpleTextEditor(id);
    if (editor) return editor.getValue();
    return document.getElementById(id)?.value ?? '';
}

export function getSimpleTextEditorWordCount(id) {
    const editor = getSimpleTextEditor(id);
    if (editor) return editor.getWordCount();
    return countWords(getSimpleTextEditorValue(id));
}

if (typeof window !== 'undefined') {
    window.SimpleTextEditor = {
        init: initSimpleTextEditors,
        get: getSimpleTextEditor,
        setValue: setSimpleTextEditorValue,
        getValue: getSimpleTextEditorValue,
        getWordCount: getSimpleTextEditorWordCount,
        stripMarkdown: stripMarkdownFormatting,
    };
}
