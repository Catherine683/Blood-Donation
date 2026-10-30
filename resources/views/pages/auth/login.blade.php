<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6" x-data="{ showPassword: false }">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <div>
                <x-label for="email" :value="__('Email address')" required class="text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        inputmode="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="w-full rounded-xl pl-10 pr-3.5 py-2.5 min-h-[44px] text-sm font-medium text-neutral-900 bg-white border {{ $errors->has('email') ? 'border-danger-500 focus:border-danger-600 focus:ring-4 focus:ring-danger-500/15' : 'border-neutral-300 hover:border-neutral-400 focus:border-primary-600 focus:ring-4 focus:ring-primary-500/15' }} transition-all duration-150 placeholder:text-neutral-400 focus:outline-none"
                    />
                </div>
                <x-error for="email" />
            </div>

            <!-- Password -->
            <div>
                <x-label for="password" :value="__('Password')" required class="text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input
                        id="password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••••••"
                        class="w-full rounded-xl pl-10 pr-10 py-2.5 min-h-[44px] text-sm font-medium text-neutral-900 bg-white border {{ $errors->has('password') ? 'border-danger-500 focus:border-danger-600 focus:ring-4 focus:ring-danger-500/15' : 'border-neutral-300 hover:border-neutral-400 focus:border-primary-600 focus:ring-4 focus:ring-primary-500/15' }} transition-all duration-150 placeholder:text-neutral-400 focus:outline-none"
                    />
                    <button
                        type="button"
                        x-on:click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-neutral-400 hover:text-neutral-700 cursor-pointer transition-colors"
                        x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                        x-bind:aria-pressed="showPassword"
                    >
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-error for="password" />
            </div>

            <!-- Remember Me & Forgot Password Row -->
            <div class="flex items-center justify-between pt-1">
                <label for="remember" class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        {{ old('remember') ? 'checked' : '' }}
                        class="w-4 h-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-500 cursor-pointer transition"
                    />
                    <span class="text-xs font-medium text-neutral-700">
                        {{ __('Remember me') }}
                    </span>
                </label>

                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="text-xs font-semibold text-primary-600 hover:text-primary-700 underline underline-offset-2 transition-colors"
                        wire:navigate
                    >
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <!-- Submit Action -->
            <div class="pt-2">
                <button
                    type="submit"
                    data-test="login-button"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-700 via-primary-600 to-primary-700 hover:from-primary-800 hover:to-primary-800 active:scale-[0.99] shadow-md shadow-primary-700/25 hover:shadow-lg hover:shadow-primary-700/35 transition-all duration-150 cursor-pointer focus:outline-none focus:ring-4 focus:ring-primary-500/25 min-h-[46px]"
                >
                    <span>{{ __('Log in') }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>

        <div class="pt-1 text-center text-xs text-neutral-600">
            <span>{{ __('Don\'t have an account?') }}</span>
            <a
                href="{{ route('register') }}"
                class="font-semibold text-primary-600 hover:text-primary-700 underline underline-offset-2 ml-1 transition-colors"
                wire:navigate
            >
                {{ __('Sign up') }}
            </a>
        </div>
    </div>
</x-layouts::auth>
