@extends('layouts.admin')

@section('title', 'ایجاد محصول')
@section('page-title', 'ایجاد محصول')

@section('content')

    <div class="admin-page-head">

        <div>
            <h1 class="admin-page-head__title">
                ایجاد محصول
            </h1>

            <p class="admin-page-head__text">
                محصول جدید را با اطلاعات، قیمت، موجودی و تصویر ثبت کنید.
            </p>
        </div>

        <a
            href="{{ route('admin.products.index') }}"
            class="admin-btn admin-btn--ghost admin-btn--sm"
        >
            بازگشت به محصولات
        </a>

    </div>


    @if($errors->any())

        <div class="alert error">

            <strong>
                اطلاعات واردشده نیاز به بررسی دارد.
            </strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route('admin.products.store') }}"
        enctype="multipart/form-data"
    >

        @csrf

        @include('admin.products._form', [
            'product' => $product,
            'variant' => $variant,
            'categories' => $categories,
            'brands' => $brands,
        ])

        <div class="admin-form-actions">

            <button
                type="submit"
                class="admin-btn admin-btn--secondary"
            >
                ایجاد محصول
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
