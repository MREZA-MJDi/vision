@extends('commerce-admin::layout')

@section('title', 'ثبت سفارش')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.orders.index') }}">سفارش‌ها</a> <span>/</span> ثبت سفارش</div><h1>ثبت سفارش</h1><p>Create a real order and reserve available stock with an inventory movement record.</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.orders.index') }}">انصراف</a><button class="ak-button ak-button-dark" type="submit" form="orderCreateForm">ثبت سفارش <span>✓</span></button></div>
    </div>

    <form id="orderCreateForm" method="POST" action="{{ route('commerce-admin.orders.store') }}">
        @csrf
        <div class="ak-form-layout ak-order-create-layout">
            <div class="ak-form-main">
                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>اطلاعات مشتری</h2><p>اگر ایمیل با حساب مشتری موجود مطابقت داشته باشد، سفارش به همان حساب متصل می‌شود.</p></div></div>
                    <div class="ak-form-grid">
                        <label class="ak-field"><span>نام مشتری <b>*</b></span><input type="text" name="customer_name" required maxlength="255" value="{{ old('customer_name') }}" placeholder="Full name"></label>
                        <label class="ak-field"><span>نشانی ایمیل</span><input type="email" name="customer_email" maxlength="255" value="{{ old('customer_email') }}" placeholder="customer@example.com"></label>
                        <label class="ak-field"><span>تلفن</span><input type="text" name="customer_phone" maxlength="30" value="{{ old('customer_phone') }}" placeholder="تلفن number"></label>
                        <label class="ak-field"><span>روش پرداخت <b>*</b></span><select name="payment_method" required><option value="cash_on_delivery" @selected(old('payment_method','cash_on_delivery')==='cash_on_delivery')>پرداخت در محل</option><option value="bank_transfer" @selected(old('payment_method')==='bank_transfer')>واریز بانکی</option><option value="manual" @selected(old('payment_method')==='manual')>پرداخت دستی / خارج از سامانه</option></select></label>
                    </div>
                </section>

                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>اطلاعات ارسال</h2><p>اطلاعات نشانی در رکورد سفارش ذخیره می‌شود.</p></div></div>
                    <div class="ak-form-grid">
                        <label class="ak-field ak-field-full"><span>نشانی ارسال</span><textarea name="shipping_address" rows="3" maxlength="2000" placeholder="Street address, building, unit...">{{ old('shipping_address') }}</textarea></label>
                        <label class="ak-field"><span>City</span><input type="text" name="shipping_city" maxlength="100" value="{{ old('shipping_city') }}"></label>
                        <label class="ak-field"><span>استان</span><input type="text" name="shipping_province" maxlength="100" value="{{ old('shipping_province') }}"></label>
                        <label class="ak-field"><span>کد پستی</span><input type="text" name="postal_code" maxlength="30" value="{{ old('postal_code') }}"></label>
                    </div>
                </section>

                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>اقلام سفارش</h2><p>فقط تنوع‌های فعال نمایش داده می‌شوند. پیش از ذخیره، تعداد بررسی و موجودی قفل می‌شود.</p></div><button class="ak-button ak-button-light ak-button-sm" type="button" data-add-order-item>＋ افزودن قلم</button></div>
                    @if($variants->isEmpty())
                        <div class="ak-inline-empty"><strong>موجودی فعالی برای فروش نیست</strong><p>پیش از ثبت سفارش، محصولی بسازید و دست‌کم یک تنوع را فعال کنید.</p><a class="ak-text-link" href="{{ route('commerce-admin.products.create') }}">افزودن محصول ←</a></div>
                    @endif
                    <div class="ak-order-item-list" data-order-items>
                        <div class="ak-order-item-row" data-order-item-row>
                            <div class="ak-order-item-field ak-order-product-select"><label class="ak-field"><span>تنوع محصول <b>*</b></span><select name="items[0][variant_id]" required data-order-variant><option value="">انتخاب محصول / گزینه</option>@foreach($variants as $variant)<option value="{{ $variant->getKey() }}" data-price="{{ $variant->sale_price ?? $variant->price }}" data-stock="{{ $variant->stock }}">{{ $variant->product->name }} · {{ $variant->display_name }}{{ $variant->sku ? ' · '.$variant->sku : '' }} — {{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)($variant->sale_price ?? $variant->price),2) }} ({{ $variant->stock }} available)</option>@endforeach</select></label></div>
                            <div class="ak-order-qty-field"><label class="ak-field"><span>تعداد <b>*</b></span><input type="number" name="items[0][quantity]" value="1" min="1" max="1000" required data-order-quantity></label></div>
                            <div class="ak-order-line-total"><small>مبلغ ردیف</small><strong data-line-total>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div>
                            <button class="ak-icon-button ak-remove-order-item" type="button" data-remove-order-item aria-label="Remove item" disabled>×</button>
                        </div>
                    </div>
                    <template id="order-item-template"><div class="ak-order-item-row" data-order-item-row><div class="ak-order-item-field ak-order-product-select"><label class="ak-field"><span>تنوع محصول <b>*</b></span><select name="items[__INDEX__][variant_id]" required data-order-variant><option value="">انتخاب محصول / گزینه</option>@foreach($variants as $variant)<option value="{{ $variant->getKey() }}" data-price="{{ $variant->sale_price ?? $variant->price }}" data-stock="{{ $variant->stock }}">{{ $variant->product->name }} · {{ $variant->display_name }}{{ $variant->sku ? ' · '.$variant->sku : '' }} — {{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)($variant->sale_price ?? $variant->price),2) }} ({{ $variant->stock }} available)</option>@endforeach</select></label></div><div class="ak-order-qty-field"><label class="ak-field"><span>تعداد <b>*</b></span><input type="number" name="items[__INDEX__][quantity]" value="1" min="1" max="1000" required data-order-quantity></label></div><div class="ak-order-line-total"><small>مبلغ ردیف</small><strong data-line-total>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div><button class="ak-icon-button ak-remove-order-item" type="button" data-remove-order-item aria-label="Remove item">×</button></div></template>
                </section>
            </div>

            <aside class="ak-form-aside">
                <section class="ak-panel ak-aside-panel"><div class="ak-panel-heading"><div><h2>خلاصهٔ سفارش</h2><p>بر اساس قیمت فعلی تنوع‌های انتخاب‌شده محاسبه می‌شود.</p></div></div>
                    <div class="ak-summary-lines"><div><span>جمع جزء</span><strong data-order-subtotal>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div><label class="ak-field"><span>هزینهٔ ارسال</span><input type="number" name="shipping_cost" value="{{ old('shipping_cost', 0) }}" min="0" step="0.01" required data-shipping-cost></label><div class="ak-summary-total"><span>مبلغ کل</span><strong data-order-total>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div></div>
                    <div class="ak-info-box"><span>i</span><p>کسر موجودی در همان تراکنش دیتابیس سفارش انجام می‌شود. اگر موجودی تنوع انتخاب‌شده کافی نباشد، هیچ تغییری ذخیره نمی‌شود.</p></div>
                    <button class="ak-button ak-button-dark ak-button-wide" type="submit" {{ $variants->isEmpty() ? 'disabled' : '' }}>ثبت سفارش <span>✓</span></button>
                </section>
            </aside>
        </div>
    </form>
@endsection
