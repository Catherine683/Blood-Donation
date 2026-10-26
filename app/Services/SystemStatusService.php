<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemStatusService
{
    public const SCHEDULER_THRESHOLD_SECONDS = 180; // 3 minutes

    public const QUEUE_THRESHOLD_SECONDS = 900; // 15 minutes

    /**
     * Get scheduler health status.
     *
     * @return array{status: 'healthy'|'stale'|'never_run', last_timestamp: int|null, age_seconds: int|null}
     */
    public function getSchedulerStatus(): array
    {
        $last = Cache::get('donorlink:heartbeat:scheduler');

        if ($last === null) {
            return [
                'status' => 'never_run',
                'last_timestamp' => null,
                'age_seconds' => null,
            ];
        }

        $age = now()->timestamp - (int) $last;

        return [
            'status' => $age <= self::SCHEDULER_THRESHOLD_SECONDS ? 'healthy' : 'stale',
            'last_timestamp' => (int) $last,
            'age_seconds' => max(0, $age),
        ];
    }

    /**
     * Get queue worker health status.
     *
     * @return array{status: 'healthy'|'stale'|'never_run', last_timestamp: int|null, age_seconds: int|null}
     */
    public function getQueueWorkerStatus(): array
    {
        $last = Cache::get('donorlink:heartbeat:queue_worker');

        if ($last === null) {
            return [
                'status' => 'never_run',
                'last_timestamp' => null,
                'age_seconds' => null,
            ];
        }

        $age = now()->timestamp - (int) $last;

        return [
            'status' => $age <= self::QUEUE_THRESHOLD_SECONDS ? 'healthy' : 'stale',
            'last_timestamp' => (int) $last,
            'age_seconds' => max(0, $age),
        ];
    }

    /**
     * Check if a warning banner should be displayed to administrators.
     */
    public function hasBackgroundIssue(): bool
    {
        $scheduler = $this->getSchedulerStatus();
        $worker = $this->getQueueWorkerStatus();

        return $scheduler['status'] !== 'healthy' || $worker['status'] !== 'healthy';
    }

    /**
     * Get pending queued jobs count.
     */
    public function getPendingJobsCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return DB::table('jobs')->count();
    }

    /**
     * Get last 20 failed jobs sanitized (no payload, no personal data).
     *
     * @return array<int, array{id: int, job: string, failed_at: string, exception: string}>
     */
    public function getFailedJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(20)
            ->get()
            ->map(function ($row): array {
                $payload = json_decode((string) $row->payload, true);
                $jobClass = $payload['displayName'] ?? $payload['data']['commandName'] ?? 'Unknown Job';

                // Truncate exception to first line, max 120 chars, sanitized
                $firstLine = strtok((string) $row->exception, "\n");
                $cleanException = mb_substr($firstLine !== false ? $firstLine : 'Exception occurred', 0, 120);

                return [
                    'id' => (int) $row->id,
                    'job' => (string) $jobClass,
                    'failed_at' => (string) $row->failed_at,
                    'exception' => $cleanException,
                ];
            })
            ->all();
    }

    /**
     * Get complete telemetry payload for /admin/system.
     */
    public function getTelemetry(MailBudget $mailBudget): array
    {
        $defaultConn = config('database.default');
        $driver = config("database.connections.{$defaultConn}.driver", $defaultConn);

        $lastReminder = Cache::get('donorlink:last_run:send_reminders');
        $lastAnnouncement = Cache::get('donorlink:last_run:dispatch_announcements');

        $failedNotifications24h = NotificationLog::query()
            ->where('status', NotificationStatus::Failed)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return [
            'scheduler' => $this->getSchedulerStatus(),
            'queue_worker' => $this->getQueueWorkerStatus(),
            'has_issue' => $this->hasBackgroundIssue(),
            'pending_jobs_count' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobs(),
            'failed_jobs_count' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'last_reminder_sweep' => $lastReminder ? Carbon::createFromTimestamp($lastReminder) : null,
            'last_announcement_run' => $lastAnnouncement ? Carbon::createFromTimestamp($lastAnnouncement) : null,
            'emails_sent_24h' => $mailBudget->sentInLast24Hours(),
            'daily_budget' => $mailBudget->dailyBudget(),
            'failed_notifications_24h' => $failedNotifications24h,
            'database_driver' => $driver,
            'database_connection' => $defaultConn,
            'app_version' => config('app.version', '1.0.0'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ];
    }
}
