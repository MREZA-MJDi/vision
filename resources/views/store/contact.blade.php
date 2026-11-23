@extends('layouts.store')

@section('title', 'تماس با ما — VISION')
@section('meta-description', 'برای پرسش درباره سفارش، محصولات و همکاری با فروشگاه VISION پیام بفرستید.')
@section('canonical', route('contact.index'))

@section('content')
<main class="store-main commerce-page vision-contact">
    <nav class="commerce-breadcrumbs" aria-label="مسیر صفحه"><a href="{{ route('storefront.index') }}">خانه</a><span>/</span><span>تماس با ما</span></nav>
    <header class="vision-contact__heading"><span class="store-eyebrow">LET'S CONNECT / VISION</span><h1>با ما در ارتباط باش<span>.</span></h1><p>برای پیگیری سفارش، پرسش درباره محصولات یا پیشنهاد همکاری از فرم زیر استفاده کن. پیام مستقیماً در سامانه فروشگاه ثبت می‌شود.</p></header>
    <div class="vision-contact__layout">
        <section class="commerce-panel vision-contact__form" aria-labelledby="contact-form-title">
            <h2 id="contact-form-title">ارسال پیام</h2>
            @if(session('status'))<p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>@endif
            @if($errors->any())<div class="commerce-alert commerce-alert--error" role="alert"><strong>لطفاً موارد زیر را بررسی کن:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('contact.store') }}">
                @csrf
                <div class="vision-contact__fields">
                    <label class="commerce-field">نام و نام خانوادگی <input name="name" value="{{ old('name') }}" maxlength="120" required autocomplete="name"></label>
                    <label class="commerce-field">ایمیل <input name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"></label>
                    <label class="commerce-field">شماره تماس <input name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" autocomplete="tel"></label>
                    <label class="commerce-field">موضوع <input name="subject" value="{{ old('subject') }}" maxlength="180" required></label>
                </div>
                <label class="commerce-field">متن پیام <textarea name="message" rows="6" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea></label>
                <p class="commerce-muted">حداقل یکی از ایمیل یا شماره تماس را وارد کن تا امکان پاسخ‌گویی وجود داشته باشد.</p>
                <button type="submit" class="commerce-button commerce-button--primary">ثبت و ارسال پیام ↗</button>
            </form>
        </section>
        <aside class="vision-contact__aside" aria-label="راهنمای ارتباط">
            <span>VISION SUPPORT</span><h2>کنار انتخاب‌های تو هستیم.</h2><p>برای پیگیری سفارش می‌توانی از حساب کاربری و تاریخچه سفارش‌ها استفاده کنی.</p>
            <a href="{{ route('account.show') }}">پیگیری سفارش‌ها ↗</a>
            <a href="{{ route('shop.index') }}">بازگشت به فروشگاه ↗</a>
            <div class="vision-contact__note"><strong>پیام امن و مستقیم</strong><small>اطلاعات فرم در دیتابیس فروشگاه ذخیره می‌شود؛ این فرم وابسته به سرویس نمایشی نیست.</small></div>
        </aside>
    </div>
</main>
@endsection
