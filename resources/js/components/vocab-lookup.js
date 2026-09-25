/**
 * Tra từ bằng cách bôi chọn trong bài.
 *
 * Chỉ hoạt động bên trong phần tử có `data-vocab-scope` — trang thi thử không
 * gắn thuộc tính này nên bôi chọn ở đó không ra gì cả.
 *
 * Luồng: bôi chọn → hiện nút "Tra từ" → bấm mới gọi API → popup kết quả.
 * CỐ Ý không tự gọi API ngay khi bôi: học viên bôi vu vơ lúc đọc rất nhiều,
 * gọi luôn thì vừa đốt tiền vừa nhấp nháy popup gây khó chịu.
 */

/** Giới hạn ký tự — khớp với `aptis.vocab.max_chars` phía server. */
const MAX_CHARS = 300;

/** Bôi trong ô nhập liệu là để sửa bài, không phải để tra từ. */
const IGNORED_TAGS = ['INPUT', 'TEXTAREA', 'SELECT', 'OPTION', 'BUTTON'];

function elementOf(node) {
    if (!node) return null;
    return node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
}

/**
 * Câu chứa đoạn đã bôi — thứ giúp AI dịch đúng nghĩa trong ngữ cảnh, và cũng
 * là câu được lưu kèm vào sổ tay.
 *
 * Tự dò biên câu thay vì dùng regex lookbehind: Safari cũ trên iPhone không
 * hỗ trợ lookbehind và sẽ ném lỗi cú pháp làm hỏng cả file.
 */
function extractContext(range, term) {
    const start = elementOf(range.startContainer);
    const block = start ? start.closest('p, li, td, th, blockquote, div') : null;
    if (!block) return null;

    const full = (block.innerText || block.textContent || '').replace(/\s+/g, ' ').trim();
    if (!full) return null;

    // Đã ngắn sẵn thì lấy nguyên khối, khỏi cắt câu.
    if (full.length <= MAX_CHARS) return full;

    const at = full.indexOf(term);
    if (at === -1) return full.slice(0, MAX_CHARS);

    const terminators = ['.', '!', '?'];
    let from = 0;
    for (let i = at - 1; i >= 0; i--) {
        if (terminators.includes(full[i])) {
            from = i + 1;
            break;
        }
    }

    let to = full.length;
    for (let i = at + term.length; i < full.length; i++) {
        if (terminators.includes(full[i])) {
            to = i + 1;
            break;
        }
    }

    return full.slice(from, to).trim().slice(0, MAX_CHARS);
}

function vocabLookup(config) {
    return {
        // config: { lookupUrl, saveUrl, folderUrl, notebookUrl, csrf, folders, source: {skill, part, setId} }
        cfg: config,

        trigger: { show: false, x: 0, y: 0 },
        panel: { show: false, x: 0, y: 0 },

        /**
         * Vị trí vùng bôi, giữ lại để đặt lại thẻ kết quả sau khi nội dung đổi.
         * Không giữ thì lúc kết quả về, thẻ cao lên và tràn khỏi màn hình.
         */
        anchorRect: null,

        term: '',
        context: null,
        mode: 'word',

        loading: false,
        error: null,
        result: null,

        saved: false,
        saving: false,
        remaining: null,

        /* Thư mục lưu từ. '' = chỉ xếp tự động theo loại từ. */
        folders: config.folders || [],
        folderId: '',
        /** Nhớ thư mục vừa chọn trong trang: lưu liền 10 từ vào cùng một thư
         *  mục thì không phải chọn lại 10 lần. */
        lastFolderId: '',
        newFolder: { open: false, name: '', busy: false },

        init() {
            // Chuột: chỉ cần mouseup là biết người dùng đã bôi xong.
            document.addEventListener('mouseup', (e) => this.onPointerUp(e));

            // Cảm ứng: iOS không bắn mouseup sau khi kéo tay cầm chọn chữ.
            // Chờ thêm một nhịp cho thanh công cụ chọn chữ của hệ điều hành ổn
            // định rồi mới đo vị trí, nếu không nút hiện lệch chỗ.
            document.addEventListener('touchend', () => {
                setTimeout(() => this.onPointerUp(null), 350);
            });

            // Bôi xong mà cuộn trang thì toạ độ cũ sai — giấu đi cho gọn.
            window.addEventListener('scroll', () => this.hideTrigger(), { passive: true });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeAll();
            });

            // Thẻ kết quả đổi chiều cao nhiều lần trong một lượt tra (khung chờ
            // → nội dung → trạng thái "Đã lưu"). Bám theo kích thước thật bằng
            // ResizeObserver thay vì canh thời điểm bằng $nextTick: Alpine giữ
            // lại nextTick trong lúc x-transition đang chạy, nên lần đặt lại vị
            // trí rơi vào trước khi thẻ cao lên và nút "Lưu từ" bị đẩy ra ngoài
            // màn hình.
            requestAnimationFrame(() => {
                const panel = this.$refs.panel;
                if (!panel || typeof ResizeObserver === 'undefined') return;

                new ResizeObserver(() => {
                    if (this.panel.show) this.clampPanel();
                }).observe(panel);
            });
        },

        /* ───────────────────────── Bắt vùng bôi ───────────────────────── */

        onPointerUp(event) {
            // Bấm vào chính popup thì đừng coi là thao tác bôi mới.
            if (event && this.$refs.root && this.$refs.root.contains(event.target)) return;

            const selection = window.getSelection();
            if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
                this.hideTrigger();
                return;
            }

            const text = selection.toString().replace(/\s+/g, ' ').trim();
            if (!text) {
                this.hideTrigger();
                return;
            }

            const range = selection.getRangeAt(0);
            const anchor = elementOf(range.startContainer);
            if (!anchor) return;

            if (IGNORED_TAGS.includes(anchor.tagName)) return;
            if (!anchor.closest('[data-vocab-scope]')) {
                this.hideTrigger();
                return;
            }

            if (text.length > MAX_CHARS) {
                this.showPanelAt(range, {
                    error: `Bạn đang bôi quá dài (${text.length} ký tự). Hãy chọn một từ hoặc một câu thôi nhé.`,
                });
                return;
            }

            this.term = text;
            this.context = extractContext(range, text);

            const rect = range.getBoundingClientRect();
            this.trigger = {
                show: true,
                // Nút bám mép trái vùng bôi, nổi lên phía trên một chút để
                // không che chính chữ đang đọc.
                x: Math.max(8, Math.min(rect.left, window.innerWidth - 120)),
                y: Math.max(8, rect.top - 44),
            };
            this.panel.show = false;
        },

        hideTrigger() {
            this.trigger.show = false;
        },

        closeAll() {
            this.trigger.show = false;
            this.panel.show = false;
            this.result = null;
            this.error = null;
        },

        showPanelAt(range, { error = null } = {}) {
            const rect = range.getBoundingClientRect();
            this.positionPanel(rect);
            this.error = error;
            this.result = null;
            this.trigger.show = false;
            this.panel.show = true;
        },

        positionPanel(rect) {
            if (rect) this.anchorRect = { top: rect.top, bottom: rect.bottom, left: rect.left };

            const anchor = this.anchorRect;
            if (!anchor) return;

            this.panel.x = Math.max(8, Math.min(anchor.left, window.innerWidth - 348));
            this.panel.y = anchor.bottom + 8;

            // Chiều cao thẻ phụ thuộc nội dung AI trả về (có ví dụ không, ghi
            // chú dài bao nhiêu) nên KHÔNG đoán được bằng hằng số — phải đo sau
            // khi Alpine vẽ xong. Đoán bằng số cố định là cách nút "Lưu từ" bị
            // đẩy xuống dưới mép màn hình.
            requestAnimationFrame(() => this.clampPanel());
        },

        /** Kéo thẻ kết quả vào trong màn hình sau khi đã biết kích thước thật. */
        clampPanel() {
            const el = this.$refs.panel;
            const anchor = this.anchorRect;
            if (!el || !anchor) return;

            const { height } = el.getBoundingClientRect();
            const margin = 8;
            let y = anchor.bottom + margin;

            if (y + height > window.innerHeight - margin) {
                // Ưu tiên lật lên trên vùng bôi để không che chính chữ đang đọc.
                const above = anchor.top - height - margin;
                y = above >= margin ? above : Math.max(margin, window.innerHeight - height - margin);
            }

            this.panel.y = y;
        },

        /* ───────────────────────── Gọi API ───────────────────────── */

        async lookup() {
            const selection = window.getSelection();
            let rect = null;
            if (selection && selection.rangeCount > 0 && !selection.isCollapsed) {
                rect = selection.getRangeAt(0).getBoundingClientRect();
            }

            this.trigger.show = false;
            this.panel.show = true;
            this.loading = true;
            this.error = null;
            this.result = null;
            this.saved = false;

            if (rect) this.positionPanel(rect);

            try {
                const response = await fetch(this.cfg.lookupUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ term: this.term, context: this.context }),
                });

                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.error = body.message || 'Không tra được từ này. Bạn thử lại nhé.';
                    if (typeof body.remaining !== 'undefined') this.remaining = body.remaining;
                    return;
                }

                this.result = body.data;
                this.mode = body.mode;
                this.saved = body.saved;
                this.remaining = body.remaining;
                // Từ đã lưu thì hiện đúng thư mục đang chứa nó — lưu lại mà lấy
                // thư mục mặc định sẽ lặng lẽ kéo từ ra khỏi thư mục cũ.
                this.folderId = body.saved
                    ? String(body.saved_folder_id ?? '')
                    : this.lastFolderId;
                this.newFolder.open = false;
            } catch (e) {
                this.error = 'Mất kết nối. Kiểm tra mạng rồi thử lại nhé.';
            } finally {
                this.loading = false;
                // Kết quả vừa thay khung xương chờ bằng nội dung thật → thẻ cao
                // lên, phải đặt lại vị trí.
                requestAnimationFrame(() => this.clampPanel());
            }
        },

        async save() {
            if (!this.result || this.saved || this.saving) return;

            this.saving = true;
            try {
                const response = await fetch(this.cfg.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        term: this.term,
                        meaning: this.result.meaning,
                        word_type: this.result.word_type,
                        part_of_speech: this.result.part_of_speech,
                        phonetic: this.result.phonetic,
                        example: this.result.example,
                        cefr: this.result.cefr,
                        context_sentence: this.context,
                        source_skill: this.cfg.source.skill,
                        source_part: this.cfg.source.part,
                        source_set_id: this.cfg.source.setId,
                        folder_id: this.folderId === '' ? null : Number(this.folderId),
                    }),
                });

                if (!response.ok) {
                    const body = await response.json().catch(() => ({}));
                    this.error = body.message || 'Chưa lưu được từ này.';
                    return;
                }

                this.saved = true;
                this.lastFolderId = this.folderId;
            } catch (e) {
                this.error = 'Mất kết nối, chưa lưu được từ này.';
            } finally {
                this.saving = false;
            }
        },

        /* ───────────────────────── Thư mục ───────────────────────── */

        onFolderChange() {
            if (this.folderId === '__new') {
                this.newFolder = { open: true, name: '', busy: false };
                this.$nextTick(() => this.$refs.newFolderInput && this.$refs.newFolderInput.focus());
                return;
            }
            // Đổi thư mục của từ đã lưu → bật lại nút để lưu thay đổi.
            this.saved = false;
        },

        cancelNewFolder() {
            this.newFolder.open = false;
            this.folderId = this.lastFolderId;
        },

        async createFolder() {
            const name = this.newFolder.name.trim();
            if (!name || this.newFolder.busy) return;

            this.newFolder.busy = true;
            this.error = null;
            try {
                const response = await fetch(this.cfg.folderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name }),
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.error = (body.errors && body.errors.name && body.errors.name[0])
                        || body.message || 'Chưa tạo được thư mục.';
                    return;
                }

                this.folders.push({ id: body.id, name: body.name });
                this.folderId = String(body.id);
                this.newFolder.open = false;
                this.saved = false;
            } catch (e) {
                this.error = 'Mất kết nối, chưa tạo được thư mục.';
            } finally {
                this.newFolder.busy = false;
            }
        },

        /** Nhãn hạn mức hiển thị dưới popup. */
        get remainingLabel() {
            if (this.remaining === null || this.remaining === 'unlimited') return '';
            return `Còn ${this.remaining} lượt tra hôm nay`;
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('vocabLookup', vocabLookup);
});

export default vocabLookup;
