<x-layouts::auth :title="__('Activate donor account')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Activate your donor account')"
            :description="__('Welcome, :name! Set a password to verify your email and activate your DonorLink UG account.', ['name' => $user->name])"
        />

        <form method="POST" action="{{ request()->fullUrl() }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-label for="email" value="Email address" />
                <input
                    type="email"
                    id="email"
                    value="{{ $user->email }}"
                    disabled
                    readonly
                    class="mt-1 w-full text-sm rounded-lg border-neutral-300 bg-neutral-100 text-neutral-600 cursor-not-allowed py-2.5 px-3 min-h-[44px]"
                />
            </div>

            <div>
                <x-label for="password" value="Password (min 8 characters)" required />
                <x-password-input
                    id="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="new-password"
                    placeholder="••••••••••••"
                    :error="$errors->has('password')"
                />
                <x-error for="password" />
            </div>

            <div>
                <x-label for="password_confirmation" value="Confirm password" required />
                <x-password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••••••"
                    :error="$errors->has('password_confirmation')"
                />
                <x-error for="password_confirmation" />
            </div>

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Activate account') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
