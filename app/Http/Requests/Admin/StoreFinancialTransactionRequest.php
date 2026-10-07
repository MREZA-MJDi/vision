<?php

namespace App\Http\Requests\Admin;

use App\Models\FinancialTransaction;
use Illuminate\Validation\Rule;

class StoreFinancialTransactionRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'category',
            'description',
            'transaction_date',
        ]);

        $amount = $this->input('amount');

        if (is_string($amount)) {
            $amount = strtr($amount, [
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
                '٬' => '',
                ',' => '',
                '،' => '',
                ' ' => '',
            ]);
        }

        $this->merge([
            'amount' => $amount,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => [
                'bail',
                'required',
                Rule::in(FinancialTransaction::TYPES),
            ],

            'category' => [
                'bail',
                'required',
                'string',
                'max:80',
            ],

            'amount' => [
                'bail',
                'required',
                'numeric',
                'gt:0',
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'transaction_date' => [
                'bail',
                'required',
                'date',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'نوع تراکنش',
            'category' => 'دسته‌بندی مالی',
            'amount' => 'مبلغ',
            'description' => 'توضیحات',
            'transaction_date' => 'تاریخ تراکنش',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع تراکنش را انتخاب کنید.',
            'type.in' => 'نوع تراکنش انتخاب‌شده معتبر نیست.',

            'category.required' => 'دسته‌بندی مالی را وارد کنید.',
            'category.max' => 'دسته‌بندی مالی نمی‌تواند بیشتر از ۸۰ کاراکتر باشد.',

            'amount.required' => 'مبلغ تراکنش را وارد کنید.',
            'amount.numeric' => 'مبلغ باید به‌صورت عدد وارد شود.',
            'amount.gt' => 'مبلغ باید بیشتر از صفر باشد.',

            'description.max' => 'توضیحات نمی‌تواند بیشتر از ۵۰۰ کاراکتر باشد.',

            'transaction_date.required' => 'تاریخ تراکنش را وارد کنید.',
            'transaction_date.date' => 'تاریخ تراکنش معتبر نیست.',
        ];
    }
}
