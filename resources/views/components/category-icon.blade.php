@props(['category'])
@if ($category->icon_path)
<img {{ $attributes->merge(['class' => 'category-custom-icon', 'width' => '24', 'height' => '24']) }} src="{{ route('category-icons.show', basename($category->icon_path)) }}" alt="" loading="lazy" decoding="async">
@else
<x-icon :name="$category->icon ?? 'grid'" {{ $attributes }} />
@endif
