@if($homeCategories->isNotEmpty())
    <section class="home-content-section home-category-editorial" aria-labelledby="home-categories-title">
        <div class="home-category-glass">
            <header class="home-editorial-heading">
                <span lang="en" dir="ltr">THE COLLECTION EDIT</span>
                <h2 id="home-categories-title">استایل خودت را پیدا کن</h2>
                <p>کالکشن‌های فروشگاه، برای هر سلیقه و هر روز.</p>
            </header>

            <div class="home-category-bento">
                @foreach($homeCategories->chunk(3) as $group)
                    <div class="home-category-bento__group{{ $loop->even ? ' home-category-bento__group--reverse' : '' }} home-category-bento__group--{{ $group->count() }}">
                        @foreach($group as $category)
                            @php($image = $category->coverMedia?->url)
                            <a class="home-category-tile home-category-mosaic__tile home-category-bento__tile{{ $loop->first ? ' home-category-bento__tile--lead' : '' }}"
                               href="{{ route('shop.index', ['category' => $category->slug]) }}#catalog">
                                @if($image)
                                    <img src="{{ $image }}" loading="lazy" decoding="async" alt="">
                                @endif
                                <span class="vision-category-card__caption">
                                    <small>کالکشن VISION</small>
                                    <strong>{{ $category->name }}</strong>
                                    <span>مشاهده مجموعه <span aria-hidden="true">←</span></span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
