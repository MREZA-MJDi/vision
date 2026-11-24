@extends('commerce-admin::layout')
@section('title', 'مدیریت برندها')
@section('content')
<div class="ak-page-heading"><div><h1>برندها</h1><p>مدیریت اطلاعات و تصاویر فروشگاه</p></div><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.brands.create') }}">+ افزودن برند</a></div>
@include('commerce-admin::components.catalog.filters', ['action' => route('commerce-admin.brands.index')])
<section class="ak-panel">
    <div class="rm-taxonomy-table-wrap"><table class="rm-taxonomy-table">
        <thead><tr><th>تصویر</th><th>نام</th><th>شناسه آدرس</th><th>محصولات</th><th>وضعیت</th><th>عملیات</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>@if($item->logoMedia?->url)<img src="{{ $item->logoMedia->url }}" alt="{{ $item->logoMedia->alt_text ?: $item->name }}" loading="lazy" width="56" height="56">@else<span class="rm-taxonomy-thumbnail">—</span>@endif</td>
                <td><strong>{{ $item->name }}</strong></td>
                <td dir="ltr">{{ $item->slug }}</td>
                <td>{{ $item->products_count }}</td><td>{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</td>
                <td><div class="rm-uploader__actions"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.brands.edit', $item->getKey()) }}">ویرایش</a>
                    <form method="POST" action="{{ route('commerce-admin.brands.destroy', $item->getKey()) }}" data-confirm="از حذف یا غیرفعال‌سازی اطمینان دارید؟">@csrf @method('DELETE')<button type="submit" data-rm-remove>حذف</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6">موردی یافت نشد.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="rm-taxonomy-pagination">{{ $items->links() }}</div>
</section>
@endsection
