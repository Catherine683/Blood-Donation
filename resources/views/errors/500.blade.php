<x-layouts::auth.card title="500 — Server Error">
    <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-danger-50 text-danger-600 mb-2 font-mono font-bold text-lg">
            500
        </div>
        <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-display">
            Server Error
        </h1>
        <p class="text-sm text-neutral-600 leading-relaxed">
            Something unexpected occurred while processing your request. Our technical staff has been notified. Please try again shortly.
        </p>
        <div class="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
            <x-button :href="route('home')" variant="primary" size="md">
                Return home
            </x-button>
        </div>
    </div>
</x-layouts::auth.card>
