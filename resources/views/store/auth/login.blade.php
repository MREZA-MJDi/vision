@extends('layouts.store')

@section('title', 'Sign In — VISION')

@section('content')
<main class="store-main commerce-page commerce-auth-page">
    <section class="commerce-auth-card commerce-panel">
        <p class="store-eyebrow">WELCOME BACK</p>
        <h1>Sign in<span>.</span></h1>
        <p class="commerce-muted">Sign in with your registered email and password. Access is routed according to your account permissions.</p>

        @if(session('status'))<p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>@endif
        @if($errors->any())<div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('login.store') }}" class="commerce-form-stack">
            @csrf
            <label class="commerce-field">Email address
                <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required autofocus>
            </label>
            <label class="commerce-field">Password
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="commerce-check-field"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me on this device</label>
            <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit">Sign in ↗</button>
        </form>
        <p class="commerce-auth-switch">New to VISION? <a href="{{ route('register') }}">Create an account</a></p>
    </section>
</main>
@endsection
