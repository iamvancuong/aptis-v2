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
        // Màn tạo tay chỉ tạo học viên (role user). Ép cứng ở đây để dù form có
        // bị sửa gửi lên role khác thì cũng không tạo được admin bằng đường này.
        $this->merge(['role' => 'user']);

        // Không có ô nhập mật khẩu ở form — mọi tài khoản tạo tay đều dùng mật
        // khẩu mặc định 12345678 (học viên đổi ở lần đăng nhập đầu).
        $this->merge(['password' => '12345678']);

        // Convert expires_at to end of day if present
        if ($this->filled('expires_at')) {
            $this->merge([
                'expires_at' => \Carbon\Carbon::parse($this->expires_at)->endOfDay()->format('Y-m-d H:i:s'),
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:user',
            // Luôn được gán 12345678 ở prepareForValidation (không có ô nhập).
            'password' => 'required|string|min:8',
            'status' => 'nullable|in:active,blocked',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Vui lòng nhập mật khẩu cho tài khoản (tối thiểu 8 ký tự).',
        ];
    }
}
