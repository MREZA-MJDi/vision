
<section class="ak-panel ak-form-panel">
    <div class="ak-panel-heading">
        <div>
            <h2>بهینه‌سازی موتور جست‌وجو</h2>
            <p>مدیریت اطلاعات سئو و نمایش در نتایج جست‌وجو</p>
        </div>
    </div>

    <div class="ak-form-grid">
        <label class="ak-field ak-field-full">
            <span>عنوان سئو</span>

            <input
                type="text"
                name="meta_title"
                maxlength="255"
                value="{{ old('meta_title', $record->meta_title) }}"
                placeholder="{{ $record->name ?: 'عنوان صفحه' }}"
            >

            <small>
                در صورت خالی بودن، عنوان به صورت خودکار ساخته می‌شود.
            </small>

            @error('meta_title')
                <small role="alert">{{ $message }}</small>
            @enderror
        </label>

        <label class="ak-field ak-field-full">
            <span>توضیحات سئو</span>

            <textarea
                name="meta_description"
                maxlength="500"
                rows="4"
                placeholder="توضیح کوتاه و مرتبط با محتوای صفحه"
            >{{ old('meta_description', $record->meta_description) }}</textarea>

            @error('meta_description')
                <small role="alert">{{ $message }}</small>
            @enderror
        </label>
    </div>
</section>
