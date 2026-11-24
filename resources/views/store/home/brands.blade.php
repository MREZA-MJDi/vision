@if($homeBrands->isNotEmpty())
    <section class="home-content-section home-brand-editorial" aria-labelledby="home-brands-title">
        <header class="home-editorial-heading">
            <span lang="en" dir="ltr">THE BRAND EDIT</span>
            <h2 id="home-brands-title">دنیای برندها</h2>
            <p>برندها را کشف کنید و استایل دلخواهتان را پیدا کنید.</p>
        </header>
        <div class="home-brand-composition">
            @foreach($homeBrands->chunk(3) as $group)
                <div class="home-brand-triptych" style="--brand-count: {{ $group->count() }}">
                    @foreach($group as $brand)
                        @php($image = $brand->logoMedia?->url)
                        <a class="home-brand-shard" href="{{ route('shop.index', ['brand' => $brand->slug]) }}#catalog">
                            <span class="home-brand-shard__visual">
                                @if($image)
                                    <img src="{{ $image }}" alt="" loading="lazy" decoding="async">
                                @else
                                    <span class="home-brand-shard__initial">{{ mb_substr($brand->name, 0, 2) }}</span>
                                @endif
                            </span>
                            <strong>{{ $brand->name }}</strong>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
@endif
