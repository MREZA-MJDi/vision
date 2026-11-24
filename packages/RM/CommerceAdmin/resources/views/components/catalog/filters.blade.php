
<div class="rm-taxonomy-stats">
    @foreach([
        'all' => 'تعداد کل',
        'active' => 'فعال',
        'used' => 'دارای محصول',
    ] as $key => $label)
        <article class="ak-panel">
            <span>{{ $label }}</span>

            <strong>
                {{ number_format($stats[$key] ?? 0) }}
            </strong>
        </article>
    @endforeach
</div>

<form
    method="GET"
    action="{{ $action }}"
    class="rm-taxonomy-filters"
    role="search"
>
    <label class="ak-field">
        <span>جست‌وجو</span>

        <input
            type="search"
            name="q"
            maxlength="120"
            value="{{ $search ?? '' }}"
            placeholder="نام یا شناسه آدرس"
        >
    </label>

    <label class="ak-field">
        <span>وضعیت نمایش</span>

        <select name="status">
            <option
                value="all"
                @selected(($status ?? 'all') === 'all')
            >
                همه موارد
            </option>

            <option
                value="active"
                @selected(($status ?? 'all') === 'active')
            >
                فعال
            </option>

            <option
                value="inactive"
                @selected(($status ?? 'all') === 'inactive')
            >
                غیرفعال
            </option>
        </select>
    </label>

    <button
        type="submit"
        class="ak-button ak-button-dark"
    >
        اعمال فیلتر
    </button>

    <a
        href="{{ $action }}"
        class="ak-button ak-button-light"
    >
        پاک کردن فیلتر
    </a>
</form>
