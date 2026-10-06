<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>ورود مدیریت — Vision</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 text-neutral-900">
<div class="min-h-screen grid place-items-center p-6">
    <div class="w-full max-w-md rounded-3xl border border-neutral-200 bg-white p-8 shadow-xl">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-neutral-400">VISION / ADMIN</p>
        <h1 class="mt-3 text-3xl font-black">ورود به پنل مدیریت</h1>
        <p class="mt-2 text-sm leading-7 text-neutral-500">برای مدیریت فروشگاه Vision وارد حساب مدیر شوید.</p>

        @if($errors->any())
            <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        @if(session('error'))
            <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="mt-6 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="phone" class="mb-2 block text-sm font-bold">شماره موبایل</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" inputmode="tel"
                    autocomplete="username" maxlength="16" required autofocus
                    class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 outline-none transition focus:border-neutral-900 focus:bg-white">
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-bold">رمز عبور</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 outline-none transition focus:border-neutral-900 focus:bg-white">
            </div>
            <label class="flex items-center gap-2 text-sm text-neutral-600">
                <input type="checkbox" name="remember" value="1">
                مرا به خاطر بسپار
            </label>
            <button type="submit" class="w-full rounded-2xl bg-neutral-900 px-4 py-3.5 text-sm font-bold text-white transition hover:bg-neutral-800">
                ورود به پنل
            </button>
        </form>
    </div>
</div>
</body>
</html>
