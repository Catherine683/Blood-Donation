<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Models\Drive;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\NewDriveAnnouncement;
use App\Services\MailBudget;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class DispatchAnnouncementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donorlink:dispatch-announcements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch queued new blood drive announcements in chunks respecting the Brevo mail budget';

    /**
     * Execute the console command.
     */
    public function handle(MailBudget $mailBudget): int
    {
        Cache::put('donorlink:last_run:dispatch_announcements', now()->timestamp);

        $remainingBudget = $mailBudget->remaining();

        if ($remainingBudget <= 0) {
            $this->warn('Announcement budget exhausted for rolling 24 hours (remaining: 0). Stopping.');

            return self::SUCCESS;
        }

        $chunkSize = $mailBudget->announcementChunk();
        $batchLimit = min($chunkSize, $remainingBudget);

        $queuedLogs = NotificationLog::query()
            ->where('type', 'new_drive_announcement')
            ->where('status', NotificationStatus::Queued)
            ->with(['user'])
            ->orderBy('id')
            ->limit($batchLimit)
            ->get();

        if ($queuedLogs->isEmpty()) {
            $this->info('No queued announcements to dispatch.');

            return self::SUCCESS;
        }

        $this->info("Dispatching up to {$batchLimit} announcements (remaining budget: {$remainingBudget})...");

        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($queuedLogs as $log) {
            // Re-verify budget capacity before each send
            if ($mailBudget->remaining() <= 0) {
                $this->warn('Announcement budget reached its limit during execution. Resuming on the next scheduled run.');
                break;
            }

            /** @var User|null $user */
            $user = $log->user;
            /** @var Drive|null $drive */
            $drive = Drive::find($log->related_id);

            // Validity checks at send time
            if (! $user || ! $user->is_active || ! $user->hasVerifiedEmail()) {
                $log->update([
                    'status' => NotificationStatus::Skipped,
                    'error' => 'User is inactive or email is unverified.',
                ]);
                $skippedCount++;

                continue;
            }

            if (! $user->notify_new_drives) {
                $log->update([
                    'status' => NotificationStatus::Skipped,
                    'error' => 'User has unsubscribed from new drive announcements.',
                ]);
                $skippedCount++;

                continue;
            }

            if (! $drive || ! $drive->isPublished() || $drive->isPast()) {
                $log->update([
                    'status' => NotificationStatus::Skipped,
                    'error' => 'Drive is no longer published or has ended.',
                ]);
                $skippedCount++;

                continue;
            }

            try {
                $notification = new NewDriveAnnouncement($drive, $user->id);

                Notification::sendNow($user, $notification);

                $log->update([
                    'status' => NotificationStatus::Sent,
                    'sent_at' => Carbon::now('UTC'),
                    'error' => null,
                ]);

                $sentCount++;
            } catch (\Throwable $e) {
                $log->update([
                    'status' => NotificationStatus::Failed,
                    'error' => mb_substr($e->getMessage(), 0, 500),
                ]);

                $failedCount++;
            }
        }

        $newRemaining = $mailBudget->remaining();
        $this->info("Dispatched {$sentCount} announcement(s) (skipped: {$skippedCount}, failed: {$failedCount}, remaining budget: {$newRemaining}).");

        return self::SUCCESS;
    }
}
