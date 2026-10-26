<x-layouts::auth.card title="503 — Service Temporarily Unavailable">
    <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 text-neutral-700 mb-2 font-mono font-bold text-lg">
            503
        </div>
        <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-display">
            Under Maintenance
        </h1>
        <p class="text-sm text-neutral-600 leading-relaxed">
            DonorLink UG is undergoing scheduled maintenance or system updates. Donation records remain secure and service will resume shortly.
        </p>
        <div class="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
            <x-button :href="route('home')" variant="primary" size="md">
                Try again
            </x-button>
        </div>
    </div>
</x-layouts::auth.card>
