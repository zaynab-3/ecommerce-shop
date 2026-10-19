<form method="post" action="{{ route('checkout.store') }}" class="sheet-checkout" data-checkout data-subtotal="{{ $subtotal }}">
    @csrf<input type="hidden" name="idempotency_key" value="{{ session('checkout_key') }}">
    <h2>Checkout</h2>
    <div class="checkout-errors" data-checkout-errors role="alert" @if(!$errors->any()) hidden @endif>@if($errors->any()){{ $errors->first() }}@endif</div>
    <div class="field-grid">
        <label class="field full-span"><span>Full name</span><input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" maxlength="120" required></label>
        <label class="field full-span"><span>Phone number</span>
            <span class="phone-field"><span class="phone-prefix" aria-hidden="true">+961</span><input name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="70 123 456" autocomplete="tel-national" maxlength="30" aria-label="Lebanese phone number, country code +961" data-lebanese-phone required></span>
        </label>
        @if ($settings->delivery_enabled)
            <label class="field full-span"><span>Delivery area</span><select name="delivery_zone_id" id="delivery-zone" required>
                <option value="" data-fee="0">Select your area</option>
                @foreach($zones->groupBy('governorate') as $gov => $areas)
                    <optgroup label="{{ $gov }}">@foreach($areas as $zone)<option value="{{ $zone->id }}" data-fee="{{ $zone->fee_cents }}" @selected((int) old('delivery_zone_id', session('delivery_zone_id')) === $zone->id)>{{ $zone->name }} — ${{ number_format($zone->fee_cents / 100, 2) }}</option>@endforeach</optgroup>
                @endforeach
            </select></label>
            @if($zones->isEmpty())<p class="field-error full-span">No delivery areas are available yet. Please contact the store before ordering.</p>@endif
        @endif
        <label class="field full-span"><span>Full address</span><textarea name="address" rows="2" placeholder="Street, building, floor, landmarks" autocomplete="street-address" minlength="8" maxlength="1000" required>{{ old('address') }}</textarea></label>
        <label class="field full-span"><span class="field-label">Order notes <span class="optional">(optional)</span></span><textarea name="notes" rows="2" placeholder="Anything we should know?" maxlength="1000">{{ old('notes') }}</textarea></label>
    </div>
    <p class="sheet-payment"><x-icon name="shield" />Cash on delivery</p>
    <div class="sheet-totals">
        <div class="summary-row"><span>Subtotal</span><strong>${{ number_format($subtotal / 100, 2) }}</strong></div>
        <div class="summary-row"><span>Delivery</span><strong id="delivery-fee">{{ $settings->delivery_enabled && !$selectedZone ? 'Select area' : '$'.number_format(($selectedZone?->fee_cents ?? 0) / 100, 2) }}</strong></div>
        <div class="summary-row total"><strong>Total</strong><strong id="checkout-total">${{ number_format(($subtotal + ($selectedZone?->fee_cents ?? 0)) / 100, 2) }}</strong></div>
    </div>
    <button type="submit" class="button button-dark full-width" @disabled($settings->delivery_enabled && $zones->isEmpty())><span>{{ $settings->whatsapp_enabled ? 'Place order & open WhatsApp' : 'Place order' }}</span><x-icon name="arrow" /></button>
    <p class="summary-note">By placing your order, you agree to our <a href="{{ route('help') }}#privacy">terms & privacy policy</a>.@if($settings->whatsapp_enabled) Your order is saved before WhatsApp opens. Tap Send there to share it.@endif</p>
</form>
