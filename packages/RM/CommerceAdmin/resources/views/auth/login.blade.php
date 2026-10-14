<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ورود مدیر فروشگاه · {{ config('commerce-admin.store_name', config('app.name', 'Shadow Outlet')) }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['packages/RM/CommerceAdmin/resources/css/admin-kit.css', 'packages/RM/CommerceAdmin/resources/js/admin-kit.js'])
    @endif
</head>
<body class="admin-kit ak-login-body">
    <main class="ak-login-shell">
        <a class="ak-brand ak-login-brand" href="{{ url('/') }}"><span class="ak-brand-mark">◈</span><span>{{ config('commerce-admin.brand_name', 'Shadow UI Kit') }}</span></a>
        <section class="ak-login-card">
            <div class="ak-login-icon">◈</div>
            <p class="ak-eyebrow">مدیریت فروشگاه</p>
            <h1>ورود به پنل فروشگاه</h1>
            <p class="ak-muted">برای مدیریت محصولات، موجودی و سفارش‌ها با حساب مدیر وارد شوید.</p>
            @if ($errors->any())
                <div class="ak-flash ak-flash-error" role="alert">{{ $errors->first() }}</div>
            @endif
            @if (session('status')) <div class="ak-flash ak-flash-success">{{ session('status') }}</div> @endif
            <form method="POST" action="{{ route('commerce-admin.login.store') }}" class="ak-form-stack">
                @csrf
                <label class="ak-field"><span>نشانی ایمیل</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="name@company.com"></label>
                <label class="ak-field"><span>رمز عبور</span><input type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"></label>
                <label class="ak-checkbox"><input type="checkbox" name="remember" value="1"><span>مرا وارد نگه دار</span></label>
                <button class="ak-button ak-button-primary ak-button-wide" type="submit">ورود <span>→</span></button>
            </form>
            <div class="ak-login-security"><span>♧</span> ورود با نشست امن Laravel و سطح دسترسی مدیر محافظت می‌شود.</div>
        </section>
        <p class="ak-login-foot">این بخش فقط برای مدیران مجاز است.</p>
    </main>
</body>
</html>
