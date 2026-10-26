<x-layouts::auth :title="__('Verify Your Email')">
    <div class="flex flex-col gap-6 text-center" x-data="{ cooldown: 0, timer: null, startCooldown() { this.cooldown = 60; this.timer = setInterval(() => { if (this.cooldown > 0) { this.cooldown--; } else { clearInterval(this.timer); } }, 1000); } }">
        {{-- Email Icon Header --}}
        <div class="mx-auto w-12 h-12 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
                Confirm your email address
            </h1>
            <p class="mt-2 text-sm text-neutral-600 leading-relaxed">
                We sent a verification link to:
            </p>
            <p class="mt-1 text-sm font-semibold text-neutral-900 bg-neutral-100 rounded-lg py-1.5 px-3 inline-block">
                {{ auth()->user()?->email }}
            </p>
            <p class="mt-3 text-xs text-neutral-500 leading-relaxed max-w-sm mx-auto">
                Please click the link in your email to verify your address and begin booking blood donation appointments with UBTS.
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <x-alert variant="success" title="Link sent">
                A new verification link has been sent to your email address.
            </x-alert>
        @endif

        <div class="flex flex-col gap-3 pt-2">
            <form method="POST" action="{{ route('verification.send') }}" x-on:submit="startCooldown()">
                @csrf
                <button
                    type="submit"
                    x-bind:disabled="cooldown > 0"
                    class="w-full inline-flex items-center justify-center font-medium rounded-lg px-4 py-2.5 text-sm transition-colors duration-150 bg-primary-600 hover:bg-primary-700 text-white disabled:bg-neutral-200 disabled:text-neutral-500 disabled:cursor-not-allowed cursor-pointer focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-600/30"
                >
                    <span x-show="cooldown === 0">Resend email</span>
                    <span x-show="cooldown > 0" x-cloak>Resend in <span x-text="cooldown"></span>s</span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="text-sm font-medium text-neutral-500 hover:text-neutral-800 underline transition-colors cursor-pointer"
                    data-test="logout-button"
                >
                    Log out
                </button>
            </form>
        </div>
    </div>
</x-layouts::auth>
