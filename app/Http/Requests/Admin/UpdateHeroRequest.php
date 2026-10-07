<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHeroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $slides = $this->input('slides');

        if (! is_array($slides)) {
            return;
        }

        foreach ($slides as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }

            foreach ([
                         'eyebrow',
                         'title',
                         'subtitle',
                         'button_text',
                         'button_url',
                     ] as $field) {
                if (
                    isset($slide[$field])
                    && is_string($slide[$field])
                ) {
                    $slides[$index][$field] = trim($slide[$field]);
                }
            }
        }

        $this->merge([
            'slides' => $slides,
        ]);
    }

    public function rules(): array
    {
        return [
            'slides' => [
                'bail',
                'required',
                'array',
                'size:4',
            ],

            'slides.*.slot' => [
                'required',
                'integer',
                'distinct',
                'between:1,4',
            ],

            'slides.*.image_file' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:3072',
                'dimensions:max_width=1920,max_height=800',
            ],

            'slides.*.mobile_image_file' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
                'dimensions:max_width=1200,max_height=1500',
            ],

            'slides.*.eyebrow' => [
                'nullable',
                'string',
                'max:120',
            ],

            'slides.*.title' => [
                'nullable',
                'string',
                'max:180',
            ],

            'slides.*.subtitle' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'slides.*.button_text' => [
                'nullable',
                'string',
                'max:80',
            ],

            'slides.*.button_url' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^(\/|https?:\/\/)/i',
            ],

            'slides.*.is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'slides' => 'اسلایدها',
            'slides.*.slot' => 'شماره اسلات',
            'slides.*.image_file' => 'تصویر دسکتاپ',
            'slides.*.mobile_image_file' => 'تصویر موبایل',
            'slides.*.eyebrow' => 'متن بالای عنوان',
            'slides.*.title' => 'عنوان',
            'slides.*.subtitle' => 'زیرعنوان',
            'slides.*.button_text' => 'متن دکمه',
            'slides.*.button_url' => 'لینک دکمه',
            'slides.*.is_active' => 'وضعیت فعال بودن',
        ];
    }
}
