@extends('commerce-admin::layout')
@section('title', 'افزودن برند')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.brands.index') }}">برندها</a><span>/</span>ایجاد</div><h1>افزودن برند</h1></div></div>
@include('commerce-admin::brands.form', ['editing' => false, 'action' => route('commerce-admin.brands.store')])
@endsection
