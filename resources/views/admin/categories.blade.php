@extends('layouts.admin')
@section('title', 'Categories')
@section('content')<x-admin-heading title="A place for everything"
        description="Organize, reorder, and show or hide whole collections." eyebrow="CATEGORIES & SUBCATEGORIES" />
    <div class="admin-form-layout">
        <div class="admin-form-primary">
            @forelse($categories as $category)
                <section class="admin-panel category-admin-panel">
                    <div class="category-admin-row"><x-reorder type="categories" :id="$category->id" /><x-icon
                            :name="$category->icon" />
                        <form method="post" action="{{ route('admin.categories.update', $category) }}"
                            class="category-name-form">@csrf @method('PATCH')<input name="name"
                                value="{{ $category->name }}" aria-label="Category name" required maxlength="100"><button
                                class="text-link">Save</button></form>
                        <form method="post" action="{{ route('admin.categories.update', $category) }}">@csrf
                            @method('PATCH')<input type="hidden" name="is_active"
                                value="{{ $category->is_active ? 0 : 1 }}"><button
                                class="visibility-toggle {{ $category->is_active ? 'on' : '' }}"
                                aria-pressed="{{ $category->is_active ? 'true' : 'false' }}"
                                aria-label="Toggle {{ $category->name }}"><span></span>{{ $category->is_active ? 'Visible' : 'Hidden' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.categories.destroy', $category) }}"
                            data-confirm="Delete this empty category?">@csrf @method('DELETE')<button class="icon-button"
                                aria-label="Delete {{ $category->name }}"><x-icon name="close" width="15" /></button>
                        </form>
                    </div>
                    @foreach ($category->children as $child)
                        <div class="category-admin-row child-row"><x-reorder type="categories" :id="$child->id" /><span
                                class="muted">↳</span>
                            <form method="post" action="{{ route('admin.categories.update', $child) }}"
                                class="category-name-form">@csrf @method('PATCH')<input name="name"
                                    value="{{ $child->name }}" aria-label="Subcategory name" required
                                    maxlength="100"><button class="text-link">Save</button></form><span
                                class="category-count">{{ $child->products_count }} items</span>
                            <form method="post" action="{{ route('admin.categories.update', $child) }}">@csrf
                                @method('PATCH')<input type="hidden" name="is_active"
                                    value="{{ $child->is_active ? 0 : 1 }}"><button
                                    class="visibility-toggle {{ $child->is_active ? 'on' : '' }}"
                                    aria-label="Toggle {{ $child->name }}"
                                    aria-pressed="{{ $child->is_active ? 'true' : 'false' }}"><span></span>{{ $child->is_active ? 'Visible' : 'Hidden' }}</button>
                            </form>
                            <form method="post" action="{{ route('admin.categories.destroy', $child) }}"
                                data-confirm="Delete this empty subcategory?">@csrf @method('DELETE')<button
                                    class="icon-button" aria-label="Delete {{ $child->name }}"><x-icon name="close"
                                        width="15" /></button></form>
                        </div>
                    @endforeach
                </section>
            @empty<div class="admin-panel table-empty">
                    <h3>A fresh start.</h3>
                    <p>Add a category to organize your collection.</p>
                </div>
            @endforelse
        </div>
        <aside class="admin-form-secondary">
            <form method="post" action="{{ route('admin.categories.store') }}" class="admin-panel form-panel">@csrf<h2>
                    Create a little order</h2>
                <div class="field-grid single"><label class="field">Name<input name="name" value="{{ old('name') }}"
                            maxlength="100" required></label><label class="field">Parent category<select name="parent_id">
                            <option value="">None · top-level category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label><label class="field">Icon<select name="icon">
                            @foreach (['grid', 'headphones', 'home', 'bag', 'sparkles', 'gamepad', 'watch'] as $icon)
                                <option>{{ $icon }}</option>
                            @endforeach
                        </select></label><button class="button button-dark">Add category <x-icon name="plus" /></button>
                </div>
            </form>
            <p class="form-hint">Hiding a parent category hides its subcategories and products in the store. Existing orders
                are preserved.</p>
        </aside>
</div>@endsection
