<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(Order::STATUSES)]];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'وضعیت سفارش الزامی است.',
            'status.in' => 'وضعیت انتخاب‌شده معتبر نیست.',
        ];
    }
}
