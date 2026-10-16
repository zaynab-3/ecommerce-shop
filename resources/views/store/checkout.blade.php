@extends('layouts.store')
@section('title', 'Checkout')
@section('content')<div class="container shopping-page">
        <div class="breadcrumb"><a href="{{ route('cart.index') }}">Your bag</a><x-icon name="chevron"
                width="13" /><span>Checkout</span></div>
        <div class="collection-title">
            <div><span class="eyebrow muted">YOU’RE ONE STEP AWAY</span>
                <h1>Make it yours<span>.</span></h1>
                <p>No account needed. Just the essentials.</p>
            </div><x-icon name="shield" width="38" height="38" />
        </div>
        <form method="post" action="{{ route('checkout.store') }}" class="shopping-layout checkout-form" data-checkout
            data-subtotal="{{ $subtotal }}">@csrf<input type="hidden" name="idempotency_key"
                value="{{ session('checkout_key') }}">
            <div class="checkout-fields">
                <section class="form-card">
                    <h2><span class="step-number">01</span> Your details</h2>
                    <div class="field-grid"><label class="field">Full name <input name="customer_name"
                                value="{{ old('customer_name') }}" autocomplete="name" maxlength="120"
                                required></label><label class="field">Phone number <input name="phone" type="tel"
                                value="{{ old('phone') }}" placeholder="+961 70 123 456" autocomplete="tel" maxlength="25"
                                required></label><label class="field full-span">Email <span
                                class="muted">(optional)</span><input name="email" type="email"
                                value="{{ old('email') }}" autocomplete="email" maxlength="190"></label></div>
                </section>
                <section class="form-card">
                    <h2><span class="step-number">02</span> Delivery details</h2>
                    <div class="field-grid">
                        @if ($settings->delivery_enabled)
                            <label class="field full-span">Delivery area <select name="delivery_zone_id" id="delivery-zone"
                                    required>
                                    <option value="" data-fee="0">Select your area</option>
                                    @foreach ($zones->groupBy('governorate') as $gov => $areas)
                                        <optgroup label="{{ $gov }}">
                                            @foreach ($areas as $zone)
                                                <option value="{{ $zone->id }}" data-fee="{{ $zone->fee_cents }}"
                                                    @selected((int) old('delivery_zone_id', session('delivery_zone_id')) === $zone->id)>{{ $zone->name }} —
                                                    ${{ number_format($zone->fee_cents / 100, 2) }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </label>
                            @if ($zones->isEmpty())
                                <p class="field-error full-span">No delivery areas are available yet. Please contact the
                                    store before ordering.</p>
                        @endif @else<p class="muted full-span">There is no delivery fee configured
                                for this order. The team will confirm your address.</p>
                        @endif
                        <label class="field full-span">
                            Full address
                            <textarea name="address" rows="3" placeholder="Street, building, floor, and any landmarks"
                                autocomplete="street-address" minlength="8" maxlength="1000" required>{{ old('address') }}</textarea>
                        </label><label class="field full-span">Order notes <span class="muted">(optional)</span>
                            <textarea name="notes" rows="2" placeholder="Anything we should know?" maxlength="1000">{{ old('notes') }}</textarea>
                        </label>
                    </div>
                </section>
                <section class="form-card">
                    <h2><span class="step-number">03</span> Payment</h2>
                    <div class="payment-option"><span class="radio-dot"></span>
                        <div><strong>Cash on delivery</strong>
                            <p>Pay when your good finds arrive.</p>
                        </div><x-icon name="shield" />
                    </div>
                </section>
            </div>
            <aside class="order-summary">
                <h2>Your order</h2>
                @foreach ($lines as $line)
                    <div class="checkout-mini-item"><img src="{{ $line['product']->image_url }}" alt=""
                            width="58" height="58">
                        <div><strong>{{ $line['product']->name }}</strong><small>{{ $line['color']?->name ?? 'Standard' }} ·
                                Qty {{ $line['quantity'] }}</small></div>
                        <strong>${{ number_format($line['total'] / 100, 2) }}</strong>
                    </div>
                @endforeach
                <div class="summary-row"><span>Subtotal</span><strong>${{ number_format($subtotal / 100, 2) }}</strong></div>
                <div class="summary-row"><span>Delivery</span><strong
                        id="delivery-fee">{{ $settings->delivery_enabled ? 'Select area' : '$0.00' }}</strong></div>
                <div class="summary-row total"><strong>Total</strong><strong
                        id="checkout-total">${{ number_format($subtotal / 100, 2) }}</strong></div>
                <p class="summary-note">By placing your order, you agree to our <a href="{{ route('help') }}#privacy">terms
                        & privacy policy</a>.</p><button class="button button-dark full-width"
                    @disabled($settings->delivery_enabled && $zones->isEmpty())>{{ $settings->whatsapp_enabled ? 'Place order & open WhatsApp' : 'Place order' }} <x-icon name="arrow" /></button>
                @if($settings->whatsapp_enabled)
                    <p class="summary-note">Your order will be saved, then WhatsApp will open with your order message. Tap Send in WhatsApp to share it with our team.</p>
                @endif
                <div class="secure-note"><x-icon name="shield" width="16" /> Your details stay private.</div>
            </aside>
        </form>
</div>@endsection
