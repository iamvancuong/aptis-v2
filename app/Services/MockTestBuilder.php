<?php

namespace App\Services;

use App\Models\Set;

/**
 * Bốc đề cho một bài thi thử: trả về mảng `sections` lưu vào `mock_tests`.
 *
 * Tách ra khỏi MockTestController để thi thử từng kỹ năng và Full Test dùng
 * chung một luật bốc đề.
 */
class MockTestBuilder
{
    /** Kỹ năng dùng MỘT bộ đề liền mạch cho mọi phần (bối cảnh xuyên suốt). */
    public const COHESIVE_SKILLS = ['writing', 'speaking', 'grammar'];

    /**
     * @param  Set|null  $cohesiveSet  bộ đề học viên chọn (Writing/Speaking ở trang
     *                                 thi thử); null = bốc ngẫu nhiên.
     * @return array<int, array>|string  sections, hoặc thông báo lỗi nếu thiếu đề
     */
    public function sectionsFor(string $skill, ?Set $cohesiveSet = null): array|string
    {
        $sectionConfig = config("aptis.exam_sections.{$skill}", []);
        $sections = [];

        if (in_array($skill, self::COHESIVE_SKILLS, true)) {
            $set = $cohesiveSet ?? $this->randomCohesiveSet($skill);

            if (! $set) {
                return 'Không đủ bộ đề hoàn chỉnh. Vui lòng liên hệ admin.';
            }

            foreach ($sectionConfig as $part) {
                $sections[] = ['part' => $part, 'set_id' => $set->id];
            }

            return $sections;
        }

        // Reading / Listening: mỗi phần bốc ngẫu nhiên một bộ (Reading Part 2 bốc 2 bộ).
        // `exam_part_counts` quy định số CÂU mỗi phần; getSectionsWithSets() tự cắt.
        $usedSetIds = [];
        foreach ($sectionConfig as $part) {
            $setCount = ($skill === 'reading' && $part == 2) ? 2 : 1;

            // Số câu mỗi bộ phải có để phần thi đủ câu (Listening Part 1 cần 13).
            // Bản cũ bốc ngẫu nhiên bất kể số câu: kho có bộ 195 câu lẫn bộ 1 câu
            // (bộ tạo thử / nhập dở) → bài thi Part 1 chỉ còn 1 câu thay vì 13.
            $perSet = (int) ceil(config("aptis.exam_part_counts.{$skill}.{$part}", 1) / $setCount);

            $candidates = Set::whereHas('quiz', function ($q) use ($skill, $part) {
                $q->where('skill', $skill)->where('part', $part);
            })
                ->where('is_public', true)
                ->whereNotIn('id', $usedSetIds)
                ->withCount('questions')
                ->get();

            // Ưu tiên bộ đủ câu (ngẫu nhiên giữa chúng); thiếu thì lấy bộ nhiều câu nhất.
            [$enough, $short] = $candidates->partition(fn ($s) => $s->questions_count >= $perSet);
            $sets = $enough->shuffle()
                ->concat($short->sortByDesc('questions_count'))
                ->filter(fn ($s) => $s->questions_count > 0)
                ->take($setCount)
                ->values();

            if ($sets->isEmpty()) {
                return "Không đủ bộ đề cho Part {$part}. Vui lòng liên hệ admin.";
            }

            $usedSetIds = array_merge($usedSetIds, $sets->pluck('id')->toArray());

            $sections[] = $setCount > 1
                ? ['part' => $part, 'set_ids' => $sets->pluck('id')->toArray()]
                : ['part' => $part, 'set_id' => $sets->first()->id];
        }

        return $sections;
    }

    /**
     * Bộ đề ngẫu nhiên cho kỹ năng dùng một bộ liền mạch.
     *
     * Writing/Speaking: bộ đề nằm ở quiz Part 1 (như trang thi thử đang lọc).
     * Grammar: quiz Grammar & Vocabulary (part = 0), mỗi bộ đủ 2 phần.
     * Chỉ lấy bộ có câu hỏi của đủ mọi phần cần thi.
     */
    public function randomCohesiveSet(string $skill): ?Set
    {
        $parts = config("aptis.exam_sections.{$skill}", []);

        $query = Set::whereHas('quiz', function ($q) use ($skill) {
            $q->where('skill', $skill);
            if ($skill !== 'grammar') {
                $q->where('part', 1);
            }
        })->where('is_public', true);

        foreach ($parts as $part) {
            $query->whereHas('questions', fn ($q) => $q->where('part', $part));
        }

        return $query->inRandomOrder()->first();
    }
}
