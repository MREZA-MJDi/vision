@extends('layouts.app')

@section('title', 'VISION — فروشگاه')

@section('content')
    <main class="vision-home">
        @php
            $slide = $slides[0] ?? null;
        @endphp

        <section class="vision-hero" aria-labelledby="vision-hero-title">
            <div class="vision-hero__inner">
                <div class="vision-hero__copy">
                    <div class="vision-hero__eyebrow">
                        VISION / NEW COLLECTION
                    </div>

                    <h1 id="vision-hero-title" class="vision-hero__title">
                        {{ $slide['title'] ?? 'VISION' }}
                        <span>{{ $slide['brand'] ?? 'NEW FORM' }}</span>
                    </h1>

                    <p class="vision-hero__description">
                        {{ $slide['description'] ?? 'A considered collection built around form, material and movement.' }}
                    </p>

                    <div class="vision-hero__actions">
                        <a class="vision-hero__action" href="#collection">
                            مشاهده کالکشن
                        </a>
                    </div>
                </div>

                <div class="vision-hero__visual">
                    @if($slide && $slide['image'])
                        <x-image-hover
                            :image="$slide['image']"
                            :alt="$slide['title'] ?? 'VISION'"
                        />
                    @else
                        <div class="vision-image-hover" aria-hidden="true"></div>
                    @endif

                    <div class="vision-hero__meta" aria-hidden="true">
                        <span class="vision-hero__counter">
                            {{ $slide['number'] ?? '01' }} / {{ str_pad((string) max(count($slides), 1), 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span>Explore the form</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="collection" style="min-height: 60vh; display:grid; place-items:center; padding:80px 24px; background:#0b0b0b;">
            <p style="margin:0; color:rgba(255,255,255,.42); font-size:12px; letter-spacing:.16em; text-transform:uppercase;">
                Collection follows
            </p>
        </section>
    </main>
@endsection
