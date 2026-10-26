<x-layouts.public title="Unsubscribe Confirmation — DonorLink UG">
    <div class="py-16 sm:py-24 bg-neutral-50/60 min-h-[60vh] flex items-center">
        <div class="max-w-xl mx-auto px-4 sm:px-6 w-full">
            <x-card padding="lg" class="text-center space-y-6">
                <div class="mx-auto w-12 h-12 rounded-full bg-warning-100 text-warning-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
                        Unsubscribe from Blood Drive Announcements
                    </h1>
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        Are you sure you want to stop receiving announcements about upcoming blood donation drives for <strong>{{ $user->email }}</strong>?
                    </p>
                </div>

                <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 text-left text-xs text-neutral-600 space-y-2 leading-relaxed">
                    <p class="font-semibold text-neutral-800">
                        Please note:
                    </p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>You will still receive important transactional emails such as booking confirmations and reminders for your appointments.</li>
                        <li>You can change your email preferences at any time in your donor profile.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ request()->fullUrl() }}" class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    @csrf
                    <x-button type="submit" variant="danger" size="md" class="w-full sm:w-auto">
                        Confirm Unsubscribe
                    </x-button>
                    <x-button :href="route('drives.index')" variant="secondary" size="md" class="w-full sm:w-auto">
                        Keep Receiving Announcements
                    </x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.public>
