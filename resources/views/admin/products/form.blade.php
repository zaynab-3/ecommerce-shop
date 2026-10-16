@extends('layouts.admin')
@section('title', $product->exists ? 'Edit product' : 'New product')
@section('content')<x-admin-heading :title="$product->exists ? 'A little fine-tuning' : 'Something new'" description="Give your product the details it deserves."
        eyebrow="YOUR COLLECTION"><a href="{{ route('admin.products.index') }}" class="text-link">← All
            products</a></x-admin-heading>
    <form method="post" enctype="multipart/form-data"
        action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
        class="admin-form-layout">@csrf @if ($product->exists)
            @method('PUT')
<input type="hidden" name="stock_revision" value="{{ $product->stock_revision }}">
        @endif
        <div class="admin-form-primary">
            <section class="admin-panel form-panel">
                <h2>The essentials</h2>
                <div class="field-grid"><label class="field full-span">Product name<input name="name"
                            value="{{ old('name', $product->name) }}" maxlength="180" required></label><label
                        class="field">SKU<input name="sku" value="{{ old('sku', $product->sku) }}" maxlength="80"
                            required></label><label class="field">Category or subcategory<select name="category_id"
                            required>
                            <option value="">Choose a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>
                                    {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label><label class="field full-span">Description
                        <textarea name="description" rows="6" maxlength="10000" required>{{ old('description', $product->description) }}</textarea>
                    </label><label class="field">Price (USD)<input type="number" name="price" step="0.01"
                            min="0" max="1000000"
                            value="{{ old('price', $product->exists ? number_format($product->price_cents / 100, 2, '.', '') : '') }}"
                            required></label><label class="field">Original price <span class="muted">Optional, for sale
                            pricing</span><input type="number" name="compare_price" step="0.01" min="0"
                            value="{{ old('compare_price', $product->compare_price_cents ? number_format($product->compare_price_cents / 100, 2, '.', '') : '') }}"></label><label
                        class="field full-span">Badge <span class="muted">Optional · e.g. New, Best seller</span><input
                            name="badge" value="{{ old('badge', $product->badge) }}" maxlength="30"></label></div>
            </section>
            <section class="admin-panel form-panel">
                <div class="panel-heading compact">
                    <div>
                        <h2>A little color</h2>
                        <p>Colors are optional. A blank hex creates a color without a swatch.</p>
                    </div><button type="button" class="button button-light small-button" data-add-color><x-icon
                            name="plus" width="15" /> Add color</button>
                </div>
                <div data-colors>
                    @foreach (old('colors', $product->exists ? $product->colors->toArray() : []) as $index => $color)
                        <div class="color-editor-row"><input type="hidden" name="colors[{{ $index }}][id]"
                                value="{{ $color['id'] ?? '' }}"><label class="field">Color name<input
                                    name="colors[{{ $index }}][name]" value="{{ $color['name'] }}" required
                                    maxlength="50"></label><label class="field">Hex <span
                                    class="muted">(optional)</span><input name="colors[{{ $index }}][hex]"
                                    value="{{ $color['hex'] ?? '' }}" placeholder="#484e3c" pattern="#[a-fA-F0-9]{6}"
                                    maxlength="7"></label><button type="button" class="icon-button" data-remove-color
                                aria-label="Remove color"><x-icon name="close" width="17" /></button></div>
                    @endforeach
                </div>
                <template id="color-template">
                    <div class="color-editor-row"><label class="field">Color name<input data-name="name" required
                                maxlength="50"></label><label class="field">Hex (optional)<input data-name="hex"
                                placeholder="#484e3c" pattern="#[a-fA-F0-9]{6}" maxlength="7"></label><button
                            type="button" class="icon-button" data-remove-color aria-label="Remove color">×</button></div>
                </template>
            </section>
            <section class="admin-panel form-panel">
                <h2>Picture it</h2>
                <p class="panel-description">Up to 10 images · JPEG, PNG or WebP · 12 MB each. Images are resized to 1,600
                    px and converted to quality-84 WebP. File size depends on the image.</p><label
                    class="upload-area"><x-icon name="plus" width="28" /><strong>Add your product
                        images</strong><span>Choose images to upload</span><input name="images[]" type="file"
                        accept="image/jpeg,image/png,image/webp" multiple></label>
                @if ($product->exists && $product->images->isNotEmpty())
                    <p class="form-hint">Use the image controls below to assign colors and set gallery order.</p>
                @endif
            </section>
        </div>
        <aside class="admin-form-secondary">
            <section class="admin-panel form-panel">
                <h2>Availability</h2><label class="checkbox-field"><input type="checkbox" name="is_active" value="1"
                        @checked(old('is_active', $product->is_active))><span>Visible in store<small>Hidden categories also hide their
                            products.</small></span></label><label class="checkbox-field"><input type="checkbox"
                        name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))><span>Featured product<small>Shown
                            first in recommended sorting.</small></span></label>
            </section>
            <section class="admin-panel form-panel">
                <h2>Stock, your way</h2>
                <div class="field-grid single"><label class="field">Quantity type<select name="stock_mode"
                            data-stock-mode>
                            <option value="tracked" @selected(old('stock_mode', $product->stock_mode) === 'tracked')>Tracked quantity</option>
                            <option value="open" @selected(old('stock_mode', $product->stock_mode) === 'open')>Open quantity</option>
                        </select></label><label class="field">Quantity <span class="muted">For tracked
                            stock</span><input name="quantity" type="number" min="0" max="10000000"
                            value="{{ old('quantity', $product->quantity) }}" data-stock-quantity></label><label
                        class="field">Low stock at <span class="muted">Blank uses the default rule</span><input
                            name="low_stock_threshold" type="number" min="0" max="10000000"
                            value="{{ old('low_stock_threshold', $product->exists ? $product->low_stock_threshold : '') }}"></label>
                    <p class="form-hint">Default thresholds: up to 10 → 5; up to 30 → 10; up to 100 → 15; above 100 → 15%.
                        The threshold stays fixed as items sell.</p><label class="field">Manual stock status <span
                            class="muted">Used for open quantity only</span><select name="manual_stock_status">
                            @foreach (['available' => 'Available', 'low' => 'Low stock', 'out' => 'Out of stock'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('manual_stock_status', $product->manual_stock_status ?? 'available') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section><button class="button button-dark full-width">Save product <x-icon name="check" /></button>
        </aside>
    </form>
    @if ($product->exists && $product->images->isNotEmpty())
        <section class="admin-panel form-panel image-management">
            <h2>Make every color picture-perfect</h2>
            <p class="panel-description">Choose a color per image, or leave it unassigned. The lowest position is the first
                gallery image.</p>
            <div class="image-admin-grid">
                @foreach ($product->images as $image)
                    <article class="image-admin-card"><img src="{{ $image->url }}" alt="{{ $image->alt }}"
                            width="200" height="180">
                        <form method="post" action="{{ route('admin.products.image', [$product, $image]) }}">@csrf
                            @method('PATCH')<label class="field">Assigned color<select name="product_color_id">
                                    <option value="">Unassigned / all colors</option>
                                    @foreach ($product->colors as $color)
                                        <option value="{{ $color->id }}" @selected($image->product_color_id === $color->id)>
                                            {{ $color->name }}</option>
                                    @endforeach
                                </select>
                            </label><label class="field">Position<input name="position" type="number" min="0"
                                    max="100" value="{{ $image->position }}" required></label><button
                                class="button button-light small-button full-width">Save image settings</button></form>
                        <form method="post" action="{{ route('admin.products.image.delete', [$product, $image]) }}"
                            data-confirm="Remove this product image?">@csrf @method('DELETE')<button
                                class="text-link danger-link">Remove image</button></form>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection
