@extends('layouts.store')
@section('content')
    @if (request()->routeIs('home') && !request()->hasAny(['q', 'category', 'saved', 'sort']))
        <section class="container hero-section">
            <div class="hero-main">
                <div class="hero-copy"><span class="eyebrow"><span class="tiny-star">✳︎</span> LESS ORDINARY. MORE YOU.</span>
                    <h1>Your everyday,<br>upgraded<span>.</span></h1>
                    <p>From little essentials to your next big find.<br>Discover things you’ll love, all in one place.</p><a
                        class="button button-dark" href="{{ route('shop') }}">Explore the collection <x-icon
                            name="arrow" /></a>
                    <div class="hero-bottom">
                        <div class="hero-line"></div><span>GOOD FINDS. GREAT PRICES. ENDLESS POSSIBILITIES.</span>
                    </div>
                </div>
                <div class="hero-art" aria-hidden="true">
                    <div class="orbit orbit-one"></div>
                    <div class="orbit orbit-two"></div><span class="floating-label"><span>✦</span> Made for your
                        everyday</span><img class="hero-bag" src="{{ asset('images/hero-bag.webp') }}" alt=""
                        fetchpriority="high" width="420" height="520">
                    <div class="hero-sticker">A little<br>different.<br><strong>A lot you.</strong><svg width="35"
                            height="35" viewBox="0 0 24 24">
                            <path d="M3 21 21 3M3 3h18v18" fill="none" stroke="currentColor" stroke-width="1.3" />
                        </svg></div>
                </div>
            </div>
            <a class="hero-side"
                href="{{ route('shop', ['category' => $categories->firstWhere('name', 'Electronics')?->slug]) }}"><span
                    class="eyebrow">SMALL DETAILS. BIG DIFFERENCE.</span>
                <h2>Meet your<br>new essentials.</h2>
                <p>Tech that fits your life.</p>
                <div class="hero-monitor"><img src="{{ asset('images/hero-monitor.webp') }}" width="360" height="280"
                        alt="Curved monitor for your workspace" fetchpriority="high"></div><span class="side-link">Explore
                    tech <span class="round-arrow"><x-icon name="arrow" /></span></span>
            </a>
        </section>
        <section class="container categories-section">
            <div class="section-heading">
                <h2>A world of good finds<span>.</span></h2><a href="{{ route('shop') }}" class="text-link">Browse
                    everything <x-icon name="arrow" /></a>
            </div>
            <div class="category-grid">
                @foreach ($categories as $category)
                    <a class="category-tile" href="{{ route('shop', ['category' => $category->slug]) }}"><span
                            class="category-icon category-{{ $loop->index % 6 }}"><x-icon :name="$category->icon" width="29"
                                height="29" /></span><span>{{ $category->name }}</span><x-icon name="chevron"
                            width="14" height="14" /></a>
                @endforeach
            </div>
        </section>
    @endif
    <section class="container catalog-section" id="collection">
        @if (request()->routeIs('home') && !request()->hasAny(['q', 'category', 'saved', 'sort']))
            <div class="section-heading product-section-heading">
                <div><span class="eyebrow muted">YOUR NEXT FAVORITES</span>
                    <h2>Good finds, picked for you<span>.</span></h2>
                </div><a href="{{ route('shop') }}" class="text-link">View all products <x-icon name="arrow" /></a>
        </div>@else<div class="breadcrumb"><a href="{{ route('home') }}">Home</a><x-icon name="chevron" width="13"
                    height="13" /><span>{{ request('saved') ? 'Saved items' : $selected?->name ?? 'Shop all' }}</span></div>
            <div class="collection-title">
                <div><span class="eyebrow muted">A LITTLE SOMETHING FOR EVERYONE</span>
                    <h1>{{ request('saved') ? 'Your saved finds' : (request('q') ? 'Results for “' . request('q') . '”' : $selected?->name ?? 'The whole collection') }}<span>.</span>
                    </h1>
                    <p>{{ request('saved') ? 'Your favorites, all in one place.' : 'Thoughtful essentials and unexpected favorites. Find your thing.' }}
                    </p>
                </div><span class="collection-count">{{ $products->total() }} good finds</span>
            </div>
        @endif
        <div class="catalog-layout">
            <aside class="catalog-sidebar">
                <div class="sidebar-heading"><x-icon name="grid" /><strong>Shop by category</strong></div><a
                    class="sidebar-category {{ !request('category') ? 'selected' : '' }}"
                    href="{{ route('shop', request()->except(['category', 'page'])) }}">All products <span>↗</span></a>
                @foreach ($categories as $category)
                    <a class="sidebar-category {{ request('category') === $category->slug ? 'selected' : '' }}"
                        href="{{ route('shop', [...request()->except('page'), 'category' => $category->slug]) }}"><x-icon
                            :name="$category->icon" width="18" height="18" />{{ $category->name }}<x-icon name="chevron"
                            width="13" height="13" /></a>
                    @if ($selected && ($selected->id === $category->id || $selected->parent_id === $category->id))
                        @foreach ($category->children as $child)
                            <a class="sidebar-child {{ $selected->id === $child->id ? 'selected' : '' }}"
                                href="{{ route('shop', [...request()->except('page'), 'category' => $child->slug]) }}">{{ $child->name }}</a>
                        @endforeach
                    @endif
                @endforeach
                <div class="sidebar-help">
                    <x-icon name="sparkles" />
                    <h3>A little inspiration?</h3>
                    <p>Start with our newest finds. You never know what you’ll discover.</p><a
                        href="{{ route('shop', ['sort' => 'newest']) }}">See what's new <x-icon name="arrow"
                            width="16" /></a>
                </div>
            </aside>
            <div class="catalog-main">
                <form class="catalog-toolbar" method="get" action="{{ route('shop') }}">
                    @foreach (request()->except(['sort', 'available', 'page']) as $k => $v)
                        @if (is_scalar($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <span class="result-count">
                        <strong>{{ $products->total() }}</strong> products</span><label class="checkbox-label"><input
                            type="checkbox" name="available" value="1" @checked(request('available')) data-submit>In
                        stock only</label><label class="sort-select"><span>Sort by:</span><select name="sort"
                            aria-label="Sort products" data-submit>
                            <option value="featured" @selected(request('sort', 'featured') === 'featured')>Recommended</option>
                            <option value="newest" @selected(request('sort') === 'newest')>Newest first</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                        </select></label><noscript><button>Apply filters</button></noscript>
                </form>
                <div class="product-grid">
                    @forelse($products as $product)
                    <x-product-card :product="$product" />@empty<div class="empty-state"><x-icon name="search"
                                width="45" height="45" />
                            <h2>{{ request('saved') ? 'Your favorites are waiting to be found.' : 'No finds here just yet.' }}
                            </h2>
                            <p>{{ request('saved') ? 'Tap the heart on anything you love to keep it here.' : 'Try a different search or explore the full collection.' }}
                            </p><a class="button button-dark" href="{{ route('shop') }}">Explore all products <x-icon
                                    name="arrow" /></a>
                        </div>
                    @endforelse
                </div>
                @if ($products->hasPages())
                    <nav class="pagination" aria-label="Product pages">
                        @if ($products->onFirstPage())
                        <span>← Previous</span>@else<a href="{{ $products->previousPageUrl() }}">← Previous</a>
                        @endif
                        <span>
                            Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
                        @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}">Next →</a>@else<span>Next →</span>
                        @endif
                    </nav>
                @endif
            </div>
        </div>
    </section>
    <section class="container discovery-banner">
        <div><span class="eyebrow">THE BEAUTY OF A GOOD FIND</span>
            <h2>Life’s little upgrades<br>start right here<span>.</span></h2>
            <p>A fresh perspective. An everyday essential. Something just for you.</p>
        </div><a class="button button-dark" href="{{ route('shop', ['sort' => 'newest']) }}">Discover something new <x-icon
                name="arrow" /></a><svg class="banner-star" aria-hidden="true" viewBox="0 0 100 100" width="230"
            height="230">
            <g stroke="#dfe3ce" stroke-width="8" stroke-linecap="round">
                <path d="M50 10v80M10 50h80M22 22l56 56M22 78l56-56" />
            </g>
        </svg>
    </section>
@endsection
