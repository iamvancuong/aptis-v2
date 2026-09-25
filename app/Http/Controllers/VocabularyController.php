<?php

namespace App\Http\Controllers;

use App\Models\VocabFolder;
use App\Models\VocabularyItem;
use App\Services\VocabularyLookupService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'saved_folder_id' => $saved?->folder_id,
            'remaining' => $this->lookupService->remainingToday($user),
        ]);
    }

    /* ─────────────────────── Lưu vào sổ tay ─────────────────────── */

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'term' => ['required', 'string', 'max:500'],
            'meaning' => ['required', 'string', 'max:1000'],
            'word_type' => ['nullable', Rule::in(array_keys(VocabularyItem::WORD_TYPES))],
            'part_of_speech' => ['nullable', 'string', 'max:50'],
            'phonetic' => ['nullable', 'string', 'max:100'],
            'example' => ['nullable', 'string', 'max:500'],
            'cefr' => ['nullable', 'string', 'max:5'],
            'context_sentence' => ['nullable', 'string', 'max:1000'],
            'source_skill' => ['nullable', 'string', 'max:20'],
            'source_part' => ['nullable', 'integer', 'between:1,4'],
            'source_set_id' => ['nullable', 'integer'],
            'folder_id' => ['nullable', 'integer', $this->ownFolderRule($request)],
        ]);

        $normalized = VocabularyItem::normalize($data['term']);

        if ($normalized === '') {
            return response()->json(['message' => 'Từ không hợp lệ.'], 422);
        }

        // Bôi lại từ đã lưu thì cập nhật nghĩa (và thư mục), KHÔNG đặt lại tiến
        // độ ôn tập — người học không nên mất chuỗi ôn chỉ vì tra lại từ cũ.
        $item = VocabularyItem::updateOrCreate(
            ['user_id' => $user->id, 'normalized_term' => $normalized],
            [
                'term' => $data['term'],
                'meaning' => $data['meaning'],
                'word_type' => $data['word_type'] ?? VocabularyItem::guessWordType($data['part_of_speech'] ?? null),
                'part_of_speech' => $data['part_of_speech'] ?? null,
                'phonetic' => $data['phonetic'] ?? null,
                'example' => $data['example'] ?? null,
                'cefr' => $data['cefr'] ?? null,
                'context_sentence' => $data['context_sentence'] ?? null,
                'source_skill' => $data['source_skill'] ?? null,
                'source_part' => $data['source_part'] ?? null,
                'source_set_id' => $data['source_set_id'] ?? null,
                'folder_id' => $data['folder_id'] ?? null,
            ],
        );

        // Từ mới: đến hạn ôn ngay, để học viên ôn được trong chính buổi học đó.
        if ($item->wasRecentlyCreated) {
            $item->forceFill(['due_at' => now()])->save();
        }

        return response()->json([
            'message' => 'Đã lưu vào sổ tay từ vựng.',
            'id' => $item->id,
            'folder_id' => $item->folder_id,
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
            ...$this->scopeRules(),
        ]);

        $query = $this->scopedQuery($request, $filters);

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
            'mastered' => $query->mastered(),
            'learning' => $query->learning(),
            default => null,
        };

        $items = $query->with('folder')->latest()->paginate(24)->withQueryString();

        // Thống kê theo phạm vi đang xem (thư mục / loại từ) — để nút "Ôn thư
        // mục này" hiện đúng số từ đến hạn của chính thư mục đó.
        $scoped = $this->scopedQuery($request, $filters);

        $folders = VocabFolder::where('user_id', $user->id)
            ->withCount('items')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $typeCounts = VocabularyItem::where('user_id', $user->id)
            ->select('word_type', DB::raw('count(*) as total'))
            ->groupBy('word_type')
            ->pluck('total', 'word_type');

        return view('vocabulary.index', [
            'items' => $items,
            'filters' => $filters,
            'folders' => $folders,
            'typeCounts' => $typeCounts,
            'currentFolder' => isset($filters['folder']) ? $folders->firstWhere('id', (int) $filters['folder']) : null,
            'totalAll' => VocabularyItem::where('user_id', $user->id)->count(),
            'stats' => [
                'total' => (clone $scoped)->count(),
                'due' => (clone $scoped)->due()->count(),
                'mastered' => (clone $scoped)->mastered()->count(),
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

    /** Chuyển một từ sang thư mục khác (null = bỏ khỏi thư mục). */
    public function move(Request $request, VocabularyItem $item): JsonResponse|RedirectResponse
    {
        $this->authorizeItem($request, $item);

        $data = $request->validate([
            'folder_id' => ['nullable', 'integer', $this->ownFolderRule($request)],
        ]);

        $item->update(['folder_id' => $data['folder_id'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json(['folder_id' => $item->folder_id]);
        }

        return back()->with('success', 'Đã chuyển "' . $item->term . '" sang thư mục mới.');
    }

    public function destroy(Request $request, VocabularyItem $item)
    {
        $this->authorizeItem($request, $item);

        $term = $item->term;
        $item->delete();

        return back()->with('success', 'Đã xoá từ "' . $term . '" khỏi sổ tay.');
    }

    /* ─────────────────────────── Thư mục ─────────────────────────── */

    public function storeFolder(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('vocab_folders', 'name')->where('user_id', $user->id),
            ],
        ], [
            'name.unique' => 'Bạn đã có thư mục tên này rồi.',
            'name.required' => 'Hãy đặt tên cho thư mục.',
        ]);

        $max = (int) config('aptis.vocab.max_folders', 50);
        if (VocabFolder::where('user_id', $user->id)->count() >= $max) {
            $message = "Bạn đã có {$max} thư mục — hãy xoá bớt trước khi tạo mới.";

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['name' => $message]);
        }

        $folder = VocabFolder::create([
            'user_id' => $user->id,
            'name' => trim($data['name']),
            'sort_order' => (int) VocabFolder::where('user_id', $user->id)->max('sort_order') + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['id' => $folder->id, 'name' => $folder->name], 201);
        }

        return redirect()->route('vocab.index', ['folder' => $folder->id])
            ->with('success', 'Đã tạo thư mục "' . $folder->name . '".');
    }

    public function updateFolder(Request $request, VocabFolder $folder): RedirectResponse
    {
        $this->authorizeFolder($request, $folder);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('vocab_folders', 'name')->where('user_id', $folder->user_id)->ignore($folder->id),
            ],
        ], [
            'name.unique' => 'Bạn đã có thư mục tên này rồi.',
        ]);

        $folder->update(['name' => trim($data['name'])]);

        return back()->with('success', 'Đã đổi tên thư mục.');
    }

    public function destroyFolder(Request $request, VocabFolder $folder): RedirectResponse
    {
        $this->authorizeFolder($request, $folder);

        $name = $folder->name;
        // Từ trong thư mục KHÔNG bị xoá theo — khoá ngoại nullOnDelete trả chúng
        // về "chưa xếp thư mục". Mất thư mục thì tạo lại được, mất từ đã ôn cả
        // tháng thì không.
        $folder->delete();

        return redirect()->route('vocab.index')
            ->with('success', 'Đã xoá thư mục "' . $name . '". Các từ bên trong vẫn còn trong sổ tay.');
    }

    /* ─────────────────────────── Ôn tập ─────────────────────────── */

    public function review(Request $request)
    {
        $user = $request->user();
        $batch = (int) config('aptis.vocab.review_batch', 20);
        $filters = $request->validate($this->scopeRules());

        // Thứ tự như Anki: thẻ đang học (sắp quên nhất) → thẻ ôn theo ngày →
        // thẻ mới. Học viên bỏ dở giữa chừng thì phần đã ôn là phần quan trọng
        // nhất.
        $cards = $this->scopedQuery($request, $filters)
            ->due()
            ->orderByRaw("case srs_state when 'learning' then 0 when 'relearning' then 0 when 'review' then 1 else 2 end")
            ->orderBy('due_at')
            ->limit($batch)
            ->get();

        return view('vocabulary.review', [
            'cards' => $cards->map(fn (VocabularyItem $c) => $this->cardPayload($c))->values(),
            'filters' => $filters,
            'scopeLabel' => $this->scopeLabel($request, $filters),
            'remainingAfter' => max(0, $this->scopedQuery($request, $filters)->due()->count() - $cards->count()),
            'totalItems' => VocabularyItem::where('user_id', $user->id)->count(),
            'learnAheadMinutes' => (int) config('aptis.vocab.srs.learn_ahead', 20),
        ]);
    }

    public function grade(Request $request, VocabularyItem $item): JsonResponse
    {
        $this->authorizeItem($request, $item);

        $data = $request->validate([
            'result' => ['required', Rule::in(VocabularyItem::RESULTS)],
        ]);

        $item->recordReview($data['result']);

        return response()->json([
            'srs_state' => $item->srs_state,
            'step' => $item->step,
            'interval_days' => $item->interval_days,
            'due_at' => $item->due_at?->toIso8601String(),
            'mastered' => $item->isMastered(),
            // Nhãn nút cho lần gặp lại trong cùng phiên (thẻ đang học quay lại
            // sau vài phút).
            'intervals' => $item->previewIntervals(),
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

            fputcsv($handle, ['Từ', 'Loại từ', 'Thư mục', 'Phiên âm', 'Nghĩa', 'Ví dụ', 'Câu gốc trong bài', 'Mức CEFR', 'Tiến độ', 'Ngày ôn kế tiếp', 'Ngày lưu']);

            VocabularyItem::where('user_id', $user->id)
                ->with('folder')
                ->orderBy('created_at')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $item) {
                        fputcsv($handle, [
                            $item->term,
                            $item->part_of_speech ?? $item->wordTypeLabel(),
                            $item->folder?->name,
                            $item->phonetic,
                            $item->meaning,
                            $item->example,
                            $item->context_sentence,
                            $item->cefr,
                            $item->statusLabel(),
                            $item->due_at?->format('d/m/Y H:i'),
                            $item->created_at->format('d/m/Y'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /* ─────────────────────────── Nội bộ ─────────────────────────── */

    /** Phạm vi xem/ôn: một thư mục tự tạo, hoặc một loại từ. */
    protected function scopeRules(): array
    {
        return [
            'folder' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::in(array_keys(VocabularyItem::WORD_TYPES))],
        ];
    }

    protected function scopedQuery(Request $request, array $filters): Builder
    {
        $query = VocabularyItem::where('user_id', $request->user()->id);

        if (! empty($filters['folder'])) {
            // Lọc thêm theo user_id ở trên nên đoán ID thư mục của người khác
            // cũng chỉ ra danh sách rỗng.
            $query->where('folder_id', (int) $filters['folder']);
        }

        if (! empty($filters['type'])) {
            $filters['type'] === 'other'
                ? $query->where(fn ($q) => $q->where('word_type', 'other')->orWhereNull('word_type'))
                : $query->where('word_type', $filters['type']);
        }

        return $query;
    }

    protected function scopeLabel(Request $request, array $filters): ?string
    {
        if (! empty($filters['folder'])) {
            return VocabFolder::where('user_id', $request->user()->id)
                ->whereKey((int) $filters['folder'])
                ->value('name');
        }

        return ! empty($filters['type']) ? VocabularyItem::WORD_TYPES[$filters['type']] : null;
    }

    protected function cardPayload(VocabularyItem $card): array
    {
        return [
            'id' => $card->id,
            'term' => $card->term,
            'phonetic' => $card->phonetic,
            'part_of_speech' => $card->part_of_speech,
            'word_type' => $card->word_type,
            'meaning' => $card->meaning,
            'example' => $card->example,
            'context' => $card->context_sentence,
            'srs_state' => $card->srs_state,
            'intervals' => $card->previewIntervals(),
        ];
    }

    /** Chỉ nhận ID thư mục của chính học viên đó. */
    protected function ownFolderRule(Request $request)
    {
        return Rule::exists('vocab_folders', 'id')->where('user_id', $request->user()->id);
    }

    /**
     * Sổ tay là dữ liệu riêng của từng học viên — admin cũng không xem ké được
     * qua URL đoán ID.
     */
    protected function authorizeItem(Request $request, VocabularyItem $item): void
    {
        abort_unless($item->user_id === $request->user()->id, 403);
    }

    protected function authorizeFolder(Request $request, VocabFolder $folder): void
    {
        abort_unless($folder->user_id === $request->user()->id, 403);
    }
}
