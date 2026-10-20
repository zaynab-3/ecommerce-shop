@props(['name' => 'grid'])
<svg
    {{ $attributes->merge(['class' => 'icon', 'width' => '20', 'height' => '20', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('search')
            <circle cx="10.8" cy="10.8" r="7.2" />
            <path d="m16 16 4.5 4.5" />
        @break

        @case('phone')
            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z" />
        @break

        @case('bag')
            <path d="M5 7h14l1 14H4L5 7Z" />
            <path d="M8 8V6a4 4 0 0 1 8 0v2" />
        @break

        @case('bag-plus')
            <path d="M5 7h14l1 14H4L5 7Z" /><path d="M8 8V6a4 4 0 0 1 8 0v2M9 14h6m-3-3v6" />
        @break

        @case('minus')
            <path d="M5 12h14" />
        @break

        @case('pause')
            <path d="M9 5v14M15 5v14" />
        @break

        @case('play')
            <path d="m8 4 12 8-12 8V4Z" />
        @break

        @case('plus')
            <path d="M5 12h14m-7-7v14" />
        @break

        @case('trash')
            <path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7" />
        @break

        @case('heart')
            <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z" />
        @break

        @case('arrow')
            <path d="M4 12h16m-6-6 6 6-6 6" />
        @break

        @case('chevron')
            <path d="m9 5 7 7-7 7" />
        @break

        @case('check')
            <path d="m5 12 4 4L19 6" />
        @break

        @case('truck')
            <path d="M1 4h13v12H1zM14 9h5l4 4v3h-9" />
            <circle cx="5" cy="18" r="2" />
            <circle cx="18" cy="18" r="2" />
        @break

        @case('shield')
            <path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6l9-4Z" />
            <path d="m8 12 3 3 5-6" />
        @break

        @case('refresh')
            <path d="M20 7a9 9 0 1 0 1 9M20 2v5h-5" />
        @break

        @case('headphones')
            <path d="M3 14v-3a9 9 0 0 1 18 0v3M3 12h3v8H3zM18 12h3v8h-3z" />
        @break

        @case('home')
            <path d="m2 10 10-8 10 8M5 9v12h14V9M9 21v-8h6v8" />
        @break

        @case('sparkles')
            <path d="m12 3 3 6 6 3-6 3-3 6-3-6-6-3 6-3 3-6ZM20 2v4m-2-2h4" />
        @break

        @case('gamepad')
            <path d="M6 7h12c4 0 6 14 2 14l-5-5H9l-5 5C0 21 2 7 6 7ZM7 9v6m-3-3h6m7-1h.01M19 14h.01" />
        @break

        @case('watch')
            <rect x="6" y="6" width="12" height="12" rx="4" />
            <path d="M8 6V1h8v5M8 18v5h8v-5m-4-8v3l2 1" />
        @break

        @case('menu-staggered')
            <path d="M4 8h16M4 16h10" />
        @break

        @case('menu')
            <path d="M3 6h18M3 12h18M3 18h18" />
        @break

        @case('close')
            <path d="m6 6 12 12M6 18 18 6" />
        @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
        @break

        @case('users')
            <circle cx="9" cy="7" r="4" />
            <path d="M2 21v-3a7 7 0 0 1 14 0v3m1-18a4 4 0 0 1 0 8m2 4a5 5 0 0 1 3 5" />
        @break

        @case('settings')
            <path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM9 2h6l1 4 4 1 2 5-3 3v4l-5 3-3-3-4 1-4-4 1-4-2-3 3-5 4 1 1-3Z" />
        @break

        @case('box')
            <path d="m12 2 10 5v10l-10 5L2 17V7l10-5Zm0 10L2 7m10 5L22 7M12 12v10M7 4l10 5" />
        @break

        @case('logout')
            <path d="M9 4H3v16h6m5-13 5 5-5 5M8 12h13" />
        @break

        @case('edit')
            <path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-2 2 5 5" />
        @break

        @case('location')
            <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10" r="3" />
        @break

        @default
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
    @endswitch
</svg>
