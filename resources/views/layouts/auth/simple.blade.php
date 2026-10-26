<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-50 text-neutral-900">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-neutral-50 font-sans antialiased text-neutral-900 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-6">
        <a href="{{ route('home') }}" class="inline-block focus-visible:rounded-lg" aria-label="DonorLink UG Home">
            <x-logo size="lg" />
        </a>
    </div>

    <main class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 sm:px-10 rounded-2xl border border-neutral-200/90 shadow-sm">
            {{ $slot }}
        </div>
    </main>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
