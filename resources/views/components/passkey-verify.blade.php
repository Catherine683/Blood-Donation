@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or continue with email'),
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                Livewire.navigate(response.redirect || '/dashboard');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div>
            <div class="grid gap-2">
                <button
                    type="button"
                    x-on:click="verify()"
                    x-bind:disabled="loading"
                    class="w-full flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl border border-neutral-300 bg-white hover:bg-neutral-50 active:bg-neutral-100 text-sm font-semibold text-neutral-700 shadow-xs transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <svg class="w-5 h-5 text-neutral-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11a7.96 7.96 0 00.99 3.868" />
                    </svg>
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-neutral-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ $loadingLabel }}
                    </span>
                </button>
                <p x-show="error" x-text="error" x-cloak
                   class="text-xs text-center text-danger-600 font-medium"></p>
            </div>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-neutral-200"></div>
                </div>
                <div class="relative flex justify-center text-[11px] uppercase tracking-wider font-semibold">
                    <span class="px-3 text-neutral-500 bg-white">
                        {{ $separator }}
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
