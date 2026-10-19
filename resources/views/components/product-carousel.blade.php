@props(['products', 'title' => 'Top picks', 'href'])
@if ($products->isNotEmpty())
<section class="container product-carousel" data-carousel aria-label="{{ $title }}">
    <div class="section-heading retail-section-heading">
        <h2>{{ $title }}</h2>
        <div class="carousel-actions">
            <a class="carousel-shop-all" href="{{ $href }}">Shop all <x-icon name="arrow" width="18" /></a>
            <button type="button" class="carousel-arrow" data-carousel-prev aria-label="Previous products" disabled><x-icon name="chevron" width="18" /></button>
            <button type="button" class="carousel-arrow" data-carousel-next aria-label="Next products" disabled><x-icon name="chevron" width="18" /></button>
        </div>
    </div>
    <div class="carousel-track" data-carousel-track tabindex="0" role="region" aria-label="{{ $title }} products, scroll for more">
        @foreach ($products as $product)<x-product-card :product="$product" />@endforeach
    </div>
</section>
@endif
