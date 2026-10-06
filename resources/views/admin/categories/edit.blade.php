@extends('layouts.admin')

@section('title', 'ویرایش دسته‌بندی')
@section('page-title', 'ویرایش دسته‌بندی')

@section('content')

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                ویرایش دسته‌بندی
            </h1>

            <p class="admin-page-head__text">
                {{ $category->name }}
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
        action="{{ route('admin.categories.update', $category) }}"
        enctype="multipart/form-data"
    >

        @csrf
        @method('PUT')

        @include('admin.categories._form', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ])

    </form>

@endsection
