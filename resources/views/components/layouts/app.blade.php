@if (auth()->check() && auth()->user()->isDonor())
    <x-layouts.donor :title="$title ?? 'Account Settings'">
        {{ $slot }}
    </x-layouts.donor>
@else
    <x-layouts.staff :title="$title ?? 'Account Settings'">
        {{ $slot }}
    </x-layouts.staff>
@endif
