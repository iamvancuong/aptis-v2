<?php

namespace App\Services\PartHandlers\Writing;

use App\Services\PartHandlers\PartHandlerInterface;

class WritingPart2Handler implements PartHandlerInterface
{
    public function formatMetadata(array $data): array
    {
        if (isset($data['metadata'])) {
            return $data['metadata'];
        }

        return [];
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.scenario' => 'required|string',
            'metadata.word_limit.min' => 'required|integer|min:1',
            'metadata.word_limit.max' => 'required|integer|min:1',
            'metadata.hints' => 'nullable|string',
            'metadata.sample_answer' => 'nullable|string',
        ];
    }
}
