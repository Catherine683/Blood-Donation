<x-layouts::auth :title="__('Set Your Password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Set Your Password')"
            :description="__('Welcome, :name! Please choose a secure password to activate your :role account.', ['name' => $user->name, 'role' => $user->isAdmin() ? 'Administrator' : 'Staff'])"
        />

        <form method="POST" action="{{ request()->fullUrl() }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Display -->
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

            <!-- Password -->
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

            <!-- Confirm Password -->
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

            <div class="pt-2">
                <x-button variant="primary" type="submit" size="md" class="w-full justify-center min-h-[44px]">
                    Activate Account & Set Password
                </x-button>
            </div>
        </form>
    </div>
</x-layouts::auth>
