@props([
    'title' => 'No records found',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-6 bg-white rounded-xl border border-dashed border-neutral-300']) }}>
    <div class="mx-auto w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center text-neutral-500 mb-4">
        @if (isset($icon))
            {{ $icon }}
        @else
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        @endif
    </div>

    <h3 class="text-base font-semibold text-neutral-900 leading-tight">
        {{ $title }}
    </h3>

    @if ($description)
        <p class="mt-1.5 text-sm text-neutral-600 max-w-sm mx-auto leading-normal">
            {{ $description }}
        </p>
    @elseif ($slot->isNotEmpty())
        <div class="mt-1.5 text-sm text-neutral-600 max-w-sm mx-auto leading-normal">
            {{ $slot }}
        </div>
    @endif

    @if (isset($action))
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
</div>
