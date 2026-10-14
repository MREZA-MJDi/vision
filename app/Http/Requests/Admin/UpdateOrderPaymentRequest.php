<?php

namespace App\Http\Requests\Admin;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['payment_status' => ['required', Rule::in(Payment::STATUSES)]];
    }

    public function messages(): array
    {
        return [
            'payment_status.required' => 'وضعیت پرداخت الزامی است.',
            'payment_status.in' => 'وضعیت پرداخت انتخاب‌شده معتبر نیست.',
        ];
    }
}
