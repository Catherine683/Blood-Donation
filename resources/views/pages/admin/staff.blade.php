<x-layouts.staff title="Staff Management — Admin Portal">
    <x-page-header
        title="Staff Accounts"
        description="Manage UBTS staff credentials, assign roles, and control access permissions."
    >
        <x-slot:badge>
            <x-badge variant="warning" size="md">Admin Only</x-badge>
        </x-slot:badge>
    </x-page-header>

    <div class="mb-6">
        <x-card title="Seeded Development Accounts" description="Pre-configured accounts for testing roles and authorizations." padding="md">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" role="table">
                    <thead class="border-b border-neutral-200 text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-3 px-4">User</th>
                            <th scope="col" class="py-3 px-4">Role</th>
                            <th scope="col" class="py-3 px-4">Status</th>
                            <th scope="col" class="py-3 px-4">Verification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr>
                            <td class="py-3.5 px-4 font-medium text-neutral-900">
                                admin@donorlink.test
                                <span class="block text-xs font-normal text-neutral-500">UBTS Administrator</span>
                            </td>
                            <td class="py-3.5 px-4"><x-badge variant="warning" size="sm">Admin</x-badge></td>
                            <td class="py-3.5 px-4"><x-badge variant="success" size="sm">Active</x-badge></td>
                            <td class="py-3.5 px-4 text-xs text-neutral-600">Verified</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-medium text-neutral-900">
                                staff@donorlink.test
                                <span class="block text-xs font-normal text-neutral-500">UBTS Staff Nurse</span>
                            </td>
                            <td class="py-3.5 px-4"><x-badge variant="primary" size="sm">Staff</x-badge></td>
                            <td class="py-3.5 px-4"><x-badge variant="success" size="sm">Active</x-badge></td>
                            <td class="py-3.5 px-4 text-xs text-neutral-600">Verified</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <x-empty-state
        title="Staff management operations"
        description="Administrative account provisioning, staff role toggling, and deactivation workflows will be fully enabled in the administrative features phase."
    />
</x-layouts.staff>
