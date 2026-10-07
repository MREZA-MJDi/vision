<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (bool) auth()->user()->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->trimValue($this->input('name')),
            'slug' => $this->trimValue($this->input('slug')),
            'description' => $this->trimValue($this->input('description')),
            'meta_title' => $this->trimValue($this->input('meta_title')),
            'meta_description' => $this->trimValue($this->input('meta_description')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:120',
            ],

            'slug' => [
                'bail',
                'nullable',
                'string',
                'max:160',
                Rule::unique('brands', 'slug'),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],

            'logo_file' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام برند',
            'slug' => 'شناسه برند',
            'description' => 'توضیحات',
            'logo_file' => 'لوگوی برند',
            'is_active' => 'وضعیت برند',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام برند را وارد کنید.',
            'name.max' => 'نام برند نمی‌تواند بیشتر از ۱۲۰ کاراکتر باشد.',

            'slug.unique' => 'این شناسه برند قبلاً استفاده شده است.',
            'slug.max' => 'شناسه برند بیش از حد طولانی است.',

            'description.max' => 'توضیحات برند بیش از حد طولانی است.',

            'logo_file.image' => 'فایل انتخاب‌شده باید یک تصویر باشد.',
            'logo_file.mimes' => 'فرمت لوگو باید JPG، PNG یا WebP باشد.',
            'logo_file.max' => 'حجم لوگو نمی‌تواند بیشتر از ۲ مگابایت باشد.',
        ];
    }

    private function trimValue(mixed $value): mixed
    {
        return is_string($value)
            ? trim($value)
            : $value;
    }
}
