{{--
    Product image, or an initials tile in the category color.
    Props: product (App\Models\Product, eager-load `category` for the color), size (sm|md|lg).
--}}
@props(['product', 'size' => 'md'])

@php
    $sizeClasses = match ($size) {
        'sm' => 'h-9 w-9 text-xs rounded-lg',
        'lg' => 'h-20 w-20 text-xl rounded-2xl',
        default => 'h-12 w-12 text-sm rounded-xl',
    };
    $colors = $product->relationLoaded('category') && $product->category
        ? $product->category->colorClasses()
        : \App\Models\Category::COLORS['slate'];
@endphp

@if ($product->image_url)
    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
         {{ $attributes->merge(['class' => "{$sizeClasses} shrink-0 object-cover ring-1 ring-slate-200"]) }}>
@else
    <span {{ $attributes->merge(['class' => "{$sizeClasses} {$colors['bg']} {$colors['text']} flex shrink-0 items-center justify-center font-bold"]) }} aria-hidden="true">
        {{ $product->initials }}
    </span>
@endif
