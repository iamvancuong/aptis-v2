<?php

namespace App\Services\PartHandlers\Writing;

use App\Services\PartHandlers\PartHandlerInterface;

class WritingPart3Handler implements PartHandlerInterface
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
            'metadata.questions' => 'required|array|min:1',
            'metadata.questions.*.prompt' => 'required|string',
            'metadata.questions.*.word_limit.min' => 'required|integer|min:1',
            'metadata.questions.*.word_limit.max' => 'required|integer|min:1',
            'metadata.sample_answer' => 'nullable|array',
        ];
    }
}
