@extends('commerce-admin::layout')

@section('title', $product ? 'ویرایش محصول' : 'افزودن محصول')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.products.index') }}">محصولات</a> <span>/</span> {{ $product ? 'ویرایش محصول' : 'افزودن محصول' }}</div><h1>{{ $product ? 'ویرایش محصول' : 'افزودن محصول' }}</h1><p>Product data, variant pricing and inventory are saved directly to the store database.</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.products.index') }}">انصراف</a><button class="ak-button ak-button-dark" type="submit" form="productForm">ذخیرهٔ محصول <span>✓</span></button></div>
    </div>

    <form id="productForm" method="POST" enctype="multipart/form-data" action="{{ $product ? route('commerce-admin.products.update', $product->getKey()) : route('commerce-admin.products.store') }}">
        @csrf
        @if($product) @method('PUT') @endif
        <div class="ak-form-layout">
            <div class="ak-form-main">
                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>اطلاعات محصول</h2><p>این فیلدها اطلاعات نمایش‌داده‌شده به مشتری را مشخص می‌کنند.</p></div><span class="ak-required-note">* الزامی</span></div>
                    <div class="ak-form-grid">
                        <label class="ak-field ak-field-full"><span>نام محصول <b>*</b></span><input type="text" name="name" value="{{ old('name', $product?->name) }}" required maxlength="255" placeholder="e.g. Acme Prism T-Shirt"><small>این نام در صفحهٔ محصول و سابقهٔ اقلام سفارش نمایش داده می‌شود.</small></label>
                        <label class="ak-field"><span>شناسهٔ آدرس</span><input type="text" name="slug" value="{{ old('slug', $product?->slug) }}" placeholder="generated-from-name"><small>برای ساخت خودکار، این فیلد را خالی بگذارید.</small></label>
                        <label class="ak-field"><span>برند</span><select name="brand_id"><option value="">بدون برند</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((string)old('brand_id', $product?->brand_id) === (string)$brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
                        <label class="ak-field"><span>دسته‌بندی</span><select name="category_id"><option value="">بدون دسته‌بندی</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('category_id', $product?->category_id) === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
                        
                        <label class="ak-field ak-field-full"><span>توضیح کوتاه</span><input type="text" name="short_description" value="{{ old('short_description', $product?->short_description) }}" maxlength="500" placeholder="One-line summary for catalog cards"></label>
                        <label class="ak-field ak-field-full"><span>توضیحات</span><textarea name="description" rows="6" placeholder="Describe the product, material and care details...">{{ old('description', $product?->description) }}</textarea><small>Plain text is supported; keep it useful and accessible.</small></label>
                    </div>
                    <div class="ak-checkbox-row">
                        <label class="ak-check-card"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product?->is_featured ?? false))><span><strong>محصول ویژه</strong><small>این محصول را در بخش محصولات ویژه اولویت بده.</small></span></label>
                        <label class="ak-check-card"><input type="checkbox" name="is_hero" value="1" @checked(old('is_hero', $product?->is_hero ?? false))><span><strong>قابل استفاده در هِرو</strong><small>اجازه بده این محصول در بخش هِرو صفحهٔ اصلی انتخاب شود.</small></span></label>
                    </div>
                </section>

                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>تصاویر محصول</h2><p>حداکثر ۸ تصویر JPG، PNG یا WebP با حجم حداکثر ۴ مگابایت برای هر تصویر بارگذاری کنید.</p></div><label class="ak-button ak-button-light ak-button-sm ak-upload-trigger">＋ افزودن تصویر<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple hidden data-image-input></label></div>
                    <div class="ak-image-upload-zone" data-image-drop><span class="ak-upload-symbol">⇧</span><strong>تصاویر را اینجا رها کنید یا <button type="button" data-browse-images>فایل‌ها را انتخاب کنید</button></strong><small>تصاویر با دیسک ذخیره‌سازی تنظیم‌شدهٔ Laravel ذخیره می‌شوند.</small><div class="ak-new-image-preview" data-image-preview></div></div>
                    @if($product && $product->galleryMedia->isNotEmpty())
                        <div class="ak-existing-images"><span class="ak-section-label">Uploaded files ({{ $product->galleryMedia->count() }})</span><div class="ak-image-grid">@foreach($product->galleryMedia as $asset)<div class="ak-image-tile">@if($asset->url)<img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $product->name }}">@endif<form method="POST" action="{{ route('commerce-admin.products.media.destroy', [$product->getKey(), $asset->getKey()]) }}" data-confirm="Remove this image from the product?">@csrf @method('DELETE')<button type="submit" aria-label="Remove image">×</button></form><small>{{ $asset->original_name ?: 'Product image' }}</small></div>@endforeach</div></div>
                    @endif
                </section>

                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>تنوع‌ها و موجودی</h2><p>هر تنوع شناسه SKU، بارکد، قیمت و موجودی مستقل دارد.</p></div><button class="ak-button ak-button-light ak-button-sm" type="button" data-add-variant>＋ افزودن تنوع</button></div>
                    <div class="ak-variant-list" data-variant-list>
                        @php $formVariants = old('variants', $variants); @endphp
                        @foreach($formVariants as $index => $variant)
                            <div class="ak-variant-card" data-variant-row>
                                <div class="ak-variant-card-head"><strong>تنوع <span data-variant-number>{{ $loop->iteration }}</span></strong><button type="button" class="ak-icon-button ak-remove-variant" data-remove-variant aria-label="Remove variant" {{ $loop->first ? 'disabled' : '' }}>×</button></div>
                                @if(data_get($variant, 'id'))<input type="hidden" name="variants[{{ $index }}][id]" value="{{ data_get($variant, 'id') }}">@endif
                                <div class="ak-variant-grid">
                                    <label class="ak-field"><span>SKU</span><input name="variants[{{ $index }}][sku]" value="{{ old('variants.'.$index.'.sku', data_get($variant, 'sku')) }}" maxlength="100" placeholder="SKU-001"></label>
                                    <label class="ak-field"><span>بارکد</span><input name="variants[{{ $index }}][barcode]" value="{{ old('variants.'.$index.'.barcode', data_get($variant, 'barcode')) }}" maxlength="100" placeholder="Scan or enter barcode"></label>
                                    <label class="ak-field"><span>اندازه / گزینه</span><input name="variants[{{ $index }}][size]" value="{{ old('variants.'.$index.'.size', data_get($variant, 'size')) }}" maxlength="50" placeholder="M / 256 GB"></label>
                                    <label class="ak-field"><span>Color</span><input name="variants[{{ $index }}][color]" value="{{ old('variants.'.$index.'.color', data_get($variant, 'color')) }}" maxlength="80" placeholder="Black"></label>
                                    <label class="ak-field"><span>کد رنگ</span><div class="ak-color-code"><input type="color" name="variants[{{ $index }}][color_code]" value="{{ old('variants.'.$index.'.color_code', data_get($variant, 'color_code') ?: '#171717') }}" aria-label="کد رنگ"><input type="text" value="{{ old('variants.'.$index.'.color_code', data_get($variant, 'color_code') ?: '#171717') }}" maxlength="20" data-color-mirror></div></label>
                                    <label class="ak-field"><span>قیمت <b>*</b></span><input type="number" step="0.01" min="0" name="variants[{{ $index }}][price]" value="{{ old('variants.'.$index.'.price', data_get($variant, 'price')) }}" required placeholder="0.00"></label>
                                    <label class="ak-field"><span>قیمت با تخفیف</span><input type="number" step="0.01" min="0" name="variants[{{ $index }}][sale_price]" value="{{ old('variants.'.$index.'.sale_price', data_get($variant, 'sale_price')) }}" placeholder="Optional"></label>
                                    <label class="ak-field"><span>موجودی قابل فروش <b>*</b></span><input type="number" min="0" step="1" name="variants[{{ $index }}][stock]" value="{{ old('variants.'.$index.'.stock', data_get($variant, 'stock', 0)) }}" required></label>
                                    <label class="ak-field"><span>حد هشدار موجودی <b>*</b></span><input type="number" min="0" step="1" name="variants[{{ $index }}][low_stock_threshold]" value="{{ old('variants.'.$index.'.low_stock_threshold', data_get($variant, 'low_stock_threshold', 5)) }}" required></label>
                                    <label class="ak-field"><span>وضعیت تنوع</span><select name="variants[{{ $index }}][is_active]"><option value="1" @selected((string)old('variants.'.$index.'.is_active', (int)data_get($variant, 'is_active', true)) === '1')>فعال</option><option value="0" @selected((string)old('variants.'.$index.'.is_active', (int)data_get($variant, 'is_active', true)) === '0')>غیرفعال</option></select></label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <template id="variant-row-template">
                        <div class="ak-variant-card" data-variant-row>
                            <div class="ak-variant-card-head"><strong>تنوع <span data-variant-number>__NUMBER__</span></strong><button type="button" class="ak-icon-button ak-remove-variant" data-remove-variant aria-label="Remove variant">×</button></div>
                            <div class="ak-variant-grid">
                                <label class="ak-field"><span>SKU</span><input name="variants[__INDEX__][sku]" maxlength="100" placeholder="SKU-001"></label>
                                <label class="ak-field"><span>بارکد</span><input name="variants[__INDEX__][barcode]" maxlength="100" placeholder="Scan or enter barcode"></label>
                                <label class="ak-field"><span>اندازه / گزینه</span><input name="variants[__INDEX__][size]" maxlength="50" placeholder="M / 256 GB"></label>
                                <label class="ak-field"><span>Color</span><input name="variants[__INDEX__][color]" maxlength="80" placeholder="Black"></label>
                                <label class="ak-field"><span>کد رنگ</span><div class="ak-color-code"><input type="color" name="variants[__INDEX__][color_code]" value="#171717" aria-label="کد رنگ"><input type="text" value="#171717" maxlength="20" data-color-mirror></div></label>
                                <label class="ak-field"><span>قیمت <b>*</b></span><input type="number" step="0.01" min="0" name="variants[__INDEX__][price]" required placeholder="0.00"></label>
                                <label class="ak-field"><span>قیمت با تخفیف</span><input type="number" step="0.01" min="0" name="variants[__INDEX__][sale_price]" placeholder="Optional"></label>
                                <label class="ak-field"><span>موجودی قابل فروش <b>*</b></span><input type="number" min="0" step="1" name="variants[__INDEX__][stock]" value="0" required></label>
                                <label class="ak-field"><span>حد هشدار موجودی <b>*</b></span><input type="number" min="0" step="1" name="variants[__INDEX__][low_stock_threshold]" value="5" required></label>
                                <label class="ak-field"><span>وضعیت تنوع</span><select name="variants[__INDEX__][is_active]"><option value="1" selected>فعال</option><option value="0">غیرفعال</option></select></label>
                            </div>
                        </div>
                    </template>
                </section>
            </div>

            <aside class="ak-form-aside">
                <section class="ak-panel ak-aside-panel"><div class="ak-panel-heading"><div><h2>انتشار</h2><p>نمایش محصول در فروشگاه را کنترل کنید.</p></div></div><label class="ak-field"><span>وضعیت کاتالوگ</span><select name="status" form="productForm" required><option value="active" @selected(old('status', $product?->is_active ? 'active' : 'draft') === 'active')>فعال</option><option value="draft" @selected(old('status', $product?->is_active ? 'active' : 'draft') === 'draft')>پیش‌نویس</option></select></label><div class="ak-info-box"><span>i</span><p>غیرفعال products stay in your database, but they should not appear in customer-facing product listings.</p></div></section>
                <section class="ak-panel ak-aside-panel"><div class="ak-panel-heading"><div><h2>تنظیمات جست‌وجو</h2><p>اطلاعات تکمیلی و اختیاری برای نتایج جست‌وجو.</p></div></div><label class="ak-field"><span>عنوان متا</span><input type="text" name="meta_title" value="{{ old('meta_title', $product?->meta_title) }}" maxlength="255" placeholder="جست‌وجو result title"></label><label class="ak-field"><span>توضیح متا</span><textarea name="meta_description" rows="4" maxlength="500" placeholder="Brief search result summary">{{ old('meta_description', $product?->meta_description) }}</textarea></label></section>
                @if($product)<section class="ak-panel ak-aside-panel"><div class="ak-panel-heading"><div><h2>جزئیات رکورد</h2></div></div><div class="ak-detail-list"><span>Product ID<strong>#{{ $product->getKey() }}</strong></span><span>تاریخ ایجاد<strong>{{ $product->created_at?->format('M d, Y') ?: '—' }}</strong></span><span>آخرین به‌روزرسانی<strong>{{ $product->updated_at?->diffForHumans() ?: '—' }}</strong></span></div><a class="ak-text-link" href="{{ route('commerce-admin.products.show', $product->getKey()) }}">مشاهدهٔ رکورد ↗</a></section>@endif
                <div class="ak-form-sticky-actions"><button class="ak-button ak-button-dark ak-button-wide" type="submit" form="productForm">ذخیرهٔ محصول <span>✓</span></button><a class="ak-button ak-button-light ak-button-wide" href="{{ route('commerce-admin.products.index') }}">انصراف</a></div>
            </aside>
        </div>
    </form>
@endsection
