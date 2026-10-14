<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\NumericInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $variants = (array) $this->input('variants', []);

        foreach ($variants as $index => $variant) {
            if (! is_array($variant)) {
                continue;
            }

            foreach (['price', 'sale_price'] as $field) {
                if (array_key_exists($field, $variant)) {
                    $variants[$index][$field] = NumericInput::normalize($variant[$field]);
                }
            }
        }

        $this->merge(['variants' => $variants]);
    }

    public function rules(): array
    {
        $productModel = (string) config('commerce-admin.models.product', Product::class);
        $categoryModel = (string) config('commerce-admin.models.category', Category::class);
        $brandModel = (string) config('commerce-admin.models.brand', Brand::class);
        $productTable = (new $productModel())->getTable();
        $categoryTable = (new $categoryModel())->getTable();
        $brandTable = (new $brandModel())->getTable();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($productTable, 'slug')->ignore($this->route('product'))],
            'category_id' => ['nullable', 'integer', Rule::exists($categoryTable, 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists($brandTable, 'id')],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_hero' => ['sometimes', 'boolean'],
            'is_taxable' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'draft'])],
            'variants' => ['required', 'array', 'min:1', 'max:30'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:80'],
            'variants.*.color_code' => ['nullable', 'string', 'max:20'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['required', 'boolean'],
            'images' => ['sometimes', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ((array) $this->input('variants', []) as $index => $variant) {
                if (! is_array($variant) || ! isset($variant['price']) || ! is_numeric($variant['price'])) {
                    continue;
                }

                $salePrice = $variant['sale_price'] ?? null;
                if ($salePrice !== null && $salePrice !== '' && is_numeric($salePrice)
                    && (float) $salePrice >= (float) $variant['price']) {
                    $validator->errors()->add(
                        "variants.$index.sale_price",
                        'قیمت با تخفیف باید کمتر از قیمت اصلی باشد.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام محصول الزامی است.',
            'slug.unique' => 'این شناسهٔ آدرس قبلاً استفاده شده است.',
            'variants.required' => 'حداقل یک تنوع برای محصول تعریف کنید.',
            'variants.*.price.required' => 'قیمت هر تنوع الزامی است.',
            'variants.*.stock.min' => 'موجودی نمی‌تواند منفی باشد.',
            'images.*.image' => 'فایل انتخاب‌شده باید تصویر باشد.',
            'images.*.max' => 'اندازهٔ هر تصویر حداکثر ۴ مگابایت است.',
        ];
    }
}
