@extends('commerce-admin::layout')

@section('title', 'استودیوی اتاق پرو')

@section('content')
<div class="fr-studio">
    <header class="fr-hero">
        <div class="fr-hero__copy">
            <h1>استودیوی <em>اتاق پرو</em></h1>
            <p>مانکن‌ها، تصویر لباس‌ها و ست‌های پیشنهادی را در یک گالری منظم بساز و کنترل کن. داده‌ها مستقیم به کاتالوگ و واریانت‌های واقعی متصل هستند.</p>
            <div class="fr-hero__actions">
                <a class="fr-button fr-button--primary" href="#fr-mannequins">۱. ثبت مدل</a><a class="fr-button" href="#fr-looks">۲. اتصال لباس‌ها</a>
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
    @if($errors->any())
        <section class="fr-feedback fr-feedback--error" role="alert"><strong>اطلاعات نیاز به اصلاح دارد.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>
    @endif
    @if(session('status'))<p class="fr-feedback" role="status">{{ session('status') }}</p>@endif
    <nav class="fr-guide-grid" aria-label="راهنمای ساخت اتاق پرو">
        <a href="#fr-mannequins"><b>۱</b><strong>مدل را ثبت کنید</strong><span>یک عکس تمام‌قد روبه‌رو، بدون برش بدن، نسبت ۲:۳. مثلاً ۱۰۲۴ × ۱۵۳۶.</span></a>
        <a href="#fr-looks"><b>۲</b><strong>لباس را انتخاب کنید</strong><span>محصول و رنگ واقعی آن را انتخاب کنید؛ تصویر کارت از فروشگاه خوانده می‌شود.</span></a>
        <a href="#fr-looks"><b>۳</b><strong>عکس پوشیده‌شده را ثبت کنید</strong><span>همان مدل، همان زاویه و اندازه. برای یک لباس فقط عکس همان لباس کافی است.</span></a>
    </nav>

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
                        <input type="hidden" name="code" value="{{ old('code') }}">
                        <label class="fr-field"><span>گروه</span><select name="audience" required><option value="women">زنانه</option><option value="men">مردانه</option><option value="all">عمومی</option></select></label>
                    </div>
                    <details class="fr-advanced"><summary>تنظیمات پیشرفته (اختیاری)</summary><label class="fr-field"><span>اولویت نمایش</span><input type="number" name="sort_order" value="0" min="0" max="65535"></label></details>
                    <label class="fr-upload">
                        <input type="file" name="base_image" accept="image/png,image/webp,image/jpeg" required data-fr-preview-input>
                        <span class="fr-upload__icon">＋</span><strong>انتخاب تصویر مانکن</strong><small>نسبت تصویر ۲:۳ · حداکثر ۸ مگابایت</small>
                        <img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small>
                    </label>
                    <label class="fr-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', false))><span>انتشار در فروشگاه (پس از بررسی عکس)</span></label>
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
                        @if($mannequin->baseMedia?->url)<a class="fr-button fr-button--wide" href="{{ route('shop.index', ['preview_model' => $mannequin->code]) }}" target="_blank" rel="noopener">تست این مدل در فروشگاه</a>@endif
                        <details class="fr-edit-details"><summary>ویرایش مشخصات و عکس مدل</summary>
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
                        </details>
                    </div>
                </article>
            @empty
                <div class="fr-empty"><strong>گالری از اینجا شروع می‌شود.</strong><span>تصویر پایهٔ مانکن را از فرم کناری آپلود کن.</span></div>
            @endforelse
        </div>
    </section>

    <section class="fr-section" id="fr-looks">
        <div class="fr-section__head">
            <div><h2>گالری ست‌ها و صحنه‌ها</h2><p>یک لباس یا یک ست کامل بسازید. عکس مدل پوشیده‌شده باید با محصول انتخابی دقیقاً یکسان باشد.</p></div>
            <span class="fr-section__count">{{ \App\Support\Money::number($mannequins->sum('looks_count')) }} ست</span>
        </div>

        <section class="fr-panel fr-look-editor">
            <div class="fr-panel__head"><span class="fr-step">ست تازه</span><span class="fr-plus">＋</span></div>
            <h3>ساخت ست جدید</h3>
            @if($mannequins->isEmpty())
                <p class="fr-muted"><a href="#fr-mannequins">برای ذخیرهٔ ست، ابتدا یک مانکن اضافه کن.</a></p>
            @endif
            <p class="fr-muted">ابتدا مدل و لباس را انتخاب کنید. برای یک بالاپوش یا پایین‌تنه فقط عکس همان حالت لازم است. برای دو لباس، عکس ترکیب کامل هم لازم است.</p>
            <form method="POST" action="{{ route('commerce-admin.try-on.looks.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="fr-look-meta">
                    <label class="fr-field"><span>مانکن</span><select name="mannequin_id" required data-fr-model>@foreach($mannequins as $mannequin)<option value="{{ $mannequin->id }}" data-audience="{{ $mannequin->audience }}" @selected((int) old('mannequin_id') === $mannequin->id)>{{ $mannequin->label }}{{ $mannequin->is_active ? '' : ' (غیرفعال)' }}</option>@endforeach</select></label>
                    <label class="fr-field"><span>عنوان ست</span><input name="label" value="{{ old('label') }}" placeholder="استایل روزمره نارنجی" required maxlength="160"></label>
                    <input type="hidden" name="code" value="{{ old('code') }}">
                    <details class="fr-advanced"><summary>اولویت نمایش (اختیاری)</summary><label class="fr-field"><span>اولویت گالری</span><input type="number" name="sort_order" value="0" min="0" max="65535"></label></details>
                </div>
                <div class="fr-step-heading"><strong>۲. انتخاب لباس</strong><small>حداقل یک محصول انتخاب کنید.</small></div><div class="fr-look-products">
                    <div class="fr-assignment">
                        <div class="fr-assignment__title"><span class="fr-slot-icon">T</span><div><strong>بالاپوش</strong><small>رنگ و سایز محصول + تصویر کارت</small></div></div>
                        @include('admin.fitting-room-studio.variant-picker', ['slot' => 'top', 'slotLabel' => 'بالاپوش', 'currentVariant' => null])
                        <label class="fr-upload fr-upload--compact"><input type="file" name="top_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">＋</span><strong>آپلود عکس محصول</strong><small>برای کارت فروشگاه · تصویر واریانت</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                        <details class="fr-advanced"><summary>تنظیم حرکت (اختیاری)</summary><label class="fr-field"><span>موقعیت پایان حرکت</span><input type="number" name="fit_y_top" value="0.38" min="0" max="1" step="0.01"></label></details>
                    </div>
                    <div class="fr-assignment">
                        <div class="fr-assignment__title"><span class="fr-slot-icon">B</span><div><strong>پایین‌تنه</strong><small>رنگ و سایز محصول + تصویر کارت</small></div></div>
                        @include('admin.fitting-room-studio.variant-picker', ['slot' => 'bottom', 'slotLabel' => 'پایین‌تنه', 'currentVariant' => null])
                        <label class="fr-upload fr-upload--compact"><input type="file" name="bottom_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">＋</span><strong>آپلود عکس محصول</strong><small>برای کارت فروشگاه · تصویر واریانت</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                        <details class="fr-advanced"><summary>تنظیم حرکت (اختیاری)</summary><label class="fr-field"><span>موقعیت پایان حرکت</span><input type="number" name="fit_y_bottom" value="0.66" min="0" max="1" step="0.01"></label></details>
                    </div>
                </div>
                <div class="fr-step-heading"><strong>۳. عکس‌های مدل با لباس</strong><small>نسبت ۲:۳، حالت و اندازهٔ مدل یکسان؛ حداکثر ۸ مگابایت.</small></div><div class="fr-scene-grid">
                    @foreach([
                        ['preview_top','T','نمای بالاپوش','برای حالت انتخاب بالاپوش؛ فایل ۲:۳'],
                        ['preview_bottom','B','نمای پایین‌تنه','برای حالت انتخاب پایین‌تنه؛ فایل ۲:۳'],
                        ['preview_combined','TB','نمای ست کامل','برای نمایش هم‌زمان بالاپوش و پایین‌تنه'],
                        ['preview_suggested','S','ست پیشنهادی','برای دکمهٔ «دیدن ست پیشنهادی» پس از انتخاب بالاپوش']
                    ] as [$input,$code,$title,$hint])
                        <label class="fr-scene-upload">
                            <span class="fr-scene-upload__code">{{ $code }}</span>
                            <span class="fr-scene-upload__copy"><strong>{{ $title }}</strong><small>{{ $hint }}</small></span>
                            <input type="file" name="{{ $input }}" accept="image/png,image/webp,image/jpeg" data-fr-preview-input data-fr-scene>
                            <span class="fr-scene-upload__choose">انتخاب تصویر <span>↗</span></span>
                            <img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small>
                        </label>
                    @endforeach
                </div>
                <label class="fr-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', false))><span>انتشار در فروشگاه پس از بررسی</span></label>
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
                                    @elseif($look->previewBottomMedia?->url)
                                        <img src="{{ $look->previewBottomMedia->url }}" alt="{{ $look->label }}" loading="lazy">
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
                                                <label class="fr-field"><span>مانکن</span><select name="mannequin_id" required data-fr-model>@foreach($mannequins as $option)<option value="{{ $option->id }}" data-audience="{{ $option->audience }}" @selected($option->id === $mannequin->id)>{{ $option->label }}</option>@endforeach</select></label>
                                                <label class="fr-field"><span>عنوان ست</span><input name="label" value="{{ $look->label }}" required maxlength="160"></label>
                                                <label class="fr-field"><span>کد</span><input name="code" value="{{ $look->code }}" required pattern="[a-zA-Z0-9_-]+" maxlength="80"></label>
                                                <label class="fr-field"><span>اولویت</span><input type="number" name="sort_order" value="{{ $look->sort_order }}" min="0" max="65535"></label>
                                            </div>
                                            <div class="fr-step-heading"><strong>۲. انتخاب لباس</strong><small>حداقل یک محصول انتخاب کنید.</small></div><div class="fr-look-products">
                                                <div class="fr-assignment">
                                                    <div class="fr-assignment__title"><span class="fr-slot-icon">T</span><div><strong>بالاپوش</strong><small>تصویر کارت محصول</small></div></div>
                                                    @include('admin.fitting-room-studio.variant-picker', ['slot' => 'top', 'slotLabel' => 'بالاپوش', 'currentVariant' => $topItem?->variant])
                                                    <label class="fr-upload fr-upload--compact"><input type="file" name="top_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس کارت</strong><small>خالی بماند تا تصویر فعلی حفظ شود</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                                                    <label class="fr-field"><span>نقطهٔ حرکت</span><input type="number" name="fit_y_top" value="{{ $topItem?->fit_y ?? 0.38 }}" min="0" max="1" step="0.01"></label>
                                                </div>
                                                <div class="fr-assignment">
                                                    <div class="fr-assignment__title"><span class="fr-slot-icon">B</span><div><strong>پایین‌تنه</strong><small>تصویر کارت محصول</small></div></div>
                                                    @include('admin.fitting-room-studio.variant-picker', ['slot' => 'bottom', 'slotLabel' => 'پایین‌تنه', 'currentVariant' => $bottomItem?->variant])
                                                    <label class="fr-upload fr-upload--compact"><input type="file" name="bottom_product_image" accept="image/png,image/webp,image/jpeg" data-fr-preview-input><span class="fr-upload__icon">↻</span><strong>تعویض عکس کارت</strong><small>خالی بماند تا تصویر فعلی حفظ شود</small><img class="fr-preview-image" alt="پیش‌نمایش فایل انتخابی" data-fr-preview hidden>
                        <small class="fr-preview-status" data-fr-preview-status aria-live="polite"></small></label>
                                                    <label class="fr-field"><span>نقطهٔ حرکت</span><input type="number" name="fit_y_bottom" value="{{ $bottomItem?->fit_y ?? 0.66 }}" min="0" max="1" step="0.01"></label>
                                                </div>
                                            </div>
                                            <div class="fr-step-heading"><strong>۳. عکس‌های مدل با لباس</strong><small>در صورت تغییر مدل یا لباس، عکس جدید همان حالت را ثبت کنید.</small></div><div class="fr-scene-grid fr-scene-grid--compact">
                                                @foreach([
                                                    ['preview_top','T','بالاپوش',$look->previewTopMedia?->url],
                                                    ['preview_bottom','B','پایین‌تنه',$look->previewBottomMedia?->url],
                                                    ['preview_combined','TB','ست کامل',$look->previewCombinedMedia?->url],
                                                    ['preview_suggested','S','پیشنهاد',$look->previewSuggestedMedia?->url]
                                                ] as [$input,$code,$title,$url])
                                                    <label class="fr-scene-upload">
                                                        <span class="fr-scene-upload__code">{{ $code }}</span><span class="fr-scene-upload__copy"><strong>{{ $title }}</strong><small>{{ $url ? 'عکس فعلی محفوظ است؛ برای تعویض فایل جدید انتخاب کن.' : 'هنوز تصویری ندارد.' }}</small></span>
                                                        <input type="file" name="{{ $input }}" accept="image/png,image/webp,image/jpeg" data-fr-preview-input data-fr-scene data-existing="{{ $url ? '1' : '0' }}">
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
            <div class="fr-empty"><strong>اولین مانکن را اضافه کن.</strong><span>برای ساخت ست‌ها، ابتدا یک مدل با عکس پایه ثبت کنید؛ می‌توانید آن را پیش از انتشار تست کنید.</span></div>
        @endif
    </section>
    <script type="application/json" id="fr-variant-cards">{!! \Illuminate\Support\Js::encode($variantCards) !!}</script>
</div>
@endsection

@push('head')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/fitting-room-studio.css', 'resources/js/fitting-room-studio.js'])
    @endif
@endpush
