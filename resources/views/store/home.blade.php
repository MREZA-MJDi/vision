@extends('store.layout')

@section('title', 'VISION — Wear Your Point of View')
@section('description', 'Discover statement fashion, modern footwear and bags curated for your own point of view.')

@section('content')
    <section class="fashion-hero" aria-labelledby="hero-title">
        <div class="fashion-hero__copy">
            <span class="eyebrow"><span class="eyebrow__line"></span> THE NEW POINT OF VIEW / 026</span>
            <h1 id="hero-title">OWN<br><em>THE</em> MOMENT<span class="hero-period">.</span></h1>
            <p class="fashion-hero__lead">Pieces that speak before you do. A new edit of elevated essentials, statement shoes and bags made to be seen.</p>
            <div class="fashion-hero__actions">
                <a class="button button--dark" href="{{ route('products.index') }}">SHOP THE EDIT <span aria-hidden="true">↗</span></a>
                <a class="text-link" href="#new-in">Discover the collection <span aria-hidden="true">↓</span></a>
            </div>
            <div class="fashion-hero__foot">
                <span>01 — 04 / EDITORIAL COLLECTION</span>
                <span class="hero-orbit" aria-hidden="true">V.</span>
            </div>
        </div>
        <div class="fashion-hero__visual">
            @php
                $heroProduct = $heroProducts->first();
                $heroImage = $heroProduct?->primaryGalleryMedia?->url;
            @endphp
            <div class="hero-image hero-image--main">
                @if($heroImage)
                    <img src="{{ $heroImage }}" alt="{{ $heroProduct->name }}" fetchpriority="high">
                @else
                    <img src="https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&fit=crop&w=1200&q=85" alt="Fashion editorial look" fetchpriority="high">
                @endif
                <div class="hero-image__label"><span>VISION / STUDIO</span><span>FW — 026</span></div>
            </div>
            <div class="hero-image hero-image--detail">
                <img src="https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=680&q=80" alt="Statement heels editorial" loading="lazy">
                <span>THE ART OF DETAILS</span>
            </div>
            <div class="hero-stamp"><span>NEW SEASON</span><strong>NO<br>RULES.</strong><span>ONLY YOURS</span></div>
            <span class="visual-index">V / 01</span>
        </div>
        <a href="#category-edit" class="scroll-cue"><span></span> SCROLL TO EXPLORE</a>
    </section>

    <div class="ticker" aria-label="Vision collection message">
        <div class="ticker__track"><span>STYLE IS A STATEMENT</span><b>✳</b><span>MAKE IT YOURS</span><b>✳</b><span>STYLE IS A STATEMENT</span><b>✳</b><span>MAKE IT YOURS</span><b>✳</b></div>
    </div>

    <section id="category-edit" class="section category-section">
        <div class="section-heading">
            <div><span class="eyebrow">01 / FIND YOUR FORM</span><h2>THE <em>EDIT</em></h2></div>
            <a class="text-link" href="{{ route('products.index') }}">VIEW ALL CATEGORIES ↗</a>
        </div>
        <div class="category-grid">
            @php
                $categoryImages = [
                    'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1551232864-3f0890e580d9?auto=format&fit=crop&w=800&q=80',
                ];
            @endphp
            @forelse($categories as $category)
                <a class="category-card" href="{{ route('products.index', ['category' => $category->slug]) }}">
                    <div class="category-card__image">
                        @if($category->coverMedia?->url)
                            <img src="{{ $category->coverMedia->url }}" alt="{{ $category->name }}" loading="lazy">
                        @else
                            <img src="{{ $categoryImages[$loop->index % count($categoryImages)] }}" alt="{{ $category->name }}" loading="lazy">
                        @endif
                        <span class="category-card__number">0{{ $loop->iteration }}</span>
                        <span class="category-card__arrow" aria-hidden="true">↗</span>
                    </div>
                    <div class="category-card__meta"><h3>{{ $category->name }}</h3><span>EXPLORE THE EDIT</span></div>
                </a>
            @empty
                @foreach([['Shoes','STEP INTO IT'],['Bags','CARRY YOURSELF'],['Clothing','DRESS DIFFERENTLY']] as $i => $item)
                    <a class="category-card" href="{{ route('products.index') }}">
                        <div class="category-card__image">
                            <img src="{{ $categoryImages[$i] }}" alt="{{ $item[0] }}" loading="lazy">
                            <span class="category-card__number">0{{ $i + 1 }}</span><span class="category-card__arrow">↗</span>
                        </div>
                        <div class="category-card__meta"><h3>{{ $item[0] }}</h3><span>{{ $item[1] }}</span></div>
                    </a>
                @endforeach
            @endforelse
        </div>
    </section>

    <section id="new-in" class="section product-section">
        <div class="section-heading">
            <div><span class="eyebrow">02 / JUST LANDED</span><h2>NEW <em>ENERGY</em></h2></div>
            <a class="text-link" href="{{ route('products.index', ['sort' => 'newest']) }}">SHOP NEW IN ↗</a>
        </div>
        <div class="product-grid">
            @forelse($newProducts as $product)
                @include('store.products._card', ['product' => $product])
            @empty
                @foreach([
                    ['The Sculpted Heel','Shoes','https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=900&q=80'],
                    ['The Everyday Icon','Bags','https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=900&q=80'],
                    ['The Power Blazer','Clothing','https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&fit=crop&w=900&q=80'],
                    ['The Mini Statement','Bags','https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=900&q=80'],
                ] as $sample)
                    <article class="product-card product-card--sample">
                        <a class="product-card__image" href="{{ route('products.index') }}"><img src="{{ $sample[2] }}" alt="{{ $sample[0] }}" loading="lazy"><span class="product-card__tag">THE EDIT</span><span class="product-card__quick">DISCOVER ↗</span></a>
                        <div class="product-card__meta"><div><span>{{ $sample[1] }}</span><h3>{{ $sample[0] }}</h3></div><strong>Explore</strong></div>
                    </article>
                @endforeach
            @endforelse
        </div>
    </section>

    <section class="campaign">
        <div class="campaign__image"><img src="https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1600&q=85" alt="Vision fashion campaign" loading="lazy"></div>
        <div class="campaign__shade"></div>
        <div class="campaign__content"><span class="eyebrow">VISION CAMPAIGN / 026</span><h2>NOT MADE<br>TO <em>BLEND IN.</em></h2><p>For the nights you remember. For the version of you that takes up space.</p><a class="button button--light" href="{{ route('products.index') }}">FIND YOUR STATEMENT <span>↗</span></a></div>
        <span class="campaign__side">WEAR YOUR POINT OF VIEW — VISION STUDIO</span>
    </section>

    <section class="section product-section featured-section">
        <div class="section-heading">
            <div><span class="eyebrow">03 / THE VISION SELECTION</span><h2>ICONIC <em>BY DESIGN</em></h2></div>
            <a class="text-link" href="{{ route('products.index') }}">VIEW THE SELECTION ↗</a>
        </div>
        <div class="product-grid">
            @forelse($featuredProducts as $product)
                @include('store.products._card', ['product' => $product])
            @empty
                <div class="empty-editorial"><span>V / CURATION</span><h3>Your next favourite<br><em>is waiting.</em></h3><a href="{{ route('products.index') }}">Explore the collection ↗</a></div>
            @endforelse
        </div>
    </section>

    <section class="newsletter">
        <div><span class="eyebrow">THE VISION LETTER / NO SPAM, JUST STYLE</span><h2>FIRST TO KNOW.<br><em>FIRST TO WEAR.</em></h2></div>
        <form class="newsletter__form" action="#" method="get" data-newsletter-form>
            <label for="newsletter-email">A LITTLE NOTE FROM US, STRAIGHT TO YOU.</label>
            <div><input id="newsletter-email" type="email" placeholder="Your email address" required><button type="submit" aria-label="Subscribe">↗</button></div>
            <p data-newsletter-message>By joining, you agree to receive the occasional VISION edit.</p>
        </form>
    </section>
@endsection
