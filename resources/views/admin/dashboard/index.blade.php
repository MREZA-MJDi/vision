@extends('layouts.admin')

@section('title', 'داشبورد مدیریت')
@section('page-title', 'داشبورد')

@section('content')

    @php
        $statusClasses = [
            'pending' => 'warning',
            'confirmed' => 'info',
            'preparing' => 'info',
            'shipped' => 'success',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'returned' => 'neutral',
        ];

        $paymentClasses = [
            'paid' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'refunded' => 'neutral',
        ];

        $paymentRate = $ordersCount > 0
            ? round(($paidOrdersCount / $ordersCount) * 100)
            : 0;

        $processingCount = (int) (
            ($orderBreakdown['pending'] ?? 0)
            + ($orderBreakdown['confirmed'] ?? 0)
            + ($orderBreakdown['preparing'] ?? 0)
        );

        $chartMax = max((float) $maxIncome, 1);

        $formatFaNumber = static function (mixed $value, int $decimals = 0): string {
            return strtr(
                number_format((float) $value, $decimals, '.', ','),
                [
                    '0' => '۰',
                    '1' => '۱',
                    '2' => '۲',
                    '3' => '۳',
                    '4' => '۴',
                    '5' => '۵',
                    '6' => '۶',
                    '7' => '۷',
                    '8' => '۸',
                    '9' => '۹',
                    ',' => '٬',
                    '.' => '٫',
                ]
            );
        };
    @endphp

    <div class="dashboard-v2">

        <section class="dashboard-v2__hero">
            <div>
                <span class="dashboard-v2__eyebrow">JANAN / CONTROL CENTER</span>

                <h1>
                    نمای کلی فروشگاه،
                    <br>
                    <em>در یک نگاه.</em>
                </h1>

                <p>
                    فروش، سفارش، موجودی و عملکرد فعلی را از یک نقطه کنترل کن.
                    اعداد این صفحه مستقیماً از داده‌های واقعی فروشگاه خوانده می‌شوند.
                </p>
            </div>

            <div class="dashboard-v2__hero-actions">
                <div class="dashboard-v2__live">
                    <i aria-hidden="true"></i>
                    <span>پنل فروشگاه</span>
                </div>

                <form
                    method="GET"
                    action="{{ route('admin.dashboard') }}"
                    class="dashboard-v2__period"
                >
                    <label for="dashboard-period">بازه گزارش</label>
                    <select id="dashboard-period" name="period" onchange="this.form.submit()">
                        @foreach([
                            7 => '۷ روز اخیر',
                            30 => '۳۰ روز اخیر',
                            60 => '۶۰ روز اخیر',
                            90 => '۹۰ روز اخیر',
                        ] as $days => $label)
                            <option value="{{ $days }}" @selected((int) $period === $days)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </section>

        <section class="dashboard-v2__stats" aria-label="شاخص‌های اصلی">
            <article class="dashboard-v2__stat dashboard-v2__stat--dark">
                <span>REVENUE</span>
                <strong>{{ $formatFaNumber((float) $revenue) }}</strong>
                <small>تومان درآمد پرداخت‌شده</small>
                <i>01</i>
            </article>

</div>
        </section>

        <section class="dashboard-v2__control-center" aria-label="آمادگی محتوا و SEO">
            <div class="dashboard-v2__control-head">
                <div>
                    <span class="dashboard-v2__kicker">CONTENT / SEO</span>
                    <h2>آمادگی محتوا برای انتشار و جستجو</h2>
                    <p>این شمارنده‌ها از محصولات، دسته‌بندی‌ها و برندهای فعال خوانده می‌شوند. عکس‌های کاتالوگ را جانان بارگذاری می‌کند؛ داده نیلا عکس را جایگزین نمی‌کند.</p>
                </div>
            </div>

            <div class="dashboard-v2__control-grid">
                <a href="{{ route('admin.products.index', ['quality' => 'missing-seo']) }}" class="dashboard-v2__control-item {{ $productsMissingSeo > 0 ? 'is-attention' : '' }}">
                    <span class="dashboard-v2__control-icon">SEO</span>
                    <div>
                        <strong>SEO محصولات</strong>
                        <small>{{ $formatFaNumber((int) $productsMissingSeo) }} محصول فعال بدون عنوان یا توضیح SEO</small>
                    </div>
                    <b aria-hidden="true">←</b>
                </a>

                <a href="{{ route('admin.products.index', ['quality' => 'missing-image']) }}" class="dashboard-v2__control-item {{ $productsMissingImage > 0 ? 'is-attention' : '' }}">
                    <span class="dashboard-v2__control-icon">عکس</span>
                    <div>
                        <strong>تصاویر محصول</strong>
                        <small>{{ $formatFaNumber((int) $productsMissingImage) }} محصول فعال بدون تصویر گالری</small>
                    </div>
                    <b aria-hidden="true">←</b>
                </a>

                <a href="{{ route('admin.categories.index') }}" class="dashboard-v2__control-item {{ $categoriesMissingSeo > 0 ? 'is-attention' : '' }}">
                    <span class="dashboard-v2__control-icon">دسته</span>
                    <div>
                        <strong>SEO دسته‌بندی‌ها</strong>
                        <small>{{ $formatFaNumber((int) $categoriesMissingSeo) }} دسته فعال نیازمند بازبینی</small>
                    </div>
                    <b aria-hidden="true">←</b>
                </a>

                <a href="{{ route('admin.brands.index') }}" class="dashboard-v2__control-item {{ $brandsMissingSeo > 0 ? 'is-attention' : '' }}">
                    <span class="dashboard-v2__control-icon">برند</span>
                    <div>
                        <strong>SEO برندها</strong>
                        <small>{{ $formatFaNumber((int) $brandsMissingSeo) }} برند فعال نیازمند بازبینی</small>
                    </div>
                    <b aria-hidden="true">←</b>
                </a>
            </div>

            <div class="dashboard-v2__panel-links">
                <a href="{{ '#' }}" target="_blank" rel="noopener">مشاهده نقشه سایت XML ↗</a>
                <a href="{{ '#' }}" target="_blank" rel="noopener">مشاهده robots.txt ↗</a>
                <a href="{{ route('admin.content.about') }}">ویرایش محتوای درباره ما ↗</a>
            </div>
        </section>

        <section class="dashboard-v2__control-center" aria-label="مرکز اقدام مدیریت">
            <div class="dashboard-v2__control-head">
                <div>
                    <span class="dashboard-v2__kicker">ACTION CENTER</span>
                    <h2>کارهایی که الان باید کنترل شوند</h2>
                    <p>این بخش فقط وضعیت‌هایی را نشان می‌دهد که واقعاً نیاز به تصمیم یا اقدام مدیریتی دارند.</p>
                </div>
            </div>

            <div class="dashboard-v2__control-grid">
<a href="{{ route('admin.inventory.index') }}" class="dashboard-v2__control-item {{ $lowStock > 0 ? 'is-attention' : '' }}">
                    <span class="dashboard-v2__control-icon">انبار</span>
                    <div>
                        <strong>کنترل موجودی</strong>
                        <small>{{ $formatFaNumber((int) $lowStock) }} واریانت در محدوده هشدار · ارزش موجودی {{ $formatFaNumber((float) $inventoryValue) }} تومان</small>
                    </div>
                    <b aria-hidden="true">←</b>
                </a>

                <a href="{{ route('admin.nila.index') }}" class="dashboard-v2__control-item">
                    <span class="dashboard-v2__control-icon">نیلا</span>
                    <div>
                        <strong>واردسازی و نگاشت نیلا / هلو</strong>
                        <small>{{ $formatFaNumber((int) $nilaProductMappings) }} محصول و {{ $formatFaNumber((int) $nilaVariantMappings) }} واریانت نگاشت داخلی دارند. این عدد به معنی اتصال زنده نیست.</small>
                    </div>
                    <span class="dashboard-v2__control-state">کنترل نیلا ←</span>
                </a>
            </div>
        </section>

        <section class="dashboard-v2__grid dashboard-v2__grid--main">

            <article class="dashboard-v2__panel dashboard-v2__panel--chart">
                <header class="dashboard-v2__panel-head">
                    <div>
                        <span class="dashboard-v2__kicker">SALES SIGNAL</span>
                        <h2>روند فروش روزانه</h2>
                        <p>درآمد ثبت‌شده در دفترکل و تعداد سفارش‌های ثبت‌شده در {{ $formatFaNumber($period) }} روز اخیر.</p>
                    </div>

                    <div class="dashboard-v2__mini-stat">
                        <strong>{{ $formatFaNumber($paymentRate) }}%</strong>
                        <span>نرخ پرداخت</span>
                    </div>
                </header>

                <div class="dashboard-v2__chart-wrap">
                    <div class="dashboard-v2__chart" style="--chart-columns: {{ max(1, $daily->count()) }};">
                        @foreach($daily as $day)
                            @php
                                $income = (float) ($day['income'] ?? 0);
                                $magnitude = abs($income);
                                $height = $magnitude > 0
                                    ? max(6, ($magnitude / $chartMax) * 100)
                                    : 3;
                                $incomeLabel = $magnitude > 0
                                    ? ($income < 0 ? '−' : '') . $formatFaNumber($magnitude / 1000000, 1) . 'M'
                                    : '—';
                                $incomeTitle = ($income < 0 ? 'کاهش خالص ' : '') . $formatFaNumber($magnitude) . ' تومان';
                            @endphp

                            <div class="dashboard-v2__bar-column">
                                <div class="dashboard-v2__bar-value {{ $income < 0 ? 'is-negative' : '' }}">
                                    {{ $incomeLabel }}
                                </div>

                                <div class="dashboard-v2__bar-track">
                                    <span
                                        style="height: {{ $height }}%;{{ $income < 0 ? 'background:linear-gradient(180deg,#d89bad,#8f405e);' : '' }}"
                                        title="{{ $incomeTitle }}"
                                    ></span>
                                </div>

                                <small
                                    data-admin-date="{{ $day['date'] ?? '' }}"
                                    data-admin-date-format="day"
                                >
                                    {{ $day['label'] ?? '—' }}
                                </small>
                                <b class="dashboard-v2__bar-orders">
                                    {{ $formatFaNumber((int) ($day['orders'] ?? 0)) }}
                                </b>
                            </div>
                        @endforeach
                    </div>
                </div>

                <footer class="dashboard-v2__chart-footer">
                    <span>مقیاس مبلغ: میلیون تومان</span>
                    <span>مبلغ بر اساس تاریخ سند؛ سفارش بر اساس تاریخ ثبت</span>
                </footer>
            </article>

            <aside class="dashboard-v2__panel">
                <header class="dashboard-v2__panel-head">
                    <div>
                        <span class="dashboard-v2__kicker">ORDER FLOW</span>
                        <h2>قیف سفارش</h2>
                        <p>تصویر سریع از وضعیت سفارش‌های بازه انتخابی.</p>
                    </div>
                </header>

                <div class="dashboard-v2__status-list">
                    @foreach($statusNames as $status => $name)
                        @php
                            $count = (int) ($orderBreakdown[$status] ?? 0);
                            $percent = $ordersCount > 0
                                ? round(($count / $ordersCount) * 100)
                                : 0;
                        @endphp

                        <div class="dashboard-v2__status">
                            <div>
                                <span>{{ $name }}</span>
                                <strong>{{ $formatFaNumber($count) }}</strong>
                            </div>

                            <div class="dashboard-v2__status-track">
                                <i
                                    class="dashboard-v2__status-fill dashboard-v2__status-fill--{{ $statusClasses[$status] ?? 'neutral' }}"
                                    style="width: {{ $percent }}%"
                                ></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>

        </section>

        <section class="dashboard-v2__grid dashboard-v2__grid--triple">

            <article class="dashboard-v2__panel dashboard-v2__panel--dark">
                <header class="dashboard-v2__panel-head dashboard-v2__panel-head--dark">
                    <div>
                        <span class="dashboard-v2__kicker">CASH FLOW</span>
                        <h2>پول واردشده و هزینه</h2>
                    </div>
                </header>

                <div class="dashboard-v2__cash-list">
                    <div>
                        <span>درآمد خالص دفترکل</span>
                        <strong>{{ $formatFaNumber((float) $revenue) }} <small>تومان</small></strong>
                    </div>
                    <div>
                        <span>هزینه‌های ثبت‌شده، بدون بازپرداخت</span>
                        <strong>{{ $formatFaNumber((float) $expenses) }} <small>تومان</small></strong>
                    </div>
                    <div class="dashboard-v2__cash-total">
                        <span>خالص</span>
                        <strong>{{ $formatFaNumber((float) $netCash) }} <small>تومان</small></strong>
                    </div>
                </div>

                <a href="{{ route('admin.accounting.index') }}" class="dashboard-v2__panel-link">
                    رفتن به حسابداری
                    <span aria-hidden="true">↗</span>
                </a>
            </article>

            <article class="dashboard-v2__panel">
                <header class="dashboard-v2__panel-head">
                    <div>
                        <span class="dashboard-v2__kicker">INVENTORY ALERT</span>
                        <h2>موجودی کم</h2>
                        <p>اولویت‌های انبار که بهتر است بررسی شوند.</p>
                    </div>

                    <a href="{{ route('admin.inventory.index') }}" class="dashboard-v2__small-link">
                        همه
                        <span aria-hidden="true">↗</span>
                    </a>
                </header>

                <div class="dashboard-v2__inventory">
                    @forelse($lowStockVariants as $variant)
                        @php
                            $threshold = max((int) ($variant->low_stock_threshold ?? 5), 1);
                            $stockPercent = min(100, max(0, ((int) $variant->stock / $threshold) * 100));
                        @endphp

                        <div class="dashboard-v2__inventory-row">
                            <div>
                                <strong>{{ $variant->product?->name ?? 'محصول' }}</strong>
                                <span dir="ltr">{{ $variant->sku ?: '—' }}</span>
                            </div>

                            <div class="dashboard-v2__inventory-bar">
                                <i style="width: {{ $stockPercent }}%"></i>
                            </div>

                            <b>{{ $formatFaNumber((int) $variant->stock) }}</b>
                        </div>
                    @empty
                        <div class="dashboard-v2__empty">
                            <strong>انبار در وضعیت مناسب است.</strong>
                            <span>هیچ تنوعی در محدوده هشدار نیست.</span>
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="dashboard-v2__panel">
                <header class="dashboard-v2__panel-head">
                    <div>
                        <span class="dashboard-v2__kicker">TOP PRODUCTS</span>
                        <h2>پرفروش‌ها</h2>
                        <p>بر اساس تعداد اقلام فروخته‌شده در بازه.</p>
                    </div>

                    <a href="{{ route('admin.products.index') }}" class="dashboard-v2__small-link">
                        محصولات
                        <span aria-hidden="true">↗</span>
                    </a>
                </header>

                <div class="dashboard-v2__top-products">
                    @forelse($topProducts as $index => $product)
                        @php
                            $salesQuantity = (int) ($product->sales_quantity ?? 0);
                            $productImage = $product->galleryMedia?->first();
                        @endphp

                        <a
                            href="{{ route('admin.products.index', ['q' => $product->name]) }}"
                            class="dashboard-v2__top-product"
                        >
                            <span class="dashboard-v2__rank">{{ $index + 1 }}</span>

                            @if($productImage?->url)
                                <img
                                    src="{{ $productImage->url }}"
                                    alt="{{ $product->name }}"
                                    loading="lazy"
                                >
                            @else
                                <span class="dashboard-v2__product-placeholder">—</span>
                            @endif

                            <div>
                                <strong>{{ $product->name }}</strong>
                                <small>{{ $product->category?->name ?? 'بدون دسته‌بندی' }}</small>
                            </div>

                            <b>{{ $formatFaNumber($salesQuantity) }} <small>عدد</small></b>
                        </a>
                    @empty
                        <div class="dashboard-v2__empty">
                            <strong>هنوز داده فروش وجود ندارد.</strong>
                            <span>پس از ثبت سفارش‌های واقعی این بخش پر می‌شود.</span>
                        </div>
                    @endforelse
                </div>
            </article>

        </section>

        <section class="dashboard-v2__panel dashboard-v2__panel--orders">

            <header class="dashboard-v2__panel-head">
                <div>
                    <span class="dashboard-v2__kicker">RECENT ACTIVITY</span>
                    <h2>آخرین سفارش‌ها</h2>
                    <p>جدیدترین سفارش‌های واقعی فروشگاه.</p>
                </div>

                <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn--secondary admin-btn--sm">
                    همه سفارش‌ها
                    <span aria-hidden="true">↗</span>
                </a>
            </header>

            @if($recentOrders->isNotEmpty())
                <div class="dashboard-v2__orders-mobile" aria-label="آخرین سفارش‌ها در موبایل">
                    @foreach($recentOrders as $order)
                        @php
                            $customerName = $order->user?->name ?? $order->customer_name ?? 'مشتری';
                        @endphp
                        <article class="dashboard-v2__order-card">
                            <div class="dashboard-v2__order-card-head">
                                <strong>{{ $order->order_number }}</strong>
                                <span class="admin-badge admin-badge--{{ $statusClasses[$order->status] ?? 'neutral' }}">{{ $statusNames[$order->status] ?? $order->status }}</span>
                            </div>
                            <div class="dashboard-v2__order-card-body">
                                <div><small>مشتری</small><strong>{{ $customerName }}</strong></div>
                                <div><small>مبلغ</small><strong>{{ $formatFaNumber((float) $order->total) }} <em>تومان</em></strong></div>
                                <div><small>پرداخت</small><strong>{{ $order->payment_status === 'paid' ? 'پرداخت‌شده' : ($order->payment_status === 'pending' ? 'در انتظار' : $order->payment_status) }}</strong></div>
                                <a href="{{ route('admin.orders.show', $order) }}" class="dashboard-v2__order-card-link">جزئیات <span aria-hidden="true">←</span></a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="dashboard-v2__orders-table">
                    <table>
                        <thead>
                        <tr>
                            <th>سفارش</th>
                            <th>مشتری</th>
                            <th>مبلغ</th>
                            <th>وضعیت</th>
                            <th>پرداخت</th>
                            <th>تاریخ</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($recentOrders as $order)
                            @php
                                $customerName = $order->user?->name ?? $order->customer_name ?? 'مشتری';
                                $customerPhone = $order->user?->phone ?? $order->customer_phone ?? null;
                            @endphp

                            <tr>
                                <td>
                                    <strong>{{ $order->order_number }}</strong>
                                </td>

                                <td>
                                    <div class="dashboard-v2__customer">
                                        <strong>{{ $customerName }}</strong>
                                        @if($customerPhone)
                                            <small dir="ltr">{{ $customerPhone }}</small>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <strong>{{ $formatFaNumber((float) $order->total) }}</strong>
                                    <small class="dashboard-v2__muted">تومان</small>
                                </td>

                                <td>
                                    <span class="admin-badge admin-badge--{{ $statusClasses[$order->status] ?? 'neutral' }}">
                                        {{ $statusNames[$order->status] ?? $order->status }}
                                    </span>
                                </td>

                                <td>
                                    <span class="admin-badge admin-badge--{{ $paymentClasses[$order->payment_status] ?? 'neutral' }}">
                                        @switch($order->payment_status)
                                            @case('paid') پرداخت‌شده @break
                                            @case('pending') در انتظار پرداخت @break
                                            @case('failed') ناموفق @break
                                            @case('refunded') بازپرداخت‌شده @break
                                            @default {{ $order->payment_status }}
                                        @endswitch
                                    </span>
                                </td>

                                <td>
                                    <span class="dashboard-v2__muted">
                                        <span
                                            class="admin-local-date"
                                            data-admin-date="{{ optional($order->placed_at)->toIso8601String() }}"
                                        >
                                            {{ optional($order->placed_at)->format('Y/m/d H:i') }}
                                        </span>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="{{ route('admin.orders.show', $order) }}"
                                        class="dashboard-v2__order-link"
                                    >
                                        جزئیات
                                        <span aria-hidden="true">←</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="dashboard-v2__empty dashboard-v2__empty--large">
                    <strong>هنوز سفارشی ثبت نشده است.</strong>
                    <span>به‌محض ثبت سفارش، فعالیت‌های اخیر اینجا نمایش داده می‌شوند.</span>
                </div>
            @endif
        </section>

        <section class="dashboard-v2__footer-grid">
            <a href="{{ route('admin.products.create') }}" class="dashboard-v2__quick-action">
                <span>01</span>
                <strong>محصول جدید</strong>
                <small>افزودن محصول به کاتالوگ</small>
                <b aria-hidden="true">↗</b>
            </a>

            <a href="{{ route('admin.orders.index') }}" class="dashboard-v2__quick-action">
                <span>02</span>
                <strong>بررسی سفارش‌ها</strong>
                <small>پیگیری وضعیت سفارش‌های جاری</small>
                <b aria-hidden="true">↗</b>
            </a>

            <a href="{{ route('admin.inventory.index') }}" class="dashboard-v2__quick-action">
                <span>03</span>
                <strong>کنترل موجودی</strong>
                <small>بررسی تنوع‌های کم‌موجودی</small>
                <b aria-hidden="true">↗</b>
            </a>

            <a href="{{ route('admin.accounting.index') }}" class="dashboard-v2__quick-action">
                <span>04</span>
                <strong>حسابداری</strong>
                <small>مشاهده جریان مالی فروشگاه</small>
                <b aria-hidden="true">↗</b>
            </a>

            <a href="{{ route('admin.content.about') }}" class="dashboard-v2__quick-action">
                <span>05</span>
                <strong>محتوای سایت</strong>
                <small>ویرایش متن‌های درباره ما</small>
                <b aria-hidden="true">↗</b>
            </a>

            <a href="{{ route('admin.contact.index') }}" class="dashboard-v2__quick-action">
                <span>06</span>
                <strong>پیام‌های تماس</strong>
                <small>پیگیری درخواست‌های مشتریان</small>
                <b aria-hidden="true">↗</b>
            </a>
        </section>

    </div>
@endsection
