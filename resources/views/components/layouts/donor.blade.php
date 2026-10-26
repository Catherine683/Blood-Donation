<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-50 text-neutral-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#B3262E">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    
    <title>{{ filled($title ?? null) ? $title . ' — DonorLink UG' : 'Donor Portal — DonorLink UG' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col font-sans antialiased bg-neutral-50 text-neutral-900 pb-20 md:pb-8">
    {{-- Skip to content link for accessibility --}}
    <a href="#donor-main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-primary-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:font-medium">
        Skip to main content
    </a>

    {{-- Top Bar --}}
    <header class="sticky top-0 z-30 bg-white border-b border-neutral-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-8">
                <a href="{{ route('donor.home') }}" class="focus-visible:rounded-lg" aria-label="DonorLink UG Portal">
                    <x-logo size="md" />
                </a>

                {{-- Desktop Top Navigation --}}
                <nav class="hidden md:flex items-center gap-1" aria-label="Donor desktop navigation">
                    <a href="{{ route('donor.home') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('donor.home') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:text-neutral-900 hover:bg-neutral-100' }}">
                        Home
                    </a>
                    <a href="{{ route('donor.appointments') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('donor.appointments') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:text-neutral-900 hover:bg-neutral-100' }}">
                        Appointments
                    </a>
                    <a href="{{ route('donor.history') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('donor.history') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:text-neutral-900 hover:bg-neutral-100' }}">
                        History
                    </a>
                    <a href="{{ route('donor.profile') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('donor.profile') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:text-neutral-900 hover:bg-neutral-100' }}">
                        Profile
                    </a>
                </nav>
            </div>

            {{-- User Account Menu --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    x-on:click="open = !open"
                    x-on:click.outside="open = false"
                    class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-neutral-100 transition-colors focus-visible:outline-primary-600 cursor-pointer min-h-[44px]"
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-label="User account menu"
                >
                    <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-800 font-semibold text-xs flex items-center justify-center shrink-0">
                        {{ auth()->user()?->initials() ?? 'D' }}
                    </span>
                    <span class="hidden sm:inline-block text-sm font-medium text-neutral-800 max-w-[140px] truncate text-left">
                        {{ auth()->user()?->name ?? 'Donor' }}
                    </span>
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    x-cloak
                    class="absolute right-0 mt-2 w-56 rounded-xl bg-white border border-neutral-200/90 shadow-lg py-1.5 z-40"
                    role="menu"
                >
                    <div class="px-4 py-2.5 border-b border-neutral-100">
                        <p class="text-xs text-neutral-500">Signed in as</p>
                        <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()?->email }}</p>
                        <p class="text-xs font-medium text-primary-700 capitalize mt-0.5">Role: Donor</p>
                    </div>

                    <a href="{{ route('donor.profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100 transition-colors" role="menuitem">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Donor Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-danger-700 hover:bg-danger-50 transition-colors text-left cursor-pointer min-h-[44px]" role="menuitem">
                            <svg class="w-4 h-4 text-danger-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main id="donor-main" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        {{ $slot }}
    </main>

    {{-- Bottom Tab Navigation for Mobile (360px - 767px) --}}
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-neutral-200 shadow-lg" aria-label="Donor mobile navigation">
        <div class="grid grid-cols-4 h-16">
            {{-- Tab 1: Home --}}
            <a
                href="{{ route('donor.home') }}"
                class="flex flex-col items-center justify-center gap-1 transition-colors {{ request()->routeIs('donor.home') ? 'text-primary-600 font-semibold' : 'text-neutral-500 hover:text-neutral-800' }}"
                aria-current="{{ request()->routeIs('donor.home') ? 'page' : 'false' }}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[11px] leading-tight">Home</span>
            </a>

            {{-- Tab 2: Appointments --}}
            <a
                href="{{ route('donor.appointments') }}"
                class="flex flex-col items-center justify-center gap-1 transition-colors {{ request()->routeIs('donor.appointments') ? 'text-primary-600 font-semibold' : 'text-neutral-500 hover:text-neutral-800' }}"
                aria-current="{{ request()->routeIs('donor.appointments') ? 'page' : 'false' }}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-[11px] leading-tight">Appointments</span>
            </a>

            {{-- Tab 3: History --}}
            <a
                href="{{ route('donor.history') }}"
                class="flex flex-col items-center justify-center gap-1 transition-colors {{ request()->routeIs('donor.history') ? 'text-primary-600 font-semibold' : 'text-neutral-500 hover:text-neutral-800' }}"
                aria-current="{{ request()->routeIs('donor.history') ? 'page' : 'false' }}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-[11px] leading-tight">History</span>
            </a>

            {{-- Tab 4: Profile --}}
            <a
                href="{{ route('donor.profile') }}"
                class="flex flex-col items-center justify-center gap-1 transition-colors {{ request()->routeIs('donor.profile') ? 'text-primary-600 font-semibold' : 'text-neutral-500 hover:text-neutral-800' }}"
                aria-current="{{ request()->routeIs('donor.profile') ? 'page' : 'false' }}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[11px] leading-tight">Profile</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>
