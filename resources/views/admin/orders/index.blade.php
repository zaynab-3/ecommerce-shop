@extends('layouts.admin')
@section('title', 'Orders')
@section('content')<x-admin-heading title="Good things, on their way"
        description="A clear view of every order, from checkout to doorstep." eyebrow="ORDER DESK" />
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
        <div class="table-scroll">
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
                            <td>{{ $order->customer_name }}<small>{{ $order->phone }}</small></td>
                            <td>{{ $order->delivery_area ?? 'To be confirmed' }}</td>
                            <td><span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td>${{ number_format($order->total_cents / 100, 2) }}</td>
                            <td>{{ $order->assignee?->name ?? 'Unassigned' }}</td>
                            <td><a class="icon-button" href="{{ route('admin.orders.show', $order) }}"
                                    aria-label="Open order {{ $order->number }}"><x-icon name="arrow"
                                        width="17" /></a></td>
                    </tr>@empty<tr>
                            <td colspan="7">
                                <div class="table-empty"><x-icon name="bag" />
                                    <h3>No orders here just yet.</h3>
                                    <p>New orders will appear here after checkout.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</section><x-admin-pagination :items="$orders" />@endsection
