@extends('commerce-admin::layout')

@section('title', ($editing ? 'ویرایش ' : 'افزودن ') . $taxonomyTitle)

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb"><a href="{{ route($routePrefix . '.index') }}">{{ $taxonomyPlural ?? ($taxonomyTitle . 'ها') }}</a> <span>/</span> {{ $editing ? 'ویرایش' : 'ایجاد' }}</div><h1>{{ $editing ? 'ویرایش ' . $taxonomyTitle : 'افزودن ' . $taxonomyTitle }}</h1><p>اطلاعات کاتالوگ را ذخیره کنید؛ آدرس یکتا در صورت خالی‌بودن به‌صورت خودکار ساخته می‌شود.</p></div>
    </div>
    <form method="POST" action="{{ $editing ? route($routePrefix . '.update', $record->getKey()) : route($routePrefix . '.store') }}" class="ak-form-layout">
        @csrf
        @if($editing) @method('PUT') @endif
        <section class="ak-panel ak-form-panel">
            <div class="ak-panel-heading"><div><h2>اطلاعات پایه</h2><p>نام و وضعیت نمایش در فروشگاه</p></div></div>
            <div class="ak-form-grid">
                <label class="ak-field"><span>نام {{ $taxonomyTitle }} *</span><input name="name" required maxlength="255" value="{{ old('name', $record->name) }}"></label>
                <label class="ak-field"><span>شناسهٔ آدرس (اختیاری)</span><input name="slug" maxlength="255" dir="ltr" value="{{ old('slug', $record->slug) }}"><small>در صورت خالی‌بودن، مقدار یکتا ساخته می‌شود.</small></label>
                @if($showParent)
                    <label class="ak-field"><span>دسته‌بندی والد</span><select name="parent_id"><option value="">دستهٔ اصلی</option>@foreach($parents as $parent)<option value="{{ $parent->getKey() }}" @selected((string) old('parent_id', $record->parent_id) === (string) $parent->getKey())>{{ $parent->name }}</option>@endforeach</select></label>
                    <label class="ak-field"><span>ترتیب نمایش</span><input type="number" min="0" max="100000" name="sort_order" value="{{ old('sort_order', $record->sort_order ?? 0) }}"></label>
                @endif
                <label class="ak-field"><span>وضعیت</span><select name="status"><option value="active" @selected(old('status', $record->is_active ? 'active' : 'draft') === 'active')>فعال</option><option value="draft" @selected(old('status', $record->is_active ? 'active' : 'draft') === 'draft')>غیرفعال</option></select></label>
                <label class="ak-field ak-field-full"><span>توضیحات</span><textarea rows="4" name="description">{{ old('description', $record->description) }}</textarea></label>
            </div>
        </section>
        <section class="ak-panel ak-form-panel">
            <div class="ak-panel-heading"><div><h2>اطلاعات سئو</h2><p>اختیاری؛ برای نمایش و جست‌وجوی بهتر</p></div></div>
            <div class="ak-form-grid">
                <label class="ak-field"><span>عنوان متا</span><input name="meta_title" maxlength="255" value="{{ old('meta_title', $record->meta_title) }}"></label>
                <label class="ak-field"><span>توضیح متا</span><textarea rows="3" name="meta_description" maxlength="500">{{ old('meta_description', $record->meta_description) }}</textarea></label>
            </div>
        </section>
        <div class="ak-heading-actions"><button class="ak-button ak-button-dark" type="submit">ذخیره</button><a class="ak-button ak-button-light" href="{{ route($routePrefix . '.index') }}">انصراف</a></div>
    </form>
@endsection
