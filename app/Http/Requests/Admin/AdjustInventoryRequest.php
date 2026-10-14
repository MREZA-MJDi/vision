<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment' => ['required', 'integer', 'min:-100000', 'max:100000', Rule::notIn([0])],
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment.required' => 'مقدار اصلاح موجودی الزامی است.',
            'adjustment.not_in' => 'برای اصلاح موجودی، عددی غیر از صفر وارد کنید.',
            'note.required' => 'ثبت دلیل تغییر موجودی الزامی است.',
            'note.min' => 'دلیل تغییر باید حداقل سه نویسه داشته باشد.',
        ];
    }
}
