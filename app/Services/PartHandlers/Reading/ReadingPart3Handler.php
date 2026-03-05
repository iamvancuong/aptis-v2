<?php

namespace App\Services\PartHandlers\Reading;

use App\Services\PartHandlers\PartHandlerInterface;

class ReadingPart3Handler implements PartHandlerInterface
{
    public function getValidationRules(): array
    {
        return [
            'metadata.options' => 'required|array|min:4',
            'metadata.options.*' => 'required|string',
            'metadata.questions' => 'required|array|min:1',
            'metadata.questions.*' => 'required|string',
            'metadata.correct_answers' => 'required|array',
            'metadata.correct_answers.*' => 'required|integer|min:0',
        ];
    }

    public function formatMetadata(array $data): array
    {
        return [
            'options' => $data['metadata']['options'] ?? [],
            'questions' => $data['metadata']['questions'] ?? [],
            'correct_answers' => $data['metadata']['correct_answers'] ?? [],
        ];
    }
}
