<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
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
            'set_id' => 'nullable|exists:sets,id', // Make nullable if not strictly enforcing for legacy data, but ideally required
            'skill' => 'required|in:reading,listening,writing',
            'part' => 'required|integer|min:1|max:4',
            'type' => 'required|string',
            'stem' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:10240',
            'speaker_audio' => 'nullable|array|max:4',
            'speaker_audio.*' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:10240',
            'point' => 'required|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'explanation' => 'nullable|string',
        ];

        // Add metadata validation based on type
        $metadataRules = $this->getMetadataRules();
        
        return array_merge($baseRules, $metadataRules);
    }

    /**
     * Get metadata validation rules based on question type.
     */
    /**
     * Get metadata validation rules based on question type.
     */
    protected function getMetadataRules(): array
    {
        // Use the PartHandlerFactory to get rules if a handler exists
        try {
            // We need to determine skill and part. 
            // In update, these might come from input OR from the existing model if not changing.
            // However, the form sends them as hidden inputs or select values.
            $skill = $this->input('skill');
            $part = $this->input('part');
            
            if ($skill && $part) {
                $factory = app(\App\Services\PartHandlerFactory::class);
                $handler = $factory->getHandler($skill, (int)$part);
                return $handler->getValidationRules();
            }
        } catch (\Exception $e) {
            // Handler not found, fallback to basic array check
        }

        return ['metadata' => 'nullable|array'];
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
