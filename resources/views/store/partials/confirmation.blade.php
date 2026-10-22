<div class="container confirmation-page">
        <div class="confirmation-icon"><x-icon name="check" width="40" height="40" /></div>
        <h1>{{ $order->status === 'cancelled' ? 'Order cancelled' : 'Order received' }}</h1>
        <p>{{ $order->status === 'cancelled' ? 'This order has been cancelled. Contact the store if you need help.' : 'Your order has been received. Our team will contact you to confirm the details.' }}</p>
        @if($whatsappUrl && $order->status !== 'cancelled')
            <x-whatsapp-order :url="$whatsappUrl" :auto-open="$autoOpenWhatsApp" />
        @endif
        <div class="confirmation-card">
            <div class="section-heading">
                <div><small class="muted">ORDER NUMBER</small>
                    <h2>{{ $order->number }}</h2>
                </div><span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
            </div>
            @foreach ($order->items as $item)
                <div class="summary-row"><span>{{ $item->name }}{{ $item->color ? ' · ' . $item->color : '' }} ×
                        {{ $item->quantity }}</span><strong>${{ number_format(($item->price_cents * $item->quantity) / 100, 2) }}</strong>
                </div>
            @endforeach
            <div class="summary-row">
                <span>Delivery{{ $order->delivery_area ? ' · ' . $order->delivery_area : '' }}</span><strong>${{ number_format($order->delivery_cents / 100, 2) }}</strong>
            </div>
            <div class="summary-row total"><strong>Total · Cash on
                    delivery</strong><strong>${{ number_format($order->total_cents / 100, 2) }}</strong></div>
            <p class="muted">Deliver to {{ $order->address }}</p>
        </div><a class="button button-dark" data-continue-shopping href="{{ route('shop') }}">Continue shopping <x-icon name="arrow" /></a>
</div>
