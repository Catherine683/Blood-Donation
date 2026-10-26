<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="max-w-xl space-y-6">
            <p class="text-sm text-neutral-600">
                {{ __('Select your preferred interface appearance. DonorLink UG default is optimized for high-clarity clinical and administrative light environments.') }}
            </p>

            <div class="p-4 rounded-xl border border-neutral-200/80 bg-neutral-50/60">
                <label class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-3">
                    {{ __('Theme Preference') }}
                </label>
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="w-full">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </div>
        </div>
    </x-pages::settings.layout>
</section>
