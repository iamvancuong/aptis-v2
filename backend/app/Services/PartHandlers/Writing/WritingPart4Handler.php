<?php

namespace App\Services\PartHandlers\Writing;

use App\Services\PartHandlers\PartHandlerInterface;

class WritingPart4Handler implements PartHandlerInterface
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
            'metadata.context' => 'required|string',
            'metadata.email.greeting' => 'nullable|string',
            'metadata.email.body' => 'required|string',
            'metadata.email.sign_off' => 'nullable|string',
            'metadata.task1.instruction' => 'required|string',
            'metadata.task1.word_limit.min' => 'required|integer|min:1',
            'metadata.task1.word_limit.max' => 'required|integer|min:1',
            'metadata.task1.sample_answer' => 'nullable|string',
            'metadata.task2.instruction' => 'required|string',
            'metadata.task2.word_limit.min' => 'required|integer|min:1',
            'metadata.task2.word_limit.max' => 'required|integer|min:1',
            'metadata.task2.sample_answer' => 'nullable|string',
        ];
    }
}
