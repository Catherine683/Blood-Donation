<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationStatus;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Notifications\AppointmentReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SendRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donorlink:send-reminders
                            {--now= : Simulated current time in YYYY-MM-DD HH:MM (EAT, non-production only)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 24-hour and 2-hour appointment reminders for upcoming booked blood donations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nowOption = $this->option('now');

        if ($nowOption) {
            if (app()->isProduction()) {
                $this->error('The --now option is not permitted in production environments.');

                return self::FAILURE;
            }

            try {
                $now = Carbon::parse($nowOption, 'Africa/Kampala');
            } catch (\Throwable) {
                $this->error('Invalid date format for --now. Please use YYYY-MM-DD HH:MM (EAT).');

                return self::FAILURE;
            }

            Carbon::setTestNow($now);
        } else {
            $now = Carbon::now('Africa/Kampala');
        }

        $nowUtc = CarbonImmutable::parse($now)->setTimezone('UTC');

        Cache::put('donorlink:last_run:send_reminders', now()->timestamp);

        $this->info("Running reminder sweep as of {$now->format('Y-m-d H:i:s')} EAT ({$nowUtc->format('Y-m-d H:i:s')} UTC)...");

        // Query booked appointments with upcoming slots
        $appointments = Appointment::query()
            ->where('status', AppointmentStatus::Booked)
            ->with(['slot.drive', 'user'])
            ->whereHas('slot', function ($query) use ($nowUtc) {
                $query->where('starts_at', '>', $nowUtc);
            })
            ->get();

        $sent24h = 0;
        $sent2h = 0;
        $skippedCount = 0;
        $duplicateCount = 0;

        foreach ($appointments as $appointment) {
            $slot = $appointment->slot;
            if (! $slot || ! $appointment->user) {
                continue;
            }

            $startsAtUtc = CarbonImmutable::parse($slot->starts_at, 'UTC');
            $secondsUntilStart = $nowUtc->diffInSeconds($startsAtUtc, false);

            $variant = null;

            // 24-hour window: time-to-start between 24 hours and 22 hours
            if ($secondsUntilStart <= (24 * 3600) && $secondsUntilStart >= (22 * 3600)) {
                $variant = '24h';
            }
            // 2-hour window: time-to-start between 2 hours and 1 hour
            elseif ($secondsUntilStart <= (2 * 3600) && $secondsUntilStart >= (1 * 3600)) {
                $variant = '2h';
            }

            if (! $variant) {
                continue;
            }

            $slotId = (int) $appointment->slot_id;
            $dedupeKey = "reminder_{$variant}:appointment:{$appointment->id}:slot:{$slotId}";

            // 3-hour skip rule check:
            // Do not send a reminder if a confirmation or reschedule email for the same slot was sent less than 3 hours earlier.
            $recentSendCutoff = $nowUtc->subHours(3);
            $recentSendExists = NotificationLog::query()
                ->where('related_type', Appointment::class)
                ->where('related_id', $appointment->id)
                ->whereIn('type', ['appointment_confirmed', 'appointment_rescheduled'])
                ->where('status', NotificationStatus::Sent->value)
                ->where('sent_at', '>=', $recentSendCutoff)
                ->exists();

            if ($recentSendExists) {
                try {
                    NotificationLog::create([
                        'user_id' => $appointment->user->id,
                        'type' => "reminder_{$variant}",
                        'channel' => 'mail',
                        'status' => NotificationStatus::Skipped,
                        'sent_at' => null,
                        'related_type' => Appointment::class,
                        'related_id' => $appointment->id,
                        'error' => 'Confirmation or reschedule sent less than 3 hours ago.',
                        'dedupe_key' => $dedupeKey,
                    ]);
                    $skippedCount++;
                    $this->line("Skipped {$variant} reminder for appointment #{$appointment->id} (recent confirmation/reschedule sent within 3h).");
                } catch (UniqueConstraintViolationException) {
                    $duplicateCount++;
                }

                continue;
            }

            // Attempt to insert queued log row first to ensure idempotency
            try {
                NotificationLog::create([
                    'user_id' => $appointment->user->id,
                    'type' => "reminder_{$variant}",
                    'channel' => 'mail',
                    'status' => NotificationStatus::Queued,
                    'sent_at' => null,
                    'related_type' => Appointment::class,
                    'related_id' => $appointment->id,
                    'error' => null,
                    'dedupe_key' => $dedupeKey,
                ]);
            } catch (UniqueConstraintViolationException) {
                $duplicateCount++;

                continue;
            }

            // Dispatched only if the insert succeeded
            $appointment->user->notify(new AppointmentReminder($appointment, $variant));

            if ($variant === '24h') {
                $sent24h++;
            } else {
                $sent2h++;
            }

            $this->info("Dispatched {$variant} reminder for appointment #{$appointment->id} to {$appointment->user->email}.");
        }

        $totalSent = $sent24h + $sent2h;
        $this->info("Reminders sweep completed: {$totalSent} dispatched (24h: {$sent24h}, 2h: {$sent2h}), {$skippedCount} skipped, {$duplicateCount} already processed.");

        return self::SUCCESS;
    }
}
