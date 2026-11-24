@if($collection->isNotEmpty())
<section class="home-content-section home-merchandise" aria-labelledby="{{ $sectionId }}-title">
    <header class="home-content-heading">
        <div>
            <span class="home-editorial-kicker" lang="en" dir="ltr">{{ $eyebrow }}</span>
            <h2 id="{{ $sectionId }}-title">{{ $heading }}</h2>
            <p>{{ $description }}</p>
        </div>
        <a class="home-section-link" href="{{ $destination }}#catalog">مشاهده همه <span aria-hidden="true">←</span></a>
    </header>
    <div class="home-merchandise-grid">
        @foreach($collection as $product)
            @include('store.home-product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif
