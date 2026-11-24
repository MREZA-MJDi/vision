@if($heroGroups->count() > 1)
    <div class="home-hero__products" role="group" aria-label="محصولات منتخب اسلایدر">
        @foreach($heroGroups as $productId => $variants)
            @php($preview = $variants->first())
            <button type="button" class="home-hero__thumbnail store-media store-media--thumb" data-home-hero-product="{{ $productId }}"
                    title="{{ $preview['title'] }}" aria-label="{{ $preview['title'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                @if($preview['image'])
                    <img src="{{ $preview['image'] }}" alt="" width="54" height="54" decoding="async">
                @else
                    <span>{{ $loop->iteration }}</span>
                @endif
            </button>
        @endforeach
    </div>
@endif