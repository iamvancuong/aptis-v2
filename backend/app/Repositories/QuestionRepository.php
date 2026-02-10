<?php

namespace App\Repositories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionRepository
{
    /**
     * Get all questions with filters.
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Question::with('quiz');

        if (!empty($filters['quiz_id'])) {
            $query->where('quiz_id', $filters['quiz_id']);
        }

        if (!empty($filters['skill'])) {
            $query->where('skill', $filters['skill']);
        }

        if (!empty($filters['part'])) {
            $query->where('part', $filters['part']);
        }

        return $query->orderBy('quiz_id')
            ->orderBy('part')
            ->orderBy('order')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Find a question by ID.
     */
    public function findById(int $id): ?Question
    {
        return Question::with(['quiz', 'sets'])->find($id);
    }

    /**
     * Create a new question.
     */
    public function create(array $data): Question
    {
        return Question::create($data);
    }

    /**
     * Update an existing question.
     */
    public function update(Question $question, array $data): bool
    {
        return $question->update($data);
    }

    /**
     * Delete a question.
     */
    public function delete(Question $question): bool
    {
        return $question->delete();
    }
}
