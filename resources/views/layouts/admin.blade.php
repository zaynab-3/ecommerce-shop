<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Overview') · MESH Studio</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}">
    <link rel="preload" href="{{ asset('fonts/space-grotesk.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/identity.css') }}?v={{ filemtime(public_path('css/identity.css')) }}">
    <link rel="stylesheet" href="{{ route('store.theme', ['color' => substr(\App\Models\StoreSetting::current()->accentColor(), 1), 'v' => \App\Services\StoreTheme::VERSION]) }}">
    <script src="{{ asset('js/admin.js') }}" type="module"></script>
</head>

<body class="admin-body"><a class="skip-link" href="#main">Skip to content</a>
    <aside class="admin-sidebar"><a class="logo" href="{{ route('admin.dashboard') }}">MESH<span
                class="logo-dot">.</span></a>
        <nav aria-label="Admin navigation"><a
                href="{{ route('admin.dashboard') }}"
                class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><x-icon name="grid" />Overview</a><a
                href="{{ route('admin.orders.index') }}"
                class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><x-icon name="bag" />Orders</a>
            @if (auth()->user()->canManageCatalog())
                <a href="{{ route('admin.products.index') }}"
                    class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><x-icon
                        name="box" />Products</a><a href="{{ route('admin.categories.index') }}"
                    class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><x-icon
                        name="grid" />Categories</a><a href="{{ route('admin.delivery.index') }}"
                    class="{{ request()->routeIs('admin.delivery.*') ? 'active' : '' }}"><x-icon name="truck" />Delivery
                    areas</a>
                @endif @if (auth()->user()->role === 'owner')
                    <a
                        href="{{ route('admin.team.index') }}"
                        class="{{ request()->routeIs('admin.team.*') ? 'active' : '' }}"><x-icon name="users" />Team &
                        access</a><a href="{{ route('admin.settings.index') }}"
                        class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><x-icon
                            name="settings" />Store settings</a>
                @endif
        </nav>
        <div class="sidebar-bottom"><a class="store-link" href="{{ route('home') }}" target="_blank"
                rel="noopener">Visit storefront <x-icon name="arrow" width="15" /></a>
            <div class="admin-profile"><span
                    class="avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small>
                </div>
                <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="icon-button"
                        aria-label="Sign out"><x-icon name="logout" width="17" /></button></form>
            </div>
        </div>
    </aside>
    <div class="admin-content">
        <header class="admin-topbar"><button class="icon-button admin-menu-toggle" type="button" data-admin-menu
                aria-expanded="false" aria-label="Toggle admin navigation"><x-icon
                    name="menu" /></button><span>Workspace <span>/</span> @yield('title', 'Overview')</span>
        </header>
        <main id="main" class="admin-main"><x-flash />@yield('content')</main>
    </div>
</body>

</html>
