<?php

namespace App\Http\Requests\Admin;

use App\Support\NumericInput;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_id' => $this->input('product_id') ?: null,

            'sku' => $this->trimValue($this->input('sku')),
            'size' => $this->trimValue($this->input('size')),
            'color' => $this->trimValue($this->input('color')),
            'color_code' => $this->trimValue($this->input('color_code')),

            'price' => $this->normalizeNumber($this->input('price')),
            'sale_price' => $this->normalizeNumber($this->input('sale_price')),            'stock' => $this->normalizeNumber($this->input('stock')),
            'low_stock_threshold' => $this->normalizeNumber(
                $this->input('low_stock_threshold')
            ),
            'sort_order' => $this->normalizeNumber(
                $this->input('sort_order')
            ),
        ]);

        $price = $this->input('price');
        $salePrice = $this->input('sale_price');

        if (
            $salePrice !== null &&
            $salePrice !== '' &&
            $price !== null &&
            $price !== '' &&
            is_numeric($salePrice) &&
            is_numeric($price) &&
            (float) $salePrice >= (float) $price
        ) {
            $this->merge([
                'sale_price' => null,
            ]);
        }
    }

    public function rules(): array
    {
        $variant = $this->route('variant')
            ?? $this->route('productVariant');

        $variantId = $variant instanceof ProductVariant
            ? $variant->getKey()
            : $variant;

        return [
            'product_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->whereNull('deleted_at'),
            ],

            'sku' => [
                'bail',
                'nullable',
                'string',
                'max:120',
                Rule::unique('product_variants', 'sku')
                    ->ignore($variantId),
            ],

            'size' => [
                'nullable',
                'string',
                'max:80',
            ],

            'color' => [
                'nullable',
                'string',
                'max:80',
            ],

            'color_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'price' => [
                'bail',
                'required',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'low_stock_threshold' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'محصول',
            'sku' => 'کد کالا',
            'size' => 'سایز',
            'color' => 'رنگ',
            'color_code' => 'رنگ',
            'price' => 'قیمت',
            'sale_price' => 'قیمت فروش ویژه',            'stock' => 'موجودی',
            'low_stock_threshold' => 'حد هشدار موجودی',
            'is_active' => 'وضعیت',
            'sort_order' => 'ترتیب نمایش',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'محصول را انتخاب کنید.',
            'product_id.exists' => 'محصول انتخاب‌شده معتبر نیست.',

            'sku.unique' => 'این کد کالا قبلاً استفاده شده است.',
            'sku.max' => 'کد کالا بیش از حد طولانی است.',

            'size.max' => 'سایز نمی‌تواند بیشتر از ۸۰ کاراکتر باشد.',
            'color.max' => 'نام رنگ نمی‌تواند بیشتر از ۸۰ کاراکتر باشد.',

            'price.required' => 'قیمت را وارد کنید.',
            'price.numeric' => 'قیمت باید به‌صورت عدد وارد شود.',
            'price.min' => 'قیمت نمی‌تواند منفی باشد.',

            'sale_price.numeric' => 'قیمت فروش ویژه باید به‌صورت عدد وارد شود.',
            'sale_price.min' => 'قیمت فروش ویژه نمی‌تواند منفی باشد.',

            'stock.required' => 'موجودی را وارد کنید.',
            'stock.integer' => 'موجودی باید یک عدد صحیح باشد.',
            'stock.min' => 'موجودی نمی‌تواند منفی باشد.',

            'low_stock_threshold.integer' => 'حد هشدار موجودی باید یک عدد صحیح باشد.',
            'low_stock_threshold.min' => 'حد هشدار موجودی نمی‌تواند منفی باشد.',

            'sort_order.integer' => 'ترتیب نمایش باید یک عدد صحیح باشد.',
            'sort_order.min' => 'ترتیب نمایش نمی‌تواند منفی باشد.',
        ];
    }

    private function trimValue(mixed $value): mixed
    {
        return is_string($value)
            ? trim($value)
            : $value;
    }

    private function normalizeNumber(mixed $value): mixed
    {
        return NumericInput::normalize($value);
    }
}
