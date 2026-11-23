@extends('commerce-admin::layout')

@section('title', 'Fitting Room Studio')

@section('content')
<div class="fr-studio">
    @if (session('status'))
        <div class="fr-feedback fr-feedback--success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="fr-feedback fr-feedback--error" role="alert">
            <strong>ذخیره انجام نشد؛ موارد زیر را اصلاح کن:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <header class="fr-hero">
        <div class="fr-hero__copy">
            <p class="fr-kicker"><span></span> VISION / CREATIVE LAB</p>
            <h1>Fitting Room <em>Studio</em></h1>
            <p>مانکن‌ها، تصویر لباس‌ها و ست‌های پیشنهادی را در یک گالری منظم بساز و کنترل کن. داده‌ها مستقیم به کاتالوگ و واریانت‌های واقعی متصل هستند.</p>
            <div class="fr-hero__actions">
                <a class="fr-button fr-button--primary" href="#fr-looks">ساخت ست جدید <span>↗</span></a>
                <a class="fr-button" href="{{ route('commerce-admin.products.index') }}">مدیریت محصولات و واریانت‌ها <span>↗</span></a>
                <a class="fr-button" href="{{ route('shop.index') }}" target="_blank" rel="noopener">پیش‌نمایش فروشگاه <span>↗</span></a>
            </div>
        </div>
        <div class="fr-hero__mark" aria-hidden="true"><span>V</span><i></i><b></b></div>
        <div class="fr-hero__stats">
            <div><strong>{{ number_format($mannequins->count()) }}</strong><span>مدل مانکن</span></div>
            <div><strong>{{ number_format($mannequins->sum('looks_count')) }}</strong><span>ست ثبت‌شده</span></div>
            <div><strong>{{ number_format($variants->count()) }}</strong><span>واریانت در دسترس</span></div>
        </div>
    </header>

    <section class="fr-section" id="fr-mannequins">
        <div class="fr-section__head">
            <div><p class="fr-kicker">01 / MANNEQUIN GALLERY</p><h2>گالری مانکن‌ها</h2><p>عکس پایهٔ مانکن را با قاب ۲:۳ آپلود کن. اولین مانکن فعال با اولویت کمتر، پیش‌فرض ویترین است.</p></div>
            <span class="fr-section__count">{{ number_format($mannequins->count()) }} مدل</span>
        </div>

        <div class="fr-mannequin-grid">
            <section class="fr-panel fr-new-mannequin">
                <div class="fr-panel__head"><span class="fr-step">NEW MODEL</span><span class="fr-plus">＋</span></div>
                <h3>افزودن مانکن</h3>
                <p class="fr-muted">فایل پایهٔ تصویر مانکن؛ فرمت JPG، PNG یا WebP.</p>
                <form method="POST" action="{{ route('commerce-admin.try-on.mannequins.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="fr-field"><span>نام نمایشی</span><input name="label" value="{{ old('label') }}" placeholder="مانکن زنانه — نمای روبه‌رو" required maxlength="160"></label>
                    <div class="fr-form-row">
                        <label class="fr-field"><span>کد یکتا</span><input name="code" value="{{ old('code') }}" placeholder="women-front" pattern="[a-zA-Z0-9_-]+" required maxlength="80"></label>
                        <label class="fr-field"><span>گروه</span><select name="audience" required><option value="women">زنانه</option><option value="men">مردانه</option><option value="all">عمومی</option></select></label>
                    </div>
                    <label class="fr-field"><span>اولویت نمایش</span><input type="number" name="sort_order" value="0" min="0" max="65535"></label>
                    <label class="fr-upload">
                        <input type="file" name="base_image" accept="image/png,image/webp,image/jpeg" required data-fr-preview-input>
                        <span class="fr-upload__icon">＋</span><strong>انتخاب تصویر مانکن</strong><small>نسبت تصویر ۲:۳ · حداکثر ۸ مگابایت</small>
                        <img alt="" data-fr-preview hidden>
                    </label>
                    <label class="fr-check"><input type="checkbox" name="is_active" value="1" checked><span>در فروشگاه فعال باشد</span></label>
                    <button class="fr-button fr-button--primary fr-button--wide" type="submit">افزودن به گالری <span>↗</span></button>
                </form>
            </section>

            @forelse($mannequins as $mannequin)
                <article class="fr-mannequin-card">
                    <div class="fr-mannequin-card__visual">
                        @if($mannequin->baseMedia?->url)
                            <img src="{{ $mannequin->baseMedia->url }}" alt="{{ $mannequin->label }}" loading="lazy">
                        @else
                            <div class="fr-image-empty"><span>V.</span><small>تصویر پایه ثبت نشده</small></div>
                        @endif
                        <span class="fr-chip {{ $mannequin->is_active ? 'is-live' : '' }}">{{ $mannequin->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        <span class="fr-count-chip">{{ $mannequin->looks_count }} ست</span>
                    </div>
                    <div class="fr-mannequin-card__body">
                        <div class="fr-mannequin-card__title"><div><h3>{{ $mannequin->label }}</h3><p>{{ $mannequin->code }} · {{ $mannequin->audience === 'women' ? 'زنانه' : ($mannequin->audience === 'men' ? 'مردانه' : 'عمومی') }}</p></div><span class="fr-order">#{{ $mannequin->sort_order }}</span></div>
                        <form method="POST" action="{{ route('commerce-admin.try-on.mannequins.update', $mannequin) }}" enctype="multipart/form-data" class="fr-compact-form">
                            @csrf @method('PUT')
                            <label class="fr-field"><span>نام مدل</span><input name="label" value="{{ $mannequin->label }}" required maxlength="160"></label>
                            <div class="fr-form-row">
                                <label class="fr-field"><span>گروه</span><select name="audience"><option value="women" @selected($mannequin->audience === 'women')>زنانه</option><option value="men" @selected($mannequin->audience === 'men')>مردانه</option><option value="all" @selected($mannequin->audience === 'all')>عمومی</option></select></label>
                                <label class="fr-field"><span>اولویت</span><input type="number" name="sort_order" value="{{ $mannequin->sort_order }}" min="0" max="65535"></label>
                            </div>
                            <label class="fr-upload fr-upload--compact"><input type="file" name="base_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس پایه</strong><small>در صورت نیاز عکس جدید انتخاب کن</small><img alt="" data-fr-preview hidden></label>
                            <label class="fr-check"><input type="checkbox" name="is_active" value="1" @checked($mannequin->is_active)><span>فعال در ویترین</span></label>
                            <button class="fr-button fr-button--primary fr-button--wide" type="submit">ذخیرهٔ مدل</button>
                        </form>
                        <form method="POST" action="{{ route('commerce-admin.try-on.mannequins.destroy', $mannequin) }}" onsubmit="return confirm('این مانکن و ست‌های وابسته حذف شوند؟')">
                            @csrf @method('DELETE')
                            <button class="fr-danger-link" type="submit">حذف مانکن و ست‌هایش</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="fr-empty"><strong>گالری از اینجا شروع می‌شود.</strong><span>تصویر پایهٔ مانکن را از فرم کناری آپلود کن.</span></div>
            @endforelse
        </div>
    </section>

    <section class="fr-section" id="fr-looks">
        <div class="fr-section__head">
            <div><p class="fr-kicker">02 / LOOK COMPOSER</p><h2>ترکیب لباس و تصاویر مانکن</h2><p>برای هر ترکیب، واریانت واقعی بالاپوش و پایین‌تنه را انتخاب کن؛ سپس سه عکس واقعی T، B و TB را برای همان مانکن و همان محصولات ثبت کن. تصویر کارت محصول و قیمت همچنان از گالری و دیتابیس محصول خوانده می‌شود.</p></div>
            <span class="fr-section__count">{{ number_format($mannequins->sum('looks_count')) }} ست</span>
        </div>

        @if($mannequins->isNotEmpty())
        <section class="fr-panel fr-look-editor">
            <div class="fr-panel__head"><span class="fr-step">STEP 02 / DEFINE GARMENTS</span><span class="fr-plus">＋</span></div>
            <h3>ساخت ست جدید</h3>
            <p class="fr-muted">عکس‌های این بخش با قاب ۲:۳ بارگذاری شوند؛ عکس محصول/واریانت جداگانه است و می‌توانی همین‌جا هم به آن اضافه‌اش کنی.</p>
            <form method="POST" action="{{ route('commerce-admin.try-on.looks.store') }}" enctype="multipart/form-data" data-fr-look-form="create">
                @csrf
                <div class="fr-look-meta">
                    <label class="fr-field"><span>مانکن</span><select name="mannequin_id" required data-fr-initial-value="{{ old('mannequin_id', $mannequins->first()->id) }}">@foreach($mannequins as $mannequin)<option value="{{ $mannequin->id }}" @selected((int) old('mannequin_id', $mannequins->first()->id) === (int) $mannequin->id)>{{ $mannequin->label }}{{ $mannequin->is_active ? '' : ' (غیرفعال)' }}</option>@endforeach</select></label>
                    <label class="fr-field"><span>عنوان ست</span><input name="label" value="{{ old('label') }}" placeholder="استایل روزمره نارنجی" required maxlength="160"></label>
                    <label class="fr-field"><span>کد یکتا</span><input name="code" value="{{ old('code') }}" placeholder="orange-casual" pattern="[a-zA-Z0-9_-]+" required maxlength="80"></label>
                    <label class="fr-field"><span>اولویت گالری</span><input type="number" name="sort_order" value="0" min="0" max="65535"></label>
                </div>
                <div class="fr-look-products">
                    <div class="fr-assignment">
                        <div class="fr-assignment__title"><span class="fr-slot-icon">T</span><div><strong>بالاپوش</strong><small>واریانت واقعی + تصویر کارت</small></div></div>
                        <label class="fr-field"><span>محصول / واریانت</span><select name="top_variant_id" data-fr-initial-value="{{ old('top_variant_id', '') }}"><option value="">بدون بالاپوش</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected((int) old('top_variant_id', 0) === (int) $variant->id)>{{ $variant->product?->name }} — {{ $variant->display_name }} · #{{ $variant->id }}</option>@endforeach</select></label>
                        <label class="fr-upload fr-upload--compact"><input type="file" name="top_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">＋</span><strong>آپلود عکس محصول</strong><small>برای کارت فروشگاه · تصویر واریانت</small><img alt="" data-fr-preview hidden></label>
                        <label class="fr-field"><span>نقطهٔ حرکت انیمیشن</span><input type="number" name="fit_y_top" value="0.38" min="0" max="1" step="0.01"></label>
                    </div>
                    <div class="fr-assignment">
                        <div class="fr-assignment__title"><span class="fr-slot-icon">B</span><div><strong>پایین‌تنه</strong><small>واریانت واقعی + تصویر کارت</small></div></div>
                        <label class="fr-field"><span>محصول / واریانت</span><select name="bottom_variant_id" data-fr-initial-value="{{ old('bottom_variant_id', '') }}"><option value="">بدون پایین‌تنه</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected((int) old('bottom_variant_id', 0) === (int) $variant->id)>{{ $variant->product?->name }} — {{ $variant->display_name }} · #{{ $variant->id }}</option>@endforeach</select></label>
                        <label class="fr-upload fr-upload--compact"><input type="file" name="bottom_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">＋</span><strong>آپلود عکس محصول</strong><small>برای کارت فروشگاه · تصویر واریانت</small><img alt="" data-fr-preview hidden></label>
                        <label class="fr-field"><span>نقطهٔ حرکت انیمیشن</span><input type="number" name="fit_y_bottom" value="0.66" min="0" max="1" step="0.01"></label>
                    </div>
                </div>
                <div class="fr-scene-grid" aria-label="سه حالت تصویر مانکن">
                    @foreach([
                        ['preview_top','T','مانکن با بالاپوش','تصویر همان مانکن با فقط همین تی‌شرت یا بالاپوش','top'],
                        ['preview_bottom','B','مانکن با پایین‌تنه','تصویر همان مانکن با فقط همین شلوار یا پایین‌تنه','bottom'],
                        ['preview_combined','TB','مانکن با ست کامل','تصویر همان مانکن با هر دو واریانت انتخاب‌شده','combined']
                    ] as [$input,$code,$title,$hint,$state])
                        <label class="fr-scene-upload" data-fr-scene-card="{{ $state }}">
                            <span class="fr-scene-upload__code">{{ $code }}</span>
                            <span class="fr-scene-upload__copy"><strong>{{ $title }}</strong><small>{{ $hint }}</small></span>
                            <input type="file" name="{{ $input }}" accept="image/png,image/webp,image/jpeg" data-fr-preview-input data-fr-scene-input="{{ $state }}" data-has-existing="0">
                            <span class="fr-scene-upload__choose">انتخاب تصویر <span>↗</span></span>
                            <small class="fr-scene-status" data-fr-scene-status>پس از انتخاب محصول، وضعیت این تصویر مشخص می‌شود.</small>
                            <img alt="" data-fr-preview hidden>
                        </label>
                    @endforeach
                </div>
                <label class="fr-check"><input type="checkbox" name="is_active" value="1" checked><span>ست در فروشگاه فعال باشد</span></label>
                <button class="fr-button fr-button--primary fr-button--wide" type="submit">ذخیرهٔ ست و صحنه‌ها <span>↗</span></button>
            </form>
        </section>
        @else
            <div class="fr-empty fr-step-note">
                <strong>مرحلهٔ اول هنوز تکمیل نشده است.</strong>
                <span>ابتدا در «گالری مانکن‌ها» یک مانکن همراه تصویر پایه ذخیره کن؛ بعد فرم ثبت لباس و سه حالت تصویر فعال می‌شود.</span>
                <a class="fr-button fr-button--primary" href="#fr-mannequins">رفتن به ساخت مانکن</a>
            </div>
        @endif

        @foreach($mannequins as $mannequin)
            @if($mannequin->looks->isNotEmpty())
                <div class="fr-look-group">
                    <div class="fr-look-group__head"><div><span class="fr-kicker">{{ $mannequin->code }}</span><h3>{{ $mannequin->label }}</h3></div><span>{{ number_format($mannequin->looks->count()) }} ترکیب</span></div>
                    <div class="fr-look-grid">
                        @foreach($mannequin->looks as $look)
                            @php
                                $topItem = $look->items->firstWhere('slot', 'top');
                                $bottomItem = $look->items->firstWhere('slot', 'bottom');
                            @endphp
                            <article class="fr-look-card">
                                <div class="fr-look-card__cover">
                                    @if($look->previewCombinedMedia?->url)
                                        <img src="{{ $look->previewCombinedMedia->url }}" alt="{{ $look->label }}" loading="lazy">
                                    @elseif($look->previewTopMedia?->url)
                                        <img src="{{ $look->previewTopMedia->url }}" alt="{{ $look->label }}" loading="lazy">
                                    @else
                                        <div class="fr-image-empty"><span>TB</span><small>تصویر ست ثبت نشده</small></div>
                                    @endif
                                    <span class="fr-chip {{ $look->is_active ? 'is-live' : '' }}">{{ $look->is_active ? 'فعال' : 'غیرفعال' }}</span>
                                    <span class="fr-look-code">{{ $look->code }}</span>
                                </div>
                                <div class="fr-look-card__body">
                                    <div class="fr-look-card__title"><h3>{{ $look->label }}</h3><span>{{ $look->items->count() }} آیتم</span></div>
                                    <div class="fr-scenes-mini" aria-label="تصاویر ثبت‌شدهٔ T، B و TB">
                                        @foreach([
                                            ['T',$look->previewTopMedia?->url],
                                            ['B',$look->previewBottomMedia?->url],
                                            ['TB',$look->previewCombinedMedia?->url]
                                        ] as [$tag,$url])
                                            <div class="fr-scenes-mini__item"><span>{{ $tag }}</span>@if($url)<img src="{{ $url }}" alt="صحنهٔ {{ $tag }}" loading="lazy">@else<div class="fr-scenes-mini__empty">—</div>@endif</div>
                                        @endforeach
                                    </div>
                                    <div class="fr-look-item-list">
                                        <div><span>T</span><p>{{ $topItem?->variant?->product?->name ?? 'بالاپوش انتخاب نشده' }}<small>{{ $topItem?->variant?->display_name ?? '—' }}</small></p></div>
                                        <div><span>B</span><p>{{ $bottomItem?->variant?->product?->name ?? 'پایین‌تنه انتخاب نشده' }}<small>{{ $bottomItem?->variant?->display_name ?? '—' }}</small></p></div>
                                    </div>
                                    <details class="fr-edit-details">
                                        <summary>ویرایش ست، محصولات و عکس‌ها <span>⌄</span></summary>
                                        <form method="POST" action="{{ route('commerce-admin.try-on.looks.update', $look) }}" enctype="multipart/form-data" data-fr-look-form="edit">
                                            @csrf @method('PUT')
                                            <div class="fr-look-meta">
                                                <label class="fr-field"><span>مانکن</span><select name="mannequin_id" required data-fr-initial-value="{{ $mannequin->id }}">@foreach($mannequins as $option)<option value="{{ $option->id }}" @selected($option->id === $mannequin->id)>{{ $option->label }}</option>@endforeach</select></label>
                                                <label class="fr-field"><span>عنوان ست</span><input name="label" value="{{ $look->label }}" required maxlength="160"></label>
                                                <label class="fr-field"><span>کد</span><input name="code" value="{{ $look->code }}" required pattern="[a-zA-Z0-9_-]+" maxlength="80"></label>
                                                <label class="fr-field"><span>اولویت</span><input type="number" name="sort_order" value="{{ $look->sort_order }}" min="0" max="65535"></label>
                                            </div>
                                            <div class="fr-look-products">
                                                <div class="fr-assignment">
                                                    <div class="fr-assignment__title"><span class="fr-slot-icon">T</span><div><strong>بالاپوش</strong><small>تصویر کارت محصول واریانت</small></div></div>
                                                    <label class="fr-field"><span>محصول / واریانت</span><select name="top_variant_id" data-fr-initial-value="{{ $topItem?->product_variant_id ?? '' }}"><option value="">بدون بالاپوش</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected((int)($topItem?->product_variant_id ?? 0) === (int)$variant->id)>{{ $variant->product?->name }} — {{ $variant->display_name }} · #{{ $variant->id }}</option>@endforeach</select></label>
                                                    @if($topItem?->variant?->primaryGalleryMedia?->url)<img class="fr-current-product-image" src="{{ $topItem->variant->primaryGalleryMedia->url }}" alt="تصویر فعلی بالاپوش">@endif
                                                    <label class="fr-upload fr-upload--compact"><input type="file" name="top_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس کارت</strong><small>خالی بماند تا تصویر فعلی حفظ شود</small><img alt="" data-fr-preview hidden></label>
                                                    <label class="fr-field"><span>نقطهٔ حرکت</span><input type="number" name="fit_y_top" value="{{ $topItem?->fit_y ?? 0.38 }}" min="0" max="1" step="0.01"></label>
                                                </div>
                                                <div class="fr-assignment">
                                                    <div class="fr-assignment__title"><span class="fr-slot-icon">B</span><div><strong>پایین‌تنه</strong><small>تصویر کارت محصول واریانت</small></div></div>
                                                    <label class="fr-field"><span>محصول / واریانت</span><select name="bottom_variant_id" data-fr-initial-value="{{ $bottomItem?->product_variant_id ?? '' }}"><option value="">بدون پایین‌تنه</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected((int)($bottomItem?->product_variant_id ?? 0) === (int)$variant->id)>{{ $variant->product?->name }} — {{ $variant->display_name }} · #{{ $variant->id }}</option>@endforeach</select></label>
                                                    @if($bottomItem?->variant?->primaryGalleryMedia?->url)<img class="fr-current-product-image" src="{{ $bottomItem->variant->primaryGalleryMedia->url }}" alt="تصویر فعلی پایین‌تنه">@endif
                                                    <label class="fr-upload fr-upload--compact"><input type="file" name="bottom_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس کارت</strong><small>خالی بماند تا تصویر فعلی حفظ شود</small><img alt="" data-fr-preview hidden></label>
                                                    <label class="fr-field"><span>نقطهٔ حرکت</span><input type="number" name="fit_y_bottom" value="{{ $bottomItem?->fit_y ?? 0.66 }}" min="0" max="1" step="0.01"></label>
                                                </div>
                                            </div>
                                            <div class="fr-scene-grid fr-scene-grid--compact" aria-label="سه حالت تصویر ثبت‌شدهٔ مانکن">
                                                @foreach([
                                                    ['preview_top','T','مانکن با بالاپوش','top',$look->previewTopMedia?->url],
                                                    ['preview_bottom','B','مانکن با پایین‌تنه','bottom',$look->previewBottomMedia?->url],
                                                    ['preview_combined','TB','مانکن با ست کامل','combined',$look->previewCombinedMedia?->url]
                                                ] as [$input,$code,$title,$state,$url])
                                                    <label class="fr-scene-upload" data-fr-scene-card="{{ $state }}">
                                                        <span class="fr-scene-upload__code">{{ $code }}</span><span class="fr-scene-upload__copy"><strong>{{ $title }}</strong><small>{{ $url ? 'تصویر فعلی ثبت شده؛ با تغییر محصول یا مانکن باید جایگزین شود.' : 'برای این حالت هنوز تصویر ثبت نشده است.' }}</small></span>
                                                        <input type="file" name="{{ $input }}" accept="image/png,image/webp,image/jpeg" data-fr-preview-input data-fr-scene-input="{{ $state }}" data-has-existing="{{ $url ? '1' : '0' }}">
                                                        @if($url)<img class="fr-scene-current" src="{{ $url }}" alt="صحنهٔ فعلی {{ $code }}">@endif
                                                        <span class="fr-scene-upload__choose">تعویض تصویر <span>↗</span></span>
                                                        <small class="fr-scene-status" data-fr-scene-status>تصویر ذخیره‌شده برای ترکیب فعلی.</small>
                                                        <img alt="" data-fr-preview hidden>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <label class="fr-check"><input type="checkbox" name="is_active" value="1" @checked($look->is_active)><span>ست فعال باشد</span></label>
                                            <button class="fr-button fr-button--primary fr-button--wide" type="submit">ذخیرهٔ تغییرات</button>
                                        </form>
                                        <form method="POST" action="{{ route('commerce-admin.try-on.looks.destroy', $look) }}" onsubmit="return confirm('این ست از گالری حذف شود؟')">
                                            @csrf @method('DELETE')
                                            <button class="fr-danger-link" type="submit">حذف این ست</button>
                                        </form>
                                    </details>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @if($mannequins->isEmpty())
            <div class="fr-empty"><strong>اولین مانکن را اضافه کن.</strong><span>برای ساخت ست‌ها، حداقل یک مانکن فعال لازم است.</span></div>
        @endif
    </section>
</div>
@endsection

@push('head')
<style>
.fr-studio{--fr-bg:#151219;--fr-panel:#201b24;--fr-card:#1b1720;--fr-line:#3a303d;--fr-text:#f8f1ea;--fr-muted:#aa9da9;--fr-ac:#e58243;--fr-glow:rgba(229,130,67,.13);color:var(--fr-text);font-family:Vazirmatn,Tahoma,sans-serif;max-width:1600px;margin:auto}
.fr-feedback{margin:0 0 18px;padding:13px 16px;border:1px solid var(--fr-line);border-radius:12px;font-size:12px;line-height:1.9}
.fr-feedback--success{border-color:rgba(90,190,130,.35);background:rgba(45,145,90,.09);color:#c6f0d4}
.fr-feedback--error{border-color:rgba(220,120,120,.42);background:rgba(165,55,65,.1);color:#ffe1e1}
.fr-feedback ul{margin:7px 0 0;padding-right:18px}
.fr-studio *{box-sizing:border-box}.fr-studio input,.fr-studio select,.fr-studio button{font:inherit}.fr-studio input,.fr-studio select{color:var(--fr-text)}.fr-studio button,.fr-studio a{transition:color .2s,border-color .2s,background .2s,transform .2s}
.fr-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) 170px;gap:20px;align-items:center;padding:clamp(22px,4vw,44px);border-radius:24px;border:1px solid var(--fr-line);background:radial-gradient(ellipse at 80% 15%,rgba(229,130,67,.18),transparent 36%),linear-gradient(135deg,#211b25,#17131b 72%);box-shadow:0 24px 80px rgba(0,0,0,.17);margin-bottom:26px}
.fr-kicker{margin:0 0 10px;color:#c2a28e;font:600 10px/1.5 "Space Grotesk",Vazirmatn,sans-serif;letter-spacing:.16em}.fr-kicker span{display:inline-block;width:6px;height:6px;background:var(--fr-ac);border-radius:50%;margin-left:8px;box-shadow:0 0 0 5px rgba(229,130,67,.12)}
.fr-hero h1{font:600 clamp(30px,4vw,46px)/1.05 "Space Grotesk",Vazirmatn,sans-serif!important;letter-spacing:-.04em;margin:0 0 14px;color:var(--fr-text)!important}.fr-hero h1 em{color:#f1a36e;font-style:normal}.fr-hero__copy>p:not(.fr-kicker){margin:0;max-width:680px;color:#c8bcc7;font-size:13px;line-height:2}
.fr-hero__actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:22px}.fr-button{display:inline-flex;align-items:center;justify-content:center;gap:10px;border:1px solid #514252;border-radius:10px;padding:10px 14px;color:#e5d8e4!important;background:rgba(255,255,255,.025);font-size:12px;text-decoration:none!important;cursor:pointer}.fr-button:hover{border-color:#ad7755!important;color:#fff!important;transform:translateY(-1px)}.fr-button--primary{background:var(--fr-ac)!important;border-color:var(--fr-ac)!important;color:#26160d!important;font-weight:700}.fr-button--primary:hover{background:#f1a36e!important;border-color:#f1a36e!important;color:#26160d!important}.fr-button--wide{width:100%;margin-top:8px}
.fr-hero__mark{width:150px;height:150px;display:grid;place-items:center;position:relative;border:1px solid rgba(241,163,110,.35);border-radius:42px;background:linear-gradient(145deg,rgba(229,130,67,.12),rgba(255,255,255,.015));transform:rotate(-7deg);justify-self:center}.fr-hero__mark:before,.fr-hero__mark:after{content:"";position:absolute;inset:12px;border:1px solid rgba(241,163,110,.12);border-radius:32px}.fr-hero__mark:after{inset:26px;border-radius:22px}.fr-hero__mark span{font:700 72px/1 "Space Grotesk",sans-serif;color:#f1a36e;text-shadow:0 10px 45px rgba(229,130,67,.25)}.fr-hero__mark i,.fr-hero__mark b{position:absolute;width:6px;height:6px;background:#ef9b62;border-radius:50%;top:19px;right:20px}.fr-hero__mark b{top:auto;right:auto;bottom:24px;left:22px}
.fr-hero__stats{grid-column:1/-1;display:flex;gap:0;border-top:1px solid rgba(255,255,255,.09);margin-top:8px;padding-top:18px}.fr-hero__stats div{display:grid;gap:3px;padding:0 26px;border-left:1px solid rgba(255,255,255,.09)}.fr-hero__stats div:first-child{padding-right:0}.fr-hero__stats strong{font:600 22px/1.2 "Space Grotesk",sans-serif;color:#f4a66e}.fr-hero__stats span{font-size:11px;color:#b8aab9}
.fr-section{margin:30px 0 44px;scroll-margin-top:20px}.fr-section__head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:17px}.fr-section__head h2{font-size:23px!important;margin:0 0 5px;color:var(--fr-text)!important;letter-spacing:-.035em}.fr-section__head>div>p:last-child{margin:0;max-width:780px;color:var(--fr-muted);font-size:12px;line-height:1.9}.fr-section__count{white-space:nowrap;padding:7px 11px;border:1px solid var(--fr-line);border-radius:9px;color:#f4a66e;background:rgba(229,130,67,.06);font-size:11px}
.fr-mannequin-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;align-items:start}.fr-panel,.fr-mannequin-card,.fr-look-card{min-width:0;overflow:hidden;border:1px solid var(--fr-line);background:linear-gradient(150deg,rgba(255,255,255,.025),transparent 45%),var(--fr-panel);border-radius:17px;box-shadow:0 15px 38px rgba(0,0,0,.06)}
.fr-panel{padding:20px}.fr-panel__head{display:flex;align-items:center;justify-content:space-between}.fr-step{font:600 9px/1.3 "Space Grotesk",sans-serif;letter-spacing:.16em;color:#d79b76}.fr-plus{display:grid;place-items:center;width:30px;height:30px;border:1px solid rgba(229,130,67,.35);border-radius:9px;background:var(--fr-glow);color:#f3a66f;font-size:18px}.fr-panel h3{font-size:17px;margin:12px 0 5px;color:var(--fr-text)}.fr-muted{font-size:11px;line-height:1.8;color:var(--fr-muted);margin:0 0 16px}
.fr-field{display:grid;gap:6px;min-width:0;margin-bottom:12px}.fr-field>span{font-size:11px;color:#c6b9c6}.fr-field input,.fr-field select{width:100%;min-width:0;background:#141118;border:1px solid #403444;border-radius:9px;padding:10px 11px;min-height:41px;color:#f3eaf1!important;outline-offset:2px;font-size:12px}.fr-field input:focus,.fr-field select:focus{border-color:#bd7e56;outline:1px solid rgba(229,130,67,.2)}.fr-field input::placeholder{color:#796d7b}.fr-form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fr-upload{display:grid;justify-items:center;gap:7px;position:relative;overflow:hidden;text-align:center;border:1px dashed #615064;border-radius:12px;padding:18px 12px;background:rgba(10,8,13,.22);cursor:pointer;min-height:126px}.fr-upload input,.fr-scene-upload input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2}.fr-upload__icon{display:grid;place-items:center;width:32px;height:32px;border-radius:50%;background:var(--fr-glow);color:#efa36f;font-size:20px}.fr-upload strong{font-size:11px;color:#f6e8e0}.fr-upload small{font-size:10px;color:var(--fr-muted)}.fr-upload img[data-fr-preview]:not([hidden]){max-height:135px;max-width:100%;object-fit:contain;border-radius:8px}.fr-upload--compact{min-height:82px;padding:12px 10px}.fr-upload--compact .fr-upload__icon{width:25px;height:25px;font-size:15px}.fr-check{display:flex;align-items:center;gap:8px;margin:8px 0;color:#c6b9c6;font-size:11px}.fr-check input{accent-color:var(--fr-ac)}
.fr-mannequin-card__visual,.fr-look-card__cover{height:210px;position:relative;display:grid;place-items:center;overflow:hidden;background:radial-gradient(ellipse at 50% 25%,rgba(229,130,67,.12),transparent 55%),#100d13}.fr-mannequin-card__visual>img,.fr-look-card__cover>img{width:100%;height:100%;object-fit:cover;display:block}.fr-chip,.fr-count-chip,.fr-look-code{position:absolute;top:12px;padding:5px 8px;border-radius:7px;background:rgba(19,15,22,.78);border:1px solid rgba(255,255,255,.13);backdrop-filter:blur(9px);color:#e6dce5;font-size:9px}.fr-chip{right:12px}.fr-chip.is-live{color:#a8edc2}.fr-count-chip{left:12px;color:#f4a66e}.fr-look-code{left:12px;font:10px "Space Grotesk",sans-serif}
.fr-image-empty{display:grid;place-content:center;justify-items:center;gap:5px;height:100%;width:100%;color:#847688;background:radial-gradient(ellipse at 50% 30%,rgba(229,130,67,.12),transparent 60%)}.fr-image-empty span{font:600 32px "Space Grotesk",sans-serif;color:#d99971}.fr-image-empty small{font-size:10px}
.fr-mannequin-card__body,.fr-look-card__body{padding:15px}.fr-mannequin-card__title,.fr-look-card__title{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:13px}.fr-mannequin-card__title h3,.fr-look-card__title h3{font-size:14px;color:var(--fr-text);margin:0 0 4px}.fr-mannequin-card__title p{font:10px "Space Grotesk",sans-serif;color:var(--fr-muted);margin:0;direction:ltr;text-align:right}.fr-order,.fr-look-card__title>span{font-size:10px;color:#d69b74;white-space:nowrap}
.fr-compact-form{display:grid;gap:0}.fr-compact-form .fr-field{margin-bottom:10px}
.fr-danger-link{display:block;margin:10px auto 0;padding:5px 8px;background:none;border:0;color:#d68e91;font-size:10px;cursor:pointer}.fr-danger-link:hover{color:#ffb7b7}
.fr-empty{grid-column:1/-1;display:grid;gap:6px;place-content:center;min-height:150px;text-align:center;border:1px dashed var(--fr-line);border-radius:16px;color:var(--fr-muted);font-size:12px}.fr-empty strong{font-size:15px;color:var(--fr-text)}
.fr-look-editor{margin-bottom:20px;padding:22px}.fr-look-meta{display:grid;grid-template-columns:1.1fr 1.2fr 1fr .55fr;gap:12px;margin:14px 0 4px}.fr-look-meta .fr-field{margin-bottom:8px}
.fr-look-products{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin:5px 0 18px}.fr-assignment{border:1px solid var(--fr-line);border-radius:14px;padding:14px;background:rgba(10,8,13,.18);min-width:0}.fr-assignment__title{display:flex;gap:10px;align-items:center;margin-bottom:12px}.fr-slot-icon{display:grid;place-items:center;width:35px;height:35px;border-radius:10px;background:var(--fr-glow);border:1px solid rgba(229,130,67,.3);color:#f2a36e;font:600 12px "Space Grotesk",sans-serif}.fr-assignment__title strong,.fr-assignment__title small{display:block}.fr-assignment__title strong{font-size:12px}.fr-assignment__title small{color:var(--fr-muted);font-size:10px;margin-top:3px}.fr-current-product-image{display:block;width:76px;height:76px;object-fit:contain;background:#17131c;border:1px solid var(--fr-line);border-radius:10px;padding:4px;margin:0 0 10px}
.fr-scene-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:15px 0}
.fr-scene-status{grid-column:1/-1;color:var(--fr-muted);font-size:9px;line-height:1.7}
.fr-scene-upload.is-required{border-color:#c9875c;background:rgba(229,130,67,.1)}
.fr-scene-upload.is-ready{border-color:rgba(112,190,145,.5)}
.fr-step-note{padding:24px}.fr-scene-upload{position:relative;display:grid;grid-template-columns:auto minmax(0,1fr);align-items:center;gap:9px;min-height:115px;padding:12px;border:1px dashed #55445a;border-radius:12px;background:rgba(9,7,12,.23);overflow:hidden;cursor:pointer}.fr-scene-upload:hover{border-color:#bd7e56;background:var(--fr-glow)}.fr-scene-upload__code{display:grid;place-items:center;width:37px;height:37px;border-radius:11px;background:var(--fr-glow);border:1px solid rgba(229,130,67,.28);color:#f1a36e;font:600 11px "Space Grotesk",sans-serif}.fr-scene-upload__copy{display:grid;gap:4px;min-width:0}.fr-scene-upload__copy strong{font-size:11px}.fr-scene-upload__copy small{font-size:9px;color:var(--fr-muted);line-height:1.65}.fr-scene-upload__choose{grid-column:1/-1;display:flex;justify-content:space-between;border-top:1px solid var(--fr-line);padding-top:8px;font-size:10px;color:#f0a06b}.fr-scene-upload img[data-fr-preview]:not([hidden]){grid-column:1/-1;width:auto;height:100px;max-width:100%;object-fit:contain;justify-self:center;border-radius:7px}.fr-scene-current{grid-column:1/-1;width:100%;height:85px;object-fit:contain;border-radius:7px;background:#100d13}.fr-scene-grid--compact .fr-scene-upload{min-height:100px}
.fr-look-group{margin:25px 0 32px}.fr-look-group__head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 12px}.fr-look-group__head h3{margin:0;font-size:17px}.fr-look-group__head>span{font-size:10px;color:var(--fr-muted)}.fr-look-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;align-items:start}
.fr-look-card__cover{height:220px}.fr-look-card__title{align-items:center}.fr-scenes-mini{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:5px;margin:12px 0}.fr-scenes-mini__item{position:relative;border:1px solid var(--fr-line);border-radius:7px;overflow:hidden;aspect-ratio:2/3;background:#121016}.fr-scenes-mini__item span{position:absolute;top:4px;right:4px;z-index:1;border-radius:4px;background:rgba(10,8,12,.8);color:#f5ad78;padding:2px 4px;font:8px "Space Grotesk",sans-serif}.fr-scenes-mini__item img{width:100%;height:100%;object-fit:cover}.fr-scenes-mini__empty{height:100%;display:grid;place-items:center;color:#756a7b;font-size:14px}
.fr-look-item-list{display:grid;gap:8px;margin:12px 0}.fr-look-item-list>div{display:flex;gap:9px;align-items:center;min-width:0}.fr-look-item-list>div>span{flex:0 0 24px;width:24px;height:24px;display:grid;place-items:center;border-radius:7px;background:var(--fr-glow);color:#f1a36e;font:10px "Space Grotesk",sans-serif}.fr-look-item-list p{margin:0;min-width:0;font-size:11px;overflow-wrap:anywhere}.fr-look-item-list small{display:block;margin-top:2px;color:var(--fr-muted);font-size:9px}
.fr-edit-details{border-top:1px solid var(--fr-line);margin-top:14px;padding-top:12px}.fr-edit-details summary{cursor:pointer;font-size:11px;color:#e3ad89;display:flex;justify-content:space-between;gap:10px}.fr-edit-details[open] summary{margin-bottom:12px}.fr-edit-details form{margin-top:10px}
@media(max-width:1200px){.fr-mannequin-grid,.fr-look-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.fr-look-meta{grid-template-columns:1fr 1fr}.fr-scene-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.fr-hero{grid-template-columns:1fr;padding:22px}.fr-hero__mark{position:absolute;left:20px;top:20px;width:68px;height:68px;border-radius:20px;opacity:.5}.fr-hero__mark span{font-size:34px}.fr-hero__mark:before{inset:6px;border-radius:14px}.fr-hero__mark:after{inset:13px;border-radius:8px}.fr-hero__actions .fr-button{flex:1}.fr-hero__stats div{padding:0 12px}.fr-hero__stats strong{font-size:18px}.fr-mannequin-grid,.fr-look-grid,.fr-look-products{grid-template-columns:1fr}.fr-section__head{align-items:flex-start}.fr-section__head h2{font-size:20px!important}.fr-section__head>div>p:last-child{max-width:none}.fr-look-meta{grid-template-columns:1fr 1fr;gap:7px}.fr-scene-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.fr-panel,.fr-look-editor{padding:14px}.fr-mannequin-card__visual,.fr-look-card__cover{height:245px}}
</style>
<script>
function fittingRoomSceneSignature(form, state) {
    const mannequin = form.querySelector('[name="mannequin_id"]')?.value || '';
    const top = form.querySelector('[name="top_variant_id"]')?.value || '';
    const bottom = form.querySelector('[name="bottom_variant_id"]')?.value || '';
    if (state === 'top') return [mannequin, top].join(':');
    if (state === 'bottom') return [mannequin, bottom].join(':');
    return [mannequin, top, bottom].join(':');
}

function updateFittingRoomSceneGuidance(form) {
    const top = form.querySelector('[name="top_variant_id"]');
    const bottom = form.querySelector('[name="bottom_variant_id"]');
    const mannequin = form.querySelector('[name="mannequin_id"]');
    if (!top || !bottom || !mannequin) return;

    const isCreate = form.dataset.frLookForm === 'create';
    const mannequinChanged = isCreate || mannequin.value !== (mannequin.dataset.initialValue || '');
    const topChanged = isCreate || top.value !== (top.dataset.initialValue || '');
    const bottomChanged = isCreate || bottom.value !== (bottom.dataset.initialValue || '');
    const topSelected = Boolean(top.value);
    const bottomSelected = Boolean(bottom.value);
    const states = {
        top: { active: topSelected, refresh: mannequinChanged || topChanged },
        bottom: { active: bottomSelected, refresh: mannequinChanged || bottomChanged },
        combined: { active: topSelected && bottomSelected, refresh: mannequinChanged || topChanged || bottomChanged }
    };

    Object.entries(states).forEach(([state, config]) => {
        const input = form.querySelector('[data-fr-scene-input="' + state + '"]');
        const card = form.querySelector('[data-fr-scene-card="' + state + '"]');
        if (!input || !card) return;

        const signature = fittingRoomSceneSignature(form, state);
        if (input.dataset.selectedFor && input.dataset.selectedFor !== signature) {
            input.value = '';
            delete input.dataset.selectedFor;
            const preview = card.querySelector('[data-fr-preview]');
            if (preview) {
                preview.hidden = true;
                preview.removeAttribute('src');
            }
        }

        const hasExisting = input.dataset.hasExisting === '1';
        const hasNewFile = Boolean(input.files && input.files.length);
        const needsUpload = config.active && (config.refresh || !hasExisting);
        const status = card.querySelector('[data-fr-scene-status]');

        card.classList.toggle('is-required', needsUpload && !hasNewFile);
        card.classList.toggle('is-ready', config.active && (hasNewFile || (!needsUpload && hasExisting)));

        if (status) {
            if (!config.active) {
                status.textContent = state === 'combined'
                    ? 'وقتی هر دو واریانت انتخاب شوند، این تصویر لازم است.'
                    : 'برای این حالت محصولی انتخاب نشده است.';
            } else if (hasNewFile) {
                status.textContent = 'فایل جدید انتخاب شده؛ پس از ذخیره به این ترکیب متصل می‌شود.';
            } else if (needsUpload) {
                status.textContent = 'برای این محصول/مانکن، بارگذاری تصویر تازه الزامی است.';
            } else {
                status.textContent = 'تصویر ذخیره‌شده با انتخاب فعلی مطابقت دارد.';
            }
        }
    });
}

document.querySelectorAll('[data-fr-look-form]').forEach(function (form) {
    ['top_variant_id', 'bottom_variant_id', 'mannequin_id'].forEach(function (name) {
        form.querySelector('[name="' + name + '"]')?.addEventListener('change', function () {
            updateFittingRoomSceneGuidance(form);
        });
    });
    updateFittingRoomSceneGuidance(form);
});

document.addEventListener('change', function (event) {
    const input = event.target.closest('[data-fr-preview-input]');
    if (!input) return;

    if (input.files && input.files[0]) {
        const preview = input.closest('label')?.querySelector('[data-fr-preview]');
        const file = input.files[0];
        if (preview && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function () { preview.src = String(reader.result || ''); preview.hidden = false; };
            reader.readAsDataURL(file);
        }
    }

    const form = input.closest('[data-fr-look-form]');
    const state = input.dataset.frSceneInput;
    if (form && state && input.files && input.files.length) {
        input.dataset.selectedFor = fittingRoomSceneSignature(form, state);
    }
    if (form) updateFittingRoomSceneGuidance(form);
});
</script>
@endpush
