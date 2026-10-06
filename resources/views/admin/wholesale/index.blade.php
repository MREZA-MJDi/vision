@extends('layouts.admin')

@section('title', 'مدیریت عمده‌فروشی')
@section('page-title', 'مدیریت عمده‌فروشی')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-head__title">مدیریت عمده‌فروشی</h1>
        <p class="admin-page-head__text">درخواست‌ها، وضعیت دسترسی و شرایط خرید عمده مشتریان.</p>
        <div class="admin-actions">
            <a href="{{ route('admin.wholesale-packs.index') }}" class="admin-btn admin-btn--secondary">مدیریت پک‌های عمده</a>
        </div>
    </div>
</div>

<div class="admin-card admin-filter-card">
    <form method="GET" action="{{ route('admin.wholesale.index') }}">
        <div class="admin-filter-grid">
            <div class="admin-field">
                <label for="q">جستجوی مشتری</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="نام، ایمیل یا شماره تماس...">
            </div>

            <div class="admin-field">
                <label for="status">وضعیت</label>
                <select id="status" name="status">
                    <option value="">همه</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ match($status) {
                                'pending' => 'در انتظار بررسی',
                                'approved' => 'تأیید شده',
                                'suspended' => 'تعلیق شده',
                                'rejected' => 'رد شده',
                                default => $status,
                            } }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn--secondary">جستجو</button>
                @if(request()->filled('q') || request()->filled('status'))
                    <a href="{{ route('admin.wholesale.index') }}" class="admin-btn admin-btn--ghost">پاک کردن</a>
                @endif
            </div>
        </div>
    </form>
</div>

<div class="admin-card" id="cheque-permission-requests">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">درخواست‌های مجوز پرداخت چکی</h2>
            <p class="admin-card-description">این مجوز فقط روش پرداخت چکی را برای مشتری فعال می‌کند؛ خرید آنلاین عمده بدون این مجوز هم باز است.</p>
        </div>
    </div>
    @if($chequePermissions->count())
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>مشتری</th><th>مبلغ درخواستی</th><th>وضعیت مجوز</th><th>عملیات مدیر</th></tr></thead>
                <tbody>
                @foreach($chequePermissions as $permission)
                    <tr>
                        <td><div class="admin-product-name">{{ $permission->user?->name ?: 'بدون نام' }}</div><div class="admin-muted" dir="ltr">{{ $permission->user?->phone ?: $permission->user?->email ?: '—' }}</div></td>
                        <td>{{ $permission->requested_amount !== null ? number_format((float) $permission->requested_amount).' تومان' : '—' }}</td>
                        <td>
                            @if($permission->isPending())<span class="admin-badge admin-badge--warning">در انتظار بررسی</span>
                            @elseif($permission->isApproved())<span class="admin-badge admin-badge--success">فعال · سقف {{ $permission->max_order_amount !== null ? number_format((float) $permission->max_order_amount).' تومان' : 'نامحدود' }}</span>
                            @elseif($permission->requested_at && $permission->disabled_at)<span class="admin-badge admin-badge--neutral">رد یا غیرفعال</span>
                            @else<span class="admin-badge admin-badge--neutral">بدون درخواست</span>@endif
                        </td>
                        <td>
                            @if($permission->isPending())
                                <form method="POST" action="{{ route('admin.customers.cheque.enable', $permission->user) }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-bottom:8px;">
                                    @csrf @method('PATCH')
                                    <div class="admin-field" style="min-width:190px;"><label>سقف هر سفارش (تومان)</label><input type="text" name="max_order_amount" inputmode="numeric" data-money-input value="{{ (int) $permission->requested_amount }}" required></div>
                                    <button class="admin-btn admin-btn--secondary" type="submit">تأیید و فعال‌سازی</button>
                                </form>
                                <form method="POST" action="{{ route('admin.customers.cheque.reject', $permission->user) }}">@csrf @method('PATCH')<button class="admin-btn admin-btn--ghost" type="submit">رد درخواست</button></form>
                            @elseif($permission->isApproved())
                                <form method="POST" action="{{ route('admin.customers.cheque.disable', $permission->user) }}">@csrf @method('PATCH')<button class="admin-btn admin-btn--ghost" type="submit">غیرفعال کردن مجوز</button></form>
                            @else<span class="admin-muted">اقدامی لازم نیست</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($chequePermissions->hasPages())<div class="admin-pagination">{{ $chequePermissions->links() }}</div>@endif
    @else
        <div class="admin-empty"><h3 class="admin-empty__title">درخواست چکی ثبت نشده</h3><p class="admin-empty__text">درخواست‌های تازه مشتریان پس از ثبت اینجا نمایش داده می‌شوند.</p></div>
    @endif
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">درخواست‌های عمده</h2>
            <p class="admin-card-description">{{ number_format($profiles->total()) }} حساب</p>
        </div>
    </div>

    @if($profiles->count())
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>مشتری</th>
                    <th>کسب‌وکار</th>
                    <th>وضعیت</th>
                    <th>حداقل سفارش</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($profiles as $profile)
                    @php
                        $statusLabel = match($profile->status) {
                            'pending' => 'در انتظار بررسی',
                            'approved' => 'تأیید شده',
                            'suspended' => 'تعلیق شده',
                            'rejected' => 'رد شده',
                            default => $profile->status,
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="admin-product-name">{{ $profile->user?->name ?: 'بدون نام' }}</div>
                            <div class="admin-muted" dir="ltr">{{ $profile->user?->phone ?: $profile->user?->email ?: '—' }}</div>
                        </td>
                        <td>
                            <div class="admin-product-name">{{ $profile->business_name ?: '—' }}</div>
                            <div class="admin-muted">{{ $profile->business_type ?: 'نوع فعالیت ثبت نشده' }}</div>
                        </td>
                        <td><span class="admin-badge">{{ $statusLabel }}</span></td>
                        <td>
                            <div class="admin-price">
                                {{ $profile->minimum_order_amount !== null ? number_format((float) $profile->minimum_order_amount) . ' تومان' : 'بدون حد مبلغ' }}
                            </div>
                            <div class="admin-muted">
                                {{ $profile->minimum_order_quantity !== null ? number_format((int) $profile->minimum_order_quantity) . ' عدد' : 'بدون حد تعداد' }}
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                @if(in_array($profile->status, ['pending', 'rejected', 'suspended'], true))
                                    <form method="POST" action="{{ route('admin.customers.wholesale.approve', $profile->user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="admin-btn admin-btn--secondary" type="submit">تأیید</button>
                                    </form>
                                @endif

                                @if($profile->status === 'pending')
                                    <form method="POST" action="{{ route('admin.customers.wholesale.reject', $profile->user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="admin-btn admin-btn--ghost" type="submit">رد</button>
                                    </form>
                                @endif

                                @if($profile->status === 'approved')
                                    <form method="POST" action="{{ route('admin.customers.wholesale.suspend', $profile->user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="admin-btn admin-btn--ghost" type="submit">تعلیق</button>
                                    </form>
                                @endif
                            </div>

                            <div style="margin-top:12px;padding:12px;border:1px solid var(--admin-border);border-radius:14px;background:var(--admin-surface-soft);">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                                    <div>
                                        <strong style="display:block;">اجازه خرید چکی</strong>
                                        <small class="admin-muted">
                                            این مجوز جدا از تأیید خرید عمده است و فقط برای همین مشتری اعمال می‌شود.
                                        </small>
                                    </div>
                                    @if($profile->user?->chequePermission?->isApproved())
                                        <span class="admin-badge admin-badge--success">فعال</span>
                                    @elseif($profile->user?->chequePermission?->isPending())
                                        <span class="admin-badge admin-badge--warning">درخواست در انتظار بررسی</span>
                                    @elseif($profile->user?->chequePermission?->requested_at && $profile->user?->chequePermission?->disabled_at)
                                        <span class="admin-badge admin-badge--neutral">درخواست قبلی رد یا غیرفعال شده</span>
                                    @else
                                        <span class="admin-badge admin-badge--neutral">غیرفعال</span>
                                    @endif
                                </div>

                                @if($profile->user?->chequePermission?->isApproved())
                                    <form method="POST" action="{{ route('admin.customers.cheque.disable', $profile->user) }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
                                        @csrf
                                        @method('PATCH')
                                        <div class="admin-field" style="min-width:220px;flex:1;">
                                            <label>سقف هر سفارش</label>
                                            <input type="text" value="{{ $profile->user->chequePermission->max_order_amount !== null ? number_format((float) $profile->user->chequePermission->max_order_amount) . ' تومان' : 'بدون سقف' }}" readonly>
                                        </div>
                                        <button class="admin-btn admin-btn--ghost" type="submit">غیرفعال کردن چک</button>
                                    </form>
                                @elseif($profile->user?->chequePermission?->isPending())
                                    <small class="admin-muted">مبلغ درخواستی: {{ number_format((float) $profile->user->chequePermission->requested_amount) }} تومان · برای بررسی از جدول درخواست‌های بالا استفاده کن.</small>
                                @else
                                    <small class="admin-muted">فعالسازی فقط پس از ثبت درخواست مشتری ممکن است؛ درخواست‌ها در جدول بالای صفحه بررسی می‌شوند.</small>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <form method="POST" action="{{ route('admin.customers.wholesale.terms', $profile->user) }}" class="admin-form-grid">
                                @csrf
                                @method('PATCH')
                                <div class="admin-field">
                                    <label>حداقل مبلغ سفارش</label>
                                    <input type="text" name="minimum_order_amount" inputmode="numeric" data-money-input value="{{ old('minimum_order_amount', $profile->minimum_order_amount) }}">
                                </div>
                                <div class="admin-field">
                                    <label>حداقل تعداد</label>
                                    <input type="number" name="minimum_order_quantity" min="1" step="1" value="{{ old('minimum_order_quantity', $profile->minimum_order_quantity) }}">
                                </div>
                                <div class="admin-field">
                                    <label>یادداشت مدیریت</label>
                                    <input name="note" value="{{ old('note', $profile->admin_note) }}">
                                </div>
                                <div class="admin-form-actions">
                                    <button class="admin-btn admin-btn--ghost" type="submit">ذخیره شرایط</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($profiles->hasPages())
            <div class="admin-pagination">{{ $profiles->links() }}</div>
        @endif
    @else
        <div class="admin-empty">
            <h3 class="admin-empty__title">درخواستی وجود ندارد</h3>
            <p class="admin-empty__text">هنوز حساب عمده‌ای مطابق فیلتر فعلی پیدا نشده است.</p>
        </div>
    @endif
</div>
@endsection
