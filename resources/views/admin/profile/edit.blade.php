@extends('layouts.admin')

@section('title', 'پروفایل مدیر')
@section('page-title', 'پروفایل')

@section('content')
<div class="admin-page-stack">

    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-head__title">پروفایل مدیر</h1>
            <p class="admin-page-head__text">اطلاعات ورود و اطلاعات تماس حساب مدیریت را از همین‌جا کنترل کن.</p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--ghost">بازگشت به داشبورد</a>
    </div>

    <form method="POST" action="{{ route('admin.profile.update') }}" class="admin-form-grid">
        @csrf
        @method('PATCH')

        <section class="admin-card admin-form-section">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">اطلاعات حساب</h2>
                    <p class="admin-card-description">این اطلاعات در هدر پنل به مدیر نمایش داده می‌شوند.</p>
                </div>
            </header>

            <div class="admin-card-body" style="display:grid;gap:15px;">
                <div class="admin-field">
                    <label for="name">نام مدیر *</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $admin->name) }}" required autocomplete="name">
                    <small class="admin-help">نامی که در پروفایل و هدر مدیریت می‌بینی.</small>
                </div>

                <div class="admin-field">
                    <label for="email">ایمیل ورود *</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $admin->email) }}" required autocomplete="email" dir="ltr">
                    <small class="admin-help">همان ایمیلی که با آن وارد پنل می‌شوی.</small>
                </div>

                <div class="admin-field">
                    <label for="phone">شماره تماس</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $admin->phone) }}" autocomplete="tel" dir="ltr">
                    <small class="admin-help">برای اطلاعات تماس حساب مدیر؛ اختیاری است.</small>
                </div>
            </div>
        </section>

        <section class="admin-card admin-form-section">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">تغییر رمز</h2>
                    <p class="admin-card-description">رمز را فقط زمانی پر کن که می‌خواهی تغییرش بدهی.</p>
                </div>
            </header>

            <div class="admin-card-body" style="display:grid;gap:15px;">
                <div class="admin-field">
                    <label for="password">رمز جدید</label>
                    <input id="password" name="password" type="password" autocomplete="new-password">
                    <small class="admin-help">حداقل ۸ کاراکتر.</small>
                </div>

                <div class="admin-field">
                    <label for="password_confirmation">تکرار رمز جدید</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                </div>

                <div class="admin-field">
                    <label for="current_password">رمز فعلی</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password">
                    <small class="admin-help">فقط برای تغییر رمز لازم است.</small>
                </div>
            </div>
        </section>

        <div class="admin-form-actions admin-form-section-full">
            <button type="submit" class="admin-btn admin-btn--secondary">ذخیره پروفایل</button>
        </div>
    </form>
</div>
@endsection
