/**
 * Logic thuần cho các kiểu ôn "Điền từ" và "Nghe & gõ": dựng câu đục lỗ, so
 * đáp án gõ vào với từ đúng. Không đụng DOM để test được bằng Node.
 *
 * Gắn vào `window.vocabPractice` vì trang ôn tập viết Alpine component ngay
 * trong Blade.
 */

/** Câu dài hơn ngần này từ thì không đục lỗ / không bắt gõ lại nguyên văn. */
const LONG_TERM_WORDS = 6;

export function wordCount(text) {
    return (text || '').trim().split(/\s+/).filter(Boolean).length;
}

/** Thẻ là cả một câu (hoặc cụm quá dài) — chỉ hợp với kiểu lật thẻ / nghe chép. */
export function isSentence(card) {
    return card.word_type === 'sentence' || wordCount(card.term) > LONG_TERM_WORDS;
}

function escapeRegExp(text) {
    return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Tìm `term` trong `sentence` (không phân biệt hoa thường, đúng ranh giới từ)
 * và trả về phần trước / sau chỗ trống. Không thấy thì null.
 */
export function blankOut(sentence, term) {
    if (!sentence || !term) return null;

    const pattern = new RegExp(`(^|[^\\p{L}\\p{N}'])(${escapeRegExp(term.trim())})(?=$|[^\\p{L}\\p{N}'])`, 'iu');
    const match = pattern.exec(sentence);
    if (!match) return null;

    const start = match.index + match[1].length;
    return {
        before: sentence.slice(0, start),
        after: sentence.slice(start + match[2].length),
        answer: match[2],
    };
}

/**
 * Đề cho kiểu "Điền từ". Ưu tiên câu gốc trong bài (học viên nhớ theo ngữ cảnh
 * đã gặp), rồi tới câu ví dụ, cuối cùng chỉ còn nghĩa tiếng Việt.
 *
 * @returns {{ kind: 'cloze', before: string, after: string, source: 'context'|'example' }
 *          | { kind: 'meaning' } | { kind: 'flip' }}
 */
export function clozePrompt(card) {
    if (isSentence(card)) return { kind: 'flip' };

    const fromContext = blankOut(card.context, card.term);
    if (fromContext) return { kind: 'cloze', ...fromContext, source: 'context' };

    const fromExample = blankOut(card.example, card.term);
    if (fromExample) return { kind: 'cloze', ...fromExample, source: 'example' };

    return { kind: 'meaning' };
}

/** Chuẩn hoá để so: thường hoá, gộp khoảng trắng, bỏ dấu câu, thống nhất dấu nháy. */
export function normalizeAnswer(text) {
    return (text || '')
        .toLowerCase()
        .replace(/[‘’ʼ`]/g, "'")
        .replace(/[“”]/g, '"')
        .replace(/[.,!?;:"()\[\]{}…–—-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

export function levenshtein(a, b) {
    if (a === b) return 0;
    if (!a.length) return b.length;
    if (!b.length) return a.length;

    let prev = Array.from({ length: b.length + 1 }, (_, i) => i);
    for (let i = 1; i <= a.length; i++) {
        const curr = [i];
        for (let j = 1; j <= b.length; j++) {
            curr[j] = Math.min(
                prev[j] + 1,
                curr[j - 1] + 1,
                prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1),
            );
        }
        prev = curr;
    }
    return prev[b.length];
}

/**
 * So đáp án gõ vào.
 *
 *   exact → gợi ý "Nhớ"     close → gợi ý "Mơ hồ" (sai chính tả nhẹ)
 *   wrong → gợi ý "Quên"
 *
 * Từ ngắn (dưới 6 ký tự) không có "gần đúng": "cat" và "cut" là hai từ khác
 * nhau chứ không phải lỗi chính tả.
 */
export function checkAnswer(input, expected) {
    const a = normalizeAnswer(input);
    const b = normalizeAnswer(expected);

    if (!a) return { verdict: 'wrong', suggestion: 'again', distance: b.length };
    if (a === b) return { verdict: 'exact', suggestion: 'good', distance: 0 };

    const distance = levenshtein(a, b);
    const tolerance = Math.floor(b.length / 6);

    return distance <= tolerance
        ? { verdict: 'close', suggestion: 'hard', distance }
        : { verdict: 'wrong', suggestion: 'again', distance };
}

/** Dùng gợi ý thì nhớ chưa trọn — hạ "Nhớ" xuống "Mơ hồ". */
export function applyHintPenalty(result, usedHint) {
    if (usedHint && result.suggestion === 'good') {
        return { ...result, suggestion: 'hard' };
    }
    return result;
}

if (typeof window !== 'undefined') {
    window.vocabPractice = {
        isSentence, blankOut, clozePrompt, normalizeAnswer, levenshtein, checkAnswer, applyHintPenalty,
    };
}
