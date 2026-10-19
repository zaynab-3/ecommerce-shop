@props(['drawer' => false])
<a class="{{ $drawer ? 'drawer-category-link' : 'all-categories' }}" href="{{ route('shop') }}"><x-icon name="grid" /> All categories @if ($drawer)<x-icon name="chevron" width="16" />@endif</a>
<div class="{{ $drawer ? 'drawer-links' : 'nav-links' }}">
    <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Discover</a>
    <a class="{{ request()->routeIs('shop') && !request('saved') ? 'active' : '' }}" href="{{ route('shop') }}" @if(request()->routeIs('shop') && !request('saved')) aria-current="page" @endif>Shop all</a>
    <a href="{{ route('shop', ['sort' => 'newest']) }}">New arrivals <span class="new-pill">NEW</span></a>
    <a href="{{ route('shop', ['sort' => 'price_asc']) }}">Everyday value</a>
</div>
@if ($drawer)
    <div class="drawer-utilities">
        <a href="{{ route('shop', ['saved' => 1]) }}"><x-icon name="heart" />Saved items</a>
        <a href="{{ route('cart.index') }}"><x-icon name="bag" />My cart<span class="drawer-cart-count">{{ collect(session('cart', []))->sum('quantity') }}</span></a>
    </div>
@endif
<a class="{{ $drawer ? 'drawer-help' : 'nav-delivery' }}" href="{{ route('help') }}"><x-icon name="truck" /> Delivery across Lebanon</a>
