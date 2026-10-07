@extends('layouts.admin')

@section('title', 'افزودن برند')
@section('page-title', 'افزودن برند')

@section('content')

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                افزودن برند
            </h1>

            <p class="admin-page-head__text">
                ثبت یک برند جدید برای فروشگاه
            </p>

        </div>

        <a
            href="{{ route('admin.brands.index') }}"
            class="admin-btn admin-btn--ghost"
        >
            بازگشت
        </a>

    </div>


    <form
        method="POST"
        action="{{ route('admin.brands.store') }}"
        enctype="multipart/form-data"
    >

        @csrf

        @include('admin.brands._form', [
            'brand' => $brand,
        ])

    </form>

@endsection
