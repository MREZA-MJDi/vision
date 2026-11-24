@php
    $channels = [
        'phone' => ['تماس', config('services.storefront.phone')],
        'instagram' => ['اینستاگرام', config('services.storefront.instagram')],
        'whatsapp' => ['واتساپ', config('services.storefront.whatsapp')],
    ];
@endphp
<nav class="home-hero__support" aria-label="راه‌های ارتباط با فروشگاه">
    @foreach($channels as $channel => [$label, $href])
        <a href="{{ $href ?: route('contact.index') }}" data-channel="{{ $channel }}"
           aria-label="{{ $href ? $label : $label . '، فرم تماس فروشگاه' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                @switch($channel)
                    @case('phone')
                        <rect x="7" y="2.5" width="10" height="19" rx="2"/>
                        <path d="M11 18.5h2"/>
                        @break
                    @case('instagram')
                        <rect x="3" y="3" width="18" height="18" rx="5"/>
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M17.3 6.8h.1"/>
                        @break
                    @case('whatsapp')
                        <path d="M4.5 16.7 3 21l4.5-1.3a9 9 0 1 0-3-3Z"/>
                        <path d="M9 7.5c-1 0-1.4 1-1.3 2 .2 2.5 3.8 5.9 6.2 6 1 0 2-.7 2-1.5l-2.4-1.1-.9 1c-1.4-.6-2.6-1.8-3.1-3.1l.9-.8L9 7.5Z"/>
                        @break
                @endswitch
            </svg>
            <span>{{ $label }}</span>
        </a>
    @endforeach
</nav>
