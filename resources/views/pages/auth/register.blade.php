<x-layouts::auth :title="__('Register as a Donor')" max-width="max-w-4xl">
    <div class="flex flex-col gap-4" x-data="{ showPassword: false, showConfirm: false }">
        {{-- Unified Standard Auth Header --}}
        <x-auth-header
            :title="__('Register as a Donor')"
            :description="__('Join voluntary blood donors in Uganda and schedule blood donations with UBTS.')"
        />

        {{-- Registration Form: Clean, Balanced 2-Column Desktop Grid --}}
        <form method="POST" action="{{ route('register.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 items-start">
            @csrf

            {{-- Row 1, Col 1: Full Name --}}
            <div>
                <x-label for="name" :value="__('Full name')" required class="text-xs font-semibold text-neutral-700 mb-1" />
                <x-input
                    id="name"
                    name="name"
                    type="text"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Grace Atuhaire"
                    :error="$errors->has('name')"
                    class="text-sm py-2"
                />
                <x-error for="name" />
            </div>

            {{-- Row 1, Col 2: Email Address --}}
            <div>
                <x-label for="email" :value="__('Email address')" required class="text-xs font-semibold text-neutral-700 mb-1" />
                <x-input
                    id="email"
                    name="email"
                    type="email"
                    inputmode="email"
                    :value="old('email')"
                    required
                    autocomplete="email"
                    placeholder="donor@example.ug"
                    :error="$errors->has('email')"
                    class="text-sm py-2"
                />
                <x-error for="email" />
            </div>

            {{-- Row 2, Col 1: Phone Number --}}
            <div>
                <x-label for="phone" :value="__('Phone number')" required class="text-xs font-semibold text-neutral-700 mb-1" />
                <x-input
                    id="phone"
                    name="phone"
                    type="tel"
                    inputmode="tel"
                    :value="old('phone')"
                    required
                    autocomplete="tel"
                    placeholder="0772 123 456"
                    :error="$errors->has('phone')"
                    class="text-sm py-2"
                />
                <p class="mt-1 text-[11px] text-neutral-500">e.g. 0772 123 456 (Ugandan mobile)</p>
                <x-error for="phone" />
            </div>

            {{-- Row 2, Col 2: Date of Birth --}}
            <div>
                <x-label for="date_of_birth" :value="__('Date of birth (optional)')" class="text-xs font-semibold text-neutral-700 mb-1" />
                <x-input
                    id="date_of_birth"
                    name="date_of_birth"
                    type="date"
                    inputmode="numeric"
                    :value="old('date_of_birth')"
                    autocomplete="bday"
                    :error="$errors->has('date_of_birth')"
                    class="text-sm py-2"
                />
                <p class="mt-1 text-[11px] text-neutral-500">Used for on-site ID verification</p>
                <x-error for="date_of_birth" />
            </div>

            {{-- Row 3, Col 1: Password --}}
            <div>
                <x-label for="password" :value="__('Password')" required class="text-xs font-semibold text-neutral-700 mb-1" />
                <div class="relative">
                    <x-input
                        id="password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••••••"
                        class="pr-10 text-sm py-2"
                        :error="$errors->has('password')"
                    />
                    <button
                        type="button"
                        x-on:click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-400 hover:text-neutral-600 cursor-pointer"
                        tabindex="-1"
                        aria-label="Toggle password visibility"
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

            {{-- Row 3, Col 2: Confirm Password --}}
            <div>
                <x-label for="password_confirmation" :value="__('Confirm password')" required class="text-xs font-semibold text-neutral-700 mb-1" />
                <div class="relative">
                    <x-input
                        id="password_confirmation"
                        name="password_confirmation"
                        x-bind:type="showConfirm ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••••••"
                        class="pr-10 text-sm py-2"
                        :error="$errors->has('password_confirmation')"
                    />
                    <button
                        type="button"
                        x-on:click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-400 hover:text-neutral-600 cursor-pointer"
                        tabindex="-1"
                        aria-label="Toggle password confirmation visibility"
                    >
                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-error for="password_confirmation" />
            </div>

            {{-- Row 4 (Full Width): Consent Checkbox Card --}}
            <div class="md:col-span-2 bg-neutral-50/90 border border-neutral-200/80 rounded-xl p-3.5">
                <label for="consent" class="flex items-start gap-3 cursor-pointer">
                    <input
                        id="consent"
                        name="consent"
                        type="checkbox"
                        value="1"
                        {{ old('consent') ? 'checked' : '' }}
                        required
                        class="mt-0.5 w-4 h-4 min-w-[16px] rounded border-neutral-300 text-primary-600 focus:ring-primary-600 cursor-pointer"
                    />
                    <span class="text-xs text-neutral-600 leading-relaxed select-none">
                        I agree to DonorLink UG storing my details so UBTS can manage my appointments and email me appointment confirmations, reminders and announcements of new blood drives. I have read the
                        <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 underline font-medium hover:text-primary-700">privacy notice</a>.
                    </span>
                </label>
                <x-error for="consent" />
            </div>

            {{-- Row 5 (Full Width): Submit Action Button --}}
            <div class="md:col-span-2 pt-1">
                <x-button type="submit" variant="primary" size="lg" class="w-full justify-center shadow-md shadow-primary-600/20 hover:shadow-lg hover:shadow-primary-600/30 font-semibold py-2.5 text-sm transition-all" data-test="register-user-button">
                    {{ __('Create donor account') }}
                </x-button>
            </div>

            {{-- Row 6 (Full Width): Login Link --}}
            <div class="md:col-span-2 text-center text-xs text-neutral-500 pt-1">
                <span>{{ __('Already registered?') }}</span>
                <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:text-primary-700 underline ml-1">
                    {{ __('Log in to your account →') }}
                </a>
            </div>
        </form>
    </div>
</x-layouts::auth>
