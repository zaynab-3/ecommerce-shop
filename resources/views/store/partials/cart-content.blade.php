@if ($lines->isEmpty())
    <div class="empty-state"><x-icon name="bag" width="56" height="56" />
        <h2>Your cart is empty</h2>
        <a class="button button-dark" data-continue-shopping href="{{ route('shop') }}">Shop products <x-icon name="arrow" /></a>
    </div>
@else
@php($checkoutPhase = request()->routeIs('checkout'))
<div data-cart-phase="cart" @if($checkoutPhase) hidden @endif>
<div class="cart-stage-layout">
    <section class="cart-review" aria-labelledby="cart-items-heading">
        <h2 id="cart-items-heading">Your items</h2>
        <div class="cart-lines">
        @foreach ($lines as $line)
            @php($image = ($line['color'] ? $line['product']->images->firstWhere('product_color_id', $line['color']->id) : null) ?? $line['product']->images->firstWhere('product_color_id', null) ?? $line['product']->images->first())
            <article class="cart-line">
                <a class="cart-line-image" href="{{ route('products.show', $line['product']) }}"><img src="{{ $image?->thumbnail_url ?? $line['product']->image_url }}" width="80" height="80" alt="{{ $line['product']->name }}"></a>
                <div class="cart-line-info">
                    <h3><a href="{{ route('products.show', $line['product']) }}">{{ $line['product']->name }}</a></h3>
                    @if($line['color'])<p>{{ $line['color']->name }}</p>@endif
                    <x-stock :product="$line['product']" />
                    @unless($line['available'])<p class="field-error">Currently unavailable. Please remove this item.</p>@endunless
                </div>
                <strong class="cart-line-price">${{ number_format($line['total'] / 100, 2) }}</strong>
                <div class="cart-line-actions">
                    <form method="post" action="{{ route('cart.update', $line['key']) }}" class="cart-quantity-form" data-cart-update>
                        @csrf @method('PATCH')
                        <button type="button" class="icon-button" data-cart-step="-1" aria-label="Decrease quantity of {{ $line['product']->name }}" @disabled($line['quantity'] <= 1)><x-icon name="minus" /></button>
                        <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="99" aria-label="Quantity of {{ $line['product']->name }}">
                        <button type="button" class="icon-button" data-cart-step="1" aria-label="Increase quantity of {{ $line['product']->name }}" @disabled($line['quantity'] >= 99)><x-icon name="plus" /></button>
                        <noscript><button class="icon-button" aria-label="Update quantity"><x-icon name="check" /></button></noscript>
                    </form>
                    <form method="post" data-cart-update action="{{ route('cart.update', $line['key']) }}">@csrf @method('PATCH')<input type="hidden" name="quantity" value="0"><button class="icon-button cart-remove" aria-label="Remove {{ $line['product']->name }}"><x-icon name="trash" /></button></form>
                </div>
            </article>
        @endforeach
        </div>
        <a class="icon-button continue-shopping" data-continue-shopping href="{{ route('shop') }}" aria-label="Continue shopping" title="Continue shopping"><x-icon name="arrow" /></a>
    </section>
    <aside class="cart-stage-summary">
        <h2>Summary</h2>
        <div class="summary-row total"><strong>Subtotal</strong><strong>${{ number_format($subtotal / 100, 2) }}</strong></div>
        <p class="summary-note">Delivery is calculated at checkout.</p>
        <a class="button button-dark full-width" data-proceed-checkout href="{{ route('checkout') }}">Proceed to checkout <x-icon name="arrow" /></a>
    </aside>
</div>
</div>
<div data-cart-phase="checkout" @unless($checkoutPhase) hidden @endunless>
    <a class="text-link checkout-back" data-cart-back href="{{ route('cart.index') }}"><x-icon name="chevron" />Back to cart</a>
    @include('store.partials.checkout-form')
</div>
@endif
