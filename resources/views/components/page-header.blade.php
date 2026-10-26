@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'pb-6 mb-6 border-b border-neutral-200/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4']) }}>
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl md:text-3xl font-bold text-neutral-900 tracking-tight font-sans">
                {{ $title ?? $slot }}
            </h1>
            @if (isset($badge))
                {{ $badge }}
            @endif
        </div>
        @if ($description)
            <p class="mt-1 text-sm text-neutral-600 leading-normal">
                {{ $description }}
            </p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-3 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
