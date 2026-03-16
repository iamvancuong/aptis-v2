<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrammarSetController extends Controller
{
    // Default APTIS Grammar and Vocab config
    private array $defaultConfig = [
        'mcq_count'            => 25,
        'vocab_count'          => 5,
        'vocab_types_required' => [
            'synonym_match',
            'definition_match',
            'sentence_completion',
            'synonym_match',
            'collocation_match',
        ],
    ];

    public function index()
    {
        $grammarQuiz = Quiz::where('skill', 'grammar')->first();
        $sets = $grammarQuiz
            ? Set::where('quiz_id', $grammarQuiz->id)->latest()->paginate(15)
            : Set::whereRaw('1=0')->paginate(15); // empty paginator

        return view('admin.grammar-sets.index', compact('sets'));
    }

    public function create()
    {
        $config = $this->defaultConfig;
        return view('admin.grammar-sets.create', compact('config'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string|max:255',
            'questions' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($request) {
            // Get or create the grammar Quiz
            $quiz = Quiz::firstOrCreate(
                ['skill' => 'grammar'],
                [
                    'title'            => 'Grammar and Vocabulary',
                    'description'      => 'APTIS Grammar & Vocabulary Test',
                    'part'             => 0,  // Grammar không chia part như Reading/Listening
                    'duration_minutes' => 25,
                ]
            );

            $config = $this->defaultConfig;

            // Create the Set (default draft)
            $set = Set::create([
                'quiz_id'  => $quiz->id,
                'title'    => $request->title,
                'status'   => 'draft',
                'metadata' => ['grammar_config' => $config],
            ]);

            // Persist questions if provided
            $this->syncQuestions($set, $request->questions ?? [], $config);

            return redirect()->route('admin.grammar-sets.edit', $set)
                ->with('success', 'Bộ đề được tạo! Nhập câu hỏi và lưu nháp, sau đó Publish khi hoàn chỉnh.');
        });
    }

    public function edit(Set $grammarSet)
    {
        $grammarSet->load(['questions' => fn($q) => $q->orderBy('order')]);
        $config   = $grammarSet->metadata['grammar_config'] ?? $this->defaultConfig;
        
        $questions = $grammarSet->questions->keyBy('order'); 
        
        // If draft exists, override the loaded DB questions with draft data
        if (isset($grammarSet->metadata['draft']['questions'])) {
            $draftArray = $grammarSet->metadata['draft']['questions'];
            foreach ($draftArray as $order => $data) {
                // Fake a model object for view compatibility
                $fakeQ = new Question([
                    'order'    => $order,
                    'stem'     => $data['stem'] ?? '',
                    'explanation' => $data['explanation'] ?? '',
                    'metadata' => $data['metadata'] ?? [],
                ]);
                $questions[$order] = $fakeQ;
            }
            // Temporarily update title for the view
            $grammarSet->title = $grammarSet->metadata['draft']['title'] ?? $grammarSet->title;
        }

        return view('admin.grammar-sets.edit', compact('grammarSet', 'config', 'questions'));
    }

    public function update(Request $request, Set $grammarSet)
    {
        $request->validate([
            'title'     => 'required|string|max:255',
            'questions' => 'nullable|array',
            'action'    => 'required|in:draft,publish',
        ]);

        $config = $grammarSet->metadata['grammar_config'] ?? $this->defaultConfig;

        if ($request->action === 'publish') {
            $errors = $this->validateForPublish($request->questions ?? [], $config);
            if (!empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }
        }

        DB::transaction(function () use ($request, $grammarSet, $config) {
            $this->syncQuestions($grammarSet, $request->questions ?? [], $config);

            // Clear the draft after a real save so edit() shows fresh DB data
            $meta = $grammarSet->metadata ?? [];
            unset($meta['draft']);
            $grammarSet->update([
                'title'    => $request->title,
                'status'   => $request->action === 'publish' ? 'published' : 'draft',
                'metadata' => $meta,
            ]);
        });

        $msg = $request->action === 'publish'
            ? 'Bộ đề đã được Publish! Học sinh có thể truy cập.'
            : 'Đã lưu nháp thành công.';

        return redirect()->route('admin.grammar-sets.edit', $grammarSet)->with('success', $msg);
    }

    /**
     * AJAX endpoint: Save draft without validation.
     */
    public function saveDraft(Request $request, Set $grammarSet)
    {
        $config = $grammarSet->metadata['grammar_config'] ?? $this->defaultConfig;
        
        DB::transaction(function () use ($request, $grammarSet, $config) {
            $formattedQuestions = [];
            foreach (($request->questions ?? []) as $orderIndex => $qData) {
                $orderIndex = (int) $orderIndex;
                $stem       = trim($qData['stem'] ?? '');
                $explanation = trim($qData['explanation'] ?? '');
                $rawMeta    = $qData['metadata'] ?? [];
                
                if ($stem === '' && empty($rawMeta) && $explanation === '') continue;
                
                $part     = ($orderIndex <= $config['mcq_count']) ? 1 : 2;
                $vocabPos = $orderIndex - $config['mcq_count'];
                $type     = ($part === 1) ? 'mcq3' : ($config['vocab_types_required'][$vocabPos - 1] ?? 'synonym_match');
                
                $metadata = $this->buildQuestionMetadata($part, $type, $rawMeta);
                
                $formattedQuestions[$orderIndex] = [
                    'stem' => $stem,
                    'explanation' => $explanation,
                    'metadata' => $metadata
                ];
            }

            $meta = $grammarSet->metadata ?? [];
            $meta['draft'] = [
                'title' => $request->title ?? $grammarSet->title,
                'questions' => $formattedQuestions
            ];

            $grammarSet->update([
                'title' => $request->title ?? $grammarSet->title,
                'metadata' => $meta
            ]);
        });

        return response()->json(['saved_at' => now('Asia/Ho_Chi_Minh')->format('H:i:s')]);
    }

    public function destroy(Set $grammarSet)
    {
        $grammarSet->questions()->detach();
        Question::whereIn('id', $grammarSet->questions->pluck('id'))->delete();
        $grammarSet->delete();

        return redirect()->route('admin.grammar-sets.index')->with('success', 'Đã xóa bộ đề.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function syncQuestions(Set $set, array $rawQuestions, array $config): void
    {
        // rawQuestions keyed by "order" (1..30) from the form
        $existingByOrder = $set->questions()->orderBy('order')->get()->keyBy('order');
        $attachedIds     = $set->questions()->pluck('question_id')->toArray();

        foreach ($rawQuestions as $orderIndex => $qData) {
            $orderIndex = (int) $orderIndex;
            $stem       = trim($qData['stem'] ?? '');
            $explanation = trim($qData['explanation'] ?? '');
            $rawMeta    = $qData['metadata'] ?? [];

            if ($stem === '' && empty($rawMeta) && $explanation === '') {
                continue; // Skip empty slots
            }

            $part     = ($orderIndex <= $config['mcq_count']) ? 1 : 2;
            $vocabPos = $orderIndex - $config['mcq_count']; // 1..5 for vocab
            $type     = ($part === 1)
                ? 'mcq3'
                : ($config['vocab_types_required'][$vocabPos - 1] ?? 'synonym_match');

            // ── Build metadata ─────────────────────────────────────────────
            $metadata = $this->buildQuestionMetadata($part, $type, $rawMeta);

            // ── Upsert ─────────────────────────────────────────────────────
            $questionData = [
                'quiz_id'  => $set->quiz_id,
                'skill'    => 'grammar',
                'part'     => $part,
                'type'     => $type,
                'stem'     => $stem ?: $type,
                'explanation' => $explanation,
                'point'    => ($part === 1) ? 1 : 5,
                'order'    => $orderIndex,
                'metadata' => $metadata,
            ];

            if ($existingByOrder->has($orderIndex)) {
                $existingByOrder[$orderIndex]->update($questionData);
            } else {
                $question = Question::create($questionData);
                $set->questions()->attach($question->id);
            }
        }
    }

    /**
     * Validate that a set is ready to be published: correct question counts
     * and vocab types in the correct order.
     */
    private function validateForPublish(array $rawQuestions, array $config): array
    {
        $errors   = [];
        $mcqCount = 0;

        // Count MCQ
        for ($i = 1; $i <= $config['mcq_count']; $i++) {
            $q = $rawQuestions[$i] ?? [];
            if (!empty($q['stem']) && !empty($q['metadata']['correct_option'])) {
                $mcqCount++;
            } else {
                $errors[] = "Câu Grammar #{$i}: chưa nhập đề hoặc chưa chọn đáp án đúng.";
            }
        }

        // Count and validate Vocab
        $requiredTypes = $config['vocab_types_required'];
        for ($i = 0; $i < $config['vocab_count']; $i++) {
            $orderIndex = $config['mcq_count'] + $i + 1; // 26..30
            $q          = $rawQuestions[$orderIndex] ?? [];
            $required   = $requiredTypes[$i] ?? null;
            $actual     = $q['metadata']['vocab_type'] ?? null;

            if (empty($q['metadata']['pairs']) || empty($q['metadata']['correct_answers'])) {
                $errors[] = "Câu Vocab #{$orderIndex}: chưa nhập đủ dữ liệu.";
            } elseif ($required && $actual !== $required) {
                $errors[] = "Câu #{$orderIndex} phải là dạng '{$required}', nhưng bạn đã nhập '{$actual}'.";
            }
        }

        return $errors;
    }

    private function buildQuestionMetadata(int $part, string $type, array $rawMeta): array
    {
        if ($part === 1) {
            // MCQ: convert flat options[A/B/C] → structured array
            $rawOptions = $rawMeta['options'] ?? [];
            return [
                'options' => [
                    ['id' => 'A', 'text' => $rawOptions['A'] ?? ''],
                    ['id' => 'B', 'text' => $rawOptions['B'] ?? ''],
                    ['id' => 'C', 'text' => $rawOptions['C'] ?? ''],
                ],
                'correct_option' => $rawMeta['correct_option'] ?? '',
            ];
        }

        // Vocab: parse dropdown_pool_raw → array, convert pairs indexed array
        $poolRaw        = $rawMeta['dropdown_pool_raw'] ?? '';
        $dropdownPool   = array_values(array_filter(array_map('trim', explode(',', $poolRaw))));
        $rawPairs       = array_values($rawMeta['pairs'] ?? []);
        $correctAnswers = [];
        
        foreach (($rawMeta['correct_answers'] ?? []) as $k => $v) {
            $correctAnswers[(string)$k] = trim($v);
        }

        $metadata = [
            'vocab_type'      => $rawMeta['vocab_type'] ?? $type,
            'instruction'     => $rawMeta['instruction'] ?? '',
            'pairs'           => $rawPairs,
            'dropdown_pool'   => $dropdownPool,
            'correct_answers' => $correctAnswers,
        ];

        if (isset($rawMeta['example'])) {
            $metadata['example']   = $rawMeta['example'];
            $metadata['connector'] = $rawMeta['connector'] ?? '=';
        }

        return $metadata;
    }
}
