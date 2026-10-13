@extends('layouts.store')

@section('title', 'Create Account — VISION')

@section('content')
<main class="store-main commerce-page commerce-auth-page">
    <section class="commerce-auth-card commerce-panel">
        <p class="store-eyebrow">YOUR VISION ACCOUNT</p>
        <h1>Create account<span>.</span></h1>
        <p class="commerce-muted">An account keeps your order history available across visits.</p>

        @if($errors->any())<div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('register.store') }}" class="commerce-form-stack">
            @csrf
            <label class="commerce-field">Full name
                <input type="text" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required autofocus>
            </label>
            <label class="commerce-field">Email address
                <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
            </label>
            <label class="commerce-field">Phone number
                <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
            </label>
            <label class="commerce-field">Password <span>(8 characters minimum)</span>
                <input type="password" name="password" autocomplete="new-password" minlength="8" required>
            </label>
            <label class="commerce-field">Confirm password
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>
            <label class="commerce-check-field"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))> I agree to the store's terms and privacy policy.</label>
            <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit">Create account ↗</button>
        </form>
        <p class="commerce-auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
    </section>
</main>
@endsection
