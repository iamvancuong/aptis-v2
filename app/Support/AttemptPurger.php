<?php

namespace App\Support;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\MockTest;
use App\Models\WritingReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Xoá hẳn một loạt bài làm: nhận xét giáo viên, câu trả lời, bản ghi attempt,
 * lượt thi thử chỉ còn là vỏ rỗng, rồi file ghi âm trên đĩa.
 *
 * Dùng cho nút "Xoá" của admin ở Lịch sử Writing/Speaking (cô minh hoạ đề mỗi
 * tuần nên bài dồn lại). Lượt thi thử nằm trong một Full Test thì GIỮ — xoá đi
 * sẽ làm hỏng bảng điểm Full Test.
 */
class AttemptPurger
{
    /** @param iterable<int> $attemptIds  @return int số bài đã xoá */
    public static function purge(iterable $attemptIds): int
    {
        $ids = collect($attemptIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        $audioPaths = [];

        $deleted = DB::transaction(function () use ($ids, &$audioPaths) {
            $answers = AttemptAnswer::whereIn('attempt_id', $ids)->get(['id', 'answer']);
            foreach ($answers as $answer) {
                array_push($audioPaths, ...SpeakingAudio::pathsOf($answer->answer));
            }

            $mockTestIds = Attempt::whereIn('id', $ids)->whereNotNull('mock_test_id')->pluck('mock_test_id')->unique();

            WritingReview::whereIn('attempt_answer_id', $answers->pluck('id'))->delete();
            AttemptAnswer::whereIn('attempt_id', $ids)->delete();
            $deleted = Attempt::whereIn('id', $ids)->delete();

            if ($mockTestIds->isNotEmpty()) {
                MockTest::whereIn('id', $mockTestIds)
                    ->whereNull('full_test_id')
                    ->whereNotIn('id', Attempt::whereNotNull('mock_test_id')->select('mock_test_id'))
                    ->delete();
            }

            return $deleted;
        });

        // Xoá file SAU khi commit: DB rollback thì file vẫn còn khớp với dữ liệu.
        $disk = Storage::disk('public');
        foreach (array_unique($audioPaths) as $path) {
            $disk->delete($path);
        }

        return $deleted;
    }
}
