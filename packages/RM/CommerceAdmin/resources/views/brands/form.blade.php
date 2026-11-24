<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="rm-taxonomy-form">
    @csrf
    @if($editing) @method('PUT') @endif
    @if($errors->any())<div class="rm-uploader__error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="rm-taxonomy-grid">
        <div class="rm-taxonomy-column">
            <section class="ak-panel ak-form-panel">
                <div class="ak-panel-heading"><h2>اطلاعات برند</h2></div>
                <div class="ak-form-grid">
                    <label class="ak-field"><span>نام برند *</span><input name="name" required maxlength="255" value="{{ old('name', $record->name) }}"></label>
                    <label class="ak-field"><span>شناسه آدرس</span><input name="slug" dir="ltr" maxlength="255" value="{{ old('slug', $record->slug) }}"><small>در صورت خالی بودن خودکار ساخته می‌شود.</small></label>
                    
                    <label class="ak-field"><span>وضعیت</span><select name="status"><option value="active" @selected(old('status', $editing && !$record->is_active ? 'draft' : 'active') === 'active')>فعال</option><option value="draft" @selected(old('status', $editing && !$record->is_active ? 'draft' : 'active') === 'draft')>غیرفعال</option></select></label>
                    <label class="ak-field ak-field-full"><span>توضیحات</span><textarea name="description" maxlength="5000" rows="5">{{ old('description', $record->description) }}</textarea></label>
                </div>
            </section>
            @include('commerce-admin::components.catalog.seo-fields')
        </div>
        <aside class="rm-taxonomy-column">
            @include('commerce-admin::components.media.image-uploader', ['media' => $record->logoMedia, 'prefix' => 'logo', 'title' => 'تصویر برند', 'aspect' => 1])
        </aside>
    </div>
    <div class="rm-taxonomy-footer"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.brands.index') }}">انصراف</a><button type="submit" class="ak-button ak-button-dark">ذخیره</button></div>
</form>
