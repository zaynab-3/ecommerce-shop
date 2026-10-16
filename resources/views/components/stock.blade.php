@props(['product'])
<span class="stock stock-{{ $product->stock_status }}" data-stock-id="{{ $product->id }}"><span
        class="stock-dot"></span><span
        data-stock-label>{{ ['available' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'][$product->stock_status] }}</span></span>
