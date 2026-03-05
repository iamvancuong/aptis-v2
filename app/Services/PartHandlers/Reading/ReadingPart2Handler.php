<?php

namespace App\Services\PartHandlers\Reading;

use App\Services\PartHandlers\PartHandlerInterface;

class ReadingPart2Handler implements PartHandlerInterface
{
    public function formatMetadata(array $data): array
    {
        // For Reading Part 2, we just need to store the sentences in their correct order.
        // The key is the array index.
        
        $metadata = [];

        if (isset($data['metadata']['sentences']) && is_array($data['metadata']['sentences'])) {
            // Ensure we only save the values, indexed 0-4
            $metadata['sentences'] = array_values($data['metadata']['sentences']);
        }

        return $metadata;
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.sentences' => 'required|array|size:6',
            'metadata.sentences.0' => 'nullable|string', // First sentence (fixed) is optional
            'metadata.sentences.1' => 'required|string',
            'metadata.sentences.2' => 'required|string',
            'metadata.sentences.3' => 'required|string',
            'metadata.sentences.4' => 'required|string',
            'metadata.sentences.5' => 'required|string',
        ];
    }
}
