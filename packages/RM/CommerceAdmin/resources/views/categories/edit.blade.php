@extends('commerce-admin::layout')
@section('title', 'ویرایش دسته‌بندی')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.categories.index') }}">دسته‌بندی‌ها</a><span>/</span>ویرایش</div><h1>ویرایش {{ $record->name }}</h1></div></div>
@include('commerce-admin::categories.form', ['editing' => true, 'action' => route('commerce-admin.categories.update', $record->getKey())])
@endsection
