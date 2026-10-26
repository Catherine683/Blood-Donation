<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DriveStatus;
use App\Http\Controllers\SitemapController;
use App\Support\DateHelper;
use Database\Factories\DriveFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $venue
 * @property string|null $address
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property DriveStatus $status
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description', 'venue', 'address', 'starts_on', 'ends_on', 'status', 'created_by'])]
class Drive extends Model
{
    /** @use HasFactory<DriveFactory> */
    use HasFactory;

    /**
     * Invalidate plain sitemap cache key whenever a drive is saved or deleted.
     */
    protected static function booted(): void
    {
        static::saved(function (): void {
            SitemapController::clearCache();
        });

        static::deleted(function (): void {
            SitemapController::clearCache();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => DriveStatus::class,
            'created_by' => 'integer',
        ];
    }

    /**
     * User who created the drive.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Time slots for this drive.
     *
     * @return HasMany<Slot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    /**
     * Donation records for this drive.
     *
     * @return HasMany<DonationRecord, $this>
     */
    public function donationRecords(): HasMany
    {
        return $this->hasMany(DonationRecord::class);
    }

    /**
     * Appointments booked across all slots of this drive.
     *
     * @return HasManyThrough<Appointment, Slot, $this>
     */
    public function appointments(): HasManyThrough
    {
        return $this->hasManyThrough(Appointment::class, Slot::class);
    }

    /**
     * Notification logs associated with this drive.
     *
     * @return MorphMany<NotificationLog, $this>
     */
    public function notificationLogs(): MorphMany
    {
        return $this->morphMany(NotificationLog::class, 'related');
    }

    /**
     * Check if the drive is in the past (ends_on has gone by in EAT).
     */
    public function isPast(): bool
    {
        $todayEat = DateHelper::today()->toDateString();

        return $this->ends_on->format('Y-m-d') < $todayEat;
    }

    /**
     * Check if the drive is published.
     */
    public function isPublished(): bool
    {
        return $this->status === DriveStatus::Published;
    }

    /**
     * Check if the drive is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === DriveStatus::Draft;
    }

    /**
     * Check if the drive is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === DriveStatus::Closed;
    }

    /**
     * Scope for published drives.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', DriveStatus::Published->value);
    }

    /**
     * Scope for upcoming drives (ends_on on or after today in EAT).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $todayEat = DateHelper::today()->toDateString();
        $query->where('ends_on', '>=', $todayEat);
    }

    /**
     * Scope for past drives (ends_on before today in EAT).
     *
     * @param  Builder<$this>  $query
     */
    public function scopePast(Builder $query): void
    {
        $todayEat = DateHelper::today()->toDateString();
        $query->where('ends_on', '<', $todayEat);
    }
}
