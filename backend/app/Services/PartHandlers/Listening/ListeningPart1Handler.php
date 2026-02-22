<?php

namespace App\Services\PartHandlers\Listening;

use App\Services\PartHandlers\PartHandlerInterface;

class ListeningPart1Handler implements PartHandlerInterface
{
    public function formatMetadata(array $data): array
    {
        // Ensure metadata structure is consistent
        // For Listening Part 1, we expect choices and correct_answer
        
        if (isset($data['metadata'])) {
            return $data['metadata'];
        }

        return [];
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.choices' => 'required|array|size:3',
            'metadata.choices.*' => 'required|string',
            'metadata.correct_answer' => 'required|integer|min:0|max:2',
        ];
    }
}
