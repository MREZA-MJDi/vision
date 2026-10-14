<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchOrderVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100']];
    }

    public function messages(): array
    {
        return ['q.max' => 'عبارت جست‌وجو حداکثر ۱۰۰ نویسه می‌تواند باشد.'];
    }
}
