@props(['drawer' => false])
<div class="{{ $drawer ? 'drawer-links' : 'nav-links' }}">
    <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Discover</a>
    <a class="{{ request()->routeIs('shop') && !request('saved') ? 'active' : '' }}" href="{{ route('shop') }}" @if(request()->routeIs('shop') && !request('saved')) aria-current="page" @endif>Shop</a>
</div>
@if ($drawer)
    <div class="drawer-utilities">
        <a href="{{ route('shop', ['saved' => 1]) }}"><x-icon name="heart" />Saved items</a>
        <a href="{{ route('cart.index') }}"><x-icon name="bag" />My cart<span class="drawer-cart-count" data-cart-count>{{ collect(session('cart', []))->sum('quantity') }}</span></a>
    </div>
@endif
<a class="{{ $drawer ? 'drawer-help' : 'nav-delivery' }}" href="{{ route('help') }}"><x-icon name="truck" /> Delivery across Lebanon</a>
