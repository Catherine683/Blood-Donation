<x-layouts.staff title="Donors — Staff Portal">
    <x-page-header
        title="Registered Donors"
        description="Search donor profiles, review donation history, and check-in attendees."
    />

    <x-empty-state
        title="Donor directory & check-in roster"
        description="Search registered donors, record walk-in donations, and view donor histories here. Donor lookup and on-site check-in will be introduced in subsequent phases."
    >
        <x-slot:icon>
            <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </x-slot:icon>
    </x-empty-state>
</x-layouts.staff>
