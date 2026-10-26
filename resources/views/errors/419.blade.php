<x-layouts::auth.card title="419 — Session Expired">
    <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-warning-50 text-warning-600 mb-2 font-mono font-bold text-lg">
            419
        </div>
        <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-display">
            Session Expired
        </h1>
        <p class="text-sm text-neutral-600 leading-relaxed">
            Your secure session or page token has expired due to inactivity. Please sign in again to continue safely.
        </p>
        <div class="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
            <x-button :href="route('login')" variant="primary" size="md">
                Sign in to continue
            </x-button>
            <x-button :href="route('home')" variant="secondary" size="md">
                Return home
            </x-button>
        </div>
    </div>
</x-layouts::auth.card>
