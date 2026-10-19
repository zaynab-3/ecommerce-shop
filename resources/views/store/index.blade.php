@extends('layouts.store')
@section('title', request('saved') ? 'Saved items' : ($selected?->name ?? (request('q') ? 'Search results' : 'Shop')))
@section('content')
    @if (request()->routeIs('home') && !request()->hasAny(['q', 'category', 'saved', 'sort']))
        <x-product-carousel :products="$products->getCollection()->take(8)" title="Top picks" :href="route('shop')" />
        <section class="container categories-section">
            <div class="section-heading retail-section-heading"><h2>Shop by category</h2><a href="{{ route('shop') }}" class="text-link">View all products <x-icon name="arrow" /></a></div>
            <div class="category-grid">
                @foreach ($categories as $category)
                    <a class="category-tile" href="{{ route('shop', ['category' => $category->slug]) }}"><span class="category-icon category-{{ $loop->index % 6 }}"><x-icon :name="$category->icon" width="28" height="28" /></span><span>{{ $category->name }}</span><x-icon name="chevron" width="16" height="16" /></a>
                @endforeach
            </div>
        </section>
    @endif
    <section class="container catalog-section" id="collection">
        @unless (request()->routeIs('home') && !request()->hasAny(['q', 'category', 'saved', 'sort']))
            <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><x-icon name="chevron" width="14" /><span>{{ request('saved') ? 'Saved items' : ($selected?->name ?? (request('q') ? 'Search results' : 'Shop all')) }}</span></div>
        @endunless
        <h1 class="sr-only">{{ request('saved') ? 'Saved items' : ($selected?->name ?? (request('q') ? 'Search results for '.request('q') : 'Products')) }}</h1>
        <div class="catalog-layout">
            <aside class="catalog-sidebar">
                <div class="sidebar-heading"><x-icon name="grid" /><strong>Shop by category</strong></div><a
                    class="sidebar-category {{ !request('category') ? 'selected' : '' }}"
                    href="{{ route('shop', request()->except(['category', 'page'])) }}">All products <x-icon name="arrow" width="16" height="16" /></a>
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
            </aside>
            <div class="catalog-main">
                <form class="mobile-category-filter" method="get" action="{{ route('shop') }}">
                    @foreach (request()->except(['category', 'page']) as $key => $value)
                        @if (is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label class="field" for="mobile-category">Category
                        <select id="mobile-category" name="category" data-submit>
                            <option value="">All products</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                                @foreach ($category->children as $child)
                                    <option value="{{ $child->slug }}" @selected(request('category') === $child->slug)>— {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </label>
                    <noscript><button class="button button-light" type="submit">Apply category</button></noscript>
                </form>
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
                            <h2>{{ request('saved') ? 'No saved items yet' : 'No products found' }}
                            </h2>
                            <p>{{ request('saved') ? 'Use the heart button to save products.' : 'Try another search or category.' }}
                            </p><a class="button button-dark" href="{{ route('shop') }}">View all products <x-icon
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
@endsection
