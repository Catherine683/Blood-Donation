<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MailBudget
{
    /**
     * Get the configured daily sending budget.
     */
    public function dailyBudget(): int
    {
        return (int) config('donorlink.mail.daily_budget', 250);
    }

    /**
     * Get the reserved buffer dedicated strictly to transactional emails.
     */
    public function transactionalReserve(): int
    {
        return (int) config('donorlink.mail.transactional_reserve', 100);
    }

    /**
     * Get the batch chunk size for announcement dispatch runs.
     */
    public function announcementChunk(): int
    {
        return (int) config('donorlink.mail.announcement_chunk', 40);
    }

    /**
     * Count messages sent in the rolling last 24 hours from notification_logs.
     */
    public function sentInLast24Hours(?Carbon $asOf = null): int
    {
        $cutoff = ($asOf ?? Carbon::now())->subHours(24);

        return NotificationLog::query()
            ->where('status', NotificationStatus::Sent->value)
            ->where(function ($query) use ($cutoff) {
                $query->where('sent_at', '>=', $cutoff)
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('sent_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            })
            ->count();
    }

    /**
     * Check if a transactional email exceeds 90% of the daily budget and log a warning if so.
     * Transactional emails are NEVER blocked.
     */
    public function checkTransactionalWarning(?Carbon $asOf = null): void
    {
        $sent = $this->sentInLast24Hours($asOf);
        $budget = $this->dailyBudget();

        if ($budget > 0 && ($sent / $budget) >= 0.90) {
            Log::warning(
                "DonorLink UG: Rolling 24-hour email volume has reached {$sent} messages (over 90% of the daily budget of {$budget})."
            );
        }
    }

    /**
     * Remaining capacity available for announcements.
     * Announcements stop when daily_budget - sent_in_last_24h < transactional_reserve.
     */
    public function remaining(?Carbon $asOf = null): int
    {
        $sent = $this->sentInLast24Hours($asOf);
        $budget = $this->dailyBudget();
        $reserve = $this->transactionalReserve();

        $available = $budget - $sent - $reserve;

        return max(0, $available);
    }

    /**
     * Determine if an announcement email can currently be sent.
     */
    public function canSendAnnouncement(?Carbon $asOf = null): bool
    {
        return $this->remaining($asOf) > 0;
    }
}
