@props([
    'disabled' => false,
    'error' => false,
    'type' => 'text',
])

@php
    $baseClasses = 'w-full rounded-lg min-h-[44px] px-3.5 py-2.5 text-sm font-normal text-neutral-900 bg-white border transition-colors duration-150 placeholder:text-neutral-400 focus:outline-hidden disabled:bg-neutral-100 disabled:text-neutral-500 disabled:cursor-not-allowed';

    $stateClasses = $error
        ? 'border-danger-500 focus:border-danger-600 focus:ring-2 focus:ring-danger-500/20'
        : 'border-neutral-300 hover:border-neutral-400 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20';
@endphp

<input
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => "$baseClasses $stateClasses"]) }}
/>
