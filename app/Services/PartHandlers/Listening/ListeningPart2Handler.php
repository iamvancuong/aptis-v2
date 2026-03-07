<?php

namespace App\Services\PartHandlers\Listening;

use App\Services\PartHandlers\PartHandlerInterface;

class ListeningPart2Handler implements PartHandlerInterface
{
    public function formatMetadata(array $data): array
    {
        // For Listening Part 2, we expect items, choices, and correct_answers
        
        if (isset($data['metadata'])) {
            return $data['metadata'];
        }

        return [];
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.items' => 'required|array|min:1',
            'metadata.items.*' => 'required|string',
            'metadata.choices' => 'required|array|min:1',
            'metadata.choices.*' => 'required|string',
            'metadata.correct_answers' => 'required|array',
            'metadata.correct_answers.*' => 'required|integer|min:0',
            'metadata.descriptions' => 'nullable|array',
            'metadata.descriptions.*' => 'nullable|string',
            'metadata.audio_files' => 'nullable|array',
            'metadata.audio_files.*' => 'nullable|string',
        ];
    }
}
