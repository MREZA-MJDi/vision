<section class="home-content-section home-trust" aria-labelledby="home-trust-title">
    <h2 id="home-trust-title" class="home-trust-title">با اطمینان خرید کن</h2>
    <div class="home-trust-grid">
        @foreach(['selection' => route('shop.index'), 'tracking' => route('account.show'), 'support' => route('contact.index')] as $slot => $url)
            <a class="home-trust-feature" href="{{ $url }}">
                <img src="{{ $trustImages[$slot]['url'] }}" width="96" height="96" loading="lazy" decoding="async" alt="">
                <h3>{{ $trustImages[$slot]['label'] }}</h3>
            </a>
        @endforeach
    </div>
</section>
