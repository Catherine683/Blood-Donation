@props([
    'value' => null,
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-neutral-800 mb-1.5']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-danger-600 font-semibold ml-0.5" aria-hidden="true">*</span>
    @endif
</label>
