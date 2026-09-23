<?php

namespace App\Services;

use App\Models\AiLookup;
use App\Models\User;
use App\Models\VocabLookupUsage;
use App\Models\VocabularyItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tra từ: kho đệm dùng chung + hạn mức theo ngày.
 *
 * Tách khỏi controller vì cả hai việc này đều là chỗ ăn tiền thật — muốn sửa
 * chính sách chi phí thì sửa đúng một file, và test được mà không cần HTTP.
 */
class VocabularyLookupService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Cụm bao nhiêu từ thì coi là câu (dịch cả câu) thay vì tra từ điển.
     */
    public function modeFor(string $term): string
    {
        $words = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $threshold = (int) config('aptis.vocab.phrase_word_threshold', 5);

        return count($words) >= $threshold ? 'phrase' : 'word';
    }

    /**
     * Trả nghĩa cho một đoạn đã bôi.
     *
     * @return array{payload: array, mode: string, cached: bool}
     *
     * @throws \Exception khi cả kho đệm lẫn AI đều không cho được kết quả
     */
    public function resolve(string $term, ?string $context): array
    {
        $mode = $this->modeFor($term);
        $normalized = VocabularyItem::normalize($term);
        $hash = AiLookup::makeHash($mode, $normalized);

        $cached = AiLookup::where('hash', $hash)->first();

        if ($cached) {
            // increment() thay vì ++ rồi save(): hai học viên tra cùng lúc thì
            // đọc-rồi-ghi sẽ nuốt mất một lượt đếm.
            $cached->increment('hit_count');

            return ['payload' => $cached->payload, 'mode' => $mode, 'cached' => true];
        }

        $result = $this->ai->lookupVocabulary($term, $context, $mode);
        $payload = $result['payload'];

        $this->remember($hash, $term, $mode, $payload);

        return ['payload' => $payload, 'mode' => $mode, 'cached' => false];
    }

    /**
     * Ghi vào kho đệm. Hỏng ở đây KHÔNG được làm hỏng lượt tra: học viên đã có
     * nghĩa rồi, mất đệm chỉ tốn thêm tiền cho lượt sau.
     */
    protected function remember(string $hash, string $term, string $mode, array $payload): void
    {
        // Câu trả lời "không tra được" mà đem đệm thì từ đó hỏng vĩnh viễn.
        if (($payload['meaning'] ?? '') === 'Không tra được từ này.') {
            return;
        }

        try {
            AiLookup::create([
                'hash' => $hash,
                'term' => mb_substr($term, 0, 500),
                'mode' => $mode,
                'payload' => $payload,
                'hit_count' => 1,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Hai người tra cùng một từ mới trong cùng một khoảnh khắc. Dòng kia
            // đã ghi rồi, không có gì phải xử lý.
        } catch (\Throwable $e) {
            Log::warning('Không ghi được kho đệm tra từ: ' . $e->getMessage());
        }
    }

    /**
     * Số lượt tra còn lại hôm nay. Admin không bị giới hạn (còn phải ngồi soạn
     * đề và thử tính năng).
     */
    public function remainingToday(User $user): int|string
    {
        if ($user->isAdmin()) {
            return 'unlimited';
        }

        $limit = (int) config('aptis.vocab.daily_limit', 60);
        $used = (int) VocabLookupUsage::where('user_id', $user->id)
            ->whereDate('usage_date', today())
            ->value('count');

        return max(0, $limit - $used);
    }

    /**
     * Ghi nhận một lượt tra. `$calledApi` = false khi trúng kho đệm.
     */
    public function recordUsage(User $user, bool $calledApi): void
    {
        // Truyền Carbon chứ KHÔNG truyền chuỗi 'Y-m-d': cột có cast `date` nên
        // giá trị lưu xuống là '… 00:00:00'. Tìm bằng chuỗi ngày trần thì không
        // khớp dòng đã có, firstOrCreate insert lại và vỡ ràng buộc unique.
        $row = VocabLookupUsage::firstOrCreate(
            ['user_id' => $user->id, 'usage_date' => today()],
            ['count' => 0, 'api_calls' => 0],
        );

        DB::table('vocab_lookup_usages')
            ->where('id', $row->id)
            ->update([
                'count' => DB::raw('count + 1'),
                'api_calls' => DB::raw('api_calls + ' . ($calledApi ? 1 : 0)),
                'updated_at' => now(),
            ]);
    }
}
