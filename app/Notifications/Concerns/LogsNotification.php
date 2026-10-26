<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Services\MailBudget;
use Illuminate\Support\Carbon;
use Throwable;

trait LogsNotification
{
    /**
     * The deduplication key for this notification instance.
     */
    public string $dedupeKey;

    /**
     * Get or create the notification log entry for this notification.
     */
    public function getLog(int $userId, string $type, ?string $relatedType = null, ?int $relatedId = null): NotificationLog
    {
        return NotificationLog::firstOrCreate(
            ['dedupe_key' => $this->dedupeKey],
            [
                'user_id' => $userId,
                'type' => $type,
                'channel' => 'mail',
                'status' => NotificationStatus::Queued,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
            ]
        );
    }

    /**
     * Mark the notification as skipped with a reason.
     */
    public function recordSkipped(int $userId, string $type, string $reason, ?string $relatedType = null, ?int $relatedId = null): void
    {
        $log = $this->getLog($userId, $type, $relatedType, $relatedId);
        $log->update([
            'status' => NotificationStatus::Skipped,
            'error' => mb_substr($reason, 0, 500),
        ]);
    }

    /**
     * Mark the notification as sent and trigger transactional warnings if applicable.
     */
    public function recordSent(int $userId, string $type, ?string $relatedType = null, ?int $relatedId = null): void
    {
        $log = $this->getLog($userId, $type, $relatedType, $relatedId);
        $log->update([
            'status' => NotificationStatus::Sent,
            'sent_at' => Carbon::now('UTC'),
            'error' => null,
        ]);

        app(MailBudget::class)->checkTransactionalWarning();
    }

    /**
     * Mark the notification as failed upon final retry failure.
     */
    public function recordFailed(Throwable $e, int $userId, string $type, ?string $relatedType = null, ?int $relatedId = null): void
    {
        $log = $this->getLog($userId, $type, $relatedType, $relatedId);
        $log->update([
            'status' => NotificationStatus::Failed,
            'error' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
