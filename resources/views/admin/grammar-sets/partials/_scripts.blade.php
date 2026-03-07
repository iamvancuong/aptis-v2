{{-- Partial: _scripts.blade.php — Grammar Edit JS --}}
<style>
    .ck-editor__editable_inline {
        min-height: 150px !important;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form      = document.getElementById('grammar-form');
    const actionEl  = document.getElementById('form-action');
    const indicator = document.getElementById('save-indicator');
    const STORAGE_KEY = 'grammar_draft_{{ $grammarSet->id }}';

    // localStorage draft banner
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        try {
            const data = JSON.parse(stored);
            const banner = document.createElement('div');
            banner.className = 'mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 text-sm flex items-center justify-between';
            banner.innerHTML = `<span>⚠️ Có bản nháp chưa lưu từ ${data.time}</span>
                <button onclick="localStorage.removeItem('${STORAGE_KEY}');this.closest('div').remove()"
                        class="text-xs underline opacity-60 hover:opacity-100">Xoá</button>`;
            form.before(banner);
        } catch(e) {}
    }

    // Auto-save timestamp to localStorage
    function saveToStorage() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ time: new Date().toLocaleTimeString('vi-VN') }));
    }
    setInterval(saveToStorage, 30000);
    document.addEventListener('visibilitychange', () => { if (document.hidden) saveToStorage(); });

    // Lưu Nháp → AJAX
    document.getElementById('btn-draft').addEventListener('click', async () => {
        const btn = document.getElementById('btn-draft');
        btn.textContent = 'Đang lưu...';
        btn.disabled = true;
        const fd = new FormData(form);
        fd.delete('_method'); // Loại bỏ PATCH/PUT giả của thẻ form Laravel để gửi đúng POST
        try {
            const resp = await fetch('{{ route("admin.grammar-sets.save-draft", $grammarSet) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: fd,
            });
            if (!resp.ok) throw new Error();
            const json = await resp.json();
            localStorage.removeItem(STORAGE_KEY);
            indicator.textContent = `✓ Đã lưu lúc ${json.saved_at}`;
            indicator.classList.remove('hidden');
            setTimeout(() => indicator.classList.add('hidden'), 4000);
            
            // Show big banner success as Fixed Toast
            const existingBanner = document.getElementById('draft-success-banner');
            if (existingBanner) existingBanner.remove();
            
            const banner = document.createElement('div');
            banner.id = 'draft-success-banner';
            // Use fixed positioning at the top-right or bottom-right
            banner.className = 'fixed top-6 right-6 z-[9999] p-4 bg-green-600 shadow-2xl rounded-xl text-white font-medium flex items-center gap-4 transition-all duration-500 transform translate-y-0 opacity-100 min-w-[320px]';
            banner.innerHTML = `
                <div class="flex-shrink-0 bg-white/20 p-2 rounded-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold">Lưu nháp thành công</p>
                    <p class="text-xs opacity-90">Lúc ${json.saved_at}</p>
                </div>
                <button type="button" @click="this.parentElement.remove()" class="text-white hover:text-green-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
            document.body.appendChild(banner);
            
            setTimeout(() => {
                const b = document.getElementById('draft-success-banner');
                if (b) {
                    b.style.opacity = '0';
                    b.style.transform = 'translateY(-20px)';
                    setTimeout(() => b.remove(), 500);
                }
            }, 4000);
        } catch(e) {
            alert('Lưu nháp thất bại, thử lại.');
        } finally {
            btn.textContent = 'Lưu Nháp';
            btn.disabled = false;
        }
    });

    // Publish / Save
    document.getElementById('btn-publish').addEventListener('click', () => {
        actionEl.value = 'publish';
        form.submit();
    });

    // ── MCQ: đổi màu option khi chọn radio ──────────────────────────────────
    document.querySelectorAll('.mcq-radio').forEach(radio => {
        radio.addEventListener('change', () => {
            if (!radio.checked) return;
            const group = radio.dataset.group;
            // Reset all options in this group
            document.querySelectorAll(`[data-mcq-group="${group}"] .mcq-option`).forEach(row => {
                row.classList.remove('border-green-400', 'bg-green-50');
                row.classList.add('border-gray-200', 'bg-white');
                row.querySelector('.mcq-badge').className =
                    'mcq-badge w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-gray-100 text-gray-500';
                row.querySelector('.mcq-label').className = 'mcq-label text-xs text-gray-300';
            });
            // Highlight selected row
            const selected = document.querySelector(`[data-mcq-group="${group}"] .mcq-option[data-opt="${radio.value}"]`);
            if (selected) {
                selected.classList.remove('border-gray-200', 'bg-white');
                selected.classList.add('border-green-400', 'bg-green-50');
                selected.querySelector('.mcq-badge').className =
                    'mcq-badge w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-green-500 text-white';
                selected.querySelector('.mcq-label').className = 'mcq-label text-xs text-green-600 font-semibold';
            }
        });
    });

    // Pool → Select sync
    function syncSelects(poolInput) {
        const order = poolInput.dataset.order;
        const words = poolInput.value.split(',').map(w => w.trim()).filter(Boolean);
        document.querySelectorAll(`.pool-select[data-order="${order}"]`).forEach(sel => {
            const current = sel.value;
            sel.innerHTML = '<option value="">Đáp án...</option>';
            words.forEach(word => {
                const o = document.createElement('option');
                o.value = o.textContent = word;
                if (word === current) o.selected = true;
                sel.appendChild(o);
            });
            if (!sel.value) {
                const fb = sel.dataset.current;
                if (fb) { const o = document.createElement('option'); o.value = o.textContent = fb; o.selected = true; sel.appendChild(o); }
            }
        });
    }

    document.querySelectorAll('.pool-input').forEach(p => {
        p.addEventListener('blur', () => syncSelects(p));
        p.addEventListener('change', () => syncSelects(p));
        syncSelects(p); // init on load
    });
});
</script>

{{-- CKEditor 5 --}}
<script src="https://cdn.ckeditor.com/ckeditor5/38.1.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function initEditor(textarea) {
            if (textarea.classList.contains('ck-editor-initialized')) return;
            
            ClassicEditor
                .create(textarea, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo']
                })
                .then(editor => {
                    textarea.classList.add('ck-editor-initialized');
                    // Sync with Alpine model if needed (though not using Alpine for these textareas directly yet)
                    editor.model.document.on('change:data', () => {
                        textarea.value = editor.getData();
                        textarea.dispatchEvent(new Event('input'));
                    });
                })
                .catch(error => {
                    console.error('CKEditor Init Error:', error);
                });
        }

        // Initial check
        document.querySelectorAll('.editor-content').forEach(initEditor);

        // Observe dynamic changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        if (node.classList.contains('editor-content')) {
                            initEditor(node);
                        }
                        node.querySelectorAll('.editor-content').forEach(initEditor);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>
