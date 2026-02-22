<?php

namespace App\Services\PartHandlers\Writing;

use App\Services\PartHandlers\PartHandlerInterface;

class WritingPart1Handler implements PartHandlerInterface
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
            'metadata.fields' => 'required|array|min:1',
            'metadata.fields.*.label' => 'required|string',
            'metadata.fields.*.placeholder' => 'nullable|string',
            'metadata.instructions' => 'nullable|string',
            'metadata.sample_answer' => 'nullable|array',
        ];
    }
}
