@php
    $options = $currentVariant && !$variants->contains('id', $currentVariant->id)
        ? $variants->getCollection()->prepend($currentVariant)
        : $variants;
    $selectedId = old($slot . '_variant_id', $currentVariant?->id);
@endphp

<label class="fr-field">
    <span>محصول و وریانت</span>
    <select name="{{ $slot }}_variant_id" data-fr-variant>
        <option value="">بدون {{ $slotLabel }}</option>
        @foreach($options as $variant)
            <option value="{{ $variant->id }}"
                    data-image="{{ $variant->primaryGalleryMedia?->url ?: $variant->product?->primaryGalleryMedia?->url }}"
                    data-name="{{ $variant->product?->name }} · {{ $variant->display_name }}"
                    data-price="{{ \App\Support\Money::format($variant->effective_price) }}"
                    data-stock="{{ $variant->stock > 0 ? \App\Support\Money::number($variant->stock) . ' عدد موجود' : 'ناموجود' }}"
                    @selected((int) $selectedId === (int) $variant->id)>
                {{ $variant->product?->name }} — {{ $variant->display_name }} · {{ $variant->sku }}
            </option>
        @endforeach
    </select>
</label>
<figure class="fr-variant-preview" data-fr-variant-preview hidden>
    <div class="fr-product-frame">
        <img alt="" data-fr-card-image hidden>
        <span data-fr-card-empty>بدون تصویر محصول</span>
    </div>
    <figcaption>
        <strong data-fr-card-name></strong>
        <span data-fr-card-price></span>
        <small data-fr-card-stock></small>
    </figcaption>
</figure>
