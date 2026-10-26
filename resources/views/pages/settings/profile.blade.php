<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="w-full max-w-xl space-y-6">
            {{-- Name Field --}}
            <div>
                <label for="name" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                    {{ __('Full Name') }} <span class="text-danger-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input
                        id="name"
                        wire:model="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        class="w-full rounded-xl border border-neutral-300 bg-white pl-10 pr-4 py-2.5 text-sm font-medium text-neutral-900 placeholder:text-neutral-400 shadow-2xs focus:border-primary-600 focus:ring-4 focus:ring-primary-500/15 transition-all outline-none"
                        placeholder="e.g. Dr. Jane Mukasa"
                    />
                </div>
                @error('name')
                    <p class="mt-1.5 text-xs text-danger-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-neutral-500">
                    {{ __('Your display name across official donor logs, audit trails, and staff activities.') }}
                </p>
            </div>

            {{-- Email Field --}}
            <div>
                <label for="email" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                    {{ __('Official Email Address') }} <span class="text-danger-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input
                        id="email"
                        wire:model="email"
                        type="email"
                        required
                        autocomplete="email"
                        class="w-full rounded-xl border border-neutral-300 bg-white pl-10 pr-4 py-2.5 text-sm font-medium text-neutral-900 placeholder:text-neutral-400 shadow-2xs focus:border-primary-600 focus:ring-4 focus:ring-primary-500/15 transition-all outline-none"
                        placeholder="staff@ubts.gov.ug"
                    />
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-danger-600 font-medium">{{ $message }}</p>
                @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-3 p-3.5 rounded-xl bg-warning-50 border border-warning-200 text-xs text-warning-800 flex items-start gap-2.5">
                        <svg class="size-4 shrink-0 text-warning-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <span class="font-semibold">{{ __('Your email address is unverified.') }}</span>
                            <button
                                type="button"
                                class="ml-1 text-primary-700 underline font-medium hover:text-primary-800 cursor-pointer"
                                wire:click.prevent="resendVerificationNotification"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </div>
                    </div>

                    @if (session('status') === 'verification-link-sent')
                        <div class="mt-2.5 p-3 rounded-xl bg-success-50 border border-success-200 text-xs text-success-800 font-medium flex items-center gap-2">
                            <svg class="size-4 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ __('A new verification link has been sent to your email address.') }}</span>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Action Row --}}
            <div class="pt-4 flex items-center gap-4">
                <button
                    type="submit"
                    data-test="update-profile-button"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm shadow-xs hover:shadow-md transition-all cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ __('Save') }}</span>
                    <span wire:loading wire:target="updateProfileInformation" class="inline-block animate-spin size-3.5 border-2 border-white border-t-transparent rounded-full ml-1"></span>
                </button>

                <span
                    x-data="{ show: false }"
                    x-show="show"
                    x-transition
                    x-init="@this.on('profile-updated', () => { show = true; setTimeout(() => show = false, 2500); })"
                    class="text-xs font-medium text-success-700 flex items-center gap-1.5"
                    style="display: none;"
                >
                    <svg class="size-4 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ __('Saved successfully.') }}
                </span>
            </div>
        </form>

        {{-- Delete Account Section --}}
        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
