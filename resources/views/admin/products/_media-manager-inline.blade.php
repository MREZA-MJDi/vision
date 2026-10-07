<section class="admin-card admin-media-manager" data-product-media-manager>
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">تصاویر محصول</h2>
            <p class="admin-card-description">
                مدیریت کامل تصاویر توسط جانان؛ نیلا و هلو به تصاویر این محصول دسترسی نوشتاری ندارند.
            </p>
        </div>
        <a href="{{ route('admin.products.media.index', $product) }}" class="admin-btn admin-btn--ghost admin-btn--sm">
            مدیریت کامل گالری
        </a>
    </div>

    <div class="admin-media-inline-grid">
        @forelse($product->galleryMedia as $item)
            <div class="admin-media-inline-item">
                <img src="{{ $item->url }}" alt="{{ $item->alt_text ?: $product->name }}" loading="lazy">
                @if($loop->first)
                    <span class="admin-media-item__primary">اصلی</span>
                @endif
            </div>
        @empty
            <div class="admin-empty-state">
                <strong>هنوز تصویر ثبت نشده است.</strong>
                <span>از مدیریت کامل گالری برای آپلود تصاویر استفاده کنید.</span>
            </div>
        @endforelse
    </div>
</section>