<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AuditLogger
{
    /**
     * Record an immutable audit log entry.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        User|int|null $actor,
        string $action,
        ?Model $subject = null,
        array $metadata = []
    ): AuditLog {
        $actorId = match (true) {
            $actor instanceof User => $actor->id,
            is_int($actor) => $actor,
            $actor === null => null,
            default => auth()->id(),
        };

        $ip = request()?->ip();

        $filteredMetadata = self::sanitizeMetadata($metadata);

        return AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $filteredMetadata,
            'ip' => $ip,
            'created_at' => Carbon::now('UTC'),
        ]);
    }

    /**
     * Prevent accidental storage of personal or medical details in metadata.
     * Only allow record IDs, counts, action indicators, and lists of changed field names.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected static function sanitizeMetadata(array $metadata): array
    {
        $disallowedKeys = [
            'blood_group', 'notes', 'staff_note', 'notes_for_staff',
            'phone', 'email', 'name', 'password', 'date_of_birth',
        ];

        $sanitized = [];
        foreach ($metadata as $key => $value) {
            if (in_array(strtolower((string) $key), $disallowedKeys, true)) {
                continue;
            }

            if ($key === 'changed_fields' && is_array($value)) {
                $sanitized[$key] = array_values(array_filter(
                    is_int(array_key_first($value) ?? 0) ? $value : array_keys($value),
                    fn ($k) => is_string($k)
                ));

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
