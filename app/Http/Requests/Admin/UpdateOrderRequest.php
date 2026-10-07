<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->trimValue($this->input('status')),
            'payment_status' => $this->trimValue(
                $this->input('payment_status')
            ),
            'customer_note' => $this->trimValue(
                $this->input('customer_note')
            ),
            'tracking_code' => $this->trimValue(
                $this->input('tracking_code')
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => [
                'bail',
                'required',
                Rule::in(Order::STATUSES),
            ],

            'payment_status' => [
                'bail',
                'required',
                Rule::in(Order::PAYMENT_STATUSES),
            ],

            'customer_note' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'tracking_code' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'وضعیت سفارش',
            'payment_status' => 'وضعیت پرداخت',
            'customer_note' => 'یادداشت سفارش',
            'tracking_code' => 'کد رهگیری',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'وضعیت سفارش را انتخاب کنید.',
            'status.in' => 'وضعیت سفارش انتخاب‌شده معتبر نیست.',

            'payment_status.required' => 'وضعیت پرداخت را انتخاب کنید.',
            'payment_status.in' => 'وضعیت پرداخت انتخاب‌شده معتبر نیست.',

            'customer_note.max' => 'یادداشت سفارش نمی‌تواند بیشتر از ۲۰۰۰ کاراکتر باشد.',

            'tracking_code.max' => 'کد رهگیری بیش از حد طولانی است.',
        ];
    }

    private function trimValue(mixed $value): mixed
    {
        return is_string($value)
            ? trim($value)
            : $value;
    }
}
