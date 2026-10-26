<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $channel
 * @property NotificationStatus $status
 * @property CarbonImmutable|null $sent_at
 * @property string|null $related_type
 * @property int|null $related_id
 * @property string|null $error
 * @property string|null $dedupe_key
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Model|null $related
 */
#[Fillable([
    'user_id',
    'type',
    'channel',
    'status',
    'sent_at',
    'related_type',
    'related_id',
    'error',
    'dedupe_key',
])]
class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status' => NotificationStatus::class,
            'sent_at' => 'immutable_datetime',
            'related_id' => 'integer',
        ];
    }

    /**
     * Recipient user of this notification.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation to related model (Appointment, Drive, etc.).
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for sent notifications.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSent(Builder $query): void
    {
        $query->where('status', NotificationStatus::Sent->value);
    }

    /**
     * Scope for queued notifications.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeQueued(Builder $query): void
    {
        $query->where('status', NotificationStatus::Queued->value);
    }
}
