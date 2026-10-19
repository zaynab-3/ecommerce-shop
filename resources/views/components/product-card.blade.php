@props(['product'])
<article class="product-card" data-product-id="{{ $product->id }}">
    <div class="product-visual">
        <a href="{{ route('products.show', $product) }}" class="product-image-link"
            aria-label="View {{ $product->name }}"><img
                src="{{ $product->images->first()?->thumbnail_url ?? $product->image_url }}"
                srcset="{{ $product->images->first()?->thumbnail_url ?? $product->image_url }} 480w, {{ $product->image_url }} 1600w"
                sizes="(max-width: 600px) 45vw, (max-width: 1200px) 30vw, 20vw"
                alt="{{ $product->images->first()?->alt ?? $product->name }}" width="420" height="420"
                loading="lazy" decoding="async"></a>
        @if ($product->badge)
            <span
                class="product-badge {{ $product->compare_price_cents > $product->price_cents ? 'sale' : '' }}">{{ $product->badge }}</span>
        @endif
        <form method="post" action="{{ route('saved.toggle', $product) }}" class="save-form">@csrf<button
                class="icon-button save-button {{ in_array($product->id, session('saved', [])) ? 'is-saved' : '' }}"
                aria-label="{{ in_array($product->id, session('saved', [])) ? 'Unsave' : 'Save' }} {{ $product->name }}"><x-icon
                    name="heart" /></button></form>
        <a class="quick-view" href="{{ route('products.show', $product) }}">View product <x-icon name="arrow" /></a>
    </div>
    <div class="product-card-body">
    <div class="product-meta"><span>{{ $product->category->name }}</span><x-stock :product="$product" /></div>
    <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
    <div class="product-bottom">
        <div class="price">${{ number_format($product->price_cents / 100, 2) }} @if ($product->compare_price_cents > $product->price_cents)
                <del>${{ number_format($product->compare_price_cents / 100, 2) }}</del>
            @endif
        </div>
        <div class="mini-colors" aria-label="{{ $product->colors->count() }} colors available">
            @foreach ($product->colors->take(4) as $color)
                <svg width="13" height="13" viewBox="0 0 16 16" role="img" aria-label="{{ $color->name }}">
                    <circle cx="8" cy="8" r="6" fill="{{ $color->hex ?? '#eeeeee' }}" stroke="#c2c2c2" />
                </svg>
            @endforeach
        </div>
    </div>
    @if ($product->colors->count() > 1)
        <a class="product-card-link" href="{{ route('products.show', $product) }}">Choose options <x-icon name="arrow" width="18" /></a>
    @else
        <form method="post" action="{{ route('cart.add', $product) }}" class="product-card-action">@csrf
            <input type="hidden" name="quantity" value="1">
            @if ($product->colors->isNotEmpty())<input type="hidden" name="color_id" value="{{ $product->colors->first()->id }}">@endif
            <button class="product-card-link" data-stock-button="{{ $product->id }}" data-available-label="Add to cart" @disabled($product->stock_status === 'out')>
                <span aria-hidden="true">+</span><span data-stock-button-label>{{ $product->stock_status === 'out' ? 'Out of stock' : 'Add to cart' }}</span>
            </button>
        </form>
    @endif
    </div>
</article>
