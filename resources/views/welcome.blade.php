<x-layouts.public title="Book Blood Donation Appointments with UBTS">
    {{-- Hero Section with Background Image and Light Gradient Overlay --}}
    <section class="relative overflow-hidden bg-neutral-50 border-b border-neutral-200/80">
        {{-- Full Background Image with Light Gradient Overlay --}}
        <div class="absolute inset-0 z-0">
            <img 
                src="{{ asset('images/blood-donor-hero.jpg') }}" 
                alt="UBTS Blood Donation Session" 
                class="w-full h-full object-cover object-center"
                loading="eager"
            />
            {{-- Light gradient overlay on top of image for maximum clarity and visibility --}}
            <div class="absolute inset-0" style="background: linear-gradient(to right, rgba(255, 255, 255, 0.96) 0%, rgba(255, 255, 255, 0.90) 42%, rgba(255, 255, 255, 0.65) 75%, rgba(255, 255, 255, 0.40) 100%);"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-16 sm:pt-16 sm:pb-20 lg:pt-20 lg:pb-24">
            <div class="lg:grid lg:grid-cols-12 lg:gap-12 lg:items-center">
                
                {{-- Left Column: Copy & Actions --}}
                <div class="lg:col-span-7">
                    {{-- Partner Pill Badge --}}
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-50 border border-primary-200/70 text-primary-800 text-xs font-semibold mb-6 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-primary-600 animate-pulse"></span>
                        <span>Uganda Blood Transfusion Services &bull; Official Scheduling</span>
                    </div>

                    {{-- Hero Headline --}}
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-neutral-900 font-sans leading-[1.14]">
                        Schedule your blood donation in Uganda with <span class="text-primary-600 font-extrabold">confidence</span>.
                    </h1>

                    {{-- One-Sentence Summary --}}
                    <p class="mt-6 text-base sm:text-lg lg:text-xl text-neutral-700 leading-relaxed font-normal max-w-2xl">
                        DonorLink UG connects voluntary blood donors across Uganda with Uganda Blood Transfusion Services (UBTS) donation drives to make scheduling convenient, predictable, and reliable.
                    </p>

                    {{-- Hero Action Buttons --}}
                    <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
                        @auth
                            @php
                                $dashboardUrl = auth()->user()->isStaff() ? route('staff.dashboard') : route('donor.home');
                            @endphp
                            <x-button :href="$dashboardUrl" variant="primary" size="lg" class="w-full sm:w-auto shadow-md">
                                Go to your dashboard
                                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </x-button>
                        @else
                            <x-button :href="route('register')" variant="primary" size="lg" class="w-full sm:w-auto shadow-md font-semibold">
                                Register as a donor
                                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </x-button>
                            <x-button :href="route('drives.index')" variant="secondary" size="lg" class="w-full sm:w-auto bg-white/90 hover:bg-white shadow-xs border-neutral-300">
                                Upcoming drives
                            </x-button>
                            <x-button :href="route('login')" variant="ghost" size="lg" class="w-full sm:w-auto text-neutral-700 hover:text-neutral-900">
                                Log in
                            </x-button>
                        @endauth
                    </div>

                    {{-- Key Metric Highlights Bar --}}
                    <div class="mt-10 pt-8 border-t border-neutral-300/80 grid grid-cols-3 gap-3 sm:gap-6 max-w-xl text-left">
                        <div class="rounded-xl p-3.5 sm:p-4 border border-white/80 shadow-xs" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                            <p class="text-xl sm:text-2xl font-bold text-primary-600 tracking-tight">1 = 3</p>
                            <p class="text-xs text-neutral-600 font-medium mt-0.5">Lives saved per unit</p>
                        </div>
                        <div class="rounded-xl p-3.5 sm:p-4 border border-white/80 shadow-xs" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                            <p class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight">100%</p>
                            <p class="text-xs text-neutral-600 font-medium mt-0.5">Voluntary donation</p>
                        </div>
                        <div class="rounded-xl p-3.5 sm:p-4 border border-white/80 shadow-xs" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                            <p class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight">UBTS</p>
                            <p class="text-xs text-neutral-600 font-medium mt-0.5">Certified drives</p>
                        </div>
                    </div>

                    {{-- Context Reassurance Note --}}
                    <p class="mt-5 text-xs text-neutral-600">
                        A public service by Uganda Blood Transfusion Services &bull; Nakasero Hill Road, Kampala
                    </p>
                </div>

                {{-- Right Column: Light Glass Clinical Assurance Card --}}
                <div class="lg:col-span-5 mt-10 lg:mt-0">
                    <div class="relative max-w-md mx-auto lg:max-w-none">
                        <div class="rounded-2xl p-6 sm:p-8 border border-white/90 shadow-xl" style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                            <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center mb-5 shadow-xs">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-bold text-neutral-900 mb-2">Standard Clinical Protocol</h3>
                            <p class="text-sm text-neutral-600 leading-relaxed mb-6">
                                Every donation session is guided by qualified UBTS phlebotomists using sterile single-use medical equipment and strict clinical standards.
                            </p>

                            <div class="space-y-3.5 border-t border-neutral-200/80 pt-5">
                                <div class="flex items-center gap-3 text-sm text-neutral-700">
                                    <svg class="w-5 h-5 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Free confidential health screening</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-neutral-700">
                                    <svg class="w-5 h-5 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>15-minute scheduled appointment slots</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-neutral-700">
                                    <svg class="w-5 h-5 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Official UBTS regional collection points</span>
                                </div>
                            </div>

                            <div class="mt-6 pt-5 border-t border-neutral-200/80">
                                <a href="{{ route('drives.index') }}" class="inline-flex items-center text-sm font-semibold text-primary-700 hover:text-primary-800 transition-colors group">
                                    Browse scheduled blood drives
                                    <svg class="w-4 h-4 ml-1.5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Visual Impact Cards Section --}}
    <section class="py-14 sm:py-20 bg-neutral-50/80 border-b border-neutral-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-14">
                <span class="text-xs font-bold text-primary-700 uppercase tracking-widest bg-primary-100/60 px-3 py-1 rounded-full">
                    Saving Lives Together
                </span>
                <h2 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900 font-sans">
                    Why your voluntary donation matters
                </h2>
                <p class="mt-3 text-base text-neutral-600">
                    From emergency surgery to maternal health, blood donated through UBTS reaches patients across Uganda when minutes count.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                
                {{-- Feature 1 --}}
                <div class="bg-white rounded-2xl overflow-hidden border border-neutral-200/80 shadow-xs hover:shadow-md transition-shadow duration-300 flex flex-col group">
                    <div class="h-48 overflow-hidden bg-neutral-100 relative">
                        <img 
                            src="{{ asset('images/blood-donor-hand.jpg') }}" 
                            alt="Blood donation appointment setup with stress ball and collection bag" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                            loading="lazy"
                        />
                        <span class="absolute top-3 left-3 bg-neutral-900/80 backdrop-blur-xs text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg">
                            Quick & Predictable
                        </span>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="text-base font-bold text-neutral-900 mb-2">
                            Reserve your slot in seconds
                        </h3>
                        <p class="text-sm text-neutral-600 leading-relaxed">
                            Pick an upcoming drive and your preferred time slot ahead of time. Skip the guesswork and walk straight to reception.
                        </p>
                    </div>
                </div>

                {{-- Feature 2 --}}
                <div class="bg-white rounded-2xl overflow-hidden border border-neutral-200/80 shadow-xs hover:shadow-md transition-shadow duration-300 flex flex-col group">
                    <div class="h-48 overflow-hidden bg-neutral-100 relative">
                        <img 
                            src="{{ asset('images/donor-care.jpg') }}" 
                            alt="Trained UBTS healthcare staff monitoring a donor" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                            loading="lazy"
                        />
                        <span class="absolute top-3 left-3 bg-neutral-900/80 backdrop-blur-xs text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg">
                            Qualified UBTS Staff
                        </span>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="text-base font-bold text-neutral-900 mb-2">
                            Experienced clinical care
                        </h3>
                        <p class="text-sm text-neutral-600 leading-relaxed">
                            Dedicated phlebotomists guide you through sterile, comfortable donation with health pre-checks and refreshments.
                        </p>
                    </div>
                </div>

                {{-- Feature 3 --}}
                <div class="bg-white rounded-2xl overflow-hidden border border-neutral-200/80 shadow-xs hover:shadow-md transition-shadow duration-300 flex flex-col group">
                    <div class="h-48 overflow-hidden bg-neutral-100 relative">
                        <img 
                            src="{{ asset('images/community-hands.jpg') }}" 
                            alt="Community hands united in voluntary support" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                            loading="lazy"
                        />
                        <span class="absolute top-3 left-3 bg-neutral-900/80 backdrop-blur-xs text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg">
                            Community Impact
                        </span>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="text-base font-bold text-neutral-900 mb-2">
                            Serving hospitals nationwide
                        </h3>
                        <p class="text-sm text-neutral-600 leading-relaxed">
                            Every voluntary unit supplies regional blood banks across Kampala, Mulago, Nakasero, and referral facilities countrywide.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- How It Works Section --}}
    <section class="py-16 sm:py-24 bg-white border-b border-neutral-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
                <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900 font-sans">
                    How it works
                </h2>
                <p class="mt-3 text-base text-neutral-600">
                    Booking an appointment takes three simple steps, saving you time at the donation centre.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-10">
                {{-- Step 1 --}}
                <div class="relative bg-neutral-50 rounded-2xl p-6 sm:p-8 border border-neutral-200/80 flex flex-col">
                    <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-800 font-bold text-lg flex items-center justify-center mb-6">
                        1
                    </div>
                    <h3 class="text-lg font-semibold text-neutral-900 mb-2">
                        Create an account
                    </h3>
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        Sign up with your name and email address. Verify your email to secure your account and manage your appointments anytime.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="relative bg-neutral-50 rounded-2xl p-6 sm:p-8 border border-neutral-200/80 flex flex-col">
                    <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-800 font-bold text-lg flex items-center justify-center mb-6">
                        2
                    </div>
                    <h3 class="text-lg font-semibold text-neutral-900 mb-2">
                        Pick a drive and a time
                    </h3>
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        Browse upcoming blood collection drives organized by UBTS across Kampala and select a specific time slot that fits your day.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="relative bg-neutral-50 rounded-2xl p-6 sm:p-8 border border-neutral-200/80 flex flex-col">
                    <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-800 font-bold text-lg flex items-center justify-center mb-6">
                        3
                    </div>
                    <h3 class="text-lg font-semibold text-neutral-900 mb-2">
                        Manage your appointments
                    </h3>
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        View your confirmed appointment details anytime, reschedule to another slot, or cancel if your schedule changes.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- What to Expect on Donation Day (Logistics with Supportive Healthcare Visual) --}}
    <section class="py-16 sm:py-24 bg-neutral-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-14">
                <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900 font-sans">
                    What to expect when you donate
                </h2>
                <p class="mt-3 text-base text-neutral-600 leading-relaxed">
                    Here is what to keep in mind regarding logistics when attending a scheduled blood donation session.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center max-w-5xl mx-auto">
                
                {{-- Left Side: Healthcare Support Visual --}}
                <div class="lg:col-span-5 order-2 lg:order-1">
                    <div class="relative rounded-2xl overflow-hidden shadow-lg border-2 border-white bg-white">
                        <img 
                            src="{{ asset('images/clinical-team.jpg') }}" 
                            alt="Friendly UBTS healthcare professionals ready to welcome donors" 
                            class="w-full h-72 sm:h-80 object-cover"
                            loading="lazy"
                        />
                        <div class="p-5 bg-white">
                            <p class="text-xs font-semibold text-primary-700 uppercase tracking-wider">
                                At the venue
                            </p>
                            <p class="mt-1 text-sm font-medium text-neutral-800">
                                UBTS medical staff conduct a confidential screening on site before your donation.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Right Side: Logistics Items --}}
                <div class="lg:col-span-7 space-y-4 order-1 lg:order-2">
                    {{-- Logistics item 1 --}}
                    <div class="bg-white rounded-xl p-5 sm:p-6 border border-neutral-200/80 shadow-xs flex items-start gap-4">
                        <div class="w-9 h-9 rounded-lg bg-neutral-100 text-neutral-700 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-neutral-900">Arrive on time</h3>
                            <p class="mt-1 text-sm text-neutral-600 leading-relaxed">
                                Please plan to arrive at the venue about 10 minutes prior to your scheduled time slot to facilitate smooth queue flow and check-in.
                            </p>
                        </div>
                    </div>

                    {{-- Logistics item 2 --}}
                    <div class="bg-white rounded-xl p-5 sm:p-6 border border-neutral-200/80 shadow-xs flex items-start gap-4">
                        <div class="w-9 h-9 rounded-lg bg-neutral-100 text-neutral-700 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-neutral-900">Bring a valid photo ID</h3>
                            <p class="mt-1 text-sm text-neutral-600 leading-relaxed">
                                Bring your National ID card, passport, driving permit, or a valid employee/student card to identify yourself during on-site reception.
                            </p>
                        </div>
                    </div>

                    {{-- Logistics item 3 --}}
                    <div class="bg-white rounded-xl p-5 sm:p-6 border border-neutral-200/80 shadow-xs flex items-start gap-4">
                        <div class="w-9 h-9 rounded-lg bg-neutral-100 text-neutral-700 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-neutral-900">Ask staff if unsure</h3>
                            <p class="mt-1 text-sm text-neutral-600 leading-relaxed">
                                UBTS staff on site will welcome you, check you in, and answer any questions you may have about the donation process.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Reassurance CTA Box --}}
            <div class="mt-12 max-w-3xl mx-auto p-6 sm:p-8 rounded-2xl bg-gradient-to-r from-primary-900 to-primary-800 text-white shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold tracking-tight text-white">
                        Ready to support voluntary blood donation?
                    </h3>
                    <p class="mt-1 text-sm text-primary-100 font-normal">
                        Book your appointment today at any UBTS drive in Uganda.
                    </p>
                </div>
                <div class="shrink-0 w-full sm:w-auto">
                    @auth
                        <x-button :href="$dashboardUrl" variant="secondary" size="md" class="w-full sm:w-auto bg-white text-primary-900 hover:bg-neutral-100 border-none font-semibold">
                            Open Dashboard
                        </x-button>
                    @else
                        <x-button :href="route('register')" variant="secondary" size="md" class="w-full sm:w-auto bg-white text-primary-900 hover:bg-neutral-100 border-none font-semibold">
                            Register as a donor
                        </x-button>
                    @endauth
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
