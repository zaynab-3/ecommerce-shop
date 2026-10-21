@props(['products', 'title' => 'Top picks', 'href', 'autoplay' => true, 'linkLabel' => 'Shop'])
@if ($products->isNotEmpty())
<section {{ $attributes->class(['container', 'product-carousel']) }} data-carousel aria-label="{{ $title }}">
    <div class="section-heading retail-section-heading">
        <h2>{{ $title }}</h2>
        <div class="carousel-actions">
            @if($autoplay)<button type="button" class="icon-button carousel-autoplay" data-carousel-autoplay aria-label="Pause automatic scrolling" title="Pause automatic scrolling" hidden><x-icon name="pause" data-carousel-pause /><x-icon name="play" data-carousel-play hidden /></button>@endif
            <a class="{{ $autoplay ? 'carousel-shop-all' : 'text-link' }}" href="{{ $href }}">{{ $linkLabel }}@if($autoplay) <x-icon name="arrow" width="18" />@endif</a>
            <button type="button" class="carousel-arrow" data-carousel-prev aria-label="Previous products" disabled><x-icon name="chevron" width="18" /></button>
            <button type="button" class="carousel-arrow" data-carousel-next aria-label="Next products" disabled><x-icon name="chevron" width="18" /></button>
        </div>
    </div>
    <div class="carousel-track" data-carousel-track tabindex="0" role="region" aria-label="{{ $title }} products, scroll for more">
        @foreach ($products as $product)<x-product-card :product="$product" />@endforeach
    </div>
</section>
@endif
