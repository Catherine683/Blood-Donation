<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-50 text-neutral-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#B3262E">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    
    <title>{{ filled($title ?? null) ? $title . ' — ' . config('app.name', 'DonorLink UG') : config('app.name', 'DonorLink UG') . ' — Blood Donation Appointments in Uganda' }}</title>
    <meta name="description" content="Book your blood donation appointment with Uganda Blood Transfusion Services (UBTS) across Kampala and regional blood banks.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col font-sans antialiased bg-neutral-50 text-neutral-900">
    {{-- Accessible Skip Link --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-primary-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:font-medium">
        Skip to main content
    </a>

    {{-- Public Header --}}
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-neutral-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="focus-visible:rounded-lg" aria-label="DonorLink UG Home">
                <x-logo size="md" />
            </a>

            <nav class="flex items-center gap-3 sm:gap-4" aria-label="Primary navigation">
                <a href="{{ route('home') }}" class="text-sm font-medium text-neutral-700 hover:text-neutral-900 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('home') ? 'text-primary-700 font-semibold' : '' }}">
                    Home
                </a>

                <a href="{{ route('drives.index') }}" class="text-sm font-medium text-neutral-700 hover:text-neutral-900 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('drives.*') ? 'text-primary-700 font-semibold' : '' }}">
                    Upcoming drives
                </a>

                @auth
                    @php
                        $dashboardRoute = auth()->user()->isStaff() ? route('staff.dashboard') : route('donor.home');
                    @endphp
                    <x-button :href="$dashboardRoute" variant="primary" size="sm">
                        Go to your dashboard
                    </x-button>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-neutral-700 hover:text-neutral-900 px-3 py-2 rounded-lg transition-colors">
                        Log in
                    </a>
                    <x-button href="{{ route('register') }}" variant="primary" size="sm">
                        Register as a donor
                    </x-button>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Main Content --}}
    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    {{-- Public Footer --}}
    <footer class="bg-white border-t border-neutral-200/80 mt-auto" role="contentinfo">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                <div>
                    <x-logo size="sm" class="mb-3" />
                    <p class="text-sm text-neutral-600 max-w-md leading-relaxed">
                        Uganda Blood Transfusion Services (UBTS) Headquarters<br>
                        Nakasero Hill Road, P.O. Box 1772, Kampala, Uganda
                    </p>
                </div>
                <div class="md:text-right">
                    <p class="text-xs text-neutral-500 leading-relaxed max-w-md md:ml-auto">
                        <strong class="font-medium text-neutral-700">Notice:</strong> DonorLink UG helps you book a blood donation appointment. It does not decide whether you are eligible to donate; our staff will do that when you arrive.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center justify-start md:justify-end gap-x-4 gap-y-1 text-xs text-neutral-400">
                        <span>&copy; {{ date('Y') }} Uganda Blood Transfusion Services. All rights reserved.</span>
                        <a href="{{ route('drives.index') }}" class="text-neutral-500 hover:text-neutral-800 underline transition-colors">Upcoming drives</a>
                        <a href="{{ route('privacy') }}" class="text-neutral-500 hover:text-neutral-800 underline transition-colors">Privacy Notice</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
