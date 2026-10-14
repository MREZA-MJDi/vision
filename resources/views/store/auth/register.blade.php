@extends('layouts.store')

@section('title', 'ساخت حساب کاربری — VISION')

@section('content')
<main class="store-main commerce-page commerce-auth-page">
    <section class="commerce-auth-card commerce-panel">
        <p class="store-eyebrow">حساب کاربری VISION</p>
        <h1>ساخت حساب کاربری<span>.</span></h1>
        <p class="commerce-muted">با ساخت حساب، سوابق سفارش‌هایت در مراجعه‌های بعدی هم در دسترس خواهد بود.</p>

        @if($errors->any())<div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('register.store') }}" class="commerce-form-stack">
            @csrf
            <label class="commerce-field">نام و نام خانوادگی
                <input type="text" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required autofocus>
            </label>
            <label class="commerce-field">نشانی ایمیل
                <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
            </label>
            <label class="commerce-field">شمارهٔ تلفن همراه
                <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
            </label>
            <label class="commerce-field">رمز عبور <span>(حداقل ۸ نویسه)</span>
                <input type="password" name="password" autocomplete="new-password" minlength="8" required>
            </label>
            <label class="commerce-field">تکرار رمز عبور
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>
            <label class="commerce-check-field"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))> با شرایط استفاده و سیاست حفظ حریم خصوصی فروشگاه موافقم.</label>
            <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit">ساخت حساب ↗</button>
        </form>
        <p class="commerce-auth-switch">از قبل حساب داری؟ <a href="{{ route('login') }}">ورود</a></p>
    </section>
</main>
@endsection
