<x-layouts.public title="Privacy Notice — DonorLink UG">
    <div class="py-12 sm:py-16 bg-white border-b border-neutral-200/80">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-neutral-900 font-sans">
                Privacy Notice
            </h1>
            <p class="mt-3 text-base sm:text-lg text-neutral-600 leading-relaxed">
                This notice explains what details DonorLink UG collects, why we collect them, and how your information is handled when you use this appointment scheduling service for Uganda Blood Transfusion Services (UBTS).
            </p>
            @if (config('donorlink.privacy.last_updated'))
                <p class="mt-2 text-xs text-neutral-500">
                    Last updated: {{ config('donorlink.privacy.last_updated') }}
                </p>
            @endif
        </div>
    </div>

    <div class="py-12 sm:py-16 bg-neutral-50/50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            {{-- Section 1 --}}
            <section class="bg-white rounded-2xl p-6 sm:p-8 border border-neutral-200/80 shadow-xs">
                <h2 class="text-xl font-semibold text-neutral-900 mb-3">
                    1. What details we collect
                </h2>
                <div class="text-sm sm:text-base text-neutral-700 space-y-3 leading-relaxed">
                    <p>When you register and use DonorLink UG, the system collects:</p>
                    <ul class="list-disc pl-5 space-y-2 text-neutral-600">
                        <li><strong>Your contact details:</strong> your full name, email address, phone number, and optional date of birth provided during registration.</li>
                        <li><strong>Your appointment bookings:</strong> the dates, times, and collection drive locations you choose to book.</li>
                        <li><strong>Records entered by UBTS staff:</strong> donation records entered in person by authorized UBTS staff, such as your blood group and previous donation dates if staff record them.</li>
                    </ul>
                </div>
            </section>

            {{-- Section 2 --}}
            <section class="bg-white rounded-2xl p-6 sm:p-8 border border-neutral-200/80 shadow-xs">
                <h2 class="text-xl font-semibold text-neutral-900 mb-3">
                    2. Why we collect this information
                </h2>
                <div class="text-sm sm:text-base text-neutral-700 space-y-3 leading-relaxed">
                    <p>We use your information strictly for the following purposes:</p>
                    <ul class="list-disc pl-5 space-y-2 text-neutral-600">
                        <li>To schedule, confirm, and manage your voluntary blood donation appointments.</li>
                        <li>To help UBTS collection teams plan for expected donor turnout at donation drives.</li>
                        <li>To send you appointment confirmations, rescheduling updates, cancellations, and reminder notifications (sent 24 hours and 2 hours before your appointment).</li>
                        <li>To email you announcements when new blood donation drives are scheduled in your area, if you have opted in.</li>
                    </ul>
                </div>
            </section>

            {{-- Section 3 --}}
            <section class="bg-white rounded-2xl p-6 sm:p-8 border border-neutral-200/80 shadow-xs">
                <h2 class="text-xl font-semibold text-neutral-900 mb-3">
                    3. How we contact you
                </h2>
                <div class="text-sm sm:text-base text-neutral-700 space-y-3 leading-relaxed">
                    <p>
                        All appointment confirmations, rescheduling notices, cancellations, and reminder notifications (sent 24 hours and 2 hours before your appointment) are sent by <strong>email</strong> to your verified email address.
                    </p>
                    <p>
                        We may also email you announcements when new blood donation drives are published. You can turn off new drive announcements at any time via the email preferences in your donor profile or by clicking the unsubscribe link included in any drive announcement email.
                    </p>
                </div>
            </section>

            {{-- Section 4 --}}
            <section class="bg-white rounded-2xl p-6 sm:p-8 border border-neutral-200/80 shadow-xs">
                <h2 class="text-xl font-semibold text-neutral-900 mb-3">
                    4. Who can see your information
                </h2>
                <div class="text-sm sm:text-base text-neutral-700 space-y-3 leading-relaxed">
                    <p>
                        Your details are accessible only to authorized UBTS staff and system administrators who manage collection sessions. Every staff access to donor records is logged within the system.
                    </p>
                </div>
            </section>

            {{-- Section 5 --}}
            <section class="bg-white rounded-2xl p-6 sm:p-8 border border-neutral-200/80 shadow-xs">
                <h2 class="text-xl font-semibold text-neutral-900 mb-3">
                    5. Correcting or removing your data
                </h2>
                <div class="text-sm sm:text-base text-neutral-700 space-y-3 leading-relaxed">
                    <p>
                        <strong>Corrections:</strong> You can review and update your name, phone number, date of birth, and email address at any time directly through your <a href="{{ route('donor.profile') }}" class="text-primary-600 underline font-medium">donor profile page</a>. Changing your email address will require you to verify the new address.
                    </p>
                    @if (config('donorlink.privacy.removal_process'))
                        <p>
                            <strong>Data removal requests:</strong> {{ config('donorlink.privacy.removal_process') }}
                        </p>
                    @endif
                </div>
            </section>

            {{-- Config-Driven Retention Section --}}
            @if (config('donorlink.privacy.retention_summary'))
                <section class="bg-white rounded-2xl p-6 sm:p-8 border border-neutral-200/80 shadow-xs">
                    <h2 class="text-xl font-semibold text-neutral-900 mb-3">
                        6. Data retention
                    </h2>
                    <div class="text-sm sm:text-base text-neutral-700 space-y-3 leading-relaxed">
                        <p>{{ config('donorlink.privacy.retention_summary') }}</p>
                    </div>
                </section>
            @endif

            {{-- Config-Driven Contact Section --}}
            @if (config('donorlink.privacy.contact_email') || config('donorlink.privacy.contact_address'))
                <section class="bg-white rounded-2xl p-6 sm:p-8 border border-neutral-200/80 shadow-xs">
                    <h2 class="text-xl font-semibold text-neutral-900 mb-3">
                        Contact information
                    </h2>
                    <div class="text-sm sm:text-base text-neutral-700 space-y-2 leading-relaxed">
                        @if (config('donorlink.privacy.contact_email'))
                            <p><strong>Email:</strong> <a href="mailto:{{ config('donorlink.privacy.contact_email') }}" class="text-primary-600 underline">{{ config('donorlink.privacy.contact_email') }}</a></p>
                        @endif
                        @if (config('donorlink.privacy.contact_address'))
                            <p><strong>Address:</strong> {{ config('donorlink.privacy.contact_address') }}</p>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts.public>
