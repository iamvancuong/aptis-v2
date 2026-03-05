<?php

namespace App\Services\PartHandlers\Reading;

use App\Services\PartHandlers\PartHandlerInterface;

class ReadingPart1Handler implements PartHandlerInterface
{
    public function formatMetadata(array $data): array
    {
        // Ensure metadata structure is consistent
        // For Reading Part 1, we expect paragraphs, choices, and correct_answers
        // We can add extra formatting logic here if needed, e.g., trimming strings

        if (isset($data['metadata'])) {
            return $data['metadata'];
        }

        return [];
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.paragraphs' => 'required|array|size:5',
            'metadata.paragraphs.*' => 'required|string',
            'metadata.choices' => 'required|array|size:5',
            'metadata.choices.*' => 'required|array|size:3',
            'metadata.choices.*.*' => 'required|string',
            'metadata.correct_answers' => 'required|array|size:5',
            'metadata.correct_answers.*' => 'required|integer|min:0|max:2',
        ];
    }
}
