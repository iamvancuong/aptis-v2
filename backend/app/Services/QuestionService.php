<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Repositories\QuestionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class QuestionService
{
    protected QuestionRepository $questionRepository;
    protected PartHandlerFactory $partHandlerFactory;

    public function __construct(QuestionRepository $questionRepository, PartHandlerFactory $partHandlerFactory)
    {
        $this->questionRepository = $questionRepository;
        $this->partHandlerFactory = $partHandlerFactory;
    }

    /**
     * Get paginated questions with filters.
     */
    public function getQuestions(array $filters): LengthAwarePaginator
    {
        return $this->questionRepository->getAll($filters);
    }

    /**
     * Get sets belonging to a quiz.
     */
    public function getSetsByQuiz(int $quizId): Collection
    {
        return Quiz::findOrFail($quizId)->sets()->orderBy('order')->get();
    }

    /**
     * Find quiz details including sets.
     */
    public function getQuizDetails(int $quizId): Quiz
    {
        return Quiz::with('sets')->findOrFail($quizId);
    }

    /**
     * Create a question and handle attachments.
     */
    public function createQuestion(array $data, ?UploadedFile $image = null, ?int $setId = null): Question
    {
        // Resolve Part Handler
        $quiz = Quiz::findOrFail($data['quiz_id']); // Ensure we have the quiz to get skill/part if needed, or use input data
        // For now, assuming passed data contains skill and part accurately or we trust the quiz correlation.
        // Actually, the input request has skill and part. Let's use that for flexibility, or fetch from quiz if strict.
        // The user input 'skill' and 'part' are in the form data.

        try {
            $handler = $this->partHandlerFactory->getHandler($data['skill'], $data['part']);
            $data['metadata'] = $handler->formatMetadata(['metadata' => $data['metadata'] ?? []]);
        } catch (\Exception $e) {
            // Handle if no handler found - maybe just keep metadata as is or log warning
            // For this strict refactor, we might want to ensure a handler exists.
            // But for other types/parts not yet implemented, we might skip.
            // Let's assume for now we only support what we have handlers for, or fallback.
        }

        // Auto-calculate order if not provided
        if (!isset($data['order'])) {
            $maxOrder = Question::where('quiz_id', $data['quiz_id'])
                ->where('part', $data['part'])
                ->max('order');
            $data['order'] = $maxOrder !== null ? $maxOrder + 1 : 0;
        }

        // Handle image upload
        if ($image) {
            $data['image_path'] = $image->store('questions', 'public');
        }

        // Create question
        $question = $this->questionRepository->create($data);

        // Attach to Set if provided
        if ($setId) {
            $question->sets()->attach($setId);
        }

        return $question;
    }

    /**
     * Update a question and handle attachments.
     */
    public function updateQuestion(Question $question, array $data, ?UploadedFile $image = null): bool
    {
        // Resolve Part Handler to format metadata
        try {
            // Use provided skill/part or fallback to question's existing values (safer for updates)
            $skill = $data['skill'] ?? $question->quiz->skill;
            $part = $data['part'] ?? $question->quiz->part;

            $handler = $this->partHandlerFactory->getHandler($skill, $part);
            $data['metadata'] = $handler->formatMetadata(['metadata' => $data['metadata'] ?? []]);
        } catch (\Exception $e) {
            // Log warning or ignore if no handler found
        }

        // Handle image upload
        if ($image) {
            // Delete old image
            if ($question->image_path) {
                Storage::disk('public')->delete($question->image_path);
            }
            $data['image_path'] = $image->store('questions', 'public');
        }

        $updated = $this->questionRepository->update($question, $data);

        // Update Set association if provided
        if ($updated && $data['set_id']) {
            $question->sets()->sync([$data['set_id']]);
        }
        
        return $updated;
    }

    /**
     * Delete a question and its resources.
     */
    public function deleteQuestion(Question $question): bool
    {
        // Delete image
        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

        return $this->questionRepository->delete($question);
    }
}
