<?php

namespace App\Services\PartHandlers\Listening;

use App\Services\PartHandlers\PartHandlerInterface;

class ListeningPart3Handler implements PartHandlerInterface
{
    public function formatMetadata(array $data): array
    {
        // For Listening Part 3, we expect questions array with choices and correct_answers
        
        if (isset($data['metadata'])) {
            return $data['metadata'];
        }

        return [];
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.topic' => 'required|string|max:255',
            'metadata.shared_choices' => 'required|array|min:2',
            'metadata.shared_choices.*' => 'required|string',
            'metadata.statements' => 'required|array|min:1',
            'metadata.statements.*' => 'required|string',
            'metadata.correct_answers' => 'required|array',
            'metadata.correct_answers.*' => 'required|integer|min:0',
        ];
    }
}
