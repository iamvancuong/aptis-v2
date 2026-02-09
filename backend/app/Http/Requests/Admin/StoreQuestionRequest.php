<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $baseRules = [
            'quiz_id' => 'required|exists:quizzes,id',
            'skill' => 'required|in:reading,listening,writing',
            'part' => 'required|integer|min:1|max:4',
            'type' => 'required|string',
            'stem' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'point' => 'required|integer|min:1',
            'order' => 'required|integer|min:0',
            'explanation' => 'nullable|string',
        ];

        // Add metadata validation based on type
        $metadataRules = $this->getMetadataRules();
        
        return array_merge($baseRules, $metadataRules);
    }

    /**
     * Get metadata validation rules based on question type.
     */
    protected function getMetadataRules(): array
    {
        $type = $this->input('type');

        return match($type) {
            'fill_in_blanks_mc' => [
                'metadata' => 'required|array',
                'metadata.paragraphs' => 'required|array|size:5',
                'metadata.paragraphs.*' => 'required|string',
                'metadata.blank_keys' => 'required|array|size:5',
                'metadata.choices' => 'required|array|size:5',
                'metadata.choices.*' => 'required|array|size:3',
                'metadata.correct_answers' => 'required|array|size:5',
            ],
            'sentence_ordering' => [
                'metadata' => 'required|array',
                'metadata.sentences' => 'required|array|size:5',
                'metadata.sentences.*' => 'required|string',
                'metadata.correct_order' => 'required|array|size:5',
            ],
            'text_question_match' => [
                'metadata' => 'required|array',
                'metadata.items' => 'required|array|size:4',
                'metadata.items.*.text' => 'required|string',
                'metadata.items.*.label' => 'required|string|in:A,B,C,D',
                'metadata.options' => 'required|array|size:7',
                'metadata.answers' => 'required|array',
            ],
            'paragraph_heading_match' => [
                'metadata' => 'required|array',
                'metadata.paragraphs' => 'required|array|size:7',
                'metadata.paragraphs.*' => 'required|string',
                'metadata.options' => 'required|array|size:7',
                'metadata.correct' => 'required|array|size:7',
            ],
            default => [
                'metadata' => 'required|array',
            ],
        };
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Encode metadata if it's an array
        if ($this->has('metadata') && is_array($this->metadata)) {
            $this->merge([
                'metadata' => $this->metadata
            ]);
        }
    }
}
