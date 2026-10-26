@props([
    'name' => null,
    'title' => null,
    'maxWidth' => 'md',
    'collapsible' => true,
])

@php
    $maxWidths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ];

    $maxWidthClass = $maxWidths[$maxWidth] ?? $maxWidths['md'];
@endphp

<div
    x-data="{ show: false, collapsed: false }"
    @if ($name)
        x-on:open-modal.window="$event.detail === '{{ $name }}' ? (show = true, collapsed = false) : null"
        x-on:close-modal.window="$event.detail === '{{ $name }}' ? (show = false, collapsed = false) : null"
    @endif
    x-on:keydown.escape.window="show = false; collapsed = false"
    x-show="show"
    x-cloak
    class="relative z-50"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $name }}-title"
>
    {{-- Backdrop (hidden when minimized/collapsed) --}}
    <div
        x-show="show && !collapsed"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs transition-opacity"
        x-on:click="show = false; collapsed = false"
        aria-hidden="true"
    ></div>

    {{-- Main Modal Dialog Panel (when not collapsed) --}}
    <div
        x-show="show && !collapsed"
        class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-end sm:items-center justify-center pointer-events-none"
    >
        <div
            x-show="show && !collapsed"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full {{ $maxWidthClass }} bg-white rounded-xl shadow-2xl border border-neutral-200 overflow-hidden transform transition-all pointer-events-auto"
        >
            @if ($title)
                <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between bg-neutral-50/50">
                    <h3 id="{{ $name }}-title" class="text-base font-semibold text-neutral-900 flex items-center gap-2">
                        <span>{{ $title }}</span>
                    </h3>
                    <div class="flex items-center gap-1">
                        @if ($collapsible)
                            <button
                                type="button"
                                x-on:click="collapsed = true"
                                class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors cursor-pointer"
                                title="Minimize / Collapse dialog"
                                aria-label="Minimize dialog"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </button>
                        @endif
                        <button
                            type="button"
                            x-on:click="show = false; collapsed = false"
                            class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors cursor-pointer"
                            title="Close dialog"
                            aria-label="Close dialog"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            <div class="p-6 max-h-[calc(100vh-12rem)] overflow-y-auto">
                {{ $slot }}
            </div>

            @if (isset($footer))
                <div class="px-6 py-4 bg-neutral-50/80 border-t border-neutral-100 flex items-center justify-end gap-3">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>

    {{-- Docked Collapsed Bar (when user collapses the dialog) --}}
    @if ($collapsible)
        <div
            x-show="show && collapsed"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-3"
            class="fixed bottom-5 right-5 z-50 bg-white border border-neutral-300 shadow-2xl rounded-2xl px-4 py-3 flex items-center gap-3.5 pointer-events-auto"
        >
            <span class="flex h-2.5 w-2.5 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary-600"></span>
            </span>
            <div class="text-xs">
                <div class="font-bold text-neutral-900 leading-tight truncate max-w-[200px]">
                    {{ $title ?? 'Dialog' }}
                </div>
                <div class="text-[10px] text-neutral-500 font-medium">Minimized</div>
            </div>
            <div class="flex items-center gap-1.5 ml-2 border-l border-neutral-200 pl-2.5">
                <button
                    type="button"
                    x-on:click="collapsed = false"
                    class="px-2.5 py-1 text-xs font-semibold bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg transition-colors cursor-pointer flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    Expand
                </button>
                <button
                    type="button"
                    x-on:click="show = false; collapsed = false"
                    class="p-1 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors cursor-pointer"
                    title="Close"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>
