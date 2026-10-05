/**
 * Đọc tiếng Anh bằng giọng có sẵn của trình duyệt (Web Speech API): không tốn
 * tiền API, không cần tệp âm thanh. Dùng chung cho popup tra từ và thẻ ôn tập.
 *
 * Gắn vào `window.vocabSpeech` vì trang ôn tập viết Alpine component ngay
 * trong Blade (không import được module).
 */

const synth = typeof window !== 'undefined' ? window.speechSynthesis : undefined;

export const canSpeak = typeof synth !== 'undefined';

/** Giọng Anh-Anh cho khớp phiên âm IPA; không có thì giọng Anh bất kỳ. */
function pickVoice() {
    const voices = synth.getVoices();
    return voices.find((v) => v.lang === 'en-GB')
        || voices.find((v) => v.lang && v.lang.startsWith('en'))
        || null;
}

/**
 * Đọc `text`. Gọi lại khi đang đọc thì đọc lại từ đầu, không xếp hàng chồng
 * lên nhau.
 *
 * @param {string} text
 * @param {{ onStart?: Function, onEnd?: Function, rate?: number }} [options]
 */
export function speak(text, { onStart, onEnd, rate = 0.9 } = {}) {
    if (!canSpeak || !text) return;

    synth.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    const voice = pickVoice();
    if (voice) utterance.voice = voice;
    // Chưa nạp xong danh sách giọng (Chrome nạp bất đồng bộ) thì vẫn ép lang
    // để trình duyệt không đọc tiếng Anh bằng giọng tiếng Việt.
    utterance.lang = voice?.lang || 'en-GB';
    utterance.rate = rate;
    utterance.onstart = () => onStart && onStart();
    utterance.onend = utterance.onerror = () => onEnd && onEnd();

    synth.speak(utterance);
}

export function cancel() {
    if (canSpeak) synth.cancel();
}

if (typeof window !== 'undefined') {
    window.vocabSpeech = { canSpeak, speak, cancel };
}
