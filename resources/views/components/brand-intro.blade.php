@props([
    'redirectTo' => null,
])

@once
    @push('head')
        @vite(['resources/css/brand-intro.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/brand-intro.js'])
    @endpush
@endonce

<div
    id="rmm-brand-intro"
    class="rmm-brand-intro"
    data-redirect-to="{{ $redirectTo }}"
    aria-hidden="true"
>
    <div class="rmm-brand-intro__noise"></div>

    <div class="rmm-brand-intro__content">
        <div class="rmm-brand-intro__brand">
            Tiamir Company
        </div>

        <div class="rmm-brand-intro__line" aria-hidden="true"></div>

        <div class="rmm-brand-intro__meta">
            <span>DEV By</span>
            <span>RM MAJIDI</span>
        </div>
    </div>

    <div class="rmm-brand-intro__progress" aria-hidden="true"></div>
</div>
