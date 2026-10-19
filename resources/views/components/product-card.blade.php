@props(['product'])
@php
$firstColor = $product->colors->first();
$initialImage = ($firstColor ? $product->images->firstWhere('product_color_id', $firstColor->id) : null) ?? $product->images->firstWhere('product_color_id', null) ?? $product->images->first();
@endphp
<article class="product-card" data-product-id="{{ $product->id }}">
    <div class="product-visual">
        <a href="{{ route('products.show', $product) }}" class="product-image-link"
            aria-label="View {{ $product->name }}"><img
                src="{{ $initialImage?->thumbnail_url ?? $product->image_url }}"
                srcset="{{ $initialImage?->thumbnail_url ?? $product->image_url }} 480w, {{ $initialImage?->url ?? $product->image_url }} 1600w"
                sizes="(max-width: 600px) 45vw, (max-width: 1200px) 30vw, 20vw"
                alt="{{ $product->images->first()?->alt ?? $product->name }}" width="420" height="420"
                loading="lazy" decoding="async"></a>
        @if ($product->badge)
            <span
                class="product-badge {{ $product->compare_price_cents > $product->price_cents ? 'sale' : '' }}">{{ $product->badge }}</span>
        @endif
        <form method="post" action="{{ route('saved.toggle', $product) }}" class="save-form" data-ajax-save data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}">@csrf<button
                class="icon-button save-button {{ in_array($product->id, session('saved', [])) ? 'is-saved' : '' }}"
                aria-pressed="{{ in_array($product->id, session('saved', [])) ? 'true' : 'false' }}" aria-label="{{ in_array($product->id, session('saved', [])) ? 'Unsave' : 'Save' }} {{ $product->name }}"><x-icon
                    name="heart" /></button></form>

        <a class="quick-view" href="{{ route('products.show', $product) }}">View product <x-icon name="arrow" /></a>
    </div>
    <div class="product-card-body">
    <div class="product-meta"><span>{{ $product->category->name }}</span><x-stock :product="$product" /></div>
    <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
    <div class="card-colors" aria-label="Colors for {{ $product->name }}">
        @foreach ($product->colors as $color)
            @php($colorImage = $product->images->firstWhere('product_color_id', $color->id) ?? $product->images->firstWhere('product_color_id', null) ?? $product->images->first())
            <button type="button" data-card-color data-color-id="{{ $color->id }}" data-color-image="{{ $colorImage?->thumbnail_url ?? $product->image_url }}" data-color-srcset="{{ $colorImage?->thumbnail_url ?? $product->image_url }} 480w, {{ $colorImage?->url ?? $product->image_url }} 1600w" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="Select {{ $color->name }} for {{ $product->name }}" title="{{ $color->name }}"><svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="{{ $color->hex ?? '#ffffff' }}" stroke="currentColor" stroke-width="1" />@unless($color->hex)<path d="m5 19 14-14" stroke="currentColor" />@endunless</svg></button>
        @endforeach
    </div>

    <div class="product-purchase-row">
    <div class="product-bottom">
        <div class="price">${{ number_format($product->price_cents / 100, 2) }} @if ($product->compare_price_cents > $product->price_cents)
                <del>${{ number_format($product->compare_price_cents / 100, 2) }}</del>
            @endif
        </div>
    </div>
        <form method="post" action="{{ route('cart.add', $product) }}" class="product-card-action" data-ajax-cart>@csrf
            <input type="hidden" name="quantity" value="1">
            @if ($product->colors->isNotEmpty())<input type="hidden" name="color_id" value="{{ $product->colors->first()->id }}">@endif
            <button class="icon-button card-cart-button" aria-label="Add {{ $product->name }} to cart" title="Add to cart" data-stock-button="{{ $product->id }}" data-available-label="Add to cart" @disabled($product->stock_status === 'out')>
                <x-icon name="plus" /><span class="sr-only" data-stock-button-label>{{ $product->stock_status === 'out' ? 'Out of stock' : 'Add to cart' }}</span>
            </button>
        </form>
    </div>

    </div>
</article>
