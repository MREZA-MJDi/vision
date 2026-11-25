@extends('commerce-admin::layout')
@section('title', 'تصاویر صفحه اصلی')
@section('content')
<div class="ak-page-heading"><div><h1>تصاویر صفحه اصلی</h1><p>ترتیب نمایش واقعی و مسیر مدیریت هر تصویر</p></div><a class="ak-button ak-button-light" href="{{ route('storefront.index') }}" target="_blank" rel="noopener">مشاهده سایت</a></div>
@if($errors->any())
    <div class="ak-panel" role="alert">{{ $errors->first() }}</div>
@endif
<p>تغییر تصویر از همین صفحه روی گالری اصلی اعمال می‌شود؛ اگر تصویر در فروشگاه هم استفاده شود، آنجا نیز به‌روزرسانی خواهد شد. حذف تصویر ممکن است تصویر بعدی یا تصویر جایگزین را نمایش دهد.</p>
<section class="ak-panel ak-home-media">
<h2>۱. Hero — تصاویر محصول و رنگ‌ها</h2><p>تصویر اول گالری واریانت، یا تصویر اصلی محصول. انتخاب Hero و ترتیب از مدیریت محصول است.</p>
<div class="ak-home-media-grid">
@forelse($heroSlides as $slide)
    @php
        $product = $heroProducts->firstWhere('id', $slide['productId']);
        $variant = $product?->activeVariants->firstWhere('id', $slide['variantId']);
        $owner = $variant?->primaryGalleryMedia?->url ? $variant : ($product?->primaryGalleryMedia?->url ? $product : ($variant ?? $product));
    @endphp
    <article>
        @if($slide['image'])<img src="{{ $slide['image'] }}" alt="" loading="lazy">@else<span>بدون تصویر</span>@endif
        <strong>{{ $slide['title'] }}</strong><small>{{ $slide['label'] }}</small>
        @if($owner)
            @include('commerce-admin::homepage.image-actions', ['ownerType' => $owner instanceof \App\Models\ProductVariant ? 'variant' : 'product', 'owner' => $owner, 'media' => $owner->primaryGalleryMedia])
        @endif
        <a href="{{ route('commerce-admin.products.edit', $slide['productId']) }}">ویرایش محصول</a>
    </article>
@empty<p>محصول Hero تعریف نشده است.</p>@endforelse
</div><a href="{{ route('commerce-admin.products.index') }}">مدیریت محصولات</a>
</section>
<section class="ak-panel ak-home-media">
<h2>۲. برندها — قاب مثلثی</h2><p>حداکثر هشت برند فعال اخیر؛ تصویر لوگو از فرم برند.</p>
<div class="ak-home-media-grid">
@forelse($homeBrands as $brand)
    <article>
        @if($image = $brand->logoMedia?->url)<img class="{{ $loop->index % 3 === 1 ? 'ak-home-triangle-inverse' : 'ak-home-triangle' }}" src="{{ $image }}" alt="" loading="lazy">@else<span>بدون تصویر</span>@endif
        <strong>{{ $brand->name }}</strong>
        @include('commerce-admin::homepage.image-actions', ['ownerType' => 'brand', 'owner' => $brand, 'media' => $brand->logoMedia])
        <a href="{{ route('commerce-admin.brands.edit', $brand->id) }}">ویرایش برند</a>
    </article>
@empty<p>برند فعال وجود ندارد.</p>@endforelse
</div><a href="{{ route('commerce-admin.brands.index') }}">مدیریت برندها</a>
</section>
<section class="ak-panel ak-home-media">
<h2>۳. دسته‌بندی‌ها — Bento</h2><p>حداکثر شش دسته فعال بر اساس ترتیب نمایش؛ قاب اصلی عمودی و قاب فرعی مربع.</p>
<div class="ak-home-media-grid">
@forelse($homeCategories as $category)
    <article>
        @if($image = $category->coverMedia?->url)<img class="{{ $loop->index % 3 === 0 ? 'ak-home-portrait' : '' }}" src="{{ $image }}" alt="" loading="lazy">@else<span>بدون تصویر</span>@endif
        <strong>{{ $category->name }}</strong><small>{{ $loop->index % 3 === 0 ? 'کارت اصلی' : 'کارت فرعی' }}</small>
        @include('commerce-admin::homepage.image-actions', ['ownerType' => 'category', 'owner' => $category, 'media' => $category->coverMedia])
        <a href="{{ route('commerce-admin.categories.edit', $category->id) }}">ویرایش دسته</a>
    </article>
@empty<p>دسته فعال وجود ندارد.</p>@endforelse
</div><a href="{{ route('commerce-admin.categories.index') }}">مدیریت دسته‌بندی‌ها</a>
</section>
@foreach(['۴. تازه‌ها' => $newProducts, '۵. منتخب VISION' => ($editorialProducts->isNotEmpty() ? $editorialProducts : $heroProducts->take(4)), '۶. تخفیف‌ها' => $saleProducts] as $heading => $items)
<section class="ak-panel ak-home-media">
<h2>{{ $heading }}</h2><p>تصویر گالری واریانت موجود، با جایگزین تصویر اصلی محصول.</p>
<div class="ak-home-media-grid">
@forelse($items as $product)
    @php($variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant)
    @php($image = $variant?->primaryGalleryMedia?->url ?: $product->primaryGalleryMedia?->url)
    @php($owner = $variant?->primaryGalleryMedia?->url ? $variant : ($product->primaryGalleryMedia?->url ? $product : ($variant ?? $product)))
    <article>
        @if($image)<img src="{{ $image }}" alt="" loading="lazy">@else<span>بدون تصویر</span>@endif
        <strong>{{ $product->name }}</strong>
        @include('commerce-admin::homepage.image-actions', ['ownerType' => $owner instanceof \App\Models\ProductVariant ? 'variant' : 'product', 'owner' => $owner, 'media' => $owner->primaryGalleryMedia])
        <a href="{{ route('commerce-admin.products.edit', $product->id) }}">ویرایش محصول</a>
    </article>
@empty<p>این سکشن در صورت نبود محصول واجد شرایط نمایش داده نمی‌شود.</p>@endforelse
</div><a href="{{ route('commerce-admin.products.index') }}">مدیریت محصولات</a>
</section>
@endforeach
<section class="ak-panel ak-home-media">
<h2>۷. اعتمادسازی — تصاویر مستقل</h2><p>JPG، PNG یا WebP؛ حداکثر ۱۰ مگابایت. قاب مربع پیشنهادی است؛ خروجی به WebP تبدیل می‌شود.</p>
<div class="ak-home-media-grid ak-home-media-grid--trust">
@foreach($trustImages as $slot => $image)
    <article>
        <strong>{{ $image['label'] }}</strong>
        <small>{{ $image['custom'] ? 'تصویر اختصاصی' : 'تصویر پیش‌فرض' }}</small>
        <form method="POST" enctype="multipart/form-data" action="{{ route('commerce-admin.homepage.trust.update', $slot) }}">
            @csrf @method('PUT')
            <div class="rm-uploader rm-uploader--trust" data-rm-uploader data-existing-url="{{ $image['url'] }}" data-aspect="1" data-existing-crop="{{ $image['custom'] ? '1' : '0' }}">
                <label class="ak-field"><span>انتخاب فایل تصویر</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-rm-input required></label>
                <input type="hidden" data-rm-remove-input value="0">
                <button type="button" class="rm-uploader__drop" data-rm-drop aria-label="انتخاب یا تغییر تصویر {{ $image['label'] }}">
                    <img src="{{ $image['url'] }}" data-rm-preview alt="{{ $image['label'] }}">
                    <span data-rm-placeholder hidden>انتخاب یا رها کردن تصویر</span>
                </button>
                <div class="rm-uploader__actions">
                    <button type="button" data-rm-crop @disabled(!$image['custom'])>تنظیم کادر</button>
                    <button type="button" data-rm-remove hidden>حذف انتخاب</button>
                    <button type="button" data-rm-restore hidden>لغو تغییر</button>
                </div>
                <small data-rm-info aria-live="polite">حداکثر حجم خروجی ۱۰ مگابایت</small>
                <p data-rm-error class="rm-uploader__error" role="alert" hidden></p>
                @include('commerce-admin::components.media.image-cropper')
            </div>
            <button class="ak-button ak-button-dark" type="submit">ذخیره تصویر</button>
        </form>
        @if($image['custom'])
        <form method="POST" action="{{ route('commerce-admin.homepage.trust.destroy', $slot) }}">@csrf @method('DELETE')<button class="ak-button ak-button-light" type="submit">بازگشت به پیش‌فرض</button></form>
        @endif
    </article>
@endforeach
</div>
</section>
@endsection
