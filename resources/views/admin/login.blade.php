<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Store Studio · Sign in</title>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>

<body class="login-body">
    <main class="login-card"><a href="{{ route('home') }}" class="logo">MESH<span class="logo-dot">.</span></a><span
            class="eyebrow muted">STORE STUDIO</span>
        <h1>A little more<br>behind the scenes<span>.</span></h1>
        <p>Sign in to manage your store.</p><x-flash />
        <form method="post" action="{{ route('admin.login.store') }}">@csrf<label class="field">Email address<input
                    name="email" type="email" autocomplete="username" value="{{ old('email') }}" required
                    autofocus></label><label class="field">Password<input name="password" type="password"
                    autocomplete="current-password" required></label><button class="button button-dark full-width">Sign
                in <x-icon name="arrow" /></button></form><span class="secure-note"><x-icon name="shield"
                width="15" /> Authorized team members only.</span>
    </main>
</body>

</html>
