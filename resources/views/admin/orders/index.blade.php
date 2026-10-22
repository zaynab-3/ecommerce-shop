@extends('layouts.admin')
@section('title', 'Orders')
@section('content')<x-admin-heading title="Orders" />
    <section class="admin-panel">
        <form method="get" class="order-filters"><label class="admin-search"><x-icon name="search" /><input name="q"
                    value="{{ request('q') }}" placeholder="Order number or customer" maxlength="100"
                    aria-label="Search orders"></label><select name="status" aria-label="Order status">
                <option value="">All statuses</option>
                @foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <label class="checkbox-field"><input name="mine" type="checkbox" value="1"
                    @checked(request('mine'))>Assigned to me</label><button
                class="button button-light small-button">Filter orders</button>
        </form>
        <div class="table-scroll" tabindex="0" role="region" aria-label="Scrollable table">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Delivery area</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Assigned to</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a class="table-link"
                                    href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a><small>{{ $order->created_at->format('d M Y, H:i') }}</small>
                            </td>
                            <td>{{ $order->customer_name }}<small>{{ $order->phone ?: 'Phone not provided' }}</small></td>
                            <td>{{ $order->delivery_area ?? 'To be confirmed' }}</td>
                            <td><span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td>${{ number_format($order->total_cents / 100, 2) }}</td>
                            <td>{{ $order->assignee?->name ?? 'Unassigned' }}</td>
                            <td><div class="order-row-actions"><a class="icon-button" href="{{ route('admin.orders.show', $order) }}"
                                    aria-label="Open order {{ $order->number }}"><x-icon name="arrow"
                                        width="17" /></a>
                                @if(auth()->user()->role === 'owner')
                                    <form method="post" action="{{ route('admin.orders.destroy', $order) }}" data-confirm="Delete {{ $order->number }} from the order list? Unshipped orders will be cancelled and their tracked stock returned. Sales history stays in Analytics.">@csrf @method('DELETE')
                                        <button class="icon-button order-delete" aria-label="Delete order {{ $order->number }}" title="Delete order"><x-icon name="trash" /></button>
                                    </form>
                                @endif
                            </div></td>
                    </tr>@empty<tr>
                            <td colspan="7">
                                <div class="table-empty"><x-icon name="bag" />
                                    <h3>No orders</h3>
                                    <p>New orders will appear here after checkout.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</section><x-admin-pagination :items="$orders" />@endsection
