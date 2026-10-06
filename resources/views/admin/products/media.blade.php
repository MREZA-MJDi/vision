@extends('layouts.admin')

@section('title', 'تصاویر محصول')
@section('page-title', 'مدیریت تصاویر')

@section('content')
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-head__title">تصاویر محصول</h1>
            <p class="admin-page-head__text">
                {{ $product->name }} — تصاویر این بخش کاملاً تحت مدیریت جانان هستند و با همگام‌سازی نیلا جایگزین نمی‌شوند.
            </p>
        </div>

        <a href="{{ route('admin.products.edit', $product) }}" class="admin-btn admin-btn--ghost admin-btn--sm">
            بازگشت به محصول
        </a>
    </div>

    <section class="admin-card admin-media-manager" data-product-media-manager data-reorder-url="{{ route('admin.products.media.reorder', $product) }}">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">گالری محصول</h2>
                <p class="admin-card-description">
                    تا ۱۲ تصویر در هر بار آپلود. تصویر اول، تصویر اصلی فروشگاه خواهد بود.
                </p>
            </div>
            <span class="admin-badge admin-badge--info">{{ $media->count() }} تصویر</span>
        </div>

        <form
            method="POST"
            action="{{ route('admin.products.media.store', $product) }}"
            enctype="multipart/form-data"
            class="admin-media-upload"
        >
            @csrf

            <label class="admin-media-dropzone" data-media-dropzone>
                <input
                    type="file"
                    name="images[]"
                    accept="image/jpeg,image/png,image/webp,image/avif"
                    multiple
                    hidden
                    data-media-upload-input
                >
                <span class="admin-media-dropzone__icon" aria-hidden="true">＋</span>
                <strong>تصاویر را اینجا رها کنید یا انتخاب کنید</strong>
                <small>JPG، PNG، WebP یا AVIF — حداکثر ۵ مگابایت برای هر تصویر</small>
                <span class="admin-media-upload-count" data-media-upload-count></span>
            </label>

            <div class="admin-media-upload-preview" data-media-upload-preview></div>

            <button type="submit" class="admin-btn admin-btn--secondary" data-media-upload-submit disabled>
                افزودن تصاویر
            </button>
        </form>

        @if($media->count())
            <div class="admin-media-grid" data-media-sortable>
                @foreach($media as $item)
                    <article class="admin-media-item" draggable="true" data-media-id="{{ $item->id }}">
                        <div class="admin-media-item__image">
                            <img src="{{ $item->url }}" alt="{{ $item->alt_text ?: $product->name }}" loading="lazy">
                            @if($loop->first)
                                <span class="admin-media-item__primary">تصویر اصلی</span>
                            @endif
                        </div>

                        <div class="admin-media-item__body">
                            <div class="admin-media-item__name" title="{{ $item->original_name }}">
                                {{ $item->original_name ?: 'تصویر محصول' }}
                            </div>

                            <form method="POST" action="{{ route('admin.products.media.update', [$product, $item]) }}">
                                @csrf
                                @method('PATCH')
                                <label class="admin-field">
                                    <span>متن جایگزین</span>
                                    <input
                                        type="text"
                                        name="alt_text"
                                        maxlength="180"
                                        value="{{ old('alt_text', $item->alt_text) }}"
                                        placeholder="مثلاً لباس خواب ساتن قرمز"
                                    >
                                </label>
                                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">
                                    ذخیره
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('admin.products.media.destroy', [$product, $item]) }}"
                                onsubmit="return confirm('این تصویر حذف شود؟');"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">
                                    حذف تصویر
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="admin-help admin-media-manager__hint">
                برای تغییر تصویر اصلی، کارت موردنظر را به ابتدای گالری بکشید. ترتیب به‌صورت خودکار ذخیره می‌شود.
            </p>
        @else
            <div class="admin-empty-state">
                <strong>هنوز تصویری برای این محصول ثبت نشده است.</strong>
                <span>اولین تصویر آپلودشده به‌عنوان تصویر اصلی فروشگاه استفاده می‌شود.</span>
            </div>
        @endif
    </section>
@endsection
