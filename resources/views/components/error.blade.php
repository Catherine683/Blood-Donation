@props([
    'for' => null,
    'message' => null,
])

@if ($for)
    @error($for)
        <p {{ $attributes->merge(['class' => 'mt-1.5 text-xs font-medium text-danger-600 flex items-center gap-1']) }} role="alert">
            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
@elseif ($message || $slot->isNotEmpty())
    <p {{ $attributes->merge(['class' => 'mt-1.5 text-xs font-medium text-danger-600 flex items-center gap-1']) }} role="alert">
        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <span>{{ $message ?? $slot }}</span>
    </p>
@endif
