@extends('layouts.store')

@section('title', 'ورود به حساب — VISION')

@section('content')
<main class="store-main commerce-page commerce-auth-page">
    <section class="commerce-auth-card commerce-panel">
        <p class="store-eyebrow">خوش برگشتی</p>
        <h1>ورود به حساب<span>.</span></h1>
        <p class="commerce-muted">با ایمیل و رمز عبور ثبت‌شده وارد شو. دسترسی بر اساس مجوزهای حساب کاربری تعیین می‌شود.</p>

        @if(session('status'))<p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>@endif
        @if($errors->any())<div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('login.store') }}" class="commerce-form-stack">
            @csrf
            <label class="commerce-field">نشانی ایمیل
                <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required autofocus>
            </label>
            <label class="commerce-field">رمز عبور
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="commerce-check-field"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> مرا در این دستگاه به خاطر بسپار</label>
            <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit">ورود ↗</button>
        </form>
        <p class="commerce-auth-switch">تازه به VISION پیوسته‌ای؟ <a href="{{ route('register') }}">ساخت حساب کاربری</a></p>
    </section>
</main>
@endsection
