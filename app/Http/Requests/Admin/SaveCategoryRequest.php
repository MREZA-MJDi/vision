
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access must be enforced by the existing admin middleware.
        return true;
    }

    public function rules(): array
    {
        $model = (string) config(
            'commerce-admin.models.category',
            Category::class
        );

        $table = (new $model())->getTable();

        return [
            'name' => ['required', 'string', 'max:255'],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique($table, 'slug')
                    ->ignore($this->route('category')),
            ],

            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists($table, 'id')
                    ->whereNull('deleted_at'),
            ],

            'description' => ['nullable', 'string', 'max:5000'],

            'cover_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
                'dimensions:max_width=4096,max_height=4096',
            ],

            'cover_alt' => ['nullable', 'string', 'max:255'],
            'remove_cover' => ['nullable', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:100000',
            ],

            'status' => [
                'required',
                Rule::in(['active', 'draft']),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = $this->input('parent_id');

            if (
                ! $parentId
                || $validator->errors()->has('parent_id')
            ) {
                return;
            }

            $model = (string) config(
                'commerce-admin.models.category',
                Category::class
            );

            $currentId = (int) $this->route('category');
            $visited = [];

            while ($parentId) {
                $id = (int) $parentId;

                if (
                    $id === $currentId
                    || isset($visited[$id])
                ) {
                    $validator->errors()->add(
                        'parent_id',
                        'انتخاب والد باعث ایجاد چرخه در دسته‌بندی‌ها می‌شود.'
                    );

                    return;
                }

                $visited[$id] = true;

                $parent = $model::query()
                    ->find($id, ['id', 'parent_id']);

                if (! $parent) {
                    $validator->errors()->add(
                        'parent_id',
                        'ساختار دسته‌بندی والد معتبر نیست.'
                    );

                    return;
                }

                $parentId = $parent->parent_id;
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام دسته‌بندی',
            'slug' => 'شناسه آدرس',
            'parent_id' => 'دسته‌بندی والد',
            'cover_image' => 'تصویر دسته‌بندی',
            'cover_alt' => 'متن جایگزین تصویر',
            'sort_order' => 'ترتیب نمایش',
            'description' => 'توضیحات',
            'meta_title' => 'عنوان سئو',
            'meta_description' => 'توضیحات سئو',
            'status' => 'وضعیت',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام دسته‌بندی الزامی است.',
            'slug.unique' => 'این شناسه قبلاً استفاده شده است.',
            'parent_id.exists' => 'دسته‌بندی والد معتبر نیست.',
            'cover_image.image' => 'فایل باید یک تصویر معتبر باشد.',
            'cover_image.mimes' => 'فرمت تصویر باید JPG، PNG یا WebP باشد.',
            'cover_image.max' => 'حجم تصویر نباید بیشتر از ۴ مگابایت باشد.',
            'cover_image.dimensions' => 'ابعاد تصویر بیش از حد مجاز است.',
        ];
    }
}
