@php
    $money = fn($cents) => '$'.number_format($cents / 100, 2);
@endphp
<div class="analytics-metrics">
    <article class="analytics-metric"><span>Delivered sales</span><strong data-metric="sales">{{ $money($summary['sales']) }}</strong><small>Product revenue · excludes delivery fees</small></article>
    <article class="analytics-metric"><span>Total orders</span><strong data-metric="orders">{{ number_format($summary['orders']) }}</strong><small>{{ number_format($units->ordered) }} units ordered · excludes cancelled units</small></article>
    <article class="analytics-metric"><span>Completed</span><strong>{{ number_format($summary['completed']) }}</strong><small>{{ number_format($units->delivered) }} units delivered</small></article>
    <article class="analytics-metric"><span>Still in progress</span><strong>{{ number_format($summary['open']) }}</strong><small>{{ $money($summary['outstanding']) }} awaiting delivery</small></article>
    <article class="analytics-metric"><span>Cancelled</span><strong>{{ number_format($statuses['cancelled']['count']) }}</strong><small>{{ $summary['cancellation_rate'] }}% of orders · {{ $money($summary['cancelled_value']) }} order value</small></article>
    <article class="analytics-metric"><span>Average delivered order</span><strong>{{ $money($summary['average']) }}</strong><small>Includes delivery · USD</small></article>
</div>
<div class="analytics-two-col analytics-primary">
    <section class="analytics-panel analytics-chart-panel">
        <div class="analytics-panel-heading"><div><h2>Sales over time</h2><p>Delivered product sales by {{ $bucket }} · USD</p></div><span class="analytics-total">{{ $money($summary['sales']) }}</span></div>
        @if($summary['orders'])
            @php
                $maxSales = max(1, collect($chart)->max('sales'));
                $chartWidth = 720;
                $step = 640 / max(1, count($chart));
            @endphp
            <svg class="analytics-sales-chart" viewBox="0 0 720 225" role="img" aria-labelledby="analytics-chart-title analytics-chart-description">
                <title id="analytics-chart-title">Delivered product sales from {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</title>
                <desc id="analytics-chart-description">{{ $money($summary['sales']) }} in delivered product sales. Each bar includes its order count. Cancelled and undelivered orders are excluded from revenue.</desc>
                @foreach([0, .5, 1] as $fraction)
                    <line class="analytics-grid-line" x1="65" x2="705" y1="{{ 178 - $fraction * 140 }}" y2="{{ 178 - $fraction * 140 }}" />
                    <text class="analytics-axis" x="57" y="{{ 182 - $fraction * 140 }}" text-anchor="end">{{ $money($maxSales * $fraction) }}</text>
                @endforeach
                @foreach($chart as $point)
                    <rect class="analytics-sales-bar" x="{{ 65 + $loop->index * $step + $step * .15 }}" y="{{ 178 - $point['sales'] / $maxSales * 140 }}" width="{{ $step * .7 }}" height="{{ $point['sales'] / $maxSales * 140 }}" rx="3" tabindex="0" aria-label="{{ $point['label'] }}: {{ $money($point['sales']) }}, {{ $point['orders'] }} orders"><title>{{ $point['label'] }} · {{ $money($point['sales']) }} · {{ $point['orders'] }} orders</title></rect>
                    @if($loop->first || $loop->last || $loop->index === (int) floor(count($chart) / 2))
                        <text class="analytics-axis" x="{{ 65 + ($loop->index + .5) * $step }}" y="205" text-anchor="{{ $loop->first ? 'start' : ($loop->last ? 'end' : 'middle') }}">{{ $point['label'] }}</text>
                    @endif
                @endforeach
            </svg>
            <div class="analytics-chart-note"><span>Total delivered value <strong>{{ $money($summary['collected']) }}</strong></span><span>Of which delivery <strong>{{ $money($summary['collected'] - $summary['sales']) }}</strong></span></div>
        @else
            <div class="analytics-empty"><x-icon name="chart" /><h3>No orders in this period</h3><p>Try another date range. New orders will appear here automatically.</p></div>
        @endif
    </section>
    <section class="analytics-panel">
        <div class="analytics-panel-heading"><div><h2>Order breakdown</h2><p>All {{ number_format($summary['orders']) }} orders in this period</p></div></div>
        <div class="analytics-status-list">
            @foreach($statuses as $status => $value)
                <a class="analytics-status-row" href="{{ route('admin.orders.index', ['status' => $status]) }}"><span>{{ $status === 'delivered' ? 'Delivered / completed' : ucfirst($status) }}</span><strong>{{ number_format($value['count']) }}</strong><svg viewBox="0 0 100 4" preserveAspectRatio="none" aria-hidden="true"><rect class="analytics-bar-track" width="100" height="4" rx="2" /><rect class="analytics-bar-fill" width="{{ $summary['orders'] ? $value['count'] * 100 / $summary['orders'] : 0 }}" height="4" rx="2" /></svg></a>
            @endforeach
        </div>
        <p class="analytics-footnote">{{ number_format($summary['deleted']) }} deleted from the order list. Their history stays in these totals. Status links open the current order queue.</p>
    </section>
</div>
<div class="analytics-two-col">
    <section class="analytics-panel">
        <div class="analytics-panel-heading"><div><h2>Most ordered products</h2><p>Ranked by units · cancelled orders excluded</p></div><span>{{ number_format($units->ordered) }} units</span></div>
        <div class="analytics-ranking">
            @forelse($topProducts as $product)
                <div class="analytics-rank-row"><span class="analytics-rank-number">{{ $loop->iteration }}</span><div class="analytics-rank-name">@if($product->slug)<a href="{{ route('admin.products.edit', $product->slug) }}">{{ $product->name }}</a>@else<strong>{{ $product->name }}</strong>@endif<small>{{ $product->orders }} orders · {{ $product->sku }}</small></div><div class="analytics-rank-value"><strong>{{ number_format($product->units) }} <small>units</small></strong><small>{{ $money($product->sales) }} delivered</small></div></div>
            @empty<div class="analytics-empty compact"><p>No non-cancelled product orders in this period.</p></div>@endforelse
        </div>
    </section>
    <section class="analytics-panel">
        <div class="analytics-panel-heading"><div><h2>Most saved products</h2><p>All-time browser saves · independent of order dates</p></div><span>{{ number_format($favoriteTotals->saved) }} saved</span></div>
        <div class="analytics-ranking">
            @forelse($favorites as $product)
                <div class="analytics-rank-row"><span class="analytics-rank-number">{{ $loop->iteration }}</span><div class="analytics-rank-name"><a href="{{ route('admin.products.edit', $product->slug) }}">{{ $product->name }}</a><small>{{ $product->sku }} · saved {{ number_format($product->additions) }} times</small></div><div class="analytics-rank-value"><strong>{{ number_format($product->saved) }}</strong><small>browser saves</small></div></div>
            @empty<div class="analytics-empty compact"><p>No saved-product data yet. New saves across the store will appear here.</p></div>@endforelse
        </div>
        <p class="analytics-footnote">Recording starts with this update; earlier browser-only saves cannot be reconstructed. Counts reflect last-known choices by anonymous browsers, not identified people. Clearing a shopping session starts a new browser count.</p>
    </section>
</div>
<section class="analytics-panel analytics-inventory">
    <div class="analytics-panel-heading"><div><h2>Inventory right now</h2><p>Current stock across the whole catalog · independent of order dates</p></div><a class="text-link" href="{{ route('admin.products.index') }}">Manage products</a></div>
    <div class="analytics-inventory-metrics">
        <div><strong>{{ number_format($inventory['products']) }}</strong><span>Products</span><small>{{ $inventory['visible'] }} visible · {{ $inventory['hidden'] }} hidden</small></div>
        <div><strong>{{ number_format($inventory['units']) }}</strong><span>Tracked units on hand</span><small>{{ $inventory['open_products'] }} products use open quantity</small></div>
        <div><strong>{{ number_format($inventory['low']) }}</strong><span>Low stock</span><small>At or below their threshold</small></div>
        <div><strong>{{ number_format($inventory['out']) }}</strong><span>Out of stock</span><small>Tracked and manually managed products</small></div>
        <div><strong>{{ $money($inventory['retail_value']) }}</strong><span>Tracked stock value</span><small>At current selling prices · not profit</small></div>
        <div><strong>{{ $inventory['categories'] }} <small>/ {{ $inventory['subcategories'] }}</small></strong><span>Categories / subcategories</span><small>Includes hidden categories</small></div>
    </div>
    <div class="analytics-restock-heading"><h3>Needs attention</h3><span>{{ $inventory['low'] + $inventory['out'] }} products · showing up to 20</span></div>
    <div class="analytics-restock-list">
        @forelse($attention as $product)
            <div class="analytics-restock-row"><div class="analytics-restock-name"><a href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a><small>{{ $product->category->name }} · {{ $product->sku }}{{ ! $product->is_active ? ' · Hidden product' : '' }}</small></div><span class="analytics-stock-state">{{ $product->stock_status === 'out' ? 'Out of stock' : 'Low stock' }}</span><div class="analytics-restock-detail">@if($product->stock_mode === 'tracked')<strong>{{ $product->quantity }} left</strong><small>Add {{ max(0, $product->low_stock_threshold + 1 - $product->quantity) }} to clear the alert · threshold {{ $product->low_stock_threshold }}</small>@else<strong>Open quantity</strong><small>Review manual availability</small>@endif</div><a class="text-link" href="{{ route('admin.products.edit', $product) }}">Update stock</a></div>
        @empty<div class="analytics-empty compact"><p>Stock looks good. No products are below their thresholds or marked unavailable.</p></div>@endforelse
    </div>
</section>
<div class="analytics-two-col">
    <section class="analytics-panel"><div class="analytics-panel-heading"><div><h2>Demand by category</h2><p>Non-cancelled units · current product categories</p></div></div><div class="analytics-ranking">@forelse($categories as $category)<div class="analytics-simple-row"><strong>{{ $category->name }}</strong><span>{{ number_format($category->units) }} units<small>{{ $money($category->sales) }} delivered</small></span></div>@empty<div class="analytics-empty compact"><p>No category orders in this period.</p></div>@endforelse</div></section>
    <section class="analytics-panel"><div class="analytics-panel-heading"><div><h2>Delivery areas</h2><p>Non-cancelled orders in the selected period</p></div></div><div class="analytics-ranking">@forelse($areas as $area)<div class="analytics-simple-row"><strong>{{ $area->name }}</strong><span>{{ number_format($area->orders) }} orders<small>{{ $money($area->sales) }} delivered</small></span></div>@empty<div class="analytics-empty compact"><p>No delivery-area data in this period.</p></div>@endforelse</div></section>
</div>
<p class="analytics-footnote analytics-method">Delivered sales are recognised by the order’s placement date and current delivered status; this is a sales view, not a cash ledger. Delivery fees are shown separately. Cancelled orders never count as sales. Deleted orders remain in reporting; deleting an unshipped order cancels it. No cost prices are stored, so this report does not calculate profit.</p>
