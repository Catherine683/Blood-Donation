<x-layouts::auth.card title="404 — Page Not Found">
    <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 text-neutral-700 mb-2 font-mono font-bold text-lg">
            404
        </div>
        <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-display">
            Page Not Found
        </h1>
        <p class="text-sm text-neutral-600 leading-relaxed">
            The page or donation resource you requested could not be found. It may have been relocated, concluded, or the link may have expired.
        </p>
        <div class="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
            <x-button :href="route('home')" variant="primary" size="md">
                Return to home
            </x-button>
            <x-button :href="route('drives.index')" variant="secondary" size="md">
                Browse drives
            </x-button>
        </div>
    </div>
</x-layouts::auth.card>
