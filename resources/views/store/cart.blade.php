@extends('layouts.store')

@section('title', 'سبد خرید — VISION')
@section('meta-description', 'محصولات انتخاب‌شدهٔ خود را بررسی و برای ثبت سفارش ادامه دهید.')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">انتخاب شما</p>
            <h1>سبد خرید<span>.</span></h1>
            <p>قیمت و موجودی کالاها پیش از ثبت نهایی سفارش، دوباره در سرور بررسی می‌شوند.</p>
        </div>
        <a class="commerce-text-link" href="{{ route('products.index') }}">ادامهٔ خرید ↗</a>
    </div>

    @if(session('status'))
        <p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>
    @endif
    @if(session('error'))
        <p class="commerce-alert commerce-alert--error" role="alert">{{ session('error') }}</p>
    @endif
    @if($errors->any())
        <div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if($items->isEmpty())
        <section class="commerce-empty">
            <span class="commerce-empty__mark">V.</span>
            <h2>سبد خرید شما خالی است.</h2>
            <p>محصولات فروشگاه را ببین و گزینه‌های موردعلاقه‌ات را به سبد اضافه کن.</p>
            <a class="commerce-button commerce-button--primary" href="{{ route('products.index') }}">مشاهدهٔ محصولات ↗</a>
        </section>
    @else
        <div class="commerce-layout">
            <section class="commerce-panel commerce-cart-items" aria-label="کالاهای سبد خرید">
                <div class="commerce-panel-heading"><h2>کالاهای انتخاب‌شده</h2><span>{{ number_format($summary['itemCount']) }} کالا</span></div>

                @foreach($items as $item)
                    @php
                        $variant = $item->productVariant;
                        $product = $variant?->product;
                        $image = $variant?->primaryGalleryMedia?->url ?? $product?->primaryGalleryMedia?->url;
                    @endphp
                    <article class="commerce-cart-row">
                        @if($image)
                            <img class="commerce-cart-row__image" src="{{ $image }}" alt="{{ $product?->name ?? 'تصویر محصول' }}" loading="lazy">
                        @else
                            <div class="commerce-cart-row__image commerce-cart-row__image--empty" aria-hidden="true">V.</div>
                        @endif
                        <div class="commerce-cart-row__details">
                            <div class="commerce-cart-row__title">
                                <div>
                                    <h3>{{ $product?->name ?? 'محصول ناموجود' }}</h3>
                                    <p>{{ $variant?->display_name ?? 'این وریانت دیگر در دسترس نیست' }}</p>
                                </div>
                                <strong>@money($item->display_line_total)</strong>
                            </div>
                            @if(!$item->checkout_available)
                                <p class="commerce-inline-error">این کالا ناموجود است یا تعداد انتخاب‌شده از موجودی بیشتر است. کالا را حذف کن یا تعداد را تغییر بده.</p>
                            @else
                                <p class="commerce-muted">هر عدد @money($item->display_unit_price) · موجودی: {{ number_format((int) $variant->stock) }} عدد</p>
                            @endif
                            <div class="commerce-cart-row__actions">
                                @if($variant && $product)
                                    <form method="POST" action="{{ route('cart.items.update', $item->getKey()) }}" class="commerce-quantity-form">
                                        @csrf @method('PATCH')
                                        <label for="cart-quantity-{{ $item->getKey() }}">تعداد</label>
                                        <input id="cart-quantity-{{ $item->getKey() }}" type="number" name="quantity" min="1" max="100" value="{{ old('quantity', $item->quantity) }}" required>
                                        <button class="commerce-button commerce-button--quiet" type="submit">بروزرسانی</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('cart.items.destroy', $item->getKey()) }}">
                                    @csrf @method('DELETE')
                                    <button class="commerce-text-button" type="submit">حذف</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="commerce-panel commerce-summary">
                <div class="commerce-panel-heading"><h2>خلاصهٔ سفارش</h2><span>{{ config('commerce-admin.currency_symbol', 'تومان') }}</span></div>
                <div class="commerce-summary__lines">
                    <div><span>جمع کالاها</span><strong>@money($summary['subtotal'])</strong></div>
                    <div><span>مالیات</span><strong>@money($summary['tax'])</strong></div>
                    <div><span>هزینهٔ ارسال</span><strong>@money($summary['shipping'])</strong></div>
                    <div class="commerce-summary__total"><span>مبلغ نهایی</span><strong>@money($summary['total'])</strong></div>
                </div>
                @if($summary['allAvailable'])
                    <a class="commerce-button commerce-button--primary commerce-button--wide" href="{{ route('checkout.index') }}">ادامه برای ثبت سفارش ↗</a>
                @else
                    <button class="commerce-button commerce-button--disabled commerce-button--wide" type="button" disabled>ابتدا کالاهای ناموجود را اصلاح کن</button>
                @endif
                <p class="commerce-muted">قیمت نهایی، مالیات و موجودی هنگام ثبت سفارش دوباره بررسی می‌شوند.</p>
            </aside>
        </div>
    @endif
</main>
@endsection
