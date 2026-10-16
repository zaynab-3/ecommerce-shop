@extends('layouts.admin')
@section('title', 'Overview')
@section('content')<x-admin-heading title="A good day to grow" description="Here’s what’s happening in your store."
        eyebrow="WELCOME BACK">
        <a class="button button-light" href="{{ route('home') }}" target="_blank" rel="noopener">Visit your store <x-icon
                name="arrow" /></a></x-admin-heading>
    <div class="stat-grid">
        <div class="stat-card"><span>Today's order value</span><x-icon
                name="bag" /><strong>${{ number_format($sales / 100, 2) }}</strong><small>Excludes cancelled orders</small>
        </div>
        <div class="stat-card"><span>Waiting for a little attention</span><x-icon
                name="box" /><strong>{{ $pending }}</strong><small>Pending orders</small></div>
        <div class="stat-card"><span>In your collection</span><x-icon
                name="grid" /><strong>{{ $productCount }}</strong><small>Total products</small></div>
        <div class="stat-card"><span>Time for a restock</span><x-icon
                name="refresh" /><strong>{{ $lowStock }}</strong><small>Tracked items at or below threshold</small>
        </div>
    </div>
    <section class="admin-panel">
        <div class="panel-heading">
            <div>
                <h2>Fresh off the checkout</h2>
                <p>Your latest orders, ready for their next step.</p>
            </div><a class="text-link" href="{{ route('admin.orders.index') }}">All orders <x-icon name="arrow" /></a>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
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
                                    href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a><small>{{ $order->created_at->diffForHumans() }}</small>
                            </td>
                            <td>{{ $order->customer_name }}</td>
                            <td><span
                                    class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td>${{ number_format($order->total_cents / 100, 2) }}</td>
                            <td>{{ $order->assignee?->name ?? 'Unassigned' }}</td>
                            <td><a class="icon-button" aria-label="View order {{ $order->number }}"
                                    href="{{ route('admin.orders.show', $order) }}"><x-icon name="arrow"
                                        width="17" /></a></td>
                    </tr>@empty<tr>
                            <td colspan="6">
                                <div class="table-empty"><x-icon name="bag" />
                                    <h3>Your next chapter starts with an order.</h3>
                                    <p>New orders will appear here as customers check out.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if (auth()->user()->canManageCatalog())
        <div class="admin-shortcuts"><a href="{{ route('admin.products.create') }}"><x-icon name="plus" />
                <div>
                    <h3>Make room for something new</h3>
                    <p>Add a product to your collection.</p>
                </div><x-icon name="arrow" />
            </a><a href="{{ route('admin.delivery.index') }}"><x-icon name="truck" />
                <div>
                    <h3>A little closer to your customers</h3>
                    <p>Manage areas and delivery prices.</p>
                </div><x-icon name="arrow" />
            </a></div>
    @endif
@endsection
