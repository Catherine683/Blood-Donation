@props([
    'title' => null,
    'maxWidth' => null,
])
@php
    $actualWidth = $maxWidth ?? $attributes->get('max-width') ?? $attributes->get('maxWidth') ?? 'max-w-md';
    $containerStyle = match($actualWidth) {
        'max-w-5xl', '5xl' => 'max-width: 60rem;',
        'max-w-4xl', '4xl' => 'max-width: 52rem;',
        'max-w-3xl', '3xl' => 'max-width: 46rem;',
        'max-w-2xl', '2xl' => 'max-width: 42rem;',
        'max-w-xl', 'xl' => 'max-width: 36rem;',
        'max-w-lg', 'lg' => 'max-width: 32rem;',
        default => 'max-width: 28rem;',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-50 text-neutral-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#B3262E">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    
    <title>{{ filled($title ?? null) ? $title . ' — DonorLink UG' : 'DonorLink UG' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 font-sans antialiased text-neutral-900 flex flex-col justify-center py-4 sm:py-6 px-4 sm:px-6 lg:px-8 relative overflow-x-hidden">
    {{-- Soft ambient background glow --}}
    <div class="fixed inset-0 pointer-events-none -z-10 bg-[radial-gradient(ellipse_75%_55%_at_50%_-10%,rgba(179,38,46,0.07),rgba(251,248,246,0))]"></div>

    {{-- Skip to main content --}}
    <a href="#auth-main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-primary-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:font-medium">
        Skip to main content
    </a>

    <div class="mx-auto w-full {{ $actualWidth }} text-center mb-3" style="{{ $containerStyle }}">
        <a href="{{ route('home') }}" class="inline-block focus-visible:rounded-lg transition-transform hover:scale-105 duration-200" aria-label="DonorLink UG Home">
            <x-logo size="md" />
        </a>
    </div>

    <main id="auth-main" class="mx-auto w-full {{ $actualWidth }} transition-all duration-300" style="{{ $containerStyle }}">
        <div class="bg-white py-6 px-6 sm:px-8 lg:px-10 rounded-2xl border border-neutral-200/90 shadow-xl shadow-neutral-900/5 relative overflow-hidden">
            {{-- Crimson Top Brand Accent Strip --}}
            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-primary-700 via-primary-600 to-rose-400"></div>

            {{ $slot }}
        </div>

        <p class="mt-3 text-center text-xs text-neutral-400 leading-normal max-w-sm mx-auto">
            Uganda Blood Transfusion Services (UBTS) &bull; Nakasero Hill Road, Kampala
        </p>
    </main>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
