@extends('layouts.admin')

@section('title', 'ویرایش محصول')
@section('page-title', 'ویرایش محصول')

@section('content')

    @php
        $variant = $product->variants->first();
    @endphp


    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}

    <div class="admin-page-head">

        <div>
            <h2 class="admin-page-head__title">
                ویرایش محصول
            </h2>

            <p class="admin-page-head__text">
                {{ $product->name }}
            </p>
        </div>

        <div class="admin-actions">
            <a href="{{ route('admin.products.variants.index', $product) }}" class="admin-btn admin-btn--secondary admin-btn--sm">
                مدیریت رنگ‌ها و سایزها
            </a>
            <a href="{{ route('admin.products.media.index', $product) }}" class="admin-btn admin-btn--ghost admin-btn--sm">
                مدیریت تصاویر محصول
            </a>
            <a href="{{ route('admin.products.index') }}" class="admin-btn admin-btn--ghost admin-btn--sm">بازگشت</a>
        </div>

    </div>


    {{-- =========================================================
         VALIDATION ERRORS
    ========================================================== --}}

    @if($errors->any())

        <div class="alert error">

            <strong>
                اطلاعات واردشده نیاز به بررسی دارد.
            </strong>

            <ul
                style="
                margin:8px 0 0;
                padding-right:18px;
            "
            >
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif


    {{-- =========================================================
         FORM
    ========================================================== --}}

    <form
        method="POST"
        action="{{ route('admin.products.update', $product) }}"
        enctype="multipart/form-data"
    >

        @csrf
        @method('PUT')


        {{-- Product form --}}
        @include('admin.products._form', [
            'product' => $product,
            'variant' => $variant,
        ])

        <div class="admin-form-section admin-form-section-full">
            @include('admin.products._media-manager-inline', ['product' => $product])
        </div>


        {{-- =====================================================
             ACTIONS
        ====================================================== --}}

        <div class="admin-form-actions">

            <button
                type="submit"
                class="admin-btn admin-btn--secondary"
            >
                ذخیره تغییرات
            </button>

            <a
                href="{{ route('admin.products.index') }}"
                class="admin-btn admin-btn--ghost"
            >
                انصراف
            </a>

        </div>

    </form>


@endsection
