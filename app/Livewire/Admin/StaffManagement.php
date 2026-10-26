<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use App\Services\AuditLogger;
use App\Services\MailBudget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class StaffManagement extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    // Create / Invite Modal State
    public bool $showInviteModal = false;

    public string $inviteName = '';

    public string $inviteEmail = '';

    public string $inviteRole = 'staff';

    // Change Role Modal State
    public bool $showRoleModal = false;

    #[Locked]
    public ?int $selectedUserId = null;

    public string $targetRole = 'staff';

    // Deactivation Modal State
    public bool $showDeactivateModal = false;

    #[Locked]
    public ?int $deactivateUserId = null;

    // In-app Notification Feedback
    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success';

    /**
     * Authorize admin action inside every method.
     */
    protected function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized administrative action.');
        }
    }

    public function openInviteModal(): void
    {
        $this->authorizeAdmin();
        $this->resetValidation();
        $this->inviteName = '';
        $this->inviteEmail = '';
        $this->inviteRole = 'staff';
        $this->showInviteModal = true;
    }

    public function sendInvitation(MailBudget $mailBudget): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'inviteName' => ['required', 'string', 'max:255'],
            'inviteEmail' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'inviteRole' => ['required', 'in:staff,admin'],
        ]);

        $roleEnum = Role::from($this->inviteRole);

        // Create the user with no usable password; email_verified_at is null until invitation accepted
        $user = User::forceCreate([
            'name' => trim($this->inviteName),
            'email' => strtolower(trim($this->inviteEmail)),
            'password' => Hash::make(Str::random(40)),
            'role' => $roleEnum,
            'is_active' => true,
            'notify_new_drives' => false,
            'consent_given_at' => now(),
        ]);

        // Generate 72-hour signed invitation URL
        $invitationUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $user->id]
        );

        $user->notify(new StaffInvitationNotification($user, $invitationUrl));

        // Audit log (ids only)
        AuditLogger::record(auth()->user(), 'staff.created', $user, [
            'user_id' => $user->id,
            'role' => $roleEnum->value,
        ]);
        AuditLogger::record(auth()->user(), 'staff.invited', $user, [
            'user_id' => $user->id,
            'role' => $roleEnum->value,
        ]);

        $this->setFeedback("Invitation sent to {$user->email} ({$roleEnum->label()}). Valid for 72 hours.", 'success');
        $this->showInviteModal = false;
        $this->reset(['inviteName', 'inviteEmail', 'inviteRole']);
    }

    public function resendInvitation(int $userId): void
    {
        $this->authorizeAdmin();

        $user = User::findOrFail($userId);

        if (! in_array($user->role, [Role::Staff, Role::Admin], true)) {
            $this->setFeedback('Invitations can only be dispatched for staff or administrator accounts.', 'error');

            return;
        }

        $invitationUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $user->id]
        );

        $user->notify(new StaffInvitationNotification($user, $invitationUrl));

        AuditLogger::record(auth()->user(), 'staff.invited', $user, [
            'user_id' => $user->id,
            'role' => $user->role->value,
        ]);

        $this->setFeedback("Invitation resent to {$user->email}. Link extended for 72 hours.", 'success');
    }

    public function openChangeRoleModal(int $userId): void
    {
        $this->authorizeAdmin();

        $currentUser = auth()->user();
        if ($currentUser->id === $userId) {
            $this->setFeedback('You cannot change your own administrative role.', 'error');

            return;
        }

        $targetUser = User::findOrFail($userId);

        if ($targetUser->isAdmin()) {
            $activeAdminCount = User::where('role', Role::Admin)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                $this->setFeedback('Cannot change role: this account is the last active administrator.', 'error');

                return;
            }
        }

        $this->selectedUserId = $userId;
        $this->targetRole = $targetUser->isAdmin() ? 'staff' : 'admin';
        $this->showRoleModal = true;
    }

    public function confirmChangeRole(): void
    {
        $this->authorizeAdmin();

        if (! $this->selectedUserId) {
            $this->showRoleModal = false;

            return;
        }

        $currentUser = auth()->user();
        if ($currentUser->id === $this->selectedUserId) {
            $this->setFeedback('You cannot change your own administrative role.', 'error');
            $this->showRoleModal = false;

            return;
        }

        $targetUser = User::findOrFail($this->selectedUserId);
        $newRole = Role::from($this->targetRole);

        if ($targetUser->isAdmin() && $newRole === Role::Staff) {
            $activeAdminCount = User::where('role', Role::Admin)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                $this->setFeedback('Cannot demote: this account is the last active administrator.', 'error');
                $this->showRoleModal = false;

                return;
            }
        }

        $oldRole = $targetUser->role;
        $targetUser->role = $newRole;
        $targetUser->save();

        AuditLogger::record($currentUser, 'staff.role_changed', $targetUser, [
            'user_id' => $targetUser->id,
            'old_role' => $oldRole->value,
            'new_role' => $newRole->value,
        ]);

        $this->setFeedback("Role updated to {$newRole->label()} for {$targetUser->name}.", 'success');
        $this->showRoleModal = false;
        $this->selectedUserId = null;
    }

    public function promptDeactivate(int $userId): void
    {
        $this->authorizeAdmin();

        $currentUser = auth()->user();
        if ($currentUser->id === $userId) {
            $this->setFeedback('You cannot deactivate your own administrative account.', 'error');

            return;
        }

        $targetUser = User::findOrFail($userId);

        if ($targetUser->isAdmin()) {
            $activeAdminCount = User::where('role', Role::Admin)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                $this->setFeedback('Cannot deactivate: this account is the last active administrator.', 'error');

                return;
            }
        }

        $this->deactivateUserId = $userId;
        $this->showDeactivateModal = true;
    }

    public function confirmDeactivate(): void
    {
        $this->authorizeAdmin();

        if (! $this->deactivateUserId) {
            $this->showDeactivateModal = false;

            return;
        }

        $currentUser = auth()->user();
        if ($currentUser->id === $this->deactivateUserId) {
            $this->setFeedback('You cannot deactivate your own account.', 'error');
            $this->showDeactivateModal = false;

            return;
        }

        $targetUser = User::findOrFail($this->deactivateUserId);

        if ($targetUser->isAdmin()) {
            $activeAdminCount = User::where('role', Role::Admin)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                $this->setFeedback('Cannot deactivate the last active administrator.', 'error');
                $this->showDeactivateModal = false;

                return;
            }
        }

        $targetUser->is_active = false;
        $targetUser->save();

        // Immediately end every active session for that user in the database
        DB::table('sessions')->where('user_id', $targetUser->id)->delete();

        AuditLogger::record($currentUser, 'staff.deactivated', $targetUser, [
            'user_id' => $targetUser->id,
            'role' => $targetUser->role->value,
        ]);

        $this->setFeedback("Account deactivated for {$targetUser->name}. Active sessions have been invalidated.", 'success');
        $this->showDeactivateModal = false;
        $this->deactivateUserId = null;
    }

    public function reactivate(int $userId): void
    {
        $this->authorizeAdmin();

        $targetUser = User::findOrFail($userId);
        $targetUser->is_active = true;
        $targetUser->save();

        AuditLogger::record(auth()->user(), 'staff.reactivated', $targetUser, [
            'user_id' => $targetUser->id,
            'role' => $targetUser->role->value,
        ]);

        $this->setFeedback("Account reactivated for {$targetUser->name}.", 'success');
    }

    protected function setFeedback(string $message, string $type = 'success'): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType = $type;
    }

    public function render()
    {
        $this->authorizeAdmin();

        $users = User::query()
            ->whereIn('role', [Role::Staff, Role::Admin])
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($sub) => $sub->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($this->roleFilter !== '', fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15);

        $currentUser = auth()->user();
        $show2FaBanner = empty($currentUser->two_factor_secret);

        return view('livewire.admin.staff-management', [
            'users' => $users,
            'show2FaBanner' => $show2FaBanner,
        ])->layout('components.layouts.staff', [
            'title' => 'Staff & Admin Management — Admin Portal',
        ]);
    }
}
