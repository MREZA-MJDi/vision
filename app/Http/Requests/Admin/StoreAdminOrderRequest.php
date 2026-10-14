<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantModel = (string) config('commerce-admin.models.variant', ProductVariant::class);
        $variant = new $variantModel();

        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'shipping_province' => ['nullable', 'string', 'max:100'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', Rule::in(['cash_on_delivery', 'bank_transfer', 'manual'])],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', Rule::exists($variant->getTable(), $variant->getKeyName())],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'نام مشتری الزامی است.',
            'payment_method.in' => 'روش پرداخت انتخاب‌شده معتبر نیست.',
            'items.required' => 'حداقل یک قلم به سفارش اضافه کنید.',
            'items.*.variant_id.distinct' => 'هر تنوع را فقط یک‌بار به سفارش اضافه کنید.',
            'items.*.quantity.min' => 'تعداد هر قلم باید حداقل یک باشد.',
        ];
    }
}
