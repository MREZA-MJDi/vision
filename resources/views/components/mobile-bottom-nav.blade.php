@props(['context' => 'admin'])
<nav class="mobile-bottom-nav mobile-bottom-nav--admin" aria-label="دسترسی سریع مدیریت">
<a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="mobile-bottom-nav__icon">⌂</span><span>خانه</span></a>
<a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><span class="mobile-bottom-nav__icon">◈</span><span>محصول</span></a>
<a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><span class="mobile-bottom-nav__icon">◫</span><span>سفارش</span></a>
<a href="{{ route('admin.contact.index') }}" class="{{ request()->routeIs('admin.contact.*') ? 'active' : '' }}"><span class="mobile-bottom-nav__icon">✉</span><span>پیام</span></a>
<a href="{{ route('admin.profile.edit') }}" class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}"><span class="mobile-bottom-nav__icon">◉</span><span>پروفایل</span></a>
</nav>
