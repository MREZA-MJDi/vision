<?php

namespace App\Http\Requests\Auth;

use App\Support\IranianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if (is_string($this->input('phone'))) {
            $data['phone'] = IranianMobileNumber::normalize($this->input('phone'));
        }

        if ($this->has('remember')) {
            $data['remember'] = $this->boolean('remember');
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'bail',
                'required',
                'string',
                'regex:/^09\d{9}$/',
            ],
            'password' => [
                'bail',
                'required',
                'string',
            ],
            'remember' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'وارد کردن :attribute الزامی است.',
            'phone.regex' => 'شماره موبایل معتبر وارد کنید.',
            'password.required' => 'وارد کردن :attribute الزامی است.',
            'password.string' => ':attribute نامعتبر است.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'شماره موبایل',
            'password' => 'رمز عبور',
            'remember' => 'مرا به خاطر بسپار',
        ];
    }
}
