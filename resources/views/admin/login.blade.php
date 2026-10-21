<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Store Studio · Sign in</title>
    <link rel="preload" href="{{ asset('fonts/space-grotesk.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/identity.css') }}?v={{ filemtime(public_path('css/identity.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store-interactions.css') }}?v={{ filemtime(public_path('css/store-interactions.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store-scrollbars.css') }}?v={{ filemtime(public_path('css/store-scrollbars.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/smooth-scroll.css') }}?v={{ filemtime(public_path('css/smooth-scroll.css')) }}">
    <link rel="stylesheet" href="{{ route('store.theme', ['color' => substr(\App\Models\StoreSetting::current()->accentColor(), 1), 'v' => \App\Services\StoreTheme::VERSION]) }}">
<script src="{{ asset('js/shared/notices.js') }}" type="module"></script>
<script src="{{ asset('js/shared/smooth-scroll.js') }}?v={{ filemtime(public_path('js/shared/smooth-scroll.js')) }}" type="module"></script>
</head>

<body class="login-body">
    <main class="login-card"><a href="{{ route('home') }}" class="logo">MESH<span class="logo-dot">.</span></a>
        <h1>Admin sign in</h1>
        <x-flash />
        <form method="post" action="{{ route('admin.login.store') }}">@csrf<label class="field">Email address<input
                    name="email" type="email" autocomplete="username" value="{{ old('email') }}" required
                    autofocus></label><label class="field">Password<input name="password" type="password"
                    autocomplete="current-password" required></label><button class="button button-dark full-width">Sign
                in <x-icon name="arrow" /></button></form><span class="secure-note"><x-icon name="shield"
                width="15" /> Authorized team members only.</span>
    </main>
</body>

</html>
