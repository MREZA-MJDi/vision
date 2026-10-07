@extends('layouts.admin')

@section('title', 'افزودن دسته‌بندی')
@section('page-title', 'افزودن دسته‌بندی')

@section('content')

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                افزودن دسته‌بندی
            </h1>

            <p class="admin-page-head__text">
                ایجاد دسته‌بندی جدید برای فروشگاه
            </p>

        </div>

        <a
            href="{{ route('admin.categories.index') }}"
            class="admin-btn admin-btn--ghost"
        >
            بازگشت
        </a>

    </div>


    <form
        method="POST"
        action="{{ route('admin.categories.store') }}"
        enctype="multipart/form-data"
    >

        @csrf

        @include('admin.categories._form', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ])

    </form>

@endsection
