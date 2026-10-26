<div class="space-y-6 pb-12">
    {{-- Two-Factor Security Banner (Unobtrusive & Persistent for Staff/Admin without 2FA) --}}
    @if ($show2FaBanner)
        <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-warning-900 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-warning-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <div class="text-xs sm:text-sm">
                    <span class="font-bold">Enhanced Security Recommended:</span> Two-factor authentication is recommended for administrative accounts to protect clinical and donor records.
                </div>
            </div>
            <a
                href="{{ route('security.edit') }}"
                class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-warning-200 text-warning-900 hover:bg-warning-300 transition-colors shrink-0 min-h-[36px]"
            >
                Enable Two-Factor
            </a>
        </div>
    @endif

    {{-- Feedback Alerts --}}
    @if ($feedbackMessage)
        <div class="rounded-xl p-4 text-xs sm:text-sm font-medium border {{ $feedbackType === 'success' ? 'bg-success-50 border-success-200 text-success-800' : 'bg-danger-50 border-danger-200 text-danger-800' }} flex items-center justify-between">
            <span>{{ $feedbackMessage }}</span>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-neutral-400 hover:text-neutral-700 text-xs font-bold p-1">
                &times;
            </button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
                    Staff & Admin Management
                </h1>
                <x-badge variant="warning" size="sm">Admin Portal</x-badge>
            </div>
            <p class="text-xs text-neutral-600 mt-1">
                Provision new UBTS personnel, manage system roles, track two-factor adoption, and control administrative access.
            </p>
        </div>

        <div>
            <x-button type="button" variant="primary" size="md" wire:click="openInviteModal" class="min-h-[44px]">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Invite Staff Member
            </x-button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <x-card padding="sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or email..."
                    class="w-full text-xs rounded-lg border-neutral-300 py-2 px-3 focus:ring-primary-500 focus:border-primary-500 min-h-[44px]"
                />
            </div>
            <div>
                <select
                    wire:model.live="roleFilter"
                    class="w-full text-xs rounded-lg border-neutral-300 py-2 px-3 focus:ring-primary-500 focus:border-primary-500 min-h-[44px]"
                >
                    <option value="">All Roles (Staff & Admin)</option>
                    <option value="staff">Staff Only</option>
                    <option value="admin">Admin Only</option>
                </select>
            </div>
            <div>
                <select
                    wire:model.live="statusFilter"
                    class="w-full text-xs rounded-lg border-neutral-300 py-2 px-3 focus:ring-primary-500 focus:border-primary-500 min-h-[44px]"
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Deactivated Only</option>
                </select>
            </div>
        </div>
    </x-card>

    {{-- Accounts Table --}}
    <x-card padding="none">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm" role="table">
                <thead class="border-b border-neutral-200 bg-neutral-50/50 text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3 px-4">User</th>
                        <th scope="col" class="py-3 px-4">Role</th>
                        <th scope="col" class="py-3 px-4">Status</th>
                        <th scope="col" class="py-3 px-4">Two-Factor</th>
                        <th scope="col" class="py-3 px-4">Verification</th>
                        <th scope="col" class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($users as $u)
                        <tr class="hover:bg-neutral-50/60 transition-colors {{ ! $u->is_active ? 'opacity-60 bg-neutral-50/30' : '' }}">
                            <td class="py-3 px-4 font-medium text-neutral-900">
                                <div>{{ $u->name }}</div>
                                <div class="text-xs text-neutral-500 font-mono">{{ $u->email }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :variant="$u->isAdmin() ? 'warning' : 'primary'" size="sm">
                                    {{ $u->role->label() }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :variant="$u->is_active ? 'success' : 'neutral'" size="sm">
                                    {{ $u->is_active ? 'Active' : 'Deactivated' }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                @if (! empty($u->two_factor_secret))
                                    <span class="inline-flex items-center gap-1 text-xs text-success-700 font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Active
                                    </span>
                                @else
                                    <span class="text-xs text-neutral-400">Disabled</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-neutral-600">
                                @if ($u->email_verified_at)
                                    <span class="text-success-700 font-medium">Verified</span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-warning-100 text-warning-800">
                                        Invitation Pending
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (! $u->email_verified_at && $u->is_active)
                                        <x-button
                                            type="button"
                                            variant="secondary"
                                            size="xs"
                                            wire:click="resendInvitation({{ $u->id }})"
                                            class="min-h-[36px]"
                                        >
                                            Resend
                                        </x-button>
                                    @endif

                                    @if (auth()->id() !== $u->id)
                                        <x-button
                                            type="button"
                                            variant="secondary"
                                            size="xs"
                                            wire:click="openChangeRoleModal({{ $u->id }})"
                                            class="min-h-[36px]"
                                        >
                                            Change Role
                                        </x-button>

                                        @if ($u->is_active)
                                            <x-button
                                                type="button"
                                                variant="danger"
                                                size="xs"
                                                wire:click="promptDeactivate({{ $u->id }})"
                                                class="min-h-[36px]"
                                            >
                                                Deactivate
                                            </x-button>
                                        @else
                                            <x-button
                                                type="button"
                                                variant="secondary"
                                                size="xs"
                                                wire:click="reactivate({{ $u->id }})"
                                                class="min-h-[36px]"
                                            >
                                                Reactivate
                                            </x-button>
                                        @endif
                                    @else
                                        <span class="text-[11px] text-neutral-400 italic px-2">Current user</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-neutral-500">
                                No staff or administrator accounts found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-neutral-100">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>

    {{-- MODAL: Create / Invite Staff Member --}}
    @if ($showInviteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-neutral-900/60 backdrop-blur-xs">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl border border-neutral-200 p-6 space-y-4">
                <h3 class="text-base font-bold text-neutral-900">
                    Invite Staff or Administrator
                </h3>
                <p class="text-xs text-neutral-600 leading-relaxed">
                    A secure 72-hour invitation link will be sent to the recipient. They will configure their own password to activate their account. Passwords are never sent via email.
                </p>

                <div class="space-y-3 pt-2">
                    <div>
                        <x-label for="inviteName" value="Full Name" required />
                        <x-input id="inviteName" type="text" wire:model.live="inviteName" placeholder="Dr. Sarah Namubiru" :error="$errors->has('inviteName')" />
                        <x-error for="inviteName" />
                    </div>

                    <div>
                        <x-label for="inviteEmail" value="Email Address" required />
                        <x-input id="inviteEmail" type="email" wire:model.live="inviteEmail" placeholder="snamubiru@health.go.ug" :error="$errors->has('inviteEmail')" />
                        <x-error for="inviteEmail" />
                    </div>

                    <div>
                        <x-label for="inviteRole" value="Portal Role" required />
                        <select id="inviteRole" wire:model.live="inviteRole" class="w-full text-xs rounded-lg border-neutral-300 py-2.5 px-3 min-h-[44px]">
                            <option value="staff">Staff (Attendance roster, outcomes recording, drive management)</option>
                            <option value="admin">Administrator (Full portal access, staff provisioning, audit log)</option>
                        </select>
                        <x-error for="inviteRole" />
                    </div>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <x-button type="button" variant="secondary" size="md" wire:click="$set('showInviteModal', false)" class="min-h-[44px]">
                        Cancel
                    </x-button>
                    <x-button type="button" variant="primary" size="md" wire:click="sendInvitation" class="min-h-[44px]">
                        Send Invitation
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Change Role Confirmation --}}
    @if ($showRoleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-neutral-900/60 backdrop-blur-xs">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl border border-neutral-200 p-6 space-y-4">
                <h3 class="text-base font-bold text-neutral-900">
                    Change Account Role
                </h3>
                <p class="text-xs text-neutral-600 leading-relaxed">
                    Select the new system role for this account. Role changes take effect immediately on subsequent actions and are logged in the audit registry.
                </p>

                <div class="pt-2">
                    <x-label for="targetRole" value="New Role" required />
                    <select id="targetRole" wire:model.live="targetRole" class="w-full text-xs rounded-lg border-neutral-300 py-2.5 px-3 min-h-[44px]">
                        <option value="staff">Staff (Operational Roster & Drive Records)</option>
                        <option value="admin">Administrator (Full Portal & Staff Management)</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <x-button type="button" variant="secondary" size="md" wire:click="$set('showRoleModal', false)" class="min-h-[44px]">
                        Cancel
                    </x-button>
                    <x-button type="button" variant="primary" size="md" wire:click="confirmChangeRole" class="min-h-[44px]">
                        Confirm Role Change
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Deactivate Confirmation --}}
    @if ($showDeactivateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-neutral-900/60 backdrop-blur-xs">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl border border-neutral-200 p-6 space-y-4">
                <h3 class="text-base font-bold text-neutral-900">
                    Deactivate Staff Account
                </h3>
                <p class="text-xs text-neutral-600 leading-relaxed">
                    Are you sure you want to deactivate this account? Deactivation takes effect <strong>immediately</strong>: all active user sessions will be terminated and subsequent logins blocked.
                </p>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <x-button type="button" variant="secondary" size="md" wire:click="$set('showDeactivateModal', false)" class="min-h-[44px]">
                        Cancel
                    </x-button>
                    <x-button type="button" variant="danger" size="md" wire:click="confirmDeactivate" class="min-h-[44px]">
                        Deactivate & End Sessions
                    </x-button>
                </div>
            </div>
        </div>
    @endif
</div>
