@extends('layouts.store')

@section('title', 'اتاق پرو هوشمند — VISION')
@section('meta-description', 'ترکیب پوشاک روی مانکن ثابت و بررسی استایل پیش از انتخاب محصول در فروشگاه VISION.')

@section('content')
<main class="store-main">
    @include('store.partials.virtual-try-on-workspace', [
        'productPage' => false,
        'tryOnInitialVariantId' => null,
        'tryOnInitialAudience' => 'women',
    ])
</main>
@endsection
