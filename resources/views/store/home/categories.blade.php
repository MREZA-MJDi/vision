@if($homeCategories->isNotEmpty())
    <section class="home-content-section home-category-editorial" aria-labelledby="home-categories-title">
        <header class="home-editorial-heading">
            <span lang="en" dir="ltr">FIND YOUR STYLE</span>
            <h2 id="home-categories-title">استایل خودت را پیدا کن</h2>
            <p>از میان دسته‌بندی‌های فروشگاه انتخاب کن.</p>
        </header>
        <div class="home-category-mosaic">
            @foreach($homeCategories as $category)
                @php($image = $category->coverMedia?->url)
                <a class="home-category-tile home-category-mosaic__tile" href="{{ route('shop.index', ['category' => $category->slug]) }}#catalog">
                    @if($image)
                        <img src="{{ $image }}" loading="lazy" decoding="async" alt="">
                    @endif
                    <span class="home-category-mosaic__number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <strong>{{ $category->name }}</strong>
                </a>
            @endforeach
        </div>
    </section>
@endif
