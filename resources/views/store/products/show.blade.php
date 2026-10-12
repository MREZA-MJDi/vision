@extends('store.layout')

@section('title', $product->meta_title ?: $product->name . ' — VISION')
@section('description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description ?: 'Discover the latest VISION edit.'), 155))

@section('content')
    <div class="product-crumb"><a href="{{ route('home') }}">HOME</a><span>/</span><a href="{{ route('products.index') }}">SHOP THE EDIT</a><span>/</span><span>{{ $product->name }}</span></div>
    <section class="product-detail">
        <div class="product-detail__gallery">
            @forelse($product->galleryMedia as $media)
                <div class="product-detail__image"><img src="{{ $media->url }}" alt="{{ $media->alt_text ?: $product->name }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}"></div>
            @empty
                <div class="product-detail__image product-detail__image--empty"><span>VISION<br>STUDIO</span></div>
            @endforelse
        </div>
        <div class="product-detail__info">
            <span class="eyebrow">{{ $product->brand?->name ?? 'VISION STUDIO' }} / COLLECTION 026</span>
            <h1>{{ $product->name }}</h1>
            @if($product->short_description)<p class="product-detail__lead">{{ $product->short_description }}</p>@endif
            @php($defaultVariant = $product->activeVariants->first())
            <div class="product-detail__price" data-product-price>
                @if($defaultVariant)
                    @if($defaultVariant->is_on_sale)<del>{{ number_format((float) $defaultVariant->price, 0) }}</del>@endif
                    <strong>{{ number_format((float) $defaultVariant->effective_price, 0) }} <small>IRR</small></strong>
                @else
                    <strong>COMING SOON</strong>
                @endif
            </div>
            @if($product->category)<div class="product-detail__category">CATEGORY <span>{{ $product->category->name }}</span></div>@endif
            @if($product->activeVariants->isNotEmpty())
                <form class="product-buy" action="#" method="post" data-product-buy>
                    <label for="variant-select">SELECT SIZE / COLOUR</label>
                    <select id="variant-select" name="variant" data-variant-select required>
                        @foreach($product->activeVariants as $variant)
                            <option value="{{ $variant->id }}" data-price="{{ $variant->effective_price }}" data-original-price="{{ $variant->price }}" data-sale="{{ $variant->is_on_sale ? '1' : '0' }}" data-stock="{{ $variant->stock }}" @selected($loop->first) @disabled($variant->stock < 1)>
                                {{ implode(' / ', array_filter([$variant->size, $variant->color])) ?: ($variant->sku ?: 'Standard') }}{{ $variant->stock < 1 ? ' — SOLD OUT' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="product-stock" data-product-stock>{{ $defaultVariant->stock > 0 ? 'IN STOCK — READY FOR YOUR EDIT' : 'CURRENTLY UNAVAILABLE' }}</p>
                    <button type="button" class="button button--dark product-buy__button" data-demo-add-to-bag @disabled(!$defaultVariant || $defaultVariant->stock < 1)>ADD TO BAG <span>↗</span></button>
                    <p class="product-buy__notice" data-buy-notice role="status">Bag and checkout need to be connected to the existing cart/order workflow before live sales.</p>
                </form>
            @else
                <div class="product-unavailable">This piece is being prepared. Check back soon.</div>
            @endif
            <div class="product-details-accordion">
                <details open><summary>THE STORY <span>＋</span></summary><div>{{ $product->description ?: $product->short_description ?: 'A considered piece from the VISION selection.' }}</div></details>
                <details><summary>SHIPPING & RETURNS <span>＋</span></summary><div>Shipping options and return terms should be configured for the store before launch.</div></details>
                <details><summary>PRODUCT DETAILS <span>＋</span></summary><div>Stock is shown from the selected product variant. Availability can change as inventory is updated.</div></details>
            </div>
            <a class="text-link product-detail__back" href="{{ route('products.index') }}">← BACK TO THE EDIT</a>
        </div>
    </section>
    @if($relatedProducts->isNotEmpty())
        <section class="section product-section">
            <div class="section-heading"><div><span class="eyebrow">YOU MAY ALSO LIKE</span><h2>MORE TO <em>LOVE.</em></h2></div><a class="text-link" href="{{ route('products.index') }}">SHOP ALL ↗</a></div>
            <div class="product-grid">@foreach($relatedProducts as $product)@include('store.products._card', ['product' => $product])@endforeach</div>
        </section>
    @endif
@endsection
