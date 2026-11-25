@php
    $options = $currentVariant && !$variants->contains('id', $currentVariant->id)
        ? $variants->getCollection()->prepend($currentVariant)
        : $variants;
    $selectedId = old($slot . '_variant_id', $currentVariant?->id);
@endphp

<label class="fr-field">
    <span>انتخاب محصول و رنگ / سایز</span>
    <input type="search" data-fr-picker-search placeholder="جست‌وجو در همین فهرست" aria-label="جست‌وجوی محصول {{ $slotLabel }}">
    <select name="{{ $slot }}_variant_id" data-fr-variant>
        <option value="">بدون {{ $slotLabel }}</option>
        @foreach($options as $variant)
            <option value="{{ $variant->id }}" data-audience="{{ $variant->product?->audience }}" @selected((int) $selectedId === (int) $variant->id)>
                {{ $variant->product?->name }} — {{ $variant->display_name }} · {{ $variant->sku }}{{ $variant->is_active && $variant->product?->is_active ? '' : ' (پیش‌نویس)' }}
            </option>
        @endforeach
    </select>
</label>
<figure class="fr-variant-preview" data-fr-variant-preview hidden>
    <div class="fr-product-frame product-media-frame">
        <img alt="" data-fr-card-image hidden>
        <span data-fr-card-empty>بدون تصویر محصول</span>
    </div>
    <figcaption>
        <strong data-fr-card-name></strong>
        <small data-fr-variant-name></small>
        <span data-fr-card-price></span>
        <small data-fr-card-stock></small>
    </figcaption>
</figure>
<fieldset class="fr-card-framing" data-fr-frame-controls hidden disabled>
    <legend>تنظیم اختیاری اندازه و موقعیت تصویر کارت</legend>
    @foreach([
        ['scale', 'اندازهٔ تصویر', 80, 125],
        ['x', 'جابه‌جایی افقی', -12, 12],
        ['y', 'جابه‌جایی عمودی', -12, 12],
    ] as [$key, $label, $min, $max])
        @php($default = \App\Models\Media::DEFAULT_CARD_FRAME[$key])
        <label>
            <span>{{ $label }}</span>
            <input type="range" dir="ltr" name="{{ $slot }}_card_frame[{{ $key }}]" min="{{ $min }}" max="{{ $max }}" value="{{ $default }}" data-fr-frame="{{ $key }}">
            <output>{{ \App\Support\Money::number($default) }}٪</output>
        </label>
    @endforeach
    <button type="button" class="fr-button" data-fr-frame-reset>بازنشانی کادر</button>
</fieldset>
