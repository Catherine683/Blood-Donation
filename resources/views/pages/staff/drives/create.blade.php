<x-layouts.staff title="Create Blood Drive — Staff Portal">
    <div class="max-w-3xl mx-auto space-y-6">
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center text-xs font-medium text-neutral-500 gap-1.5" aria-label="Breadcrumb">
            <a href="{{ route('staff.drives') }}" class="hover:text-neutral-800 transition-colors">Drives</a>
            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-neutral-800">Create Drive</span>
        </nav>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Create a Blood Donation Drive</h1>
            <p class="text-sm text-neutral-600 mt-1">
                Set up a new donation drive location and schedule. New drives are saved as a <strong class="font-semibold text-neutral-700">draft</strong> by default until you configure time slots and choose to publish.
            </p>
        </div>

        @if ($errors->any())
            <x-alert variant="danger" title="Please review the errors below">
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <x-card padding="lg">
            <form method="POST" action="{{ route('staff.drives.store') }}" class="space-y-6">
                @csrf

                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                        Drive Title <span class="text-danger-600">*</span>
                    </label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        value="{{ old('title') }}"
                        required
                        placeholder="e.g. Nakawa Community Blood Donation Camp"
                        class="w-full rounded-lg border {{ $errors->has('title') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                    >
                    @error('title')
                        <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Venue & Address --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="venue" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                            Venue / Facility <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="text"
                            name="venue"
                            id="venue"
                            value="{{ old('venue') }}"
                            required
                            placeholder="e.g. Nakawa Community Centre"
                            class="w-full rounded-lg border {{ $errors->has('venue') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                        >
                        @error('venue')
                            <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                            Physical Address / Landmark
                        </label>
                        <input
                            type="text"
                            name="address"
                            id="address"
                            value="{{ old('address') }}"
                            placeholder="e.g. Near Market Gate 2, Jinja Road"
                            class="w-full rounded-lg border {{ $errors->has('address') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                        >
                        @error('address')
                            <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Dates (in EAT) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="starts_on" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                            Start Date (EAT) <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="date"
                            name="starts_on"
                            id="starts_on"
                            value="{{ old('starts_on', \App\Support\DateHelper::today()->toDateString()) }}"
                            min="{{ \App\Support\DateHelper::today()->toDateString() }}"
                            required
                            class="w-full rounded-lg border {{ $errors->has('starts_on') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                        >
                        @error('starts_on')
                            <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-neutral-500">New drives cannot start in the past.</p>
                    </div>

                    <div>
                        <label for="ends_on" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                            End Date (EAT) <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="date"
                            name="ends_on"
                            id="ends_on"
                            value="{{ old('ends_on', \App\Support\DateHelper::today()->toDateString()) }}"
                            min="{{ \App\Support\DateHelper::today()->toDateString() }}"
                            required
                            class="w-full rounded-lg border {{ $errors->has('ends_on') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                        >
                        @error('ends_on')
                            <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-neutral-500">Must be on or after the start date.</p>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                        Description / Notes for Donors
                    </label>
                    <textarea
                        name="description"
                        id="description"
                        rows="3"
                        placeholder="Logistical guidance for arriving donors, e.g. entry instructions, parking availability, or targeted blood groups."
                        class="w-full rounded-lg border {{ $errors->has('description') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-100">
                    <x-button :href="route('staff.drives')" variant="secondary" size="md">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="md">
                        Save as Draft & Continue &rarr;
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.staff>
