        @if ($lines->isEmpty())
            <div class="empty-state"><x-icon name="bag" width="56" height="56" />
                <h2>Your cart is empty</h2>
                <p>Add products to your cart to get started.</p><a class="button button-dark"
                    href="{{ route('shop') }}">Shop products <x-icon name="arrow" /></a>
        </div>@else<div class="shopping-layout">
                <div class="cart-lines">
                    @foreach ($lines as $line)
                        <article class="cart-line"><a href="{{ route('products.show', $line['product']) }}"><img
                                    src="{{ $line['product']->image_url }}" width="130" height="130"
                                    alt="{{ $line['product']->name }}"></a>
                            <div class="cart-line-info"><span
                                    class="eyebrow muted">{{ $line['product']->category->name }}</span>
                                <h2><a href="{{ route('products.show', $line['product']) }}">{{ $line['product']->name }}</a>
                                </h2>
                                <p>{{ $line['color']?->name ?? 'Standard' }}</p><x-stock :product="$line['product']" />
                                @unless ($line['available'])
                                    <p class="field-error">This item is currently unavailable. Please remove it.</p>
                                @endunless
                                <form method="post" action="{{ route('cart.update', $line['key']) }}"
                                    class="cart-quantity-form" data-cart-update>
                                    @csrf @method('PATCH')<label>Qty <input type="number" name="quantity"
                                            value="{{ $line['quantity'] }}" min="0" max="99"
                                            aria-label="Quantity of {{ $line['product']->name }}"></label><button
                                        class="text-link">Update</button></form>
                            </div>
                            <div class="cart-line-total"><strong>${{ number_format($line['total'] / 100, 2) }}</strong>
                                <form method="post" data-cart-update action="{{ route('cart.update', $line['key']) }}">@csrf
                                    @method('PATCH')<input type="hidden" name="quantity" value="0"><button
                                        class="muted text-link">Remove</button></form>
                            </div>
                        </article>
                    @endforeach
                    <a class="text-link continue-shopping" data-continue-shopping href="{{ route('shop') }}">
                        ← Continue shopping</a>
                </div>
                <aside class="order-summary">
                    <h2>Order summary</h2>
                    <div class="summary-row"><span>Subtotal</span><strong>${{ number_format($subtotal / 100, 2) }}</strong>
                    </div>
                    @if ($settings->delivery_enabled)
                        <form method="get" action="{{ route('cart.index') }}" class="cart-delivery-estimate" data-cart-delivery><label
                                class="field">Estimate your delivery<select name="delivery_zone_id">
                                    <option value="">Select area</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" @selected($selectedZone?->id === $zone->id)>
                                            {{ $zone->name }} — ${{ number_format($zone->fee_cents / 100, 2) }}</option>
                                    @endforeach
                                </select>
                            </label><noscript><button class="text-link">Update estimate</button></noscript></form>
                    @endif
                    <div class="summary-row">
                        <span>Delivery</span><span>{{ $settings->delivery_enabled ? ($selectedZone ? '$' . number_format($selectedZone->fee_cents / 100, 2) : 'Select your area') : '$0.00' }}</span>
                    </div>
                    <div class="summary-row total">
                        <strong>{{ $settings->delivery_enabled && !$selectedZone ? 'Subtotal' : 'Total' }}</strong><strong>${{ number_format(($subtotal + ($selectedZone?->fee_cents ?? 0)) / 100, 2) }}</strong>
                    </div>
                    <p class="summary-note">Delivery charges, if enabled, are shown before you place your order.</p><a
                        class="button button-dark full-width" href="{{ route('checkout') }}">Continue to checkout <x-icon
                            name="arrow" /></a>
                    <div class="secure-note"><x-icon name="shield" width="16" /> Secure checkout · Cash on delivery
                    </div>
                </aside>
            </div>
        @endif
