<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="Shop products, check stock and delivery availability, and order with cash on delivery.">
    <title>@yield('title', 'Shop') — {{ \App\Models\StoreSetting::current()->store_name }}</title>
    <link rel="stylesheet" href="{{ asset('css/store-motion.css') }}?v={{ filemtime(public_path('css/store-motion.css')) }}">
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/space-grotesk.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store-navigation.css') }}?v={{ filemtime(public_path('css/store-navigation.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/identity.css') }}?v={{ filemtime(public_path('css/identity.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store-interactions.css') }}?v={{ filemtime(public_path('css/store-interactions.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store-scrollbars.css') }}?v={{ filemtime(public_path('css/store-scrollbars.css')) }}">
    <link rel="stylesheet" href="{{ route('store.theme', ['color' => substr(\App\Models\StoreSetting::current()->accentColor(), 1), 'v' => \App\Services\StoreTheme::VERSION]) }}">
    <script src="{{ asset('js/store.js') }}?v={{ filemtime(public_path('js/store.js')) }}" type="module"></script>@stack('head')
</head>

<body>
    <a href="#main" class="skip-link">Skip to content</a>
    <div class="store-chrome" data-store-header>
    <div class="announcement">
        <div class="container announcement-inner"><a class="announcement-delivery" href="{{ route('help') }}"><x-icon name="truck" width="16" height="16" />Delivery across Lebanon</a><span
                class="announcement-center">Cash on delivery</span><a href="{{ route('help') }}">Help & support</a></div>
    </div>
    <header class="site-header">
        <div class="container header-inner">
            <button class="icon-button mobile-menu-toggle" type="button" data-menu-toggle aria-label="Open navigation"
                aria-expanded="false" aria-controls="mobile-navigation" aria-haspopup="dialog"><x-icon name="menu-staggered" /></button>
            <a class="logo" href="{{ route('home') }}">{{ \App\Models\StoreSetting::current()->store_name }}<span
                    class="logo-dot">.</span></a>
            <button class="header-search-trigger" type="button" data-search-toggle aria-label="Open search" aria-controls="search-dialog" aria-haspopup="dialog" aria-expanded="false"><x-icon name="search" /><span>Search products…</span></button>
            <div class="header-actions"><button type="button" class="icon-button mobile-search-toggle" data-search-toggle aria-label="Open search" aria-expanded="false" aria-controls="search-dialog" aria-haspopup="dialog"><x-icon name="search" /></button><a href="{{ route('shop', ['saved' => 1]) }}" class="icon-button saved-link"
                    aria-label="Saved items, {{ count(session('saved', [])) }} items"><x-icon name="heart" /><span class="header-count" data-saved-count aria-hidden="true">{{ count(session('saved', [])) }}</span></a><a class="cart-link"
                    href="{{ route('cart.index') }}"
                    aria-label="Shopping cart, {{ collect(session('cart', []))->sum('quantity') }} items"><x-icon
                        name="bag" /><span
                        class="bag-count header-count" data-cart-count aria-hidden="true">{{ collect(session('cart', []))->sum('quantity') }}</span></a></div>
        </div>
    </header>
    </div>
    <x-mobile-navigation />
    <x-store-dialogs />
    <main id="main">
        <x-flash />@yield('content')
    </main>
    @unless(View::hasSection('hide-footer'))
    <section class="benefits">
        <div class="container benefits-grid">
            <div><x-icon name="truck" /><span><strong>Delivery in Lebanon</strong><small>Delivery to selected
                        areas in Lebanon</small></span></div>
            <div><x-icon name="shield" /><span><strong>Cash on delivery</strong><small>Secure checkout. Cash on
                        delivery.</small></span></div>
            <div><x-icon name="sparkles" /><span><strong>Stock checked at checkout</strong><small>Order quantities are confirmed when you buy</small></span></div>
        </div>
    </section>
    <div class="store-footer-area">
    <footer class="site-footer">
        <div class="container footer-top">
            <div class="footer-brand"><a class="logo"
                    href="{{ route('home') }}">{{ \App\Models\StoreSetting::current()->store_name }}<span
                        class="logo-dot">.</span></a>
                <p>Products, prices, and stock in one place.</p><span class="made-in">Shopping in Lebanon <x-icon name="arrow" /></span>
            </div>
            <div>
                <h3>Explore</h3><a href="{{ route('shop') }}">All products</a><a
                    href="{{ route('shop', ['saved' => 1]) }}">Your saved items</a>
            </div>
            <div>
                <h3>Here to help</h3><a href="{{ route('help') }}#delivery">Delivery information</a><a
                    href="{{ route('help') }}#returns">Returns & exchanges</a><a
                    href="{{ route('help') }}#contact">Contact & support</a>
            </div>
        </div>
        <div class="container footer-bottom"><span>© {{ date('Y') }}
                {{ \App\Models\StoreSetting::current()->store_name }}. All rights reserved.</span>
            <div class="footer-policy-links"><a href="{{ route('privacy') }}">Privacy policy</a><a href="{{ route('terms') }}">Terms & conditions</a><a href="{{ route('privacy') }}#cookies" data-cookie-settings>Cookie settings</a></div>
            <span>Lebanon · USD $</span></div>
    </footer>
    </div>
    @endunless
    <x-cookie-notice />
</body>

</html>
