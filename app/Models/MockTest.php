<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockTest extends Model
{
    protected $fillable = [
        'user_id',
        'full_test_id',
        'skill',
        'sections',
        'duration_minutes',
        'started_at',
        'finished_at',
        'duration_seconds',
        'score',
        'section_scores',
        'status',
    ];

    protected $casts = [
        'sections' => 'array',
        'section_scores' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullTest(): BelongsTo
    {
        return $this->belongsTo(FullTest::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    /**
     * Học viên có đang trong giờ thi một bài thi thử chứa bộ đề này không.
     *
     * Dùng để khoá API xem đáp án của chế độ luyện tập (`practice.check`): thi
     * thử và luyện tập dùng chung bộ đề, nên nếu không khoá thì trong lúc thi
     * chỉ cần gọi `/practice/{set}/check` từ console là lấy được đáp án cả đề.
     *
     * "Đang trong giờ thi" = status in_progress VÀ chưa quá thời lượng + 15 phút
     * dự phòng — bài bỏ dở cả tuần trước không được khoá luyện tập mãi mãi.
     */
    public static function userIsTakingSet(int $userId, int $setId): bool
    {
        return static::where('user_id', $userId)
            ->where('status', 'in_progress')
            ->where('started_at', '>=', now()->subMinutes(24 * 60))
            ->get(['id', 'sections', 'started_at', 'duration_minutes'])
            ->contains(function (self $m) use ($setId) {
                $deadline = $m->started_at?->copy()->addMinutes((int) $m->duration_minutes + 15);
                if (! $deadline || $deadline->isPast()) {
                    return false;
                }

                foreach ((array) $m->sections as $section) {
                    $ids = $section['set_ids'] ?? (isset($section['set_id']) ? [$section['set_id']] : []);
                    if (in_array($setId, array_map('intval', (array) $ids), true)) {
                        return true;
                    }
                }

                return false;
            });
    }

    /**
     * Get the sets for each section, eager loaded with questions.
     */
    public function getSectionsWithSets()
    {
        $sections = $this->sections;
        $allSetIds = [];
        foreach ($sections as $section) {
            if (isset($section['set_ids'])) {
                $allSetIds = array_merge($allSetIds, $section['set_ids']);
            } elseif (isset($section['set_id'])) {
                $allSetIds[] = $section['set_id'];
            }
        }
        $setIds = collect($allSetIds)->unique()->values();

        $sets = Set::with(['questions' => function ($q) {
            $q->orderBy('order');
        }, 'quiz'])->whereIn('id', $setIds)->get()->keyBy('id');

        return collect($sections)->map(function ($section, $index) use ($sets) {
            $virtSet = null;
            if (isset($section['set_ids'])) {
                $combinedQuestions = collect();
                foreach ($section['set_ids'] as $sid) {
                    if ($s = $sets->get($sid)) {
                        $combinedQuestions = $combinedQuestions->concat($s->questions);
                    }
                }
                
                // Clone the first set's object as a template for the virtual set
                $firstSet = $sets->get($section['set_ids'][0]);
                if ($firstSet) {
                    $virtSet = clone $firstSet;
                    $virtSet->setRelation('questions', $combinedQuestions);
                }
            } else {
                $virtSet = $sets->get($section['set_id']);
            }

            // Apply global part-specific question limit if exists.
            // IMPORTANT: sort DETERMINISTICALLY by (mockTestId, questionId) so that show(), submit(),
            // and result() always pick the same questions for the same test instance.
            // Using shuffle() was bugged — each call returned different questions → grading mismatch.
            $limit = config("aptis.exam_part_counts.{$this->skill}.{$section['part']}");
            if ($virtSet && $limit && $virtSet->questions->count() > $limit) {
                $mockId = $this->id;
                $virtSet->setRelation('questions',
                    $virtSet->questions
                        ->sortBy(fn($q) => crc32($mockId . '_' . $q->id))
                        ->take($limit)
                        ->values()
                );
            }

            return [
                'index' => $index,
                'part' => $section['part'],
                'set_id' => $section['set_id'] ?? ($section['set_ids'][0] ?? null),
                'set' => $virtSet,
            ];
        });
    }
}
