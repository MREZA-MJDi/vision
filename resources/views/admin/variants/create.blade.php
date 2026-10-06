@extends('layouts.admin')

@section('title', 'افزودن واریانت')
@section('page-title', 'افزودن واریانت')

@section('content')

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                افزودن واریانت
            </h1>

            <p class="admin-page-head__text">
                {{ $product->name }} — هر ترکیب رنگ و سایز یک گزینهٔ جداست؛ تکرار رنگ اشکالی ندارد. مثلاً «قرمز / S» و «قرمز / M» دو واریانت‌اند.
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
        action="{{ route('admin.products.variants.store', $product) }}"
    >

        @csrf

        @include('admin.variants._form', [
            'product' => $product,
            'variant' => $variant ?? new \App\Models\ProductVariant(),
        ])

    </form>

    <section class="admin-card admin-variant-image-note">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">عکس همین رنگ یا سایز</h2>
                <p class="admin-card-description">بعد از ساخت واریانت، از فهرست واریانت‌ها وارد «ویرایش» همین گزینه شو و بخش «تصویر اختصاصی واریانت» را جدا از عکس اصلی محصول پر کن.</p>
            </div>
        </div>
    </section>

@endsection
