@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium transition-colors duration-150 rounded-lg min-h-[44px] focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-60 disabled:cursor-not-allowed select-none cursor-pointer';

    $variants = [
        'primary' => 'bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white shadow-xs focus-visible:outline-primary-600 disabled:hover:bg-primary-600',
        'secondary' => 'bg-neutral-100 hover:bg-neutral-200 active:bg-neutral-300 text-neutral-800 border border-neutral-300 focus-visible:outline-neutral-700 disabled:hover:bg-neutral-100',
        'ghost' => 'bg-transparent hover:bg-neutral-100 active:bg-neutral-200 text-neutral-700 hover:text-neutral-900 focus-visible:outline-primary-600 disabled:hover:bg-transparent',
        'danger' => 'bg-danger-600 hover:bg-danger-700 active:bg-danger-800 text-white shadow-xs focus-visible:outline-danger-600 disabled:hover:bg-danger-600',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'md' => 'px-4 py-2.5 text-sm gap-2',
        'lg' => 'px-6 py-3 text-base gap-2.5',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $disabled ? '#' : $href }}" {{ $attributes->merge(['class' => $classes, 'role' => 'button', 'aria-disabled' => $disabled ? 'true' : null]) }}>
        @if ($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled || $loading ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
