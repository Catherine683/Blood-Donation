<x-layouts.staff title="Register Voluntary Donor — Staff Portal">
    <div class="space-y-6 pb-12">
        {{-- Breadcrumb & Header --}}
        <div>
            <nav class="flex items-center gap-2 text-xs text-neutral-500 mb-2" aria-label="Breadcrumb">
                <a href="{{ route('staff.donors') }}" class="hover:text-primary-700 transition-colors">Registered Donors</a>
                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-neutral-900 font-semibold" aria-current="page">Register New Donor</span>
            </nav>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-neutral-200">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Register Voluntary Donor</h1>
                    <p class="text-xs sm:text-sm text-neutral-600 mt-1">
                        Enroll a new blood donor into the UBTS national directory. All details are securely archived and audited.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <x-button :href="route('staff.donors')" variant="secondary" size="md" class="min-h-[44px]">
                        &larr; Back to Directory
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Flash / Error Messages --}}
        @if ($errors->any())
            <x-alert variant="danger" title="Please correct the errors in the form below before submitting.">
                <ul class="list-disc list-inside mt-2 space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        {{-- Registration Form --}}
        <form method="POST" action="{{ route('staff.donors.store') }}" class="space-y-8 max-w-4xl">
            @csrf

            {{-- Card 1: Personal & Contact Information --}}
            <div class="bg-white rounded-2xl border border-neutral-200/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 bg-neutral-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="size-8 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm">
                            1
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-neutral-900">Personal & Contact Information</h2>
                            <p class="text-xs text-neutral-500">Official contact and demographic details</p>
                        </div>
                    </div>
                    <span class="text-[11px] font-semibold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-md border border-primary-200/60">
                        Required Details
                    </span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Full Name --}}
                    <div class="md:col-span-2">
                        <label for="name" class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                            Full Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            value="{{ old('name') }}"
                            placeholder="e.g. Isaac Okello"
                            class="w-full text-sm rounded-xl border border-neutral-300 px-3.5 py-2.5 focus:ring-4 focus:ring-primary-500/15 focus:border-primary-600 transition-all outline-none"
                        />
                        @error('name')
                            <p class="text-xs text-danger-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email Address --}}
                    <div>
                        <label for="email" class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                            Email Address <span class="text-danger-500">*</span>
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            value="{{ old('email') }}"
                            placeholder="e.g. isaac.okello@gmail.com"
                            class="w-full text-sm rounded-xl border border-neutral-300 px-3.5 py-2.5 focus:ring-4 focus:ring-primary-500/15 focus:border-primary-600 transition-all outline-none"
                        />
                        @error('email')
                            <p class="text-xs text-danger-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Ugandan Phone Number --}}
                    <div>
                        <label for="phone" class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                            Ugandan Phone Number <span class="text-danger-500">*</span>
                        </label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            required
                            value="{{ old('phone') }}"
                            placeholder="e.g. 0772123456 or +256772123456"
                            class="w-full text-sm rounded-xl border border-neutral-300 px-3.5 py-2.5 focus:ring-4 focus:ring-primary-500/15 focus:border-primary-600 transition-all outline-none"
                        />
                        <p class="text-[11px] text-neutral-500 mt-1">Accepts standard MTN, Airtel, or Ugandan prefixes</p>
                        @error('phone')
                            <p class="text-xs text-danger-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date of Birth --}}
                    <div>
                        <label for="date_of_birth" class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                            Date of Birth
                        </label>
                        <input
                            id="date_of_birth"
                            name="date_of_birth"
                            type="date"
                            max="{{ now()->format('Y-m-d') }}"
                            value="{{ old('date_of_birth') }}"
                            class="w-full text-sm rounded-xl border border-neutral-300 px-3.5 py-2.5 focus:ring-4 focus:ring-primary-500/15 focus:border-primary-600 transition-all outline-none"
                        />
                        <p class="text-[11px] text-neutral-500 mt-1">Eligible blood donation age in Uganda is 17 to 65 years</p>
                        @error('date_of_birth')
                            <p class="text-xs text-danger-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Card 2: Clinical Profile --}}
            <div class="bg-white rounded-2xl border border-neutral-200/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 bg-neutral-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="size-8 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm">
                            2
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-neutral-900">Clinical Profile & Administrative Notes</h2>
                            <p class="text-xs text-neutral-500">Blood group confirmation and staff observation notes</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Blood Group --}}
                    <div>
                        <label for="blood_group" class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                            Blood Group (ABO / Rh Type)
                        </label>
                        <select
                            id="blood_group"
                            name="blood_group"
                            class="w-full text-sm rounded-xl border border-neutral-300 px-3.5 py-2.5 bg-white focus:ring-4 focus:ring-primary-500/15 focus:border-primary-600 transition-all outline-none"
                        >
                            <option value="">-- Unknown / Pending Laboratory Test --</option>
                            @foreach ($bloodGroups as $group)
                                <option value="{{ $group }}" @selected(old('blood_group') === $group)>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-neutral-500 mt-1">Can be updated after laboratory screening outcome</p>
                        @error('blood_group')
                            <p class="text-xs text-danger-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Staff Notes --}}
                    <div class="md:col-span-2">
                        <label for="notes_for_staff" class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                            Administrative Staff Notes
                        </label>
                        <textarea
                            id="notes_for_staff"
                            name="notes_for_staff"
                            rows="3"
                            maxlength="500"
                            placeholder="Add non-confidential operational notes (e.g. Registered during Makerere University campus drive)..."
                            class="w-full text-sm rounded-xl border border-neutral-300 px-3.5 py-2.5 focus:ring-4 focus:ring-primary-500/15 focus:border-primary-600 transition-all outline-none"
                        >{{ old('notes_for_staff') }}</textarea>
                        <p class="text-[11px] text-neutral-500 mt-1">Maximum 500 characters. Visible to all staff members.</p>
                        @error('notes_for_staff')
                            <p class="text-xs text-danger-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Action Row --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <x-button :href="route('staff.donors')" variant="secondary" size="md" class="min-h-[44px]">
                    Cancel
                </x-button>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm shadow-xs hover:shadow-md transition-all cursor-pointer min-h-[44px] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Register Voluntary Donor</span>
                </button>
            </div>
        </form>
    </div>
</x-layouts.staff>
