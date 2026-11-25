@extends('commerce-admin::layout')
@section('title', 'مدیریت دسته‌بندی‌ها')
@section('content')
<div class="ak-page-heading"><div><h1>دسته‌بندی‌ها</h1><p>مدیریت اطلاعات و تصاویر فروشگاه</p></div><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.categories.create') }}">+ افزودن دسته‌بندی</a></div>
@include('commerce-admin::components.catalog.filters', ['action' => route('commerce-admin.categories.index')])
<section class="ak-panel">
    <div class="rm-taxonomy-table-wrap"><table class="rm-taxonomy-table">
        <thead><tr><th>تصویر</th><th>نام</th><th>والد</th><th>زیرمجموعه</th><th>ترتیب</th><th>محصولات</th><th>وضعیت</th><th>عملیات</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>@if($item->coverMedia?->url)<img src="{{ $item->coverMedia->url }}" alt="{{ $item->coverMedia->alt_text ?: $item->name }}" loading="lazy" width="56" height="56">@else<span class="rm-taxonomy-thumbnail">—</span>@endif</td>
                <td><strong>{{ $item->name }}</strong></td>
                <td>{{ $item->parent?->name ?? 'اصلی' }}</td><td>{{ $item->children_count }}</td><td>{{ $item->sort_order }}</td>
                <td>{{ $item->products_count }}</td><td>{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</td>
                <td><div class="rm-uploader__actions"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.categories.edit', $item->getKey()) }}">ویرایش</a>
                    <form method="POST" action="{{ route('commerce-admin.categories.destroy', $item->getKey()) }}" data-confirm="از حذف یا غیرفعال‌سازی اطمینان دارید؟">@csrf @method('DELETE')<button type="submit" data-rm-remove>حذف</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="8">موردی یافت نشد.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="rm-taxonomy-pagination">{{ $items->links() }}</div>
</section>
@endsection
