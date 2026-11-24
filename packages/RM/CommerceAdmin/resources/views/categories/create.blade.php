@extends('commerce-admin::layout')
@section('title', 'افزودن دسته‌بندی')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.categories.index') }}">دسته‌بندی‌ها</a><span>/</span>ایجاد</div><h1>افزودن دسته‌بندی</h1></div></div>
@include('commerce-admin::categories.form', ['editing' => false, 'action' => route('commerce-admin.categories.store')])
@endsection
