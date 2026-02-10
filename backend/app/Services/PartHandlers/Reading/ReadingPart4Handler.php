<?php

namespace App\Services\PartHandlers\Reading;

use App\Services\PartHandlers\PartHandlerInterface;
use Illuminate\Support\Facades\Validator;

class ReadingPart4Handler implements PartHandlerInterface
{
    public function validate(array $data): array
    {
        $rules = $this->getValidationRules();
        $validator = Validator::make($data, $rules);

        return $validator->validate();
    }

    public function getValidationRules(): array
    {
        return [
            'metadata.paragraphs' => 'required|array|min:1',
            'metadata.paragraphs.*' => 'required|string',
            'metadata.headings' => 'required|array|min:2',
            'metadata.headings.*' => 'required|string',
            'metadata.correct_answers' => 'required|array',
            'metadata.correct_answers.*' => 'required|integer|min:0',
        ];
    }

    public function formatMetadata(array $data): array
    {
        return [
            'paragraphs' => $data['metadata']['paragraphs'],
            'headings' => $data['metadata']['headings'],
            'correct_answers' => $data['metadata']['correct_answers'],
        ];
    }
}
