@extends('layouts.admin')

@section('title', 'مشاهده پیام')
@section('page-title', 'جزئیات پیام')

@section('content')
<div class="admin-page-stack">

    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-head__title">{{ $message->subject ?: 'پیام بدون موضوع' }}</h1>
            <p class="admin-page-head__text">پیام ارسالی از صفحه تماس با جانان.</p>
        </div>

        <a href="{{ route('admin.contact.index') }}" class="admin-btn admin-btn--ghost">بازگشت به Inbox</a>
    </div>

    <div class="admin-grid-2">
        <section class="admin-card">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">پیام</h2>
                    <p class="admin-card-description">جزئیات کامل درخواست مشتری</p>
                </div>
            </header>

            <div class="admin-card-body">
                <div class="admin-message-copy">{{ $message->message }}</div>
            </div>
        </section>

        <aside class="admin-card">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">اطلاعات فرستنده</h2>
                    <p class="admin-card-description">اطلاعاتی که کاربر در فرم وارد کرده است.</p>
                </div>
            </header>

            <div class="admin-card-body admin-detail-stack">
                <div><span>نام</span><strong>{{ $message->name }}</strong></div>
                <div><span>ایمیل</span><strong dir="ltr">{{ $message->email ?: '—' }}</strong></div>
                <div><span>تلفن</span><strong dir="ltr">{{ $message->phone ?: '—' }}</strong></div>
                <div><span>ثبت</span><strong class="admin-local-date" data-admin-date="{{ optional($message->created_at)->toIso8601String() }}">{{ optional($message->created_at)->format('Y-m-d H:i') }}</strong></div>
            </div>
        </aside>
    </div>

    <section class="admin-card">
        <header class="admin-card-header">
            <div>
                <h2 class="admin-card-title">وضعیت پیگیری</h2>
                <p class="admin-card-description">وضعیت پیام را بعد از رسیدگی تغییر بده.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.contact.status', $message) }}" class="admin-form-actions">
            @csrf
            @method('PATCH')

            <label class="admin-status-choice">
                <input type="radio" name="status" value="new" @checked($message->status === 'new')>
                <span>جدید</span>
            </label>

            <label class="admin-status-choice">
                <input type="radio" name="status" value="read" @checked($message->status === 'read')>
                <span>خوانده‌شده</span>
            </label>

            <label class="admin-status-choice">
                <input type="radio" name="status" value="replied" @checked($message->status === 'replied')>
                <span>پاسخ‌داده‌شده</span>
            </label>

            <button type="submit" class="admin-btn admin-btn--secondary">ذخیره وضعیت</button>
        </form>
    </section>
</div>
@endsection
