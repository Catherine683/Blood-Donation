<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\UgandanPhoneNumber;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property Role $role
 * @property bool $is_active
 * @property Carbon|null $consent_given_at
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone', 'date_of_birth', 'notify_new_drives', 'password', 'role', 'is_active', 'consent_given_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone' => UgandanPhoneNumber::class,
            'date_of_birth' => 'date',
            'consent_given_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'notify_new_drives' => 'boolean',
        ];
    }

    /**
     * Get the donor profile associated with the user.
     *
     * @return HasOne<DonorProfile, $this>
     */
    public function donorProfile(): HasOne
    {
        return $this->hasOne(DonorProfile::class);
    }

    /**
     * Get the drives created by this user.
     *
     * @return HasMany<Drive, $this>
     */
    public function drives(): HasMany
    {
        return $this->hasMany(Drive::class, 'created_by');
    }

    /**
     * Appointments booked by this user.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Notifications logged for this user.
     *
     * @return HasMany<NotificationLog, $this>
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Donation records for this user (as donor).
     *
     * @return HasMany<DonationRecord, $this>
     */
    public function donationRecords(): HasMany
    {
        return $this->hasMany(DonationRecord::class);
    }

    /**
     * Audit logs authored by this user.
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    /**
     * Check if user is a donor
     */
    public function isDonor(): bool
    {
        return $this->role === Role::Donor;
    }

    /**
     * Check if user is staff (staff or admin)
     */
    public function isStaff(): bool
    {
        return $this->role === Role::Staff || $this->role === Role::Admin;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
