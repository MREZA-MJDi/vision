@php
    $edit = $editorialProducts->isNotEmpty() ? $editorialProducts : $heroProducts;
    $lead = $edit->first();
    $variant = $lead?->primaryAvailableVariant ?? $lead?->primaryActiveVariant;
    $image = $variant?->primaryGalleryMedia?->url ?: $lead?->primaryGalleryMedia?->url;
@endphp
@if($lead && $image)
<section class="home-content-section home-feature-edit" aria-labelledby="home-feature-title">
    <div class="home-feature-edit__frame">
        <a class="home-feature-edit__image" href="{{ route('products.show', ['product' => $lead->slug, 'variant_id' => $variant?->getKey()]) }}">
            <img src="{{ $image }}" alt="{{ $lead->name }}" loading="lazy" decoding="async">
        </a>
        <div class="home-feature-edit__copy">
            <span class="home-editorial-kicker" lang="en" dir="ltr">THE VISION EDIT</span>
            <h2 id="home-feature-title">انتخاب VISION</h2>
            <h3>{{ $lead->name }}</h3>
            @if($lead->short_description)<p>{{ \Illuminate\Support\Str::limit($lead->short_description, 180) }}</p>@endif
            @if($variant)<strong>@money($variant->effective_price)</strong>@endif
            <a class="home-hero__cta" href="{{ route('products.show', ['product' => $lead->slug, 'variant_id' => $variant?->getKey()]) }}">مشاهده محصول</a>
        </div>
    </div>
    @if($edit->count() > 1)
        <div class="home-merchandise-grid home-feature-edit__more">
            @foreach($edit->slice(1, 3) as $product)
                @include('store.home-product-card', ['product' => $product])
            @endforeach
        </div>
    @endif
</section>
@endif
