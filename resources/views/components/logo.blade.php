@props([
    'size' => 'md',
    'iconOnly' => false,
])

@php
    $iconSizes = [
        'sm' => 'h-7 w-7',
        'md' => 'h-9 w-9',
        'lg' => 'h-12 w-12',
    ];

    $textSizes = [
        'sm' => 'text-lg',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
    ];

    $iconClass = $iconSizes[$size] ?? $iconSizes['md'];
    $textClass = $textSizes[$size] ?? $textSizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 select-none']) }}>
    {{-- Droplet Icon Mark --}}
    <svg class="{{ $iconClass }} text-primary-600 shrink-0" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M18 3C18 3 7 15.6667 7 22.8C7 28.9856 11.9249 34 18 34C24.0751 34 29 28.9856 29 22.8C29 15.6667 18 3 18 3Z" fill="currentColor"/>
        {{-- Soft inner highlight --}}
        <path d="M14 20C14 17.5 16 14.5 17.5 13C16.5 15 15.5 18 16 21C16.2 22.2 15.2 23 14 23C14 22 14 21 14 20Z" fill="white" fill-opacity="0.35"/>
    </svg>

    @unless($iconOnly)
        <span class="{{ $textClass }} font-bold tracking-tight text-neutral-900 font-sans flex items-center gap-1.5">
            <span>DonorLink</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-primary-100 text-primary-800 tracking-wide uppercase">UG</span>
        </span>
    @endunless
</div>
