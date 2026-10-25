@extends('commerce-admin::layout')

@section('title', 'مدیریت اتاق پرو')

@section('content')
    <div class="ak-page-heading">
        <div>
            <div class="ak-breadcrumb"><a href="{{ route('commerce-admin.dashboard') }}">داشبورد</a> <span>/</span> اتاق پرو</div>
            <h1>مدیریت اتاق پرو</h1>
            <p>تصویر پایهٔ مانکن و لایهٔ شفاف هر تنوع محصول را در همین پنل مدیریت کنید.</p>
        </div>
        <a class="ak-button ak-button-light" href="{{ route('commerce-admin.products.index') }}">رفتن به محصولات ↗</a>
    </div>

    <section class="ak-panel ak-form-panel">
        <div class="ak-panel-heading">
            <div><h2>مانکن‌ها و تصاویر پایه</h2><p>تصویر پایه باید شفاف و هم‌اندازهٔ بوم ۱۰۲۴×۱۵۳۶ باشد.</p></div>
        </div>
        <div class="ak-tryon-admin-list">
            @forelse($mannequins as $mannequin)
                <form class="ak-tryon-admin-card" method="POST" enctype="multipart/form-data" action="{{ route('commerce-admin.try-on.mannequins.update', $mannequin) }}">
                    @csrf
                    @method('PUT')
                    <div class="ak-tryon-admin-card__preview">
                        @if($mannequin->baseMedia?->url)
                            <img src="{{ $mannequin->baseMedia->url }}" alt="{{ $mannequin->label }}">
                        @else
                            <span>تصویر پایه ثبت نشده</span>
                        @endif
                    </div>
                    <div class="ak-tryon-admin-card__fields">
                        <strong>{{ $mannequin->audience === 'women' ? 'زنانه' : 'مردانه' }} · {{ $mannequin->code }}</strong>
                        <label class="ak-field"><span>عنوان مانکن</span><input name="label" value="{{ old('label', $mannequin->label) }}" maxlength="160" required></label>
                        <label class="ak-field"><span>تصویر پایه</span><input type="file" name="base_image" accept="image/png,image/webp,image/jpeg"><small>PNG، WebP یا JPG · حداکثر ۴ مگابایت · ۱۰۲۴×۱۵۳۶</small></label>
                        <label class="ak-check-card"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $mannequin->is_active))><span><strong>مانکن فعال</strong><small>برای پیش‌نمایش مشتری در دسترس باشد.</small></span></label>
                        <button class="ak-button ak-button-dark" type="submit">ذخیرهٔ مانکن</button>
                    </div>
                </form>
            @empty
                <p>هنوز مانکنی ثبت نشده است. ابتدا دادهٔ پایهٔ مانکن را در پیکربندی Virtual Try-On تعریف کنید.</p>
            @endforelse
        </div>
    </section>

    <section class="ak-panel ak-form-panel">
        <div class="ak-panel-heading">
            <div><h2>تصاویر لباس روی مانکن</h2><p>محصول و تنوع را انتخاب کنید، جایگاه لباس را مشخص کنید و تصویر لایه‌ای آن را بارگذاری کنید.</p></div>
        </div>
        <div class="ak-tryon-admin-list">
            @forelse($variants as $variant)
                @php
                    $asset = $variant->tryOnAssets->first();
                    $layerImage = $asset?->media?->url;
                @endphp
                <form class="ak-tryon-admin-card" method="POST" enctype="multipart/form-data" action="{{ route('commerce-admin.try-on.variants.update', $variant) }}">
                    @csrf
                    @method('PUT')
                    <div class="ak-tryon-admin-card__preview">
                        @if($layerImage)
                            <img src="{{ $layerImage }}" alt="لایهٔ {{ $variant->product?->name }}">
                        @elseif($variant->primaryGalleryMedia?->url)
                            <img src="{{ $variant->primaryGalleryMedia->url }}" alt="{{ $variant->product?->name }}">
                        @else
                            <span>تصویر ثبت نشده</span>
                        @endif
                    </div>
                    <div class="ak-tryon-admin-card__fields">
                        <strong>{{ $variant->product?->name ?? 'محصول حذف‌شده' }}</strong>
                        <small>{{ $variant->display_name }} · شناسهٔ تنوع {{ $variant->id }}</small>
                        <label class="ak-field"><span>مانکن سازگار</span>
                            <select name="mannequin_id" required>
                                <option value="">انتخاب مانکن</option>
                                @foreach($mannequins as $mannequin)
                                    @if($mannequin->is_active && $mannequin->audience === $variant->product?->audience)
                                        <option value="{{ $mannequin->id }}" @selected((string)old('mannequin_id', $asset?->try_on_mannequin_id) === (string)$mannequin->id)>{{ $mannequin->label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </label>
                        <label class="ak-field"><span>جایگاه لباس</span>
                            <select name="slot" required>
                                @foreach(\Rm\VirtualTryOn\Support\VirtualTryOnConfig::SLOT_LABELS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('slot', $asset?->slot ?? 'top') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="ak-field"><span>{{ $layerImage ? 'تعویض تصویر لایه‌ای' : 'تصویر لایه‌ای محصول' }}</span><input type="file" name="layer_image" accept="image/png,image/webp"><small>PNG/WebP با شفافیت و اندازهٔ دقیق ۱۰۲۴×۱۵۳۶، هم‌تراز با مانکن.</small></label>
                        <label class="ak-check-card"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $asset?->is_active ?? true))><span><strong>فعال در اتاق پرو</strong><small>فقط لایه‌های فعال برای مشتری نمایش داده می‌شوند.</small></span></label>
                        <label class="ak-check-card"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $asset?->is_default ?? false))><span><strong>لباس پیش‌فرض جایگاه</strong><small>هنگام انتخاب این مانکن به‌صورت اولیه نمایش داده شود.</small></span></label>
                        <button class="ak-button ak-button-dark" type="submit">ذخیرهٔ تصویر لباس</button>
                    </div>
                </form>
            @empty
                <p>محصول فعال و تنوعی برای مدیریت پیدا نشد.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('head')
<style>
    .ak-tryon-admin-list{display:grid;gap:1rem}
    .ak-tryon-admin-card{display:grid;grid-template-columns:minmax(8rem,12rem) minmax(0,1fr);gap:1rem;padding:1rem;border:1px solid var(--ak-line,#e5e7eb);border-radius:1rem;background:var(--ak-surface,#fff)}
    .ak-tryon-admin-card__preview{display:grid;place-items:center;min-height:12rem;overflow:hidden;border-radius:.8rem;background:#f3f0e9;color:#777;font-size:.8rem;text-align:center}
    .ak-tryon-admin-card__preview img{display:block;width:100%;height:100%;max-height:22rem;object-fit:contain}
    .ak-tryon-admin-card__fields{display:grid;align-content:start;gap:.8rem;min-width:0}
    .ak-tryon-admin-card__fields>small{color:var(--ak-muted,#737373);font-size:.78rem}
    @media(max-width:720px){.ak-tryon-admin-card{grid-template-columns:minmax(0,1fr)}.ak-tryon-admin-card__preview{min-height:10rem}}
</style>
@endpush
