@props([
    'title' => null,
    'description' => null,
    'footer' => null,
    'padding' => 'md',
])

@php
    $paddingClasses = [
        'none' => 'p-0',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    $pad = $paddingClasses[$padding] ?? $paddingClasses['md'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-neutral-200/80 shadow-xs overflow-hidden']) }}>
    @if ($title || $description)
        <div class="px-6 pt-6 pb-4 border-b border-neutral-100">
            @if ($title)
                <h3 class="text-base font-semibold text-neutral-900 leading-tight">
                    {{ $title }}
                </h3>
            @endif
            @if ($description)
                <p class="mt-1 text-sm text-neutral-600 leading-normal">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    <div class="{{ $pad }}">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-6 py-4 bg-neutral-50/80 border-t border-neutral-100 flex items-center justify-between gap-3">
            {{ $footer }}
        </div>
    @endif
</div>
