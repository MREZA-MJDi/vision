    <aside id="fit" aria-labelledby="fit-title" hidden tabindex="-1">
        <h2 class="fit-heading">
            <span id="fit-title">اتاق پرو</span>
            <button id="close" type="button" data-shop-url="{{ route('shop.index') }}" aria-label="بازگشت به فروشگاه">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </h2>
        <div class="fit-content">
            <p class="fit-guide">یک لباس انتخاب کن؛ بعد از دیدن نتیجه، خریدت را ادامه بده.</p>
            <div class="stage" id="stage" role="group" aria-label="پیش‌نمایش مانکن و لباس‌ها" aria-busy="false">
                <div class="load" id="load" role="status" hidden><span class="spin" aria-hidden="true"></span><b>در حال آماده‌سازی</b></div>
            </div>
            <button type="button" id="sugg" class="shop-fit-suggestion" aria-pressed="false" hidden>پیش‌نمایش ست پیشنهادی</button>
            <p class="fit-scene-notice" id="fit-scene-notice" role="status" aria-live="polite" hidden></p>
            <button type="button" id="fit-retry" class="shop-fit-suggestion" hidden>تلاش دوباره</button>
            <div class="pk"><span>لباس‌های انتخاب‌شده</span><span id="pcount" role="status">۰ تکه</span></div>
            <div class="picks" id="picks" aria-label="لباس‌های انتخاب‌شده"></div>

            <section class="fit-recommendations" aria-labelledby="fit-recommend-title" hidden>
                <div class="fit-section-head">
                    <h3 id="fit-recommend-title">با این ست کن</h3>
                </div>
                <div class="fit-recommend-grid" id="fit-recommend-grid" aria-live="polite"></div>
            </section>

            <details class="fit-compare" aria-labelledby="fit-compare-title">
                <summary class="fit-compare-summary" id="fit-compare-title">مقایسه‌ی استایل‌ها</summary>
                <div class="fit-compare-content">
                    <div class="fit-section-head fit-compare-head">
                        <p>دو ترکیب را ذخیره کن و قیمتشان را کنار هم ببین.</p>
                        <button type="button" id="clearCompare" aria-label="پاک کردن استایل‌های مقایسه">پاک‌کردن</button>
                    </div>
                    <div class="fit-compare-actions">
                        <button type="button" id="saveCompareA">ذخیره‌ی ترکیب A</button>
                        <button type="button" id="saveCompareB">ذخیره‌ی ترکیب B</button>
                    </div>
                    <div class="fit-compare-grid" id="fit-compare-grid" aria-live="polite"></div>
                </div>
            </details>
        </div>
        <div class="fit-footer">
            <div class="tot" id="tot">هنوز چیزی انتخاب نشده</div>
            <div class="btns">
                <button class="pri" id="addset" type="button" disabled>افزودن انتخاب‌ها به سبد خرید</button>
                <button id="strip" type="button" disabled>پاک کردن انتخاب‌ها</button>
            </div>
            <a class="fit-cart-next" id="fit-cart-next" href="{{ route('cart.index') }}" hidden>مشاهده سبد خرید و ادامه خرید</a>
        </div>
    </aside>
