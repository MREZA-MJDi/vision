@extends('layouts.admin')

@section('title', 'ویرایش واریانت')
@section('page-title', 'ویرایش واریانت')

@section('content')

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                ویرایش واریانت
            </h1>

            <p class="admin-page-head__text">
                {{ $product->name }}
                —
                {{ $variant->sku ?: 'کد خودکار' }}
            </p>

        </div>

        <a
            href="{{ route('admin.products.variants.index', $product) }}"
            class="admin-btn admin-btn--ghost"
        >
            بازگشت
        </a>

    </div>


    <form
        method="POST"
        action="{{ route('admin.products.variants.update', [$product, $variant]) }}"
    >

        @csrf
        @method('PUT')

        @include('admin.variants._form', [
            'product' => $product,
            'variant' => $variant,
        ])

    </form>

    @include('admin.components.media-uploader', [
        'mediaItem' => $variant->galleryMedia->first(),
        'uploadType' => 'variant',
        'uploadId' => $variant->id,
        'title' => 'تصویر اختصاصی همین واریانت',
        'description' => 'این عکس فقط برای همین ترکیب رنگ و سایز است و از تصویر اصلی محصول جدا نمایش داده می‌شود.',
        'videoAllowed' => false,
        'accept' => 'image/jpeg,image/png,image/webp,image/avif',
    ])


@endsection
