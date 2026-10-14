@extends('layouts.store')

@section('title', 'ثبت سفارش — VISION')
@section('meta-description', 'سفارش خود را با قیمت و موجودی تأییدشده در سرور نهایی کنید.')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">مرحلهٔ پایانی</p>
            <h1>ثبت سفارش<span>.</span></h1>
            <p>می‌توانی بدون ورود سفارش ثبت کنی یا وارد حسابت شوی تا سوابق سفارش‌هایت ذخیره شود.</p>
        </div>
        <a class="commerce-text-link" href="{{ route('cart.index') }}">← بازگشت به سبد خرید</a>
    </div>

    @if($errors->any())
        <div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if(!$summary['allAvailable'])
        <div class="commerce-alert commerce-alert--error" role="alert">یک یا چند کالا ناموجود هستند یا تعدادشان از موجودی بیشتر است. برای اصلاح موارد، به سبد خرید برگرد.</div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" class="commerce-layout commerce-checkout-layout">
        @csrf
        <div class="commerce-form-stack">
            <section class="commerce-panel">
                <div class="commerce-panel-heading"><h2>اطلاعات تماس</h2><span>01</span></div>
                <div class="commerce-form-grid">
                    <label class="commerce-field commerce-field--wide">نام و نام خانوادگی <span>*</span>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" maxlength="255" autocomplete="name" required>
                        @error('customer_name')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">نشانی ایمیل
                        <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()?->email) }}" maxlength="255" autocomplete="email">
                        @error('customer_email')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">شمارهٔ تلفن همراه <span>*</span>
                        <input type="tel" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" maxlength="30" autocomplete="tel" required>
                        @error('customer_phone')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <section class="commerce-panel">
                <div class="commerce-panel-heading"><h2>نشانی تحویل</h2><span>02</span></div>
                <div class="commerce-form-grid">
                    <label class="commerce-field commerce-field--wide">نشانی کامل <span>*</span>
                        <textarea name="shipping_address" rows="3" maxlength="2000" autocomplete="street-address" required>{{ old('shipping_address') }}</textarea>
                        @error('shipping_address')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">شهر
                        <input type="text" name="shipping_city" value="{{ old('shipping_city') }}" maxlength="100" autocomplete="address-level2">
                        @error('shipping_city')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">استان
                        <input type="text" name="shipping_province" value="{{ old('shipping_province') }}" maxlength="100" autocomplete="address-level1">
                        @error('shipping_province')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">کد پستی
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="20" autocomplete="postal-code">
                        @error('postal_code')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field commerce-field--wide">توضیحات سفارش
                        <textarea name="customer_note" rows="2" maxlength="2000" placeholder="توضیحات اختیاری برای تحویل سفارش">{{ old('customer_note') }}</textarea>
                        @error('customer_note')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <section class="commerce-panel">
                <div class="commerce-panel-heading"><h2>روش پرداخت</h2><span>03</span></div>
                <div class="commerce-payment-options">
                    <label><input type="radio" name="payment_method" value="cash_on_delivery" @checked(old('payment_method', 'cash_on_delivery') === 'cash_on_delivery') required><span><strong>پرداخت هنگام تحویل</strong><small>هزینه را هنگام تحویل سفارش پرداخت کن.</small></span></label>
                    <label><input type="radio" name="payment_method" value="bank_transfer" @checked(old('payment_method') === 'bank_transfer') required><span><strong>واریز بانکی</strong><small>اطلاعات واریز پس از ثبت سفارش باید از طرف فروشگاه اعلام شود.</small></span></label>
                </div>
                @error('payment_method')<small class="commerce-field-error">{{ $message }}</small>@enderror
                <p class="commerce-muted">پرداخت آنلاین با کارت فعال نیست؛ هیچ اطلاعات کارت بانکی در این صفحه دریافت یا ذخیره نمی‌شود.</p>
            </section>
        </div>

        <aside class="commerce-panel commerce-summary">
            <div class="commerce-panel-heading"><h2>سفارش شما</h2><span>{{ number_format($summary['itemCount']) }} کالا</span></div>
            <div class="commerce-summary__products">
                @foreach($items as $item)
                    @php($variant = $item->productVariant)
                    <div><span>{{ $variant?->product?->name ?? 'کالای ناموجود' }} × {{ (int) $item->quantity }}</span><strong>@money($item->display_line_total)</strong></div>
                @endforeach
            </div>
            <div class="commerce-summary__lines">
                <div><span>جمع کالاها</span><strong>@money($summary['subtotal'])</strong></div>
                <div><span>مالیات</span><strong>@money($summary['tax'])</strong></div>
                <div><span>هزینهٔ ارسال</span><strong>@money($summary['shipping'])</strong></div>
                <div class="commerce-summary__total"><span>مبلغ نهایی</span><strong>@money($summary['total'])</strong></div>
            </div>
            <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit" @disabled(!$summary['allAvailable'])>ثبت نهایی سفارش ↗</button>
            <p class="commerce-muted">با ثبت سفارش، سفارش و رزرو موجودی در یک تراکنش پایگاه داده انجام می‌شود. قیمت و موجودی در سرور دوباره محاسبه می‌شوند.</p>
        </aside>
    </form>
</main>
@endsection
