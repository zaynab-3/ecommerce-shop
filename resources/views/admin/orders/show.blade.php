@extends('layouts.admin')
@section('title', $order->number)
@section('content')<x-admin-heading :title="$order->number" :description="'Placed ' . $order->created_at->format('d M Y \a\t H:i') . ' · Cash on delivery'"><a class="text-link"
            href="{{ route('admin.orders.index') }}">← All orders</a></x-admin-heading>
    <div class="admin-form-layout">
        <div class="admin-form-primary">
            <section class="admin-panel">
                <div class="panel-heading">
                    <h2>Order items</h2><span
                        class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                </div>
                <div class="table-scroll" tabindex="0" role="region" aria-label="Scrollable table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td><strong>{{ $item->name }}</strong><small>{{ $item->sku }}{{ $item->color ? ' · ' . $item->color : '' }}</small>
                                    </td>
                                    <td>${{ number_format($item->price_cents / 100, 2) }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ number_format(($item->price_cents * $item->quantity) / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="admin-order-totals">
                    <div class="summary-row">
                        <span>Subtotal</span><strong>${{ number_format($order->subtotal_cents / 100, 2) }}</strong></div>
                    <div class="summary-row">
                        <span>Delivery</span><strong>${{ number_format($order->delivery_cents / 100, 2) }}</strong></div>
                    <div class="summary-row total">
                        <strong>Total</strong><strong>${{ number_format($order->total_cents / 100, 2) }}</strong></div>
                </div>
            </section>
            <section class="admin-panel form-panel">
                <h2>Customer & delivery</h2>
                <div class="customer-details">
                    <div><small>CUSTOMER</small><strong>{{ $order->customer_name }}</strong>@if($order->phone)<a href="tel:{{ $order->phone }}">{{ $order->phone }}</a>@else<small>Phone not provided · WhatsApp checkout</small>@endif
                        @if ($order->email)
                            <a href="mailto:{{ $order->email }}">{{ $order->email }}</a>
                        @endif
                    </div>
                    <div><small>DELIVER TO</small><strong>{{ $order->delivery_area ?? 'Area to be confirmed' }}</strong>
                        <p>{{ $order->address }}</p>
                    </div>
                </div>
                @if ($order->notes)
                    <div class="order-notes"><small>CUSTOMER NOTE</small>
                        <p>{{ $order->notes }}</p>
                    </div>
                @endif
            </section>
        </div>
        <aside class="admin-form-secondary">
            <form method="post" action="{{ route('admin.orders.update', $order) }}" class="admin-panel form-panel">@csrf
                @method('PATCH')<h2>Order status</h2>
                <div class="field-grid single"><label class="field">Order status<select name="status">
                            @foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </label><label class="field">Assigned team member<select name="assigned_to">
                            @if (auth()->user()->role !== 'orders')
                                <option value="">Unassigned</option>
                                @foreach ($admins as $admin)
                                    <option value="{{ $admin->id }}" @selected($order->assigned_to === $admin->id)>{{ $admin->name }}
                                    </option>
                            @endforeach @else<option value="{{ auth()->id() }}">
                                    {{ auth()->user()->name }} (claim this order)</option>
                            @endif
                        </select>
                    </label>
                    <p class="form-hint">Move forward: Pending → Confirmed → Processing → Shipped → Delivered. Cancellation
                        before shipping restores tracked stock once.</p><button class="button button-dark full-width">Update
                        order <x-icon name="check" /></button>
                </div>
            </form>

            @if(auth()->user()->role === 'owner')
                <form method="post" action="{{ route('admin.orders.destroy', $order) }}" class="admin-panel form-panel order-delete-panel" data-confirm="Delete {{ $order->number }} from the order list? Unshipped orders will be cancelled and their tracked stock returned. Sales history stays in Analytics.">@csrf @method('DELETE')
                    <h2>Delete order</h2>
                    <p class="form-hint">Removes this order from the list. Unshipped orders are cancelled and tracked stock is returned once. Sales history stays in Analytics.</p>
                    <button class="text-link order-delete"><x-icon name="trash" /> Delete order</button>
                </form>
            @endif

        </aside>
</div>@endsection
