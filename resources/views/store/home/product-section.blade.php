@if($collection->isNotEmpty())
<section class="home-content-section home-merchandise" aria-labelledby="{{ $sectionId }}-title">
    <header class="home-content-heading">
        <div>
            <span class="home-editorial-kicker" lang="en" dir="ltr">{{ $eyebrow }}</span>
            <h2 id="{{ $sectionId }}-title">{{ $heading }}</h2>
            <p>{{ $description }}</p>
        </div>
        <a class="home-section-link" href="{{ $destination }}#catalog">مشاهده همه <svg class="home-section-link__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="3.5" width="6" height="6" rx="1"/><rect x="14.5" y="3.5" width="6" height="6" rx="1"/><rect x="3.5" y="14.5" width="6" height="6" rx="1"/><rect x="14.5" y="14.5" width="6" height="6" rx="1"/></svg></a>
    </header>
    <div class="home-merchandise-grid">
        @foreach($collection as $product)
            @include('store.home-product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif
