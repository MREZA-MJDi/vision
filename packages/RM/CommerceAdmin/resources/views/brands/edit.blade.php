@extends('commerce-admin::layout')
@section('title', 'ویرایش برند')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.brands.index') }}">برندها</a><span>/</span>ویرایش</div><h1>ویرایش {{ $record->name }}</h1></div></div>
@include('commerce-admin::brands.form', ['editing' => true, 'action' => route('commerce-admin.brands.update', $record->getKey())])
@endsection
