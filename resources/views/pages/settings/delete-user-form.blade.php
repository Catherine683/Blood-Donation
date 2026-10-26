<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-12 pt-8 border-t border-neutral-200/80">
    <div class="rounded-2xl border border-danger-200/80 bg-danger-50/40 p-6 sm:p-7">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
            <div class="space-y-1.5 max-w-xl">
                <div class="flex items-center gap-2 text-danger-700">
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <flux:heading class="!text-danger-900 font-bold text-base">{{ __('Delete account') }}</flux:heading>
                </div>
                <flux:subheading class="!text-neutral-600 text-sm">
                    {{ __('Delete your account and all of its resources') }}
                </flux:subheading>
                <p class="text-xs text-neutral-500 pt-1 leading-relaxed">
                    {{ __('Once your account is deleted, your staff portal credentials will be permanently revoked. Historical donation records, audit logs, and medical activity remain archived under national compliance regulations.') }}
                </p>
            </div>

            <div class="self-start sm:self-center shrink-0">
                <flux:modal.trigger name="confirm-user-deletion">
                    <flux:button variant="danger" data-test="delete-user-button" class="cursor-pointer font-semibold shadow-xs">
                        {{ __('Delete account') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>

    <livewire:pages::settings.delete-user-modal />
</section>
