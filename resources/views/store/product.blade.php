@extends('layouts.store')
@section('title', $product->name)
@section('content')
    <div class="container product-page">
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><x-icon name="chevron" width="13" /><a
                href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a><x-icon
                name="chevron" width="13" /><span>{{ $product->name }}</span></div>
        <div class="product-detail" data-product-detail>
            <div class="product-gallery">
                <div class="main-product-image">
                    @if ($product->badge)
                        <span class="product-badge">{{ $product->badge }}</span>
                    @endif
                    <img id="detail-image" src="{{ $product->image_url }}" alt="{{ $product->name }}" width="800"
                        height="800" fetchpriority="high">
                </div>
                <div class="gallery-thumbs">
                    @foreach ($product->images as $image)
                        <button type="button" class="gallery-thumb {{ $loop->first ? 'selected' : '' }}"
                            data-image="{{ $image->url }}" data-alt="{{ $image->alt }}"
                            data-image-color="{{ $image->product_color_id }}"
                            aria-label="View image {{ $loop->iteration }} of {{ $product->name }}"><img
                                src="{{ $image->thumbnail_url }}" alt="{{ $image->alt }}" width="90" height="90"
                                loading="lazy"></button>
                    @endforeach
                </div>
            </div>
            <div class="product-info"><span class="eyebrow muted">{{ $product->category->name }}</span>
                <h1>{{ $product->name }}</h1>
                <div class="detail-price"><span>${{ number_format($product->price_cents / 100, 2) }}</span>
                    @if ($product->compare_price_cents > $product->price_cents)
                        <del>${{ number_format($product->compare_price_cents / 100, 2) }}</del><span class="saving-pill">Save
                            {{ round((1 - $product->price_cents / $product->compare_price_cents) * 100) }}%</span>
                    @endif
                </div>
                <x-stock :product="$product" />
                <form action="{{ route('cart.add', $product) }}" method="post" class="add-to-bag-form" data-ajax-cart>@csrf
                    @if ($product->colors->isNotEmpty())
                        <fieldset class="color-options">
                            <legend>Color: <strong
                                    id="selected-color">{{ old('color_id') ? $product->colors->firstWhere('id', (int) old('color_id'))?->name : $product->colors->first()->name }}</strong>
                            </legend>
                            <div class="color-swatches">
                                @foreach ($product->colors as $color)
                                    <label class="color-option"><input type="radio" name="color_id"
                                            value="{{ $color->id }}" @checked((int) old('color_id', $product->colors->first()->id) === $color->id)
                                            data-color-name="{{ $color->name }}"
                                            data-color-image="{{ $product->images->firstWhere('product_color_id', $color->id)?->url ?? ($product->images->firstWhere('product_color_id', null)?->url ?? $product->image_url) }}"
                                            required><span><svg width="28" height="28" viewBox="0 0 32 32"
                                                aria-hidden="true">
                                                <circle cx="16" cy="16" r="14"
                                                    fill="{{ $color->hex ?? '#f0f0f0' }}" stroke="#b8b8b8" />
                                                @unless ($color->hex)
                                                    <path d="m6 26 20-20" stroke="#777" />
                                                @endunless
                                            </svg>
                                            <span>{{ $color->name }}</span></span></label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    <div class="buy-row">
                        <span class="quantity-label">Quantity</span>
                        <div class="quantity-control"><button type="button" data-quantity="-1"
                                aria-label="Decrease quantity">−</button><label class="sr-only"
                                for="quantity">Quantity</label><input id="quantity" name="quantity" type="number"
                                value="1" min="1"
                                max="{{ $product->stock_mode === 'tracked' ? min(99, max(1, $product->quantity)) : 99 }}"
                                required><button type="button" data-quantity="1" aria-label="Increase quantity">+</button>
                        </div><button class="button button-dark add-bag" data-stock-button="{{ $product->id }}" data-available-label="Add to cart"
                            @disabled($product->stock_status === 'out')><span aria-hidden="true">+</span><span data-stock-button-label>{{ $product->stock_status === 'out' ? 'Out of stock' : 'Add to cart' }}</span></button>
                        <button class="button buy-now" name="buy_now" value="1" data-stock-button="{{ $product->id }}" data-available-label="Buy it now" @disabled($product->stock_status === 'out')><span data-stock-button-label>{{ $product->stock_status === 'out' ? 'Out of stock' : 'Buy it now' }}</span></button>
                    </div>
                </form>
                @if ($whatsappUrl)
                    <a class="button product-whatsapp" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"><x-icon name="phone" /><span>Ask about this product</span></a>
                @endif
                <form method="post" action="{{ route('saved.toggle', $product) }}" data-ajax-save data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}">@csrf<button
                        class="save-detail text-link {{ in_array($product->id, session('saved', [])) ? 'is-saved' : '' }}" aria-pressed="{{ in_array($product->id, session('saved', [])) ? 'true' : 'false' }}"><x-icon
                            name="heart" /><span data-saved-label>{{ in_array($product->id, session('saved', [])) ? 'Saved to your favorites' : 'Save for later' }}</span></button>
                </form>
                <div class="detail-benefits">
                    <div><x-icon name="truck" /><span><strong>Delivered to your doorstep</strong><small>Choose your
                                delivery area at checkout.</small></span></div>
                    <div><x-icon name="shield" /><span><strong>Easy, secure checkout</strong><small>No account needed. Pay
                                cash on delivery.</small></span></div>
                </div>
                <details class="product-disclosure" open>
                    <summary>The details <span>+</span></summary>
                    <p>{{ $product->description }}</p><small>Product code: {{ $product->sku }}</small>
                </details>
                <details class="product-disclosure">
                    <summary>Delivery & returns <span>+</span></summary>
                    <p>Delivery availability and fees are shown before placing your order. <a
                            href="{{ route('help') }}#returns">Read our delivery and returns information.</a></p>
                </details>
            </div>
        </div>
        <x-product-carousel class="related" :products="$related" title="Related products" :href="route('shop', ['category' => $product->category->slug])" :autoplay="false" link-label="Explore more" />
    </div>
@endsection
