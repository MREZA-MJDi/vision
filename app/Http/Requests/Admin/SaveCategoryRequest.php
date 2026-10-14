<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = (string) config('commerce-admin.models.category', \App\Models\Category::class);
        $table = (new $model())->getTable();
        $id = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($table, 'slug')->ignore($id)],
            'parent_id' => ['nullable', 'integer', Rule::exists($table, 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cover_alt' => ['nullable', 'string', 'max:255'],
            'remove_cover' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(['active', 'draft'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $parentId = $this->input('parent_id');
            if (! $parentId || $validator->errors()->has('parent_id')) {
                return;
            }

            $model = (string) config('commerce-admin.models.category', \App\Models\Category::class);
            $parent = $model::query()->find((int) $parentId);
            $categoryId = $this->route('category');
            $seen = [];

            while ($parent && $parent->getKey()) {
                $currentId = (int) $parent->getKey();

                if ($categoryId && $currentId === (int) $categoryId) {
                    $validator->errors()->add('parent_id', 'یک دسته‌بندی نمی‌تواند والد خودش یا یکی از والدهایش باشد.');
                    return;
                }

                if (isset($seen[$currentId])) {
                    return;
                }

                $seen[$currentId] = true;
                $parent = $parent->parent;
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام دسته‌بندی الزامی است.',
            'slug.unique' => 'این شناسهٔ آدرس قبلاً استفاده شده است.',
            'parent_id.exists' => 'دسته‌بندی والد انتخاب‌شده معتبر نیست.',
            'cover_image.image' => 'فایل انتخاب‌شده باید تصویر معتبر باشد.',
            'cover_image.mimes' => 'فرمت تصویر باید JPG، PNG یا WebP باشد.',
            'cover_image.max' => 'حجم تصویر دسته‌بندی نباید بیشتر از ۴ مگابایت باشد.',
            'cover_alt.max' => 'متن جایگزین تصویر نباید بیشتر از ۲۵۵ نویسه باشد.',
            'status.in' => 'وضعیت انتخاب‌شده معتبر نیست.',
        ];
    }
}
