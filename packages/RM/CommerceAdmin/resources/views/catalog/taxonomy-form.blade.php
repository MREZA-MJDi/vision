@extends('commerce-admin::layout')

@section('title', ($editing ? 'ویرایش ' : 'افزودن ') . $taxonomyTitle)

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb"><a href="{{ route($routePrefix . '.index') }}">{{ $taxonomyPlural ?? ($taxonomyTitle . 'ها') }}</a> <span>/</span> {{ $editing ? 'ویرایش' : 'ایجاد' }}</div><h1>{{ $editing ? 'ویرایش ' . $taxonomyTitle : 'افزودن ' . $taxonomyTitle }}</h1><p>اطلاعات کاتالوگ را ذخیره کنید؛ آدرس یکتا در صورت خالی‌بودن به‌صورت خودکار ساخته می‌شود.</p></div>
    </div>
    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route($routePrefix . '.update', $record->getKey()) : route($routePrefix . '.store') }}" class="ak-form-layout">
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
        @php
            $image = $showParent ? $record->coverMedia : $record->logoMedia;
            $prefix = $showParent ? 'cover' : 'logo';
        @endphp
        <section class="ak-panel ak-form-panel">
            <div class="ak-panel-heading"><div><h2>تصویر {{ $taxonomyTitle }}</h2><p>تصویر در صفحهٔ اصلی فروشگاه نمایش داده می‌شود.</p></div></div>
            <div class="ak-category-cover-upload" data-taxonomy-image>
                <img data-taxonomy-image-preview @if($image?->url) src="{{ $image->url }}" data-existing-src="{{ $image->url }}" @else hidden @endif alt="{{ $image?->alt_text ?: $record->name }}">
                <label class="ak-field"><span>انتخاب و برش تصویر</span>
                    <input type="file" name="{{ $prefix }}_image" accept="image/jpeg,image/png,image/webp" data-taxonomy-image-input>
                    <small>JPG، PNG یا WebP؛ حداکثر ۴ مگابایت پس از برش.</small>
                    @error($prefix . '_image')<small role="alert">{{ $message }}</small>@enderror
                </label>
                <label class="ak-field"><span>متن جایگزین تصویر</span><input name="{{ $prefix }}_alt" maxlength="255" value="{{ old($prefix . '_alt', $image?->alt_text) }}"></label>
                @if($image?->url)
                    <label class="ak-check-card"><input type="checkbox" name="remove_{{ $prefix }}" value="1" data-remove-taxonomy-image @checked(old('remove_' . $prefix))><span>حذف تصویر فعلی</span></label>
                @endif
            </div>
        </section>

        <section class="ak-panel ak-form-panel">
            <div class="ak-panel-heading"><div><h2>اطلاعات سئو</h2><p>اختیاری؛ برای نمایش و جست‌وجوی بهتر</p></div></div>
            <div class="ak-form-grid">
                <label class="ak-field"><span>عنوان متا</span><input name="meta_title" maxlength="255" value="{{ old('meta_title', $record->meta_title) }}" placeholder="{{ $record->meta_title ?: $record->name }}"></label>
                <label class="ak-field"><span>توضیح متا</span><textarea rows="3" name="meta_description" maxlength="500" placeholder="{{ $record->meta_description ?: $record->description ?: 'توضیح کوتاه و دقیق برای نتایج جست‌وجو' }}">{{ old('meta_description', $record->meta_description) }}</textarea></label>
            </div>
        </section>
        <div class="ak-heading-actions"><button class="ak-button ak-button-dark" type="submit">ذخیره</button><a class="ak-button ak-button-light" href="{{ route($routePrefix . '.index') }}">انصراف</a></div>
    </form>
    @include('commerce-admin::catalog.image-cropper')
@endsection
