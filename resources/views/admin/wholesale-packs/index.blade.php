@extends('layouts.admin')

@section('title', 'پک‌های عمده')
@section('page-title', 'پک‌های عمده')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-head__title">پک‌های عمده</h1>
        <p class="admin-page-head__text">پک‌های ترکیبی را از Variantهای واقعی محصولات بساز و تعداد هر قلم را مشخص کن.</p>
    </div>
    <a href="{{ route('admin.wholesale-packs.create') }}" class="admin-btn admin-btn--secondary">+ ساخت پک عمده</a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">فهرست پک‌ها</h2>
            <p class="admin-card-description">{{ number_format($packs->total()) }} پک</p>
        </div>
    </div>

    @if($packs->count())
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>پک</th>
                    <th>اقلام</th>
                    <th>تعداد کل</th>
                    <th>قیمت عمده</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($packs as $pack)
                    <tr>
                        <td>
                            <strong class="admin-product-name">{{ $pack->name }}</strong>
                            <div class="admin-muted" dir="ltr">{{ $pack->slug }}</div>
                        </td>
                        <td>
                            <div style="display:grid;gap:4px;">
                                @foreach($pack->items->take(5) as $item)
                                    <span class="admin-muted">
                                        {{ $item->variant?->product?->brand?->name ? $item->variant->product->brand->name . ' · ' : '' }}
                                        {{ $item->variant?->product?->name }}
                                        @if($item->variant?->display_name) / {{ $item->variant->display_name }} @endif
                                        × {{ number_format($item->quantity) }}
                                    </span>
                                @endforeach
                                @if($pack->items->count() > 5)
                                    <span class="admin-muted">+ {{ number_format($pack->items->count() - 5) }} قلم دیگر</span>
                                @endif
                            </div>
                        </td>
                        <td><strong>{{ number_format($pack->pack_quantity) }} عدد</strong></td>
                        <td>{{ number_format($pack->display_price) }} تومان</td>
                        <td>
                            <span class="admin-badge {{ $pack->is_active ? 'admin-badge--success' : 'admin-badge--neutral' }}">
                                {{ $pack->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-btn admin-btn--ghost admin-btn--sm" href="{{ route('admin.wholesale-packs.edit', $pack) }}">ویرایش</a>
                                <form method="POST" action="{{ route('admin.wholesale-packs.destroy', $pack) }}" onsubmit="return confirm('پک حذف شود؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn admin-btn--danger admin-btn--sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $packs->links() }}</div>
    @else
        <div class="admin-empty">
            <h3 class="admin-empty__title">هنوز پکی ساخته نشده</h3>
            <p class="admin-empty__text">اولین پک عمده را از Variantهای محصولات بساز.</p>
        </div>
    @endif
</div>
@endsection
