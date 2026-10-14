<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkArchiveProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = (string) config('commerce-admin.models.product', Product::class);
        $product = new $model();

        return [
            'product_ids' => ['required', 'array', 'min:1', 'max:100'],
            'product_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists($product->getTable(), $product->getKeyName()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_ids.required' => 'حداقل یک محصول انتخاب کنید.',
            'product_ids.min' => 'حداقل یک محصول انتخاب کنید.',
            'product_ids.max' => 'در هر عملیات حداکثر ۱۰۰ محصول قابل بایگانی است.',
            'product_ids.*.distinct' => 'یک محصول بیش از یک‌بار انتخاب شده است.',
            'product_ids.*.exists' => 'یکی از محصولات انتخاب‌شده معتبر نیست.',
        ];
    }
}
