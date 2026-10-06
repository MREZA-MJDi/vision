@extends('layouts.admin')

@section('title', 'پیام‌ها و ارتباطات')
@section('page-title', 'ارتباط با مشتری')

@section('content')
<div class="admin-page-stack">

    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-head__title">پیام‌ها و اطلاعات تماس</h1>
            <p class="admin-page-head__text">
                پیام‌های ارسال‌شده از Contact Us و اطلاعاتی که در سایت برای تماس نمایش داده می‌شود.
            </p>
        </div>

        <a href="{{ route('contact') }}" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost">
            مشاهده صفحه تماس
            <span>↗</span>
        </a>
    </div>

    <section class="admin-card admin-form-section admin-form-section-full">
        <header class="admin-card-header">
            <div>
                <h2 class="admin-card-title">اطلاعات تماس فروشگاه</h2>
                <p class="admin-card-description">
                    این مقادیر در صفحه تماس استفاده می‌شوند. اگر خالی باشند، سیستم از مقادیر محیطی فروشگاه استفاده می‌کند.
                </p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.content.contact.update') }}">
            @csrf
            <div class="admin-form-grid" style="padding:20px 22px 0;">
                <div class="admin-field">
                    <label for="phone">شماره تماس</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $contact['phone']) }}" placeholder="مثلاً 02112345678">
                    <small class="admin-help">در کارت تلفن صفحه Contact نمایش داده می‌شود.</small>
                </div>

                <div class="admin-field">
                    <label for="email">ایمیل</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $contact['email']) }}" placeholder="support@example.com">
                    <small class="admin-help">با کلیک روی آن، برنامه ایمیل کاربر باز می‌شود.</small>
                </div>

                <div class="admin-field">
                    <label for="address">آدرس</label>
                    <textarea id="address" name="address" rows="3" placeholder="آدرس فروشگاه...">{{ old('address', $contact['address']) }}</textarea>
                    <small class="admin-help">متن آدرس در بخش ارتباط نمایش داده می‌شود.</small>
                </div>

                <div class="admin-field">
                    <label for="working_hours">ساعات پاسخگویی</label>
                    <textarea id="working_hours" name="working_hours" rows="3" placeholder="شنبه تا چهارشنبه، ۹ تا ۱۸">{{ old('working_hours', $contact['working_hours']) }}</textarea>
                    <small class="admin-help">به کاربر می‌گوید چه زمانی انتظار پاسخ داشته باشد.</small>
                </div>
            </div>

            <div class="admin-form-actions" style="margin:18px 22px 22px;">
                <button type="submit" class="admin-btn admin-btn--secondary">ذخیره اطلاعات تماس</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <header class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Inbox پیام‌ها</h2>
                <p class="admin-card-description">{{ number_format($messages->total()) }} پیام ثبت شده است.</p>
            </div>
        </header>

        <form method="GET" class="admin-filter-grid" style="padding:18px 22px;">
            <div class="admin-field">
                <label for="contact-q">جستجو</label>
                <input id="contact-q" type="search" name="q" value="{{ request('q') }}" placeholder="نام، ایمیل، تلفن یا موضوع...">
            </div>

            <div class="admin-field">
                <label for="contact-status">وضعیت</label>
                <select id="contact-status" name="status">
                    <option value="">همه پیام‌ها</option>
                    <option value="new" @selected(request('status') === 'new')>جدید</option>
                    <option value="read" @selected(request('status') === 'read')>خوانده‌شده</option>
                    <option value="replied" @selected(request('status') === 'replied')>پاسخ‌داده‌شده</option>
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn--secondary">فیلتر</button>
                <a href="{{ route('admin.contact.index') }}" class="admin-btn admin-btn--ghost">پاک کردن</a>
            </div>
        </form>

        @if($messages->count())
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>فرستنده</th>
                        <th>موضوع</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($messages as $message)
                        <tr>
                            <td>
                                <div class="admin-product-name">{{ $message->name }}</div>
                                <div class="admin-product-meta">{{ $message->email ?: ($message->phone ?: 'بدون اطلاعات تماس') }}</div>
                            </td>
                            <td>{{ $message->subject ?: 'بدون موضوع' }}</td>
                            <td>
                                @php
                                    $class = match($message->status) {
                                        'new' => 'warning',
                                        'replied' => 'success',
                                        default => 'neutral',
                                    };
                                    $label = match($message->status) {
                                        'new' => 'جدید',
                                        'replied' => 'پاسخ‌داده‌شده',
                                        default => 'خوانده‌شده',
                                    };
                                @endphp
                                <span class="admin-badge admin-badge--{{ $class }}">{{ $label }}</span>
                            </td>
                            <td>
                                <span class="admin-local-date" data-admin-date="{{ optional($message->created_at)->toIso8601String() }}">
                                    {{ optional($message->created_at)->format('Y-m-d H:i') }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.contact.show', $message) }}" class="admin-btn admin-btn--ghost admin-btn--sm">
                                    مشاهده پیام
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($messages->hasPages())
                <div class="admin-pagination">{{ $messages->links() }}</div>
            @endif
        @else
            <div class="admin-empty">
                <div class="admin-empty__icon">✉</div>
                <h3 class="admin-empty__title">پیامی وجود ندارد</h3>
                <p class="admin-empty__text">وقتی کاربر از صفحه تماس پیام بفرستد، اینجا می‌بینی.</p>
            </div>
        @endif
    </section>
</div>
@endsection
