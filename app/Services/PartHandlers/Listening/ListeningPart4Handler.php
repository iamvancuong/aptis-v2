<?php

namespace App\Services\PartHandlers\Listening;

use App\Services\PartHandlers\PartHandlerInterface;

class ListeningPart4Handler implements PartHandlerInterface
{
    public function formatMetadata(array $data): array
    {
        // For Listening Part 4, we expect questions array with choices and correct_answers
        // Same structure as Part 3 but different context (monologue vs conversation)
        
        if (isset($data['metadata'])) {
            return $data['metadata'];
        }

        return [];
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.topic' => 'required|string|max:255',
            'metadata.questions' => 'required|array|size:2',
            'metadata.questions.*.question' => 'required|string',
            'metadata.questions.*.choices' => 'required|array|size:3',
            'metadata.questions.*.choices.*' => 'required|string',
            'metadata.correct_answers' => 'required|array|size:2',
            'metadata.correct_answers.*' => 'required|integer|min:0|max:2',
        ];
    }
}
