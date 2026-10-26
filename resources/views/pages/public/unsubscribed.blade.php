<x-layouts.public title="Unsubscribed — DonorLink UG">
    <div class="py-16 sm:py-24 bg-neutral-50/60 min-h-[60vh] flex items-center">
        <div class="max-w-xl mx-auto px-4 sm:px-6 w-full">
            <x-card padding="lg" class="text-center space-y-6">
                <div class="mx-auto w-12 h-12 rounded-full bg-success-100 text-success-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
                        Preferences Updated
                    </h1>
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        You have been unsubscribed from announcements of new blood drives for <strong>{{ $user->email }}</strong>.
                    </p>
                </div>

                <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 text-left text-xs text-neutral-600 space-y-2 leading-relaxed">
                    <p class="font-semibold text-neutral-800">
                        What happens next?
                    </p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>You will no longer receive mass emails announcing new blood collection drives.</li>
                        <li>You will <strong>still receive</strong> essential booking confirmations and reminders whenever you book or reschedule an appointment.</li>
                        <li>You can re-enable new drive announcements at any time on your donor profile page.</li>
                    </ul>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <x-button :href="route('drives.index')" variant="primary" size="md" class="w-full sm:w-auto">
                        Browse Blood Drives
                    </x-button>
                    @auth
                        <x-button :href="route('donor.profile')" variant="secondary" size="md" class="w-full sm:w-auto">
                            Go to Profile
                        </x-button>
                    @else
                        <x-button :href="route('login')" variant="secondary" size="md" class="w-full sm:w-auto">
                            Sign In
                        </x-button>
                    @endauth
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.public>
