@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $baseClasses = 'inline-flex items-center font-medium rounded-full select-none';

    $variants = [
        'primary' => 'bg-primary-50 text-primary-800 border border-primary-200',
        'neutral' => 'bg-neutral-100 text-neutral-800 border border-neutral-200',
        'success' => 'bg-success-50 text-success-800 border border-success-200',
        'warning' => 'bg-warning-50 text-warning-800 border border-warning-200',
        'danger' => 'bg-danger-50 text-danger-800 border border-danger-200',
        'info' => 'bg-info-50 text-info-800 border border-info-200',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs gap-1',
        'md' => 'px-2.5 py-1 text-xs gap-1.5',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['neutral']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
