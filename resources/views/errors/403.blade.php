<x-layouts::auth.card title="403 — Access Forbidden">
    <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-danger-50 text-danger-600 mb-2 font-mono font-bold text-lg">
            403
        </div>
        <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-display">
            Access Forbidden
        </h1>
        <p class="text-sm text-neutral-600 leading-relaxed">
            You do not have permission to access the requested page or operational resource. If you believe this is an error, please contact Uganda Blood Transfusion Services.
        </p>
        <div class="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
            <x-button :href="route('home')" variant="primary" size="md">
                Return to home
            </x-button>
        </div>
    </div>
</x-layouts::auth.card>
