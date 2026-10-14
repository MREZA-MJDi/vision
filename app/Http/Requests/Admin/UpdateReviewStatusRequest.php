<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(ProductReview::STATUSES)]];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'وضعیت نظر الزامی است.',
            'status.in' => 'وضعیت انتخاب‌شده برای نظر معتبر نیست.',
        ];
    }
}
