@extends('commerce-admin::layout')

@section('title', 'استودیوی اتاق پرو')

@section('content')
<div class="fr-studio">
    <header class="fr-hero">
        <div class="fr-hero__copy">
            <h1>استودیوی <em>اتاق پرو</em></h1>
            <p>مانکن‌ها، تصویر لباس‌ها و ست‌های پیشنهادی را در یک گالری منظم بساز و کنترل کن. داده‌ها مستقیم به کاتالوگ و واریانت‌های واقعی متصل هستند.</p>
            <div class="fr-hero__actions">
                <a class="fr-button fr-button--primary" href="#fr-looks">ساخت ست جدید <span>↗</span></a>
                <a class="fr-button" href="{{ route('commerce-admin.products.index') }}">مدیریت محصولات و واریانت‌ها <span>↗</span></a>
                <a class="fr-button" href="{{ route('shop.index') }}" target="_blank" rel="noopener">پیش‌نمایش فروشگاه <span>↗</span></a>
            </div>
        </div>
        <div class="fr-hero__mark" aria-hidden="true"><span>V</span><i></i><b></b></div>
        <div class="fr-hero__stats">
            <div><strong>{{ \App\Support\Money::number($mannequins->count()) }}</strong><span>مدل مانکن</span></div>
            <div><strong>{{ \App\Support\Money::number($mannequins->sum('looks_count')) }}</strong><span>ست ثبت‌شده</span></div>
            <div><strong>{{ \App\Support\Money::number($variants->count()) }}</strong><span>واریانت صفحهٔ فعلی از {{ \App\Support\Money::number($variants->total()) }} مورد</span></div>
        </div>
    </header>

    <form method="GET" action="{{ route('commerce-admin.try-on.index') }}" class="fr-panel fr-variant-search" role="search">
        <label class="fr-field" for="fr-variant-search-input"><span>جست‌وجوی واریانت برای ساخت ست</span>
            <input id="fr-variant-search-input" type="search" name="variant_search" value="{{ $variantSearch }}" maxlength="100" placeholder="نام محصول یا کد SKU">
        </label>
        <button type="submit" class="fr-button fr-button--primary">جست‌وجو در کل محصولات ↗</button>
        @if($variantSearch !== '')<a class="fr-button" href="{{ route('commerce-admin.try-on.index') }}">پاک کردن جست‌وجو</a>@endif
        <p class="fr-muted">نتایج در صفحه‌های ۱۰۰تایی قابل مرورند؛ برای پیدا کردن هر واریانت، نام محصول یا SKU را جست‌وجو کن. هنگام تغییر صفحه، انتخاب‌های ذخیره‌نشده فرم حفظ نمی‌شوند.</p>
    </form>

    @if($variants->hasPages())
        <nav class="fr-variant-pages" aria-label="صفحه‌بندی واریانت‌های استودیو">
            @if($variants->onFirstPage())
                <span aria-disabled="true">صفحه قبل</span>
            @else
                <a href="{{ $variants->previousPageUrl() }}">صفحه قبل</a>
            @endif
            <span>صفحه {{ \App\Support\Money::number($variants->currentPage()) }} از {{ \App\Support\Money::number($variants->lastPage()) }}</span>
            @if($variants->hasMorePages())
                <a href="{{ $variants->nextPageUrl() }}">صفحه بعد</a>
            @else
                <span aria-disabled="true">صفحه بعد</span>
            @endif
        </nav>
    @endif

    <section class="fr-section" id="fr-mannequins">
        <div class="fr-section__head">
            <div><h2>گالری مانکن‌ها</h2><p>عکس پایهٔ مانکن را با قاب ۲:۳ آپلود کن. اولین مانکن فعال با اولویت کمتر، پیش‌فرض ویترین است.</p></div>
            <span class="fr-section__count">{{ \App\Support\Money::number($mannequins->count()) }} مدل</span>
        </div>

        <div class="fr-mannequin-grid">
            <section class="fr-panel fr-new-mannequin">
                <div class="fr-panel__head"><span class="fr-step">مانکن تازه</span><span class="fr-plus">＋</span></div>
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
                        <img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small>
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
                        <span class="fr-chip {{ $mannequin->is_active ? 'is-live' : '' }}">{{ $mannequin->id === $publicMannequinId ? 'در ویترین فروشگاه' : ($mannequin->is_active ? 'فعال در استودیو' : 'غیرفعال') }}</span>
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
                            <label class="fr-upload fr-upload--compact"><input type="file" name="base_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس پایه</strong><small>در صورت نیاز عکس جدید انتخاب کن</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
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
            <div><h2>گالری ست‌ها و صحنه‌ها</h2><p>هر ست، واریانت واقعی بالاپوش/پایین‌تنه را به تصاویر T، B، TB و S متصل می‌کند. برای کارت محصول هم تصویر و قیمت از دیتابیس محصولات خوانده می‌شود.</p></div>
            <span class="fr-section__count">{{ \App\Support\Money::number($mannequins->sum('looks_count')) }} ست</span>
        </div>

        <section class="fr-panel fr-look-editor">
            <div class="fr-panel__head"><span class="fr-step">ست تازه</span><span class="fr-plus">＋</span></div>
            <h3>ساخت ست جدید</h3>
            @if($mannequins->isEmpty())
                <p class="fr-muted"><a href="#fr-mannequins">برای ذخیرهٔ ست، ابتدا یک مانکن اضافه کن.</a></p>
            @endif
            <p class="fr-muted">صحنه‌ها در قاب ۲:۳ و عکس کارت‌ها در قاب ۴:۵ پیش‌نمایش داده می‌شوند. تغییر تصویر کارت روی وریانت محصول ذخیره می‌شود.</p>
            <form method="POST" action="{{ route('commerce-admin.try-on.looks.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="fr-look-meta">
                    <label class="fr-field"><span>مانکن</span><select name="mannequin_id" required>@foreach($mannequins as $mannequin)<option value="{{ $mannequin->id }}">{{ $mannequin->label }}{{ $mannequin->is_active ? '' : ' (غیرفعال)' }}</option>@endforeach</select></label>
                    <label class="fr-field"><span>عنوان ست</span><input name="label" value="{{ old('label') }}" placeholder="استایل روزمره نارنجی" required maxlength="160"></label>
                    <label class="fr-field"><span>کد یکتا</span><input name="code" value="{{ old('code') }}" placeholder="orange-casual" pattern="[a-zA-Z0-9_-]+" required maxlength="80"></label>
                    <label class="fr-field"><span>اولویت گالری</span><input type="number" name="sort_order" value="0" min="0" max="65535"></label>
                </div>
                <div class="fr-look-products">
                    <div class="fr-assignment">
                        <div class="fr-assignment__title"><span class="fr-slot-icon">T</span><div><strong>بالاپوش</strong><small>واریانت واقعی + تصویر کارت</small></div></div>
                        @include('admin.fitting-room-studio.variant-picker', ['slot' => 'top', 'slotLabel' => 'بالاپوش', 'currentVariant' => null])
                        <label class="fr-upload fr-upload--compact"><input type="file" name="top_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">＋</span><strong>آپلود عکس محصول</strong><small>برای کارت فروشگاه · تصویر واریانت</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                        <label class="fr-field"><span>نقطهٔ حرکت انیمیشن</span><input type="number" name="fit_y_top" value="0.38" min="0" max="1" step="0.01"></label>
                    </div>
                    <div class="fr-assignment">
                        <div class="fr-assignment__title"><span class="fr-slot-icon">B</span><div><strong>پایین‌تنه</strong><small>واریانت واقعی + تصویر کارت</small></div></div>
                        @include('admin.fitting-room-studio.variant-picker', ['slot' => 'bottom', 'slotLabel' => 'پایین‌تنه', 'currentVariant' => null])
                        <label class="fr-upload fr-upload--compact"><input type="file" name="bottom_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">＋</span><strong>آپلود عکس محصول</strong><small>برای کارت فروشگاه · تصویر واریانت</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                        <label class="fr-field"><span>نقطهٔ حرکت انیمیشن</span><input type="number" name="fit_y_bottom" value="0.66" min="0" max="1" step="0.01"></label>
                    </div>
                </div>
                <div class="fr-scene-grid">
                    @foreach([
                        ['preview_top','T','نمای بالاپوش','برای حالت انتخاب بالاپوش؛ فایل ۲:۳'],
                        ['preview_bottom','B','نمای پایین‌تنه','برای حالت انتخاب پایین‌تنه؛ فایل ۲:۳'],
                        ['preview_combined','TB','نمای ست کامل','برای نمایش هم‌زمان بالاپوش و پایین‌تنه'],
                        ['preview_suggested','S','ست پیشنهادی','برای دکمهٔ «دیدن ست پیشنهادی» پس از انتخاب بالاپوش']
                    ] as [$input,$code,$title,$hint])
                        <label class="fr-scene-upload">
                            <span class="fr-scene-upload__code">{{ $code }}</span>
                            <span class="fr-scene-upload__copy"><strong>{{ $title }}</strong><small>{{ $hint }}</small></span>
                            <input type="file" name="{{ $input }}" accept="image/png,image/webp,image/jpeg" data-fr-preview-input>
                            <span class="fr-scene-upload__choose">انتخاب تصویر <span>↗</span></span>
                            <img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small>
                        </label>
                    @endforeach
                </div>
                <label class="fr-check"><input type="checkbox" name="is_active" value="1" checked><span>ست در فروشگاه فعال باشد</span></label>
                <button class="fr-button fr-button--primary fr-button--wide" type="submit" @disabled($mannequins->isEmpty())>ذخیرهٔ ست و صحنه‌ها <span>↗</span></button>
            </form>
        </section>

        @foreach($mannequins as $mannequin)
            @if($mannequin->looks->isNotEmpty())
                <div class="fr-look-group">
                    <div class="fr-look-group__head"><div><span class="fr-kicker">{{ $mannequin->code }}</span><h3>{{ $mannequin->label }}</h3></div><span>{{ \App\Support\Money::number($mannequin->looks->count()) }} ترکیب</span></div>
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
                                    <span class="fr-chip {{ $look->is_active ? 'is-live' : '' }}">{{ $look->is_active ? ($mannequin->id === $publicMannequinId ? 'مانکن ویترین' : 'فعال در استودیو') : 'غیرفعال' }}</span>
                                    <span class="fr-look-code">{{ $look->code }}</span>
                                </div>
                                <div class="fr-look-card__body">
                                    <div class="fr-look-card__title"><h3>{{ $look->label }}</h3><span>{{ $look->items->count() }} آیتم</span></div>
                                    <div class="fr-scenes-mini">
                                        @foreach([
                                            ['T',$look->previewTopMedia?->url],
                                            ['B',$look->previewBottomMedia?->url],
                                            ['TB',$look->previewCombinedMedia?->url],
                                            ['S',$look->previewSuggestedMedia?->url]
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
                                        <form method="POST" action="{{ route('commerce-admin.try-on.looks.update', $look) }}" enctype="multipart/form-data">
                                            @csrf @method('PUT')
                                            <div class="fr-look-meta">
                                                <label class="fr-field"><span>مانکن</span><select name="mannequin_id" required>@foreach($mannequins as $option)<option value="{{ $option->id }}" @selected($option->id === $mannequin->id)>{{ $option->label }}</option>@endforeach</select></label>
                                                <label class="fr-field"><span>عنوان ست</span><input name="label" value="{{ $look->label }}" required maxlength="160"></label>
                                                <label class="fr-field"><span>کد</span><input name="code" value="{{ $look->code }}" required pattern="[a-zA-Z0-9_-]+" maxlength="80"></label>
                                                <label class="fr-field"><span>اولویت</span><input type="number" name="sort_order" value="{{ $look->sort_order }}" min="0" max="65535"></label>
                                            </div>
                                            <div class="fr-look-products">
                                                <div class="fr-assignment">
                                                    <div class="fr-assignment__title"><span class="fr-slot-icon">T</span><div><strong>بالاپوش</strong><small>تصویر کارت محصول واریانت</small></div></div>
                                                    @include('admin.fitting-room-studio.variant-picker', ['slot' => 'top', 'slotLabel' => 'بالاپوش', 'currentVariant' => $topItem?->variant])
                                                    <label class="fr-upload fr-upload--compact"><input type="file" name="top_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس کارت</strong><small>خالی بماند تا تصویر فعلی حفظ شود</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                                                    <label class="fr-field"><span>نقطهٔ حرکت</span><input type="number" name="fit_y_top" value="{{ $topItem?->fit_y ?? 0.38 }}" min="0" max="1" step="0.01"></label>
                                                </div>
                                                <div class="fr-assignment">
                                                    <div class="fr-assignment__title"><span class="fr-slot-icon">B</span><div><strong>پایین‌تنه</strong><small>تصویر کارت محصول واریانت</small></div></div>
                                                    @include('admin.fitting-room-studio.variant-picker', ['slot' => 'bottom', 'slotLabel' => 'پایین‌تنه', 'currentVariant' => $bottomItem?->variant])
                                                    <label class="fr-upload fr-upload--compact"><input type="file" name="bottom_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس کارت</strong><small>خالی بماند تا تصویر فعلی حفظ شود</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                                                    <label class="fr-field"><span>نقطهٔ حرکت</span><input type="number" name="fit_y_bottom" value="{{ $bottomItem?->fit_y ?? 0.66 }}" min="0" max="1" step="0.01"></label>
                                                </div>
                                            </div>
                                            <div class="fr-scene-grid fr-scene-grid--compact">
                                                @foreach([
                                                    ['preview_top','T','بالاپوش',$look->previewTopMedia?->url],
                                                    ['preview_bottom','B','پایین‌تنه',$look->previewBottomMedia?->url],
                                                    ['preview_combined','TB','ست کامل',$look->previewCombinedMedia?->url],
                                                    ['preview_suggested','S','پیشنهاد',$look->previewSuggestedMedia?->url]
                                                ] as [$input,$code,$title,$url])
                                                    <label class="fr-scene-upload">
                                                        <span class="fr-scene-upload__code">{{ $code }}</span><span class="fr-scene-upload__copy"><strong>{{ $title }}</strong><small>{{ $url ? 'عکس فعلی محفوظ است؛ برای تعویض فایل جدید انتخاب کن.' : 'هنوز تصویری ندارد.' }}</small></span>
                                                        <input type="file" name="{{ $input }}" accept="image/png,image/webp,image/jpeg" data-fr-preview-input>
                                                        @if($url)<img class="fr-scene-current" src="{{ $url }}" alt="صحنهٔ فعلی {{ $code }}">@endif
                                                        <span class="fr-scene-upload__choose">تعویض تصویر <span>↗</span></span><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small>
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
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/fitting-room-studio.css', 'resources/js/fitting-room-studio.js'])
    @endif
@endpush
