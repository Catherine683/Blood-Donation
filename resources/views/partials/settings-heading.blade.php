<div class="mb-6 w-full">
    {{-- Breadcrumb --}}
    <nav class="mb-3 flex items-center gap-2 text-xs font-medium text-neutral-500" aria-label="Breadcrumb">
        <a href="{{ route('staff.dashboard') }}" class="hover:text-primary-700 transition-colors">Staff Portal</a>
        <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-neutral-800 font-semibold">Account Settings</span>
    </nav>

    {{-- Title and Badges --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-neutral-200">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">
                {{ __('Account Settings') }}
            </h1>
            <p class="mt-1 text-sm text-neutral-600">
                {{ __('Manage your profile identity, security credentials, and system preferences.') }}
            </p>
        </div>

        @auth
            <div class="flex items-center gap-2.5 self-start sm:self-center">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-neutral-200 shadow-2xs text-xs">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-success-500"></span>
                    </span>
                    <span class="font-medium text-neutral-700">{{ auth()->user()->name }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wider bg-primary-50 text-primary-700 border border-primary-200/60">
                        {{ auth()->user()->role?->value ?? 'Staff' }}
                    </span>
                </div>
            </div>
        @endauth
    </div>
</div>
