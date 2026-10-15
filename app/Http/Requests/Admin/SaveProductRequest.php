<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\NumericInput;
use Rm\VirtualTryOn\Models\TryOnMannequin;
use Rm\VirtualTryOn\Support\VirtualTryOnConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $variants = (array) $this->input('variants', []);

        foreach ($variants as $index => $variant) {
            if (! is_array($variant)) {
                continue;
            }

            foreach (['price', 'sale_price'] as $field) {
                if (array_key_exists($field, $variant)) {
                    $variants[$index][$field] = NumericInput::normalize($variant[$field]);
                }
            }
        }

        $this->merge(['variants' => $variants]);
    }

    public function rules(): array
    {
        $productModel = (string) config('commerce-admin.models.product', Product::class);
        $categoryModel = (string) config('commerce-admin.models.category', Category::class);
        $brandModel = (string) config('commerce-admin.models.brand', Brand::class);
        $productTable = (new $productModel())->getTable();
        $categoryTable = (new $categoryModel())->getTable();
        $brandTable = (new $brandModel())->getTable();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($productTable, 'slug')->ignore($this->route('product'))],
            'category_id' => ['nullable', 'integer', Rule::exists($categoryTable, 'id')->whereNull('deleted_at')],
            'brand_id' => ['nullable', 'integer', Rule::exists($brandTable, 'id')->whereNull('deleted_at')],
            'audience' => ['nullable', 'string', Rule::in(VirtualTryOnConfig::AUDIENCES)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'attributes' => ['sometimes', 'array', 'max:50'],
            'attributes.*.key' => ['nullable', 'string', 'max:100'],
            'attributes.*.value' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_hero' => ['sometimes', 'boolean'],
            'is_taxable' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'draft'])],
            'variants' => ['required', 'array', 'min:1', 'max:30'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:80'],
            'variants.*.color_code' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['required', 'boolean'],
            'variants.*.image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'variants.*.images' => ['sometimes', 'array', 'max:4'],
            'variants.*.images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'variants.*.try_on_mannequin_id' => ['nullable', 'integer', 'min:1'],
            'variants.*.try_on_slot' => ['nullable', 'string', Rule::in(array_keys(VirtualTryOnConfig::SLOT_ORDER))],
            'variants.*.try_on_image' => ['sometimes', 'nullable', 'image', 'mimes:png,webp', 'dimensions:width=1024,height=1536', 'max:4096'],
            'variants.*.remove_try_on_image' => ['sometimes', 'boolean'],
            'variants.*.try_on_default' => ['sometimes', 'boolean'],
            'images' => ['sometimes', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $seenAttributeKeys = [];
            foreach ((array) $this->input('attributes', []) as $index => $attribute) {
                if (! is_array($attribute)) {
                    continue;
                }

                $key = trim((string) ($attribute['key'] ?? ''));
                $value = trim((string) ($attribute['value'] ?? ''));
                if ($key === '' && $value === '') {
                    continue;
                }
                if ($key === '') {
                    $validator->errors()->add("attributes.$index.key", 'برای مقدار واردشده، عنوان مشخصه را بنویسید.');
                    continue;
                }

                $normalizedKey = mb_strtolower($key);
                if (isset($seenAttributeKeys[$normalizedKey])) {
                    $validator->errors()->add("attributes.$index.key", 'عنوان مشخصه نباید تکراری باشد.');
                }
                $seenAttributeKeys[$normalizedKey] = true;
            }

            $audience = (string) $this->input('audience', '');
            $variants = (array) $this->input('variants', []);
            $requestedMannequinIds = collect($variants)
                ->map(fn ($variant) => is_array($variant) ? (int) ($variant['try_on_mannequin_id'] ?? 0) : 0)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
            $validMannequinIds = $requestedMannequinIds !== []
                && in_array($audience, VirtualTryOnConfig::AUDIENCES, true)
                    ? TryOnMannequin::query()
                        ->active()
                        ->whereIn('id', $requestedMannequinIds)
                        ->where('audience', $audience)
                        ->pluck('id')
                        ->map(fn ($id): int => (int) $id)
                        ->all()
                    : [];

            $variantIds = collect($variants)
                ->pluck('id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
            $existingLayerVariantIds = $variantIds === []
                ? []
                : TryOnAsset::query()
                    ->whereIn('product_variant_id', $variantIds)
                    ->where('is_active', true)
                    ->pluck('product_variant_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();
            $seenDefaultSlots = [];

            foreach ($variants as $index => $variant) {
                if (! is_array($variant)) {
                    continue;
                }

                $slot = trim((string) ($variant['try_on_slot'] ?? ''));
                $mannequinId = (int) ($variant['try_on_mannequin_id'] ?? 0);
                $hasLayerImage = $this->hasFile("variants.{$index}.try_on_image");
                $removeLayerImage = (bool) ($variant['remove_try_on_image'] ?? false);
                $makeDefault = (bool) ($variant['try_on_default'] ?? false);
                $variantId = (int) ($variant['id'] ?? 0);

                if ($makeDefault) {
                    if ($slot === '' || $mannequinId < 1) {
                        $validator->errors()->add("variants.$index.try_on_default", 'برای پیش‌فرض کردن لباس، مانکن و جایگاه را مشخص کن.');
                    }

                    if (! $hasLayerImage && ! in_array($variantId, $existingLayerVariantIds, true)) {
                        $validator->errors()->add("variants.$index.try_on_default", 'ابتدا تصویر لایه‌ای معتبر این تنوع را ثبت کن.');
                    }

                    if ($removeLayerImage && ! $hasLayerImage) {
                        $validator->errors()->add("variants.$index.try_on_default", 'نمی‌توان تصویر را حذف کرد و هم‌زمان همان لباس را پیش‌فرض نگه داشت.');
                    }

                    $defaultKey = $mannequinId . ':' . $slot;
                    if (isset($seenDefaultSlots[$defaultKey])) {
                        $validator->errors()->add("variants.$index.try_on_default", 'برای هر مانکن و جایگاه فقط یک لباس پیش‌فرض انتخاب کن.');
                        $validator->errors()->add("variants.{$seenDefaultSlots[$defaultKey]}.try_on_default", 'برای هر مانکن و جایگاه فقط یک لباس پیش‌فرض انتخاب کن.');
                    } else {
                        $seenDefaultSlots[$defaultKey] = $index;
                    }
                }

                if (! ($removeLayerImage && ! $hasLayerImage) && ($hasLayerImage || $slot !== '' || $mannequinId > 0 || $makeDefault)) {
                    if (! in_array($audience, VirtualTryOnConfig::AUDIENCES, true)) {
                        $validator->errors()->add('audience', 'برای استفاده از اتاق پرو ابتدا گروه زنانه یا مردانه را انتخاب کنید.');
                    }

                    if (($slot === '') !== ($mannequinId === 0)) {
                        $validator->errors()->add("variants.$index.try_on_slot", 'برای تصویر لایه‌ای، هم مانکن و هم جایگاه لباس را انتخاب کنید.');
                    }

                    if ($hasLayerImage && ($slot === '' || $mannequinId === 0)) {
                        $validator->errors()->add("variants.$index.try_on_image", 'پیش از بارگذاری تصویر لایه‌ای، مانکن و جایگاه را مشخص کنید.');
                    }

                    if ($mannequinId > 0 && ! in_array($mannequinId, $validMannequinIds, true)) {
                        $validator->errors()->add("variants.$index.try_on_mannequin_id", 'مانکن باید فعال و هم‌گروه با محصول باشد.');
                    }
                } elseif ($removeLayerImage) {
                    // Removal is allowed even if the product no longer has an audience assigned.
                }
            }

            foreach ((array) $this->input('variants', []) as $index => $variant) {
                if (! is_array($variant) || ! isset($variant['price']) || ! is_numeric($variant['price'])) {
                    continue;
                }

                $salePrice = $variant['sale_price'] ?? null;
                if ($salePrice !== null && $salePrice !== '' && is_numeric($salePrice)
                    && (float) $salePrice >= (float) $variant['price']) {
                    $validator->errors()->add(
                        "variants.$index.sale_price",
                        'قیمت با تخفیف باید کمتر از قیمت اصلی باشد.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام محصول الزامی است.',
            'slug.unique' => 'این شناسهٔ آدرس قبلاً استفاده شده است.',
            'variants.required' => 'حداقل یک تنوع برای محصول تعریف کنید.',
            'variants.*.price.required' => 'قیمت هر تنوع الزامی است.',
            'variants.*.stock.min' => 'موجودی نمی‌تواند منفی باشد.',
            'images.*.image' => 'فایل انتخاب‌شده باید تصویر باشد.',
            'images.*.max' => 'اندازهٔ هر تصویر حداکثر ۴ مگابایت است.',
            'variants.*.color_code.regex' => 'کد رنگ باید مقدار HEX معتبر مانند #000000 باشد.',
            'variants.*.image.image' => 'تصویر تنوع باید یک فایل تصویری معتبر باشد.',
            'variants.*.image.max' => 'اندازهٔ تصویر هر تنوع حداکثر ۴ مگابایت است.',
            'variants.*.try_on_image.image' => 'تصویر لایه‌ای باید یک تصویر PNG یا WebP معتبر باشد.',
            'variants.*.try_on_image.mimes' => 'برای لایهٔ مانکن فقط تصویر شفاف PNG یا WebP بارگذاری کن.',
            'variants.*.try_on_image.dimensions' => 'اندازهٔ تصویر لایه‌ای باید دقیقاً ۱۰۲۴ در ۱۵۳۶ پیکسل باشد.',
            'variants.*.try_on_image.max' => 'حجم هر لایهٔ مانکن حداکثر ۴ مگابایت است.',
            'variants.*.images.*.image' => 'تصویر هر تنوع باید یک فایل تصویری معتبر باشد.',
            'variants.*.images.*.max' => 'اندازهٔ هر تصویر تنوع حداکثر ۴ مگابایت است.',
        ];
    }
}
