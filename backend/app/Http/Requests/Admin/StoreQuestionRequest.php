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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'quiz_id' => 'required|exists:quizzes,id',
            'set_id' => 'required|exists:sets,id',
            'skill' => 'required|string|in:reading,listening,gramvar,writing,speaking',
            'part' => 'required|integer|min:1|max:5',
            'type' => 'required|string',
            'stem' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:10240',
            'speaker_audio' => 'nullable|array|max:4',
            'speaker_audio.*' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:10240',
            'point' => 'required|integer|min:0',
            'order' => 'nullable|integer|min:0',
            'metadata' => 'array',
        ];

        // Specific validation based on question type / part
        // We use the PartHandlerFactory to get rules if a handler exists
        try {
            $skill = $this->input('skill');
            $part = $this->input('part');
            
            if ($skill && $part) {
                // Manually instantiate factory since we can't easily inject into rules()
                // Or better, use usage of App service container
                $factory = app(\App\Services\PartHandlerFactory::class);
                $handler = $factory->getHandler($skill, (int)$part);
                $rules = array_merge($rules, $handler->getValidationRules());
            }
        } catch (\Exception $e) {
            // Handler not found, fallback or ignore
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'metadata' => $this->input('metadata', []), // Default to empty array if not present
        ]);
    }
}
