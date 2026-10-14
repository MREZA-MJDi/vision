<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin sign in · {{ config('commerce-admin.store_name', config('app.name', 'Shadow Outlet')) }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/commerce-admin/admin-kit.css') }}">
    <script src="{{ asset('vendor/commerce-admin/admin-kit.js') }}" defer></script>
</head>
<body class="admin-kit ak-login-body">
    <main class="ak-login-shell">
        <a class="ak-brand ak-login-brand" href="{{ url('/') }}"><span class="ak-brand-mark">◈</span><span>{{ config('commerce-admin.brand_name', 'Shadow UI Kit') }}</span></a>
        <section class="ak-login-card">
            <div class="ak-login-icon">◈</div>
            <p class="ak-eyebrow">ADMINISTRATION</p>
            <h1>Sign in to your store</h1>
            <p class="ak-muted">Use an administrator account to manage products, inventory and orders.</p>
            @if ($errors->any())
                <div class="ak-flash ak-flash-error" role="alert">{{ $errors->first() }}</div>
            @endif
            @if (session('status')) <div class="ak-flash ak-flash-success">{{ session('status') }}</div> @endif
            <form method="POST" action="{{ route('commerce-admin.login.store') }}" class="ak-form-stack">
                @csrf
                <label class="ak-field"><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="name@company.com"></label>
                <label class="ak-field"><span>Password</span><input type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"></label>
                <label class="ak-checkbox"><input type="checkbox" name="remember" value="1"><span>Keep me signed in</span></label>
                <button class="ak-button ak-button-primary ak-button-wide" type="submit">Sign in <span>→</span></button>
            </form>
            <div class="ak-login-security"><span>♧</span> Protected by your Laravel session and administrator role.</div>
        </section>
        <p class="ak-login-foot">Access is available only to users with administrator privileges.</p>
    </main>
</body>
</html>
