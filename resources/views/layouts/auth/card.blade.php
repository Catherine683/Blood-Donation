<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-50 text-neutral-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#B3262E">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    
    <title>{{ filled($title ?? null) ? $title . ' — DonorLink UG' : 'DonorLink UG' }}</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 font-sans antialiased text-neutral-900 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
    {{-- Skip to main content --}}
    <a href="#auth-main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-primary-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:font-medium">
        Skip to main content
    </a>

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-6">
        <a href="{{ route('home') }}" class="inline-block focus-visible:rounded-lg" aria-label="DonorLink UG Home">
            <x-logo size="lg" />
        </a>
    </div>

    <main id="auth-main" class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 sm:px-10 rounded-2xl border border-neutral-200/90 shadow-sm">
            {{ $slot }}
        </div>

        <p class="mt-8 text-center text-xs text-neutral-500 leading-normal max-w-xs mx-auto">
            Uganda Blood Transfusion Services (UBTS)<br>
            Nakasero Hill Road, Kampala
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
