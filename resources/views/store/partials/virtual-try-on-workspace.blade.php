<section class="vto-page {{ ($productPage ?? false) ? 'vto-page--product' : '' }} {{ ($inline ?? false) ? 'vto-page--inline' : '' }}"
      @if($inline ?? false)
      id="vto-inline-workspace" data-vto-inline="true" inert aria-hidden="true" tabindex="-1" aria-label="اتاق پرو"
      @else
      aria-labelledby="vto-title"
      @endif
      data-virtual-try-on
      data-bootstrap-url="{{ route('api.virtual-try-on.bootstrap') }}"
      data-catalog-url="{{ route('api.virtual-try-on.catalog') }}"
      data-preview-url="{{ route('api.virtual-try-on.preview') }}"
      data-currency="{{ config('commerce-admin.currency_symbol', 'تومان') }}"
      data-initial-variant-id="{{ $tryOnInitialVariantId ?? '' }}"
      data-initial-audience="{{ $tryOnInitialAudience ?? '' }}"
      >
    @if(!($inline ?? false))
    <header class="vto-heading">
        <div>
            <p class="vto-eyebrow"><span aria-hidden="true"></span> VISION / FITTING ROOM</p>
            @if($productPage ?? false)
            <h1 id="vto-title">این محصول را روی مانکن <em>امتحان کن.</em></h1>
            <p class="vto-heading__intro">رنگ یا واریانت همین محصول را انتخاب کن و بعد لباس‌های دیگر را هم روی همین مانکن ترکیب کن.</p>
        @else
            <h1 id="vto-title">استایل خودت را <em>بساز.</em></h1>
        @endif
        </div>
        @if(!($productPage ?? false))
            <a class="vto-back-link" href="{{ route('shop.index') }}">بازگشت به فروشگاه <span aria-hidden="true">↗</span></a>
        @endif
    </header>
    @endif

    <section class="vto-workspace" aria-label="اتاق پرو و انتخاب پوشاک">
        @if(!($inline ?? false))
        <aside class="vto-controls">
            <section class="vto-panel" aria-labelledby="vto-audience-title">
                <div class="vto-panel__heading"><div><p class="vto-step">مرحلهٔ ۰۱</p><h2 id="vto-audience-title">مانکن را انتخاب کن</h2></div><span class="vto-panel__index">01</span></div>
                <div class="vto-audience-switch" role="group" aria-label="انتخاب مانکن">
                    <button type="button" data-vto-audience="women" aria-pressed="true">زنانه <span>↗</span></button>
                    <button type="button" data-vto-audience="men" aria-pressed="false">مردانه <span>↗</span></button>
                </div>
                <div class="vto-mannequin-list" data-vto-mannequins aria-live="polite"></div>
            </section>

            <section class="vto-panel" aria-labelledby="vto-slots-title">
                <div class="vto-panel__heading"><div><p class="vto-step">مرحلهٔ ۰۲</p><h2 id="vto-slots-title">کدام بخش را تغییر می‌دهی؟</h2></div><span class="vto-panel__index">02</span></div>
                <div class="vto-slot-list" data-vto-slots role="group" aria-label="جایگاه لباس">
                    <button type="button" data-vto-slot="top" aria-pressed="true"><span class="vto-slot-icon">01</span><span>بالاپوش</span><i data-vto-slot-state="top">انتخاب نشده</i></button>
                    <button type="button" data-vto-slot="outerwear" aria-pressed="false"><span class="vto-slot-icon">02</span><span>لایهٔ رویی</span><i data-vto-slot-state="outerwear">انتخاب نشده</i></button>
                    <button type="button" data-vto-slot="bottom" aria-pressed="false"><span class="vto-slot-icon">03</span><span>پایین‌تنه</span><i data-vto-slot-state="bottom">انتخاب نشده</i></button>
                    <button type="button" data-vto-slot="shoes" aria-pressed="false"><span class="vto-slot-icon">04</span><span>کفش</span><i data-vto-slot-state="shoes">انتخاب نشده</i></button>
                    <button type="button" data-vto-slot="accessory" aria-pressed="false"><span class="vto-slot-icon">05</span><span>اکسسوری</span><i data-vto-slot-state="accessory">انتخاب نشده</i></button>
                </div>
            </section>

            <section class="vto-panel vto-catalog-panel" aria-labelledby="vto-catalog-title">
                <div class="vto-panel__heading"><div><p class="vto-step">مرحلهٔ ۰۳</p><h2 id="vto-catalog-title">انتخاب‌های این بخش</h2></div><span class="vto-result-count" data-vto-result-count>۰ گزینه</span></div>
                <p class="vto-catalog-note" data-vto-catalog-note>فقط محصولاتی نمایش داده می‌شوند که تصویر لایه‌ای معتبر برای این مانکن داشته باشند.</p>
                <div class="vto-products" data-vto-products aria-live="polite" aria-busy="false"></div>
                <button class="vto-load-more" data-vto-load-more type="button" hidden>نمایش گزینه‌های بیشتر <span>↓</span></button>
            </section>
        </aside>
        @endif

        <section class="vto-preview-column" aria-label="پیش‌نمایش ترکیب">
            <div class="vto-preview-card">
                @if($inline ?? false)
                    <div class="vto-inline-header">
                        <div>
                            <h2>اتاق پرو</h2>
                            <p>روی چوب‌لباسی هر محصول بزن تا آن را روی مانکن ببینی.</p>
                        </div>
                        <button class="vto-inline-close" type="button" data-vto-close aria-label="بستن اتاق پرو" title="بستن">×</button>
                    </div>
                @else
                    <div class="vto-preview-topline"><div class="vto-preview-tools"><button class="vto-expand-button" type="button" data-vto-expand aria-expanded="false" aria-controls="vto-stage-wrap">بزرگ‌نمایی اتاق پرو ↗</button><span class="vto-live-indicator"><i></i> پیش‌نمایش زنده</span></div><div><p class="vto-step">LIVE PREVIEW / 01</p><h2>نمای ترکیب</h2></div></div>
                @endif
                <div class="vto-stage-wrap" id="vto-stage-wrap">
                    <div class="vto-stage" data-vto-stage style="aspect-ratio: 2 / 3;" role="img" aria-label="مانکن و لایه‌های لباس انتخاب‌شده">
                        <div class="vto-stage-glow" aria-hidden="true"></div>
                        <img class="vto-stage-base" data-vto-base-image alt="" hidden>
                        <svg class="vto-silhouette" data-vto-fallback-mannequin viewBox="0 0 512 768" aria-hidden="true">
                            <defs>
                                <linearGradient id="vto-body-shade" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#e1d8cb"/><stop offset=".55" stop-color="#c9beb0"/><stop offset="1" stop-color="#aa9f92"/></linearGradient>
                                <linearGradient id="vto-body-light" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#a89d90"/><stop offset=".45" stop-color="#eee5d8"/><stop offset="1" stop-color="#bcb1a4"/></linearGradient>
                            </defs>
                            <g data-vto-silhouette="women" fill="url(#vto-body-shade)">
                                <ellipse cx="256" cy="84" rx="43" ry="51"/><path d="M240 128h32v43h-32z"/>
                                <path d="M208 158 Q176 165 153 197 L119 286 Q112 304 128 314 Q143 322 151 303 L186 235 L195 336 Q190 366 173 411 L191 479 L207 626 Q210 646 230 646 Q248 644 247 624 L252 490 L260 490 L265 624 Q264 644 282 646 Q302 646 305 626 L322 479 L339 411 Q322 366 317 336 L326 235 L361 303 Q369 322 384 314 Q400 304 393 286 L359 197 Q336 165 304 158 L279 164 L233 164 Z"/>
                                <path fill="url(#vto-body-light)" d="M208 158 Q176 165 153 197 L119 286 Q112 304 128 314 Q143 322 151 303 L186 235 L195 336 L230 334 L223 184 Z"/>
                            </g>
                            <g data-vto-silhouette="men" fill="url(#vto-body-shade)" style="display:none">
                                <ellipse cx="256" cy="84" rx="44" ry="52"/><path d="M239 129h34v43h-34z"/>
                                <path d="M222 158 Q190 157 158 181 L117 272 Q108 296 126 307 Q142 316 153 292 L188 224 L198 352 Q193 382 178 423 L195 482 L209 626 Q212 646 231 646 Q248 644 248 623 L253 494 L259 494 L264 623 Q264 644 282 646 Q301 646 304 626 L318 482 L334 423 Q319 382 314 352 L324 224 L359 292 Q370 316 386 307 Q404 296 395 272 L354 181 Q322 157 290 158 L272 164 L240 164 Z"/>
                            </g>
                            <path d="M198 647h116l14 34h-55l-17-10-17 10h-55z" fill="#9f9486"/><path d="M220 682h73" stroke="#8e8376" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                        <div class="vto-stage-layers" data-vto-layers aria-live="polite"></div>
                        <div class="vto-stage-empty" data-vto-stage-empty><span>✳</span><p>اولین انتخاب را انجام بده</p><small>لایه‌ها بدون تغییر جایگاه مانکن اضافه می‌شوند.</small></div>
                        <div class="vto-stage-loading" data-vto-stage-loading hidden><span></span><small>در حال ترکیب لایه‌ها</small></div>
                    </div>
                    <div class="vto-stage-caption"><span data-vto-mannequin-caption>مانکن زنانه · نمای روبه‌رو</span><span>CANVAS 2:3</span></div>
                </div>
                <div class="vto-selection-summary">
                    @if($inline ?? false)
                        <div class="vto-inline-picks-heading">
                            <h3>لباس‌های انتخاب‌شده</h3>
                            <span data-vto-picks-count>۰ تکه</span>
                        </div>
                        <div class="vto-selected-list" data-vto-selected-list></div>
                        <div class="vto-inline-footer">
                            <button type="button" data-vto-clear-all>شروع دوباره ↺</button>
                        </div>
                    @else
                        <div class="vto-selection-summary__head"><div><p class="vto-step">YOUR LOOK / SUMMARY</p><h3>چیزهایی که انتخاب کردی</h3></div><button type="button" data-vto-clear-all>شروع دوباره ↺</button></div>
                        <div class="vto-selected-list" data-vto-selected-list></div>
                        <div class="vto-total-line"><span>جمع قیمت انتخاب‌ها <small>قیمت‌ها در مرحلهٔ خرید دوباره بررسی می‌شوند.</small></span><strong data-vto-total>۰ تومان</strong></div>
                        <div class="vto-preview-actions"><a href="{{ route('shop.index') }}" class="vto-secondary-action">دیدن همهٔ محصولات</a><span class="vto-inventory-note" data-vto-inventory-note>قیمت و موجودی نهایی هنگام خرید تأیید می‌شود.</span></div>
                    @endif
                </div>
            </div>
            @if(!($inline ?? false))
                <div class="vto-tip"><span>✳</span><p><strong>راهنمای تصویر دقیق</strong> نتیجهٔ بهتر زمانی به‌دست می‌آید که هر لایه تصویر شفاف PNG/WebP با اندازهٔ یکسان و هم‌تراز با مانکن باشد.</p></div>
            @endif
        </section>
    </section>
    <div class="vto-toast" data-vto-toast role="status" aria-live="polite"></div>
</section>
@if($inline ?? false)
    <button class="vto-inline-launcher" type="button" data-vto-launcher aria-controls="vto-inline-workspace" aria-expanded="false">
        <span class="vto-inline-launcher__mark" aria-hidden="true">✳</span>
        <span>Try it on</span>
    </button>
@endif
