<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access must be enforced by the existing admin middleware.
        return true;
    }

    public function rules(): array
    {
        $model = (string) config(
            'commerce-admin.models.brand',
            Brand::class
        );

        return [
            'name' => ['required', 'string', 'max:255'],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique((new $model())->getTable(), 'slug')
                    ->ignore($this->route('brand')),
            ],

            'description' => ['nullable', 'string', 'max:5000'],

            'logo_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
                'dimensions:max_width=4096,max_height=4096',
            ],

            'logo_alt' => ['nullable', 'string', 'max:255'],
            'remove_logo' => ['nullable', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            'status' => [
                'required',
                Rule::in(['active', 'draft']),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام برند',
            'slug' => 'شناسه آدرس',
            'description' => 'توضیحات',
            'logo_image' => 'تصویر برند',
            'logo_alt' => 'متن جایگزین تصویر',
            'meta_title' => 'عنوان سئو',
            'meta_description' => 'توضیحات سئو',
            'status' => 'وضعیت',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام برند الزامی است.',
            'slug.unique' => 'این شناسه قبلاً استفاده شده است.',
            'logo_image.image' => 'فایل باید یک تصویر معتبر باشد.',
            'logo_image.mimes' => 'فرمت تصویر باید JPG، PNG یا WebP باشد.',
            'logo_image.max' => 'حجم تصویر نباید بیشتر از ۱۰ مگابایت باشد.',
            'logo_image.dimensions' => 'ابعاد تصویر بیش از حد مجاز است.',
        ];
    }
}
