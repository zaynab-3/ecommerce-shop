@extends('layouts.admin')
@section('title', 'Products')
@section('content')<x-admin-heading title="Products">
        <a class="button button-dark" href="{{ route('admin.products.create') }}"><x-icon name="plus" /> Add
            product</a></x-admin-heading>
    <section class="admin-panel">
        <div class="panel-heading">
            <div>
                <h2>All products</h2>
                <p>{{ $products->total() }} products</p>
            </div>
            <form class="admin-search" method="get"><x-icon name="search" /><input name="q"
                    value="{{ request('q') }}" placeholder="Find a product" maxlength="100"
                    aria-label="Search products"><button>Search</button></form>
        </div>
        <p class="table-hint">Scroll across for stock and visibility controls.</p>
        <div class="table-scroll" tabindex="0" role="region" aria-label="Scrollable table">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock · quick update</th>
                        <th>Visibility</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><x-reorder type="products" :id="$product->id" /></td>
                            <td><a class="table-product" href="{{ route('admin.products.edit', $product) }}"><img
                                        src="{{ $product->image_url }}" alt="" width="50"
                                        height="50"><span><strong>{{ $product->name }}</strong><small>{{ $product->category->name }}
                                            · {{ $product->sku }}</small></span></a></td>
                            <td>${{ number_format($product->price_cents / 100, 2) }}</td>
                            <td>
                                <div class="stock-quick"><x-stock :product="$product" />
                                    @if ($product->stock_mode === 'tracked')
                                        <div class="quantity-quick">
                                            <strong>{{ $product->quantity }}</strong><span>units</span>
                                            <form method="post" action="{{ route('admin.products.quick', $product) }}">
                                                @csrf @method('PATCH')<input type="number" name="delta" value="1"
                                                    min="-100000" max="100000"
                                                    aria-label="Stock adjustment for {{ $product->name }}" required><button
                                                    title="Add or subtract stock"
                                                    aria-label="Apply stock adjustment for {{ $product->name }}">Apply</button>
                                            </form>
                                    </div><small class="muted">Low at {{ $product->low_stock_threshold }}</small>@else
                                        <form method="post" action="{{ route('admin.products.quick', $product) }}"
                                            class="inline-form">@csrf @method('PATCH')<select name="manual_stock_status"
                                                aria-label="Stock status for {{ $product->name }}">
                                                @foreach (['available' => 'Available', 'low' => 'Low stock', 'out' => 'Out of stock'] as $value => $label)
                                                    <option value="{{ $value }}" @selected($product->manual_stock_status === $value)>
                                                        {{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button>Set</button>
                                        </form><small class="muted">Open quantity</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <form method="post" action="{{ route('admin.products.quick', $product) }}">@csrf
                                    @method('PATCH')<input type="hidden" name="is_active"
                                        value="{{ $product->is_active ? 0 : 1 }}"><button
                                        class="visibility-toggle {{ $product->is_active ? 'on' : '' }}"
                                        aria-label="{{ $product->is_active ? 'Hide' : 'Publish' }} {{ $product->name }}"
                                        aria-pressed="{{ $product->is_active ? 'true' : 'false' }}"><span></span>{{ $product->is_active ? 'Visible' : 'Hidden' }}</button>
                                </form>
                            </td>
                            <td><a class="icon-button" href="{{ route('admin.products.edit', $product) }}"
                                    aria-label="Edit {{ $product->name }}"><x-icon name="edit" width="17" /></a>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="6">
                                <div class="table-empty"><x-icon name="box" />
                                    <h3>No products yet</h3>
                                    <p>Add your first product to get started.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</section><x-admin-pagination :items="$products" />@endsection
