<dialog id="search-dialog" class="store-dialog search-dialog" aria-labelledby="search-title">
    <div class="dialog-heading"><h2 id="search-title">Find a product</h2><button type="button" class="icon-button" data-dialog-close aria-label="Close search"><x-icon name="close" /></button></div>
    <form id="store-search" class="modal-search-form" method="get" action="{{ route('shop') }}" role="search"><label for="search" class="sr-only">Search products</label><input id="search" name="q" placeholder="What are you looking for?" value="{{ request('q') }}" maxlength="100" autocomplete="off" autofocus><button class="button button-dark" type="submit" aria-label="Search"><x-icon name="search" /></button></form>
</dialog>
<dialog id="cart-dialog" class="store-dialog cart-dialog" aria-labelledby="cart-title" data-cart-url="{{ route('cart.index') }}" data-checkout-url="{{ route('checkout') }}" data-shop-url="{{ route('shop') }}">
    <div class="dialog-heading"><h2 id="cart-title"><span data-cart-title>Cart & checkout</span> <span data-cart-count>{{ collect(session('cart', []))->sum('quantity') }}</span></h2><button type="button" class="icon-button" data-dialog-close aria-label="Close cart" autofocus><x-icon name="close" /></button></div>
    <div class="cart-dialog-body" data-cart-content aria-live="polite"><p>Loading your cart…</p></div>
</dialog>
