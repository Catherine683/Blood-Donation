<x-layouts::auth :title="__('Verify password reset code')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Check your email')"
            :description="__('Enter the six-digit code we sent to your email. The code expires in 10 minutes.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.otp.verify') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                :value="old('email', $email)"
            />

            <flux:input
                name="code"
                :label="__('Six-digit code')"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                minlength="6"
                maxlength="6"
                required
                autocomplete="one-time-code"
                autofocus
            />

            @error('code')
                <p class="text-sm text-danger-600">{{ $message }}</p>
            @enderror

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Verify code and continue') }}
            </flux:button>
        </form>

        <form method="POST" action="{{ route('password.email') }}" class="text-center">
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <button type="submit" class="text-sm text-primary-700 hover:underline">
                {{ __('Send a new code') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
