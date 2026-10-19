<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="Shop products, check stock and delivery availability, and order with cash on delivery.">
    <title>@yield('title', 'Shop') — {{ \App\Models\StoreSetting::current()->store_name }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/space-grotesk.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/teko.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <link rel="stylesheet" href="{{ route('store.theme', ['color' => substr(\App\Models\StoreSetting::current()->accentColor(), 1)]) }}">
    <script src="{{ asset('js/store.js') }}" type="module"></script>@stack('head')
</head>

<body>
    <a href="#main" class="skip-link">Skip to content</a>
    <div class="announcement">
        <div class="container announcement-inner"><span>Delivery areas at checkout</span><span
                class="announcement-center">Cash on delivery <x-icon name="arrow"
                    width="14" height="14" /></span><a href="{{ route('help') }}">Help & support</a></div>
    </div>
    <header class="site-header">
        <div class="container header-inner">
            <button class="icon-button mobile-menu-toggle" type="button" data-menu-toggle aria-label="Open navigation"
                aria-expanded="false"><x-icon name="menu" /></button>
            <a class="logo" href="{{ route('home') }}">{{ \App\Models\StoreSetting::current()->store_name }}<span
                    class="logo-dot">.</span></a>
            <form id="store-search" class="search-form" method="get" action="{{ route('shop') }}" role="search"><x-icon
                    name="search" /><label for="search" class="sr-only">Search products</label><input id="search"
                    name="q" placeholder="Search products…" value="{{ request('q') }}"
                    maxlength="100"><button type="submit">Search</button></form>
            <div class="header-actions"><button type="button" class="icon-button mobile-search-toggle" data-search-toggle aria-label="Open search" aria-expanded="false" aria-controls="store-search"><x-icon name="search" /></button><a href="{{ route('shop', ['saved' => 1]) }}" class="icon-button saved-link"
                    aria-label="Saved items"><x-icon name="heart" /></a><a class="cart-link"
                    href="{{ route('cart.index') }}"
                    aria-label="Shopping cart, {{ collect(session('cart', []))->sum('quantity') }} items"><x-icon
                        name="bag" /><span class="bag-label">My cart</span><span
                        class="bag-count">{{ collect(session('cart', []))->sum('quantity') }}</span></a></div>
        </div>
        <nav class="main-nav" aria-label="Main navigation">
            <div class="container nav-inner" data-mobile-nav>
                <a class="all-categories" href="{{ route('shop') }}"><x-icon name="grid" /> All categories</a>
                <div class="nav-links"><a class="{{ request()->routeIs('home') ? 'active' : '' }}"
                        href="{{ route('home') }}">Discover</a><a
                        class="{{ request()->routeIs('shop') && !request('saved') ? 'active' : '' }}"
                        href="{{ route('shop') }}">Shop all</a><a href="{{ route('shop', ['sort' => 'newest']) }}">New
                        arrivals <span class="new-pill">NEW</span></a><a
                        href="{{ route('shop', ['sort' => 'price_asc']) }}">Everyday value</a></div><a
                    class="nav-delivery" href="{{ route('help') }}"><x-icon name="truck" /> Delivery across
                    Lebanon</a>
            </div>
        </nav>
    </header>
    <main id="main">
        @if (session('success') || $errors->any())
            <div class="container flash-container"><x-flash /></div>
        @endif @yield('content')
    </main>
    <section class="benefits">
        <div class="container benefits-grid">
            <div><x-icon name="truck" /><span><strong>Delivery in Lebanon</strong><small>Delivery to selected
                        areas in Lebanon</small></span></div>
            <div><x-icon name="shield" /><span><strong>Cash on delivery</strong><small>Secure checkout. Cash on
                        delivery.</small></span></div>
            <div><x-icon name="sparkles" /><span><strong>Stock checked at checkout</strong><small>Order quantities are confirmed when you buy</small></span></div>
        </div>
    </section>
    <footer class="site-footer">
        <div class="container footer-top">
            <div class="footer-brand"><a class="logo"
                    href="{{ route('home') }}">{{ \App\Models\StoreSetting::current()->store_name }}<span
                        class="logo-dot">.</span></a>
                <p>Products, prices, and stock in one place.</p><span class="made-in">Shopping in Lebanon <span>↗</span></span>
            </div>
            <div>
                <h3>Explore</h3><a href="{{ route('shop') }}">All products</a><a
                    href="{{ route('shop', ['sort' => 'newest']) }}">New arrivals</a><a
                    href="{{ route('shop', ['saved' => 1]) }}">Your saved items</a>
            </div>
            <div>
                <h3>Here to help</h3><a href="{{ route('help') }}#delivery">Delivery information</a><a
                    href="{{ route('help') }}#returns">Returns & exchanges</a><a
                    href="{{ route('help') }}#contact">Contact & support</a>
            </div>
        </div>
        <div class="container footer-bottom"><span>© {{ date('Y') }}
                {{ \App\Models\StoreSetting::current()->store_name }}. All rights reserved.</span><a
                href="{{ route('help') }}#privacy">Privacy & terms</a><span>Lebanon · USD $</span></div>
    </footer>
</body>

</html>
