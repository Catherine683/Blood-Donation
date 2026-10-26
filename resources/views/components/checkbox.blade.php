@props([
    'disabled' => false,
    'label' => null,
])

<label class="inline-flex items-center gap-3 cursor-pointer select-none min-h-[44px] {{ $disabled ? 'opacity-60 cursor-not-allowed' : '' }}">
    <input
        type="checkbox"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'h-5 w-5 rounded border-neutral-300 text-primary-600 transition duration-150 focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-neutral-100 cursor-pointer']) }}
    />
    @if ($label || $slot->isNotEmpty())
        <span class="text-sm font-medium text-neutral-800 leading-snug">
            {{ $label ?? $slot }}
        </span>
    @endif
</label>
