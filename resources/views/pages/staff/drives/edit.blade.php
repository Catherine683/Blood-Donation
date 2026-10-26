<x-layouts.staff title="Edit Blood Drive — Staff Portal">
    <div class="max-w-3xl mx-auto space-y-6">
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center text-xs font-medium text-neutral-500 gap-1.5" aria-label="Breadcrumb">
            <a href="{{ route('staff.drives') }}" class="hover:text-neutral-800 transition-colors">Drives</a>
            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('staff.drives.show', $drive) }}" class="hover:text-neutral-800 transition-colors">{{ $drive->title }}</a>
            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-neutral-800">Edit</span>
        </nav>

        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Edit Drive Details</h1>
                <p class="text-sm text-neutral-600 mt-1">
                    Update location, schedule, or logistics for <strong class="font-semibold text-neutral-800">{{ $drive->title }}</strong>.
                </p>
            </div>
            <x-badge :variant="$drive->status->badgeVariant()" size="md">
                {{ $drive->status->label() }}
            </x-badge>
        </div>

        @if ($drive->isPublished())
            <x-alert variant="warning" title="Published Drive">
                This drive is currently published. You may update details, but shortening the date range so that existing time slots are excluded is strictly refused.
            </x-alert>
        @endif

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
            <form method="POST" action="{{ route('staff.drives.update', $drive) }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                        Drive Title <span class="text-danger-600">*</span>
                    </label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        value="{{ old('title', $drive->title) }}"
                        required
                        class="w-full rounded-lg border {{ $errors->has('title') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
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
                            value="{{ old('venue', $drive->venue) }}"
                            required
                            class="w-full rounded-lg border {{ $errors->has('venue') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
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
                            value="{{ old('address', $drive->address) }}"
                            class="w-full rounded-lg border {{ $errors->has('address') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
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
                            value="{{ old('starts_on', $drive->starts_on->format('Y-m-d')) }}"
                            required
                            class="w-full rounded-lg border {{ $errors->has('starts_on') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                        >
                        @error('starts_on')
                            <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="ends_on" class="block text-sm font-semibold text-neutral-800 mb-1.5">
                            End Date (EAT) <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="date"
                            name="ends_on"
                            id="ends_on"
                            value="{{ old('ends_on', $drive->ends_on->format('Y-m-d')) }}"
                            required
                            class="w-full rounded-lg border {{ $errors->has('ends_on') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                        >
                        @error('ends_on')
                            <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                        @enderror
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
                        class="w-full rounded-lg border {{ $errors->has('description') ? 'border-danger-500 bg-danger-50/20' : 'border-neutral-300 bg-white' }} px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                    >{{ old('description', $drive->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-danger-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-100">
                    <x-button :href="route('staff.drives.show', $drive)" variant="secondary" size="md">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="md">
                        Save Changes
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.staff>
