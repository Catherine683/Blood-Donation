@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $variants = [
        'info' => [
            'wrapper' => 'bg-info-50 text-info-800 border-info-200',
            'title' => 'text-info-900',
            'icon' => '<svg class="w-5 h-5 text-info-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        ],
        'success' => [
            'wrapper' => 'bg-success-50 text-success-800 border-success-200',
            'title' => 'text-success-900',
            'icon' => '<svg class="w-5 h-5 text-success-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        ],
        'warning' => [
            'wrapper' => 'bg-warning-50 text-warning-800 border-warning-200',
            'title' => 'text-warning-900',
            'icon' => '<svg class="w-5 h-5 text-warning-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        ],
        'danger' => [
            'wrapper' => 'bg-danger-50 text-danger-800 border-danger-200',
            'title' => 'text-danger-900',
            'icon' => '<svg class="w-5 h-5 text-danger-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        ],
    ];

    $cfg = $variants[$variant] ?? $variants['info'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl p-4 border flex gap-3.5 ' . $cfg['wrapper']]) }} role="alert">
    <div class="pt-0.5">
        {!! $cfg['icon'] !!}
    </div>
    <div class="flex-1 text-sm leading-relaxed">
        @if ($title)
            <h4 class="font-semibold mb-1 {{ $cfg['title'] }}">{{ $title }}</h4>
        @endif
        <div class="text-sm font-normal">
            {{ $slot }}
        </div>
    </div>
</div>
