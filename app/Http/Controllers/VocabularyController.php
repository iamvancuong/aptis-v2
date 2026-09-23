<?php

namespace App\Http\Controllers;

use App\Models\VocabularyItem;
use App\Services\VocabularyLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VocabularyController extends Controller
{
    public function __construct(protected VocabularyLookupService $lookupService)
    {
        // Công tắc tắt nhanh: tắt là cả trang sổ tay lẫn endpoint tra từ biến
        // mất, không cần gỡ route hay deploy lại.
        abort_unless(config('aptis.vocab.enabled'), 404);
    }

    /* ─────────────────────────── Tra từ ─────────────────────────── */

    public function lookup(Request $request): JsonResponse
    {
        $maxChars = (int) config('aptis.vocab.max_chars', 300);

        $data = $request->validate([
            'term' => ['required', 'string', 'max:' . $maxChars],
            'context' => ['nullable', 'string', 'max:1000'],
        ], [
            'term.max' => "Bạn đang bôi quá dài (tối đa {$maxChars} ký tự). Hãy chọn một từ hoặc một câu thôi nhé.",
        ]);

        $term = trim($data['term']);
        $user = $request->user();

        if ($term === '') {
            return response()->json(['message' => 'Chưa chọn nội dung cần tra.'], 422);
        }

        $remaining = $this->lookupService->remainingToday($user);

        if ($remaining !== 'unlimited' && $remaining <= 0) {
            return response()->json([
                'message' => 'Bạn đã dùng hết lượt tra từ hôm nay. Lượt mới sẽ được cấp lại vào ngày mai.',
                'remaining' => 0,
            ], 429);
        }

        try {
            $result = $this->lookupService->resolve($term, $data['context'] ?? null);
        } catch (\Throwable $e) {
            Log::error('Tra từ thất bại', ['term' => $term, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Không tra được từ này lúc này. Bạn thử lại sau ít giây nhé.',
            ], 503);
        }

        // Chỉ trừ lượt khi đã thực sự có kết quả — hỏng mạng mà vẫn trừ thì học
        // viên mất lượt oan.
        $this->lookupService->recordUsage($user, ! $result['cached']);

        $normalized = VocabularyItem::normalize($term);
        $saved = VocabularyItem::where('user_id', $user->id)
            ->where('normalized_term', $normalized)
            ->first();

        return response()->json([
            'term' => $term,
            'mode' => $result['mode'],
            'data' => $result['payload'],
            'saved' => (bool) $saved,
            'saved_id' => $saved?->id,
            'remaining' => $this->lookupService->remainingToday($user),
        ]);
    }

    /* ─────────────────────── Lưu vào sổ tay ─────────────────────── */

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'term' => ['required', 'string', 'max:500'],
            'meaning' => ['required', 'string', 'max:1000'],
            'part_of_speech' => ['nullable', 'string', 'max:50'],
            'phonetic' => ['nullable', 'string', 'max:100'],
            'example' => ['nullable', 'string', 'max:500'],
            'cefr' => ['nullable', 'string', 'max:5'],
            'context_sentence' => ['nullable', 'string', 'max:1000'],
            'source_skill' => ['nullable', 'string', 'max:20'],
            'source_part' => ['nullable', 'integer', 'between:1,4'],
            'source_set_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $normalized = VocabularyItem::normalize($data['term']);

        if ($normalized === '') {
            return response()->json(['message' => 'Từ không hợp lệ.'], 422);
        }

        // Bôi lại từ đã lưu thì cập nhật nghĩa, KHÔNG đặt lại tiến độ ôn tập —
        // người học không nên mất chuỗi ôn chỉ vì tra lại từ cũ.
        $item = VocabularyItem::updateOrCreate(
            ['user_id' => $user->id, 'normalized_term' => $normalized],
            [
                'term' => $data['term'],
                'meaning' => $data['meaning'],
                'part_of_speech' => $data['part_of_speech'] ?? null,
                'phonetic' => $data['phonetic'] ?? null,
                'example' => $data['example'] ?? null,
                'cefr' => $data['cefr'] ?? null,
                'context_sentence' => $data['context_sentence'] ?? null,
                'source_skill' => $data['source_skill'] ?? null,
                'source_part' => $data['source_part'] ?? null,
                'source_set_id' => $data['source_set_id'] ?? null,
            ],
        );

        // Từ mới: đến hạn ôn ngay, để học viên ôn được trong chính buổi học đó.
        if ($item->wasRecentlyCreated) {
            $item->forceFill(['due_at' => now()])->save();
        }

        return response()->json([
            'message' => 'Đã lưu vào sổ tay từ vựng.',
            'id' => $item->id,
            'total' => VocabularyItem::where('user_id', $user->id)->count(),
        ], $item->wasRecentlyCreated ? 201 : 200);
    }

    /* ─────────────────────────── Sổ tay ─────────────────────────── */

    public function index(Request $request)
    {
        $user = $request->user();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'skill' => ['nullable', Rule::in(['reading', 'listening', 'writing', 'speaking', 'grammar'])],
            'status' => ['nullable', Rule::in(['due', 'learning', 'mastered'])],
        ]);

        $query = VocabularyItem::where('user_id', $user->id);

        if ($keyword = $filters['q'] ?? null) {
            $query->where(function ($q) use ($keyword) {
                $q->where('term', 'like', "%{$keyword}%")
                    ->orWhere('meaning', 'like', "%{$keyword}%");
            });
        }

        if ($skill = $filters['skill'] ?? null) {
            $query->where('source_skill', $skill);
        }

        match ($filters['status'] ?? null) {
            'due' => $query->due(),
            'mastered' => $query->where('box', '>=', VocabularyItem::MAX_BOX)->where('streak', '>=', 2),
            'learning' => $query->where(function ($q) {
                $q->where('box', '<', VocabularyItem::MAX_BOX)->orWhere('streak', '<', 2);
            }),
            default => null,
        };

        $items = $query->latest()->paginate(24)->withQueryString();

        $all = VocabularyItem::where('user_id', $user->id);

        return view('vocabulary.index', [
            'items' => $items,
            'filters' => $filters,
            'stats' => [
                'total' => (clone $all)->count(),
                'due' => (clone $all)->due()->count(),
                'mastered' => (clone $all)->where('box', '>=', VocabularyItem::MAX_BOX)->where('streak', '>=', 2)->count(),
            ],
        ]);
    }

    public function update(Request $request, VocabularyItem $item)
    {
        $this->authorizeItem($request, $item);

        $data = $request->validate([
            'meaning' => ['required', 'string', 'max:1000'],
            'example' => ['nullable', 'string', 'max:500'],
        ]);

        $item->update($data);

        return back()->with('success', 'Đã cập nhật từ "' . $item->term . '".');
    }

    public function destroy(Request $request, VocabularyItem $item)
    {
        $this->authorizeItem($request, $item);

        $term = $item->term;
        $item->delete();

        return back()->with('success', 'Đã xoá từ "' . $term . '" khỏi sổ tay.');
    }

    /* ─────────────────────────── Ôn tập ─────────────────────────── */

    public function review(Request $request)
    {
        $user = $request->user();
        $batch = (int) config('aptis.vocab.review_batch', 20);

        // Hộp thấp trước: từ hay quên nhất phải được gặp lại trước, và nếu học
        // viên bỏ dở giữa chừng thì phần đã ôn cũng là phần quan trọng nhất.
        $cards = VocabularyItem::where('user_id', $user->id)
            ->due()
            ->orderBy('box')
            ->orderBy('due_at')
            ->limit($batch)
            ->get();

        return view('vocabulary.review', [
            'cards' => $cards,
            'remainingAfter' => max(0, VocabularyItem::where('user_id', $user->id)->due()->count() - $cards->count()),
            'totalItems' => VocabularyItem::where('user_id', $user->id)->count(),
        ]);
    }

    public function grade(Request $request, VocabularyItem $item): JsonResponse
    {
        $this->authorizeItem($request, $item);

        $data = $request->validate([
            'result' => ['required', Rule::in(['again', 'hard', 'good'])],
        ]);

        $item->recordReview($data['result']);

        return response()->json([
            'box' => $item->box,
            'due_at' => $item->due_at?->toDateString(),
            'interval_days' => $item->interval_days,
            'mastered' => $item->isMastered(),
        ]);
    }

    /* ────────────────────────── Xuất file ────────────────────────── */

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $filename = 'so-tay-tu-vung-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($user) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8: thiếu dòng này Excel trên Windows mở ra là tiếng Việt
            // vỡ hết dấu — lỗi khách báo lại đầu tiên mỗi khi quên.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Từ', 'Loại từ', 'Phiên âm', 'Nghĩa', 'Ví dụ', 'Câu gốc trong bài', 'Mức CEFR', 'Hộp ôn tập', 'Ngày ôn kế tiếp', 'Ngày lưu']);

            VocabularyItem::where('user_id', $user->id)
                ->orderBy('created_at')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $item) {
                        fputcsv($handle, [
                            $item->term,
                            $item->part_of_speech,
                            $item->phonetic,
                            $item->meaning,
                            $item->example,
                            $item->context_sentence,
                            $item->cefr,
                            $item->box . '/' . VocabularyItem::MAX_BOX,
                            $item->due_at?->format('d/m/Y'),
                            $item->created_at->format('d/m/Y'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Sổ tay là dữ liệu riêng của từng học viên — admin cũng không xem ké được
     * qua URL đoán ID.
     */
    protected function authorizeItem(Request $request, VocabularyItem $item): void
    {
        abort_unless($item->user_id === $request->user()->id, 403);
    }
}
