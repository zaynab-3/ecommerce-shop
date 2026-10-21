@props(['category'])
@if ($category->icon_path)
@php($maskId = 'category-icon-'.\Illuminate\Support\Str::uuid())
<svg {{ $attributes->merge(['class' => 'category-custom-icon', 'width' => '24', 'height' => '24', 'viewBox' => '0 0 24 24', 'aria-hidden' => 'true', 'fill' => 'currentColor']) }}>
    <defs><mask id="{{ $maskId }}" mask-type="alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24"><image href="{{ route('category-icons.show', basename($category->icon_path)) }}" width="24" height="24" preserveAspectRatio="xMidYMid meet" /></mask></defs>
    <rect width="24" height="24" mask="url(#{{ $maskId }})" />
</svg>
@else
<x-icon :name="$category->icon ?? 'grid'" {{ $attributes }} />
@endif
