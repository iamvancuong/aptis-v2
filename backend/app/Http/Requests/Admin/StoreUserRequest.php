<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default password for students
        if ($this->role === 'user' && !$this->filled('password')) {
            $this->merge([
                'password' => '12345678',
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:user,admin',
            'status' => 'nullable|in:active,blocked',
            'expires_at' => 'nullable|date|after:today',
        ];

        // Admin users must provide custom password, user role will have default set
        if ($this->role === 'admin') {
            $rules['password'] = 'required|string|min:8';
        } else {
            // For user role, password is optional but will be defaulted to 12345678
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Admin accounts require a custom password.',
        ];
    }
}
