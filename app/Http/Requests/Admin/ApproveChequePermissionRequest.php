<?php

namespace App\Http\Requests\Admin;

use App\Support\NumericInput;
use Illuminate\Foundation\Http\FormRequest;

class ApproveChequePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'max_order_amount' => ['required_if:enabled,1', 'nullable', 'numeric', 'min:1', 'max:999999999999.99'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'max_order_amount' => NumericInput::normalize($this->input('max_order_amount')),
            'enabled' => $this->has('enabled')
                ? filter_var($this->input('enabled'), FILTER_VALIDATE_BOOLEAN)
                : true,
        ]);
    }

    public function attributes(): array
    {
        return ['max_order_amount' => 'سقف هر سفارش', 'note' => 'یادداشت مدیریت'];
    }
}
