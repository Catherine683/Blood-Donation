<div class="flex flex-col lg:flex-row items-start gap-8">
    {{-- Settings Navigation Column --}}
    <aside class="w-full lg:w-72 shrink-0 space-y-4" aria-label="{{ __('Settings Navigation') }}">
        <div class="bg-white rounded-2xl border border-neutral-200/80 p-2 shadow-2xs space-y-1">
            {{-- Profile Tab --}}
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition-all text-sm font-medium border-l-3',
                    'bg-primary-50/90 text-primary-900 border-primary-600 shadow-2xs font-semibold' => request()->routeIs('profile.edit'),
                    'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 border-transparent' => ! request()->routeIs('profile.edit'),
                ])
            >
                <div @class([
                    'flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors',
                    'bg-primary-600 text-white shadow-2xs' => request()->routeIs('profile.edit'),
                    'bg-neutral-100 text-neutral-500' => ! request()->routeIs('profile.edit'),
                ])>
                    <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="leading-tight truncate">{{ __('Profile') }}</p>
                    <p class="text-xs font-normal text-neutral-500 mt-0.5 truncate">{{ __('Name & email address') }}</p>
                </div>
            </a>

            {{-- Security Tab --}}
            <a
                href="{{ route('security.edit') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition-all text-sm font-medium border-l-3',
                    'bg-primary-50/90 text-primary-900 border-primary-600 shadow-2xs font-semibold' => request()->routeIs('security.edit'),
                    'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 border-transparent' => ! request()->routeIs('security.edit'),
                ])
            >
                <div @class([
                    'flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors',
                    'bg-primary-600 text-white shadow-2xs' => request()->routeIs('security.edit'),
                    'bg-neutral-100 text-neutral-500' => ! request()->routeIs('security.edit'),
                ])>
                    <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="leading-tight truncate">{{ __('Security') }}</p>
                    <p class="text-xs font-normal text-neutral-500 mt-0.5 truncate">{{ __('Password, 2FA & keys') }}</p>
                </div>
            </a>

            {{-- Appearance Tab --}}
            <a
                href="{{ route('appearance.edit') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition-all text-sm font-medium border-l-3',
                    'bg-primary-50/90 text-primary-900 border-primary-600 shadow-2xs font-semibold' => request()->routeIs('appearance.edit'),
                    'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 border-transparent' => ! request()->routeIs('appearance.edit'),
                ])
            >
                <div @class([
                    'flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors',
                    'bg-primary-600 text-white shadow-2xs' => request()->routeIs('appearance.edit'),
                    'bg-neutral-100 text-neutral-500' => ! request()->routeIs('appearance.edit'),
                ])>
                    <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4 4 0 014-4 4 4 0 014 4 4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="leading-tight truncate">{{ __('Appearance') }}</p>
                    <p class="text-xs font-normal text-neutral-500 mt-0.5 truncate">{{ __('Theme & display') }}</p>
                </div>
            </a>
        </div>

        {{-- Staff Identity Card --}}
        @auth
            <div class="bg-white rounded-2xl border border-neutral-200/80 p-4 shadow-2xs hidden lg:block">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-bold text-sm">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-neutral-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500">
                    <span>Role</span>
                    <span class="font-medium text-neutral-800 capitalize">{{ auth()->user()->role?->value ?? 'Staff' }}</span>
                </div>
            </div>
        @endauth
    </aside>

    {{-- Main Settings Content Card --}}
    <main class="flex-1 w-full min-w-0 bg-white rounded-2xl border border-neutral-200/80 p-6 sm:p-8 shadow-2xs">
        @if (!empty($heading))
            <div class="pb-5 mb-6 border-b border-neutral-100">
                <h2 class="text-xl font-bold tracking-tight text-neutral-900">{{ $heading }}</h2>
                @if (!empty($subheading))
                    <p class="mt-1 text-sm text-neutral-600">{{ $subheading }}</p>
                @endif
            </div>
        @endif

        <div class="w-full">
            {{ $slot }}
        </div>
    </main>
</div>
