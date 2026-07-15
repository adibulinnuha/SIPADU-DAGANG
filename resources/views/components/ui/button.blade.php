@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
])

@php
    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 focus:ring-slate-300',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'outline' => 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 focus:ring-blue-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-3 text-base',
    ];

    $classes = "
        inline-flex items-center justify-center
        rounded-lg font-medium
        transition duration-200
        focus:outline-none focus:ring-2 focus:ring-offset-2
        {$variants[$variant]}
        {$sizes[$size]}
    ";
@endphp


@if($href)

    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>

@else

    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>

@endif