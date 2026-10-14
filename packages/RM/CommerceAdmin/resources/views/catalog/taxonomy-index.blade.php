@extends('commerce-admin::layout')

@section('title', $taxonomyPlural)

@section('content')
    <div class="ak-page-heading">
        <div>
            <div class="ak-breadcrumb">فروشگاه <span>/</span> {{ $taxonomyPlural }}</div>
            <h1>{{ $taxonomyPlural }}</h1>
            <p>مدیریت داده‌های واقعی کاتالوگ؛ حذف رکوردهای متصل به محصول به‌صورت امن انجام می‌شود.</p>
        </div>
        <div class="ak-heading-actions">
            <a class="ak-button ak-button-dark" href="{{ route($routePrefix . '.create') }}">＋ افزودن {{ $taxonomyTitle }}</a>
        </div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-three">
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">همه</span></div><div class="ak-stat-value">{{ number_format((int) $stats['all']) }}</div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">فعال</span></div><div class="ak-stat-value">{{ number_format((int) $stats['active']) }}</div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">متصل به محصول</span></div><div class="ak-stat-value">{{ number_format((int) $stats['used']) }}</div></article>
    </section>

    <section class="ak-panel ak-table-panel">
        <div class="ak-panel-heading">
            <div><h2>فهرست {{ $taxonomyPlural }} <span class="ak-count-chip">{{ number_format($items->total()) }}</span></h2><p>برای مرتب‌سازی و مدیریت، از فیلترها و صفحه‌بندی استفاده کنید.</p></div>
        </div>
        <form class="ak-filter-bar is-visible" method="GET" action="{{ route($routePrefix . '.index') }}">
            <label class="ak-inline-field"><span>جست‌وجو</span><input type="search" name="q" value="{{ $search }}" placeholder="نام یا شناسهٔ آدرس"></label>
            <label class="ak-inline-field"><span>وضعیت</span><select name="status"><option value="all" @selected($status === 'all')>همه</option><option value="active" @selected($status === 'active')>فعال</option><option value="inactive" @selected($status === 'inactive')>غیرفعال</option></select></label>
            <div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">اعمال فیلتر</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route($routePrefix . '.index') }}">پاک‌کردن</a></div>
        </form>

        @if($items->isEmpty())
            <div class="ak-empty-state"><strong>موردی پیدا نشد</strong><p>برای این فیلتر رکوردی وجود ندارد. می‌توانید {{ $taxonomyTitle }} جدید اضافه کنید.</p></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table">
                <thead><tr><th>نام</th><th>شناسهٔ آدرس</th>@if($showParent)<th>والد</th><th>زیر‌دسته</th>@endif<th>محصول‌ها</th><th>وضعیت</th><th>آخرین تغییر</th><th>عملیات</th></tr></thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td><strong class="ak-table-primary">{{ $item->name }}</strong></td>
                        <td><span class="ak-mono">{{ $item->slug }}</span></td>
                        @if($showParent)
                            <td>{{ $item->parent?->name ?: 'دستهٔ اصلی' }}</td>
                            <td>{{ number_format((int) $item->children_count) }}</td>
                        @endif
                        <td>{{ number_format((int) $item->products_count) }}</td>
                        <td><span class="ak-status-pill {{ $item->is_active ? 'status-active' : 'status-inactive' }}">{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                        <td>@commerceDate($item->updated_at, 'j F Y')</td>
                        <td><div class="ak-toolbar">
                            <a class="ak-button ak-button-light ak-button-sm" href="{{ route($routePrefix . '.edit', $item->getKey()) }}">ویرایش</a>
                            <form method="POST" action="{{ route($routePrefix . '.destroy', $item->getKey()) }}" data-confirm="حذف یا غیرفعال‌کردن این رکورد انجام شود؟">@csrf @method('DELETE')<button class="ak-button ak-button-light ak-button-sm" type="submit">حذف</button></form>
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="ak-pagination-wrap"><span>نمایش {{ $items->firstItem() }} تا {{ $items->lastItem() }} از {{ $items->total() }}</span>{{ $items->links('commerce-admin::partials.pagination') }}</div>
        @endif
    </section>
@endsection
