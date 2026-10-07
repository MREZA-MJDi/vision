<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryMovement;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'note',
        ]);

        $quantity = $this->input('quantity');

        if (is_string($quantity)) {
            $quantity = strtr($quantity, [
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

        if ($quantity !== null && is_numeric($quantity)) {
            $quantity = (int) $quantity;

            $quantity = match ($this->input('type')) {
                'purchase', 'return' => abs($quantity),
                'sale', 'damage' => -abs($quantity),
                default => $quantity,
            };
        }

        $this->merge([
            'quantity' => $quantity,
        ]);
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->whereNull('deleted_at'),
            ],

            'type' => [
                'bail',
                'required',
                Rule::in(InventoryMovement::TYPES),
            ],

            'quantity' => [
                'bail',
                'required',
                'integer',
                'not_in:0',
            ],

            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_variant_id' => 'محصول',
            'type' => 'نوع گردش موجودی',
            'quantity' => 'مقدار',
            'note' => 'یادداشت',
        ];
    }

    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'محصول را انتخاب کنید.',
            'product_variant_id.exists' => 'محصول انتخاب‌شده معتبر نیست.',

            'type.required' => 'نوع گردش موجودی را انتخاب کنید.',
            'type.in' => 'نوع گردش موجودی انتخاب‌شده معتبر نیست.',

            'quantity.required' => 'مقدار موجودی را وارد کنید.',
            'quantity.integer' => 'مقدار موجودی باید یک عدد صحیح باشد.',
            'quantity.not_in' => 'مقدار موجودی نمی‌تواند صفر باشد.',

            'note.max' => 'یادداشت نمی‌تواند بیشتر از ۵۰۰ کاراکتر باشد.',
        ];
    }
}
