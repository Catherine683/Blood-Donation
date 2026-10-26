<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $actor_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip
 * @property CarbonImmutable $created_at
 * @property-read User|null $actor
 * @property-read Model|null $subject
 */
#[Fillable(['actor_id', 'action', 'subject_type', 'subject_id', 'metadata', 'ip', 'created_at'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    /**
     * Disable updated_at timestamp.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_id' => 'integer',
            'subject_id' => 'integer',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * Staff or system actor responsible for the action.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Target entity of the audited action.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
