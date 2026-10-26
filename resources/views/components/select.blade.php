@props([
    'disabled' => false,
    'error' => false,
])

@php
    $baseClasses = 'w-full rounded-lg min-h-[44px] px-3.5 py-2.5 text-sm font-normal text-neutral-900 bg-white border transition-colors duration-150 focus:outline-hidden disabled:bg-neutral-100 disabled:text-neutral-500 disabled:cursor-not-allowed appearance-none bg-[url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22M7%208l3%203%203-3%22%20stroke%3D%22%2362574E%22%20stroke-width%3D%221.5%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E")] bg-[length:1.25rem_1.25rem] bg-[right_0.75rem_center] bg-no-repeat pr-10';

    $stateClasses = $error
        ? 'border-danger-500 focus:border-danger-600 focus:ring-2 focus:ring-danger-500/20'
        : 'border-neutral-300 hover:border-neutral-400 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20';
@endphp

<select
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => "$baseClasses $stateClasses"]) }}
>
    {{ $slot }}
</select>
