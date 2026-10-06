<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (bool) auth()->user()->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->input('parent_id') ?: null,
            'name' => $this->trimValue($this->input('name')),
            'slug' => $this->trimValue($this->input('slug')),
            'description' => $this->trimValue($this->input('description')),
            'meta_title' => $this->trimValue($this->input('meta_title')),
            'meta_description' => $this->trimValue($this->input('meta_description')),
            'sort_order' => $this->input('sort_order') === ''
                ? null
                : $this->input('sort_order'),
        ]);
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at'),
            ],

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
                Rule::unique('categories', 'slug'),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],

            'image_file' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
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
            'parent_id' => 'دسته‌بندی والد',
            'name' => 'نام دسته‌بندی',
            'slug' => 'شناسه دسته‌بندی',
            'description' => 'توضیحات',
            'image_file' => 'تصویر دسته‌بندی',
            'sort_order' => 'ترتیب نمایش',
            'is_active' => 'وضعیت دسته‌بندی',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'دسته‌بندی والد انتخاب‌شده معتبر نیست.',

            'name.required' => 'نام دسته‌بندی را وارد کنید.',
            'name.max' => 'نام دسته‌بندی نمی‌تواند بیشتر از ۱۲۰ کاراکتر باشد.',

            'slug.unique' => 'این شناسه دسته‌بندی قبلاً استفاده شده است.',
            'slug.max' => 'شناسه دسته‌بندی بیش از حد طولانی است.',

            'description.max' => 'توضیحات دسته‌بندی بیش از حد طولانی است.',

            'image_file.image' => 'فایل انتخاب‌شده باید یک تصویر باشد.',
            'image_file.mimes' => 'فرمت تصویر باید JPG، PNG یا WebP باشد.',
            'image_file.max' => 'حجم تصویر نمی‌تواند بیشتر از ۲ مگابایت باشد.',

            'sort_order.integer' => 'ترتیب نمایش باید یک عدد باشد.',
            'sort_order.min' => 'ترتیب نمایش نمی‌تواند منفی باشد.',
            'sort_order.max' => 'ترتیب نمایش نمی‌تواند بیشتر از ۹۹۹۹ باشد.',
        ];
    }

    private function trimValue(mixed $value): mixed
    {
        return is_string($value)
            ? trim($value)
            : $value;
    }
}
