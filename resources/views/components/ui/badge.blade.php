@props([
    'variant' => 'success',
])

@php
    $variants = [
        'success' => 'sipadu-badge-success',
        'warning' => 'sipadu-badge-warning',
        'danger' => 'sipadu-badge-danger',
        'primary' => 'bg-blue-100 text-blue-700',
        'secondary' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'sipadu-badge ' . ($variants[$variant] ?? $variants['secondary'])
]) }}>
    {{ $slot }}
</span>