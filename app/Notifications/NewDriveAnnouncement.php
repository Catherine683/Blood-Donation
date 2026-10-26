<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Drive;
use App\Models\User;
use App\Notifications\Concerns\LogsNotification;
use App\Support\DateHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Throwable;

class NewDriveAnnouncement extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public Drive $drive,
        public ?int $recipientUserId = null,
    ) {
        if ($recipientUserId) {
            $this->dedupeKey = "announcement:drive:{$drive->id}:user:{$recipientUserId}";
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (empty($this->dedupeKey) && isset($notifiable->id)) {
            $this->dedupeKey = "announcement:drive:{$this->drive->id}:user:{$notifiable->id}";
        }

        return ['mail'];
    }

    /**
     * Determine if the notification should be sent at dispatch/run time.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (empty($this->dedupeKey) && isset($notifiable->id)) {
            $this->dedupeKey = "announcement:drive:{$this->drive->id}:user:{$notifiable->id}";
        }

        if (! $notifiable instanceof User || ! $notifiable->is_active || ! $notifiable->hasVerifiedEmail()) {
            $this->recordSkipped($notifiable->id ?? 0, 'new_drive_announcement', 'User is inactive or email is unverified.', Drive::class, $this->drive->id);

            return false;
        }

        if (! $notifiable->notify_new_drives) {
            $this->recordSkipped($notifiable->id, 'new_drive_announcement', 'User has unsubscribed from new drive announcements.', Drive::class, $this->drive->id);

            return false;
        }

        $this->drive->refresh();

        if (! $this->drive->isPublished() || $this->drive->isPast()) {
            $this->recordSkipped($notifiable->id, 'new_drive_announcement', 'Drive is no longer published or has ended.', Drive::class, $this->drive->id);

            return false;
        }

        return true;
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if (empty($this->dedupeKey) && isset($notifiable->id)) {
            $this->dedupeKey = "announcement:drive:{$this->drive->id}:user:{$notifiable->id}";
        }

        $subject = "New Blood Drive Announced: {$this->drive->title}";
        $dateRangeEat = DateHelper::formatDateRange($this->drive->starts_on, $this->drive->ends_on);

        $unsubscribeUrl = URL::signedRoute('unsubscribe.announcements', ['user' => $notifiable->id]);

        $this->recordSent($notifiable->id, 'new_drive_announcement', Drive::class, $this->drive->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.new-drive-announcement', [
                'subject' => $subject,
                'heading' => 'New Blood Donation Drive',
                'donorName' => $notifiable->name,
                'driveTitle' => $this->drive->title,
                'venue' => $this->drive->venue,
                'address' => $this->drive->address,
                'dateRangeEat' => $dateRangeEat,
                'description' => $this->drive->description,
                'actionText' => 'See Available Times',
                'actionUrl' => route('drives.show', $this->drive),
                'unsubscribeUrl' => $unsubscribeUrl,
            ])
            ->text('emails.new-drive-announcement-text', [
                'subject' => $subject,
                'heading' => 'New Blood Donation Drive',
                'donorName' => $notifiable->name,
                'driveTitle' => $this->drive->title,
                'venue' => $this->drive->venue,
                'address' => $this->drive->address,
                'dateRangeEat' => $dateRangeEat,
                'description' => $this->drive->description,
                'actionText' => 'See Available Times',
                'actionUrl' => route('drives.show', $this->drive),
                'unsubscribeUrl' => $unsubscribeUrl,
            ])
            ->withSymfonyMessage(function ($message) use ($unsubscribeUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }

    /**
     * Handle delivery failure after all retries are exhausted.
     */
    public function failed(Throwable $e): void
    {
        $userId = $this->recipientUserId ?? 0;
        $this->recordFailed($e, $userId, 'new_drive_announcement', Drive::class, $this->drive->id);
    }
}
