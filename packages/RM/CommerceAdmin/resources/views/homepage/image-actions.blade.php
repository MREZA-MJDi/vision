@php
    $parameters = ['type' => $ownerType, 'owner' => $owner->getKey()];
@endphp
<div class="ak-stack">
    <small>{{ $ownerType === 'variant' ? 'تصویر اختصاصی تنوع' : ($ownerType === 'product' ? 'تصویر اصلی محصول' : ($ownerType === 'category' ? 'کاور دسته‌بندی' : 'لوگوی برند')) }}</small>
    <form method="POST" enctype="multipart/form-data" action="{{ route('commerce-admin.homepage.images.update', $parameters) }}">
        @csrf @method('PUT')
        <input type="hidden" name="media_id" value="{{ $media?->id }}">
        <label class="ak-field"><span>{{ $media ? 'جایگزینی تصویر' : 'افزودن تصویر' }}</span>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
        </label>
        <button class="ak-button ak-button-dark" type="submit">ذخیره تصویر</button>
    </form>
    @if($media)
        <form method="POST" action="{{ route('commerce-admin.homepage.images.destroy', $parameters) }}" onsubmit="return confirm('تصویر از منبع اصلی حذف می‌شود. ادامه می‌دهید؟')">
            @csrf @method('DELETE')
            <input type="hidden" name="media_id" value="{{ $media->id }}">
            <button class="ak-button ak-button-light" type="submit">حذف تصویر</button>
        </form>
    @endif
</div>
