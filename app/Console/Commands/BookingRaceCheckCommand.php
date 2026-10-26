<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Enums\DriveStatus;
use App\Enums\Role;
use App\Exceptions\SlotFullException;
use App\Models\Appointment;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\BookingService;
use App\Support\DateHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BookingRaceCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donorlink:booking-race-check
                            {--capacity=5 : The slot capacity to test against}
                            {--attempts=20 : The number of concurrent booking attempts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that concurrent booking attempts never overbook a slot (Dev only)';

    /**
     * Execute the console command.
     */
    public function handle(BookingService $bookingService): int
    {
        // 1. Refuse to run when APP_ENV=production
        if ($this->laravel->environment('production')) {
            $this->error('The booking race-check command cannot be run in production.');

            return self::FAILURE;
        }

        $capacity = max(1, (int) $this->option('capacity'));
        $attempts = max($capacity, (int) $this->option('attempts'));

        $this->info("Starting concurrent booking race check: capacity={$capacity}, attempts={$attempts}...");

        $createdUserIds = [];
        $drive = null;
        $slot = null;

        try {
            // Enable WAL mode on SQLite if applicable for higher concurrency throughput
            if (DB::connection()->getDriverName() === 'sqlite') {
                try {
                    DB::statement('PRAGMA journal_mode=WAL;');
                    DB::statement('PRAGMA busy_timeout=10000;');
                } catch (\Throwable) {
                    // Ignore if pragma not permitted
                }
            }

            // Find or create an admin/staff user to create the drive
            $creator = User::whereIn('role', [Role::Admin->value, Role::Staff->value])->first()
                ?? User::create([
                    'name' => 'Temporary Race Staff',
                    'email' => 'race-staff-'.Str::random(8).'@donorlink.test',
                    'role' => Role::Staff,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]);

            if (! in_array($creator->id, $createdUserIds, true) && Str::startsWith($creator->email, 'race-staff-')) {
                $createdUserIds[] = $creator->id;
            }

            // 2. Create a temporary published drive
            $tomorrowEat = DateHelper::today()->addDays(2);
            $drive = Drive::create([
                'title' => '[TEMP RACE CHECK] Concurrent Drive '.Str::random(6),
                'description' => 'Temporary blood drive for concurrency testing.',
                'venue' => 'Race Check Testing Center',
                'address' => 'Kampala Central',
                'starts_on' => $tomorrowEat->toDateString(),
                'ends_on' => $tomorrowEat->addDays(1)->toDateString(),
                'status' => DriveStatus::Published,
                'created_by' => $creator->id,
            ]);

            // Create one slot with the given capacity starting well outside the cutoff
            $slotStartsAt = Carbon::now('UTC')->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);
            $slotEndsAt = $slotStartsAt->copy()->addMinutes(30);

            $slot = Slot::create([
                'drive_id' => $drive->id,
                'starts_at' => $slotStartsAt,
                'ends_at' => $slotEndsAt,
                'capacity' => $capacity,
                'booked_count' => 0,
            ]);

            // Create temporary verified voluntary donors
            $this->line("Creating {$attempts} temporary verified voluntary donors...");
            for ($i = 0; $i < $attempts; $i++) {
                $donor = User::forceCreate([
                    'name' => "Race Donor {$i} ".Str::random(5),
                    'email' => "race-donor-{$i}-".Str::random(8).'@donorlink.test',
                    'role' => Role::Donor,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]);
                $createdUserIds[] = $donor->id;
            }

            $this->line("Dispatching {$attempts} booking attempts truly in parallel via Concurrency facade...");

            $slotId = $slot->id;
            $tasks = [];
            foreach ($createdUserIds as $donorId) {
                if ($donorId === $creator->id && Str::startsWith($creator->email, 'race-staff-')) {
                    continue;
                }

                $tasks[] = static function () use ($donorId, $slotId): array {
                    if (DB::connection()->getDriverName() === 'sqlite') {
                        try {
                            DB::statement('PRAGMA busy_timeout=15000;');
                        } catch (\Throwable) {
                        }
                    }

                    $maxAttempts = 5;
                    for ($try = 1; $try <= $maxAttempts; $try++) {
                        try {
                            /** @var User $donor */
                            $donor = User::findOrFail($donorId);
                            /** @var Slot $slotInstance */
                            $slotInstance = Slot::findOrFail($slotId);

                            app(BookingService::class)->book($donor, $slotInstance);

                            return ['status' => 'success', 'donor_id' => $donorId];
                        } catch (SlotFullException $e) {
                            return ['status' => 'full', 'donor_id' => $donorId];
                        } catch (\Throwable $e) {
                            if ($try < $maxAttempts && str_contains($e->getMessage(), 'database is locked')) {
                                usleep(random_int(20000, 100000));

                                continue;
                            }

                            return ['status' => 'error', 'message' => $e->getMessage(), 'donor_id' => $donorId];
                        }
                    }

                    return ['status' => 'error', 'message' => 'Exhausted retry attempts due to lock', 'donor_id' => $donorId];
                };
            }

            // 3. Fire the attempts truly in parallel using Laravel's Concurrency facade
            /** @var array<int, array{status: string, donor_id?: int, message?: string}> $results */
            $results = Concurrency::run($tasks);

            $successCount = 0;
            $fullCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($results as $res) {
                if (($res['status'] ?? '') === 'success') {
                    $successCount++;
                } elseif (($res['status'] ?? '') === 'full') {
                    $fullCount++;
                } else {
                    $errorCount++;
                    $errors[] = $res['message'] ?? 'Unknown error';
                }
            }

            // Fresh database reads
            $slot->refresh();
            $actualBookedCount = $slot->booked_count;
            $actualAppointmentsCount = Appointment::query()
                ->where('slot_id', $slotId)
                ->where('status', AppointmentStatus::Booked->value)
                ->count();

            $this->line("Results: successes={$successCount}, slot_full={$fullCount}, errors={$errorCount}");
            $this->line("Database State: slot.booked_count={$actualBookedCount}, booked_appointments_count={$actualAppointmentsCount}");

            if ($errorCount > 0) {
                $this->warn('Encountered non-capacity errors: '.implode(', ', array_slice($errors, 0, 3)));
            }

            // 4. Asserts that exactly capacity bookings succeeded, booked_count equals capacity,
            // and the number of booked appointments equals capacity.
            $pass = ($successCount === $capacity)
                && ($actualBookedCount === $capacity)
                && ($actualAppointmentsCount === $capacity);

            if ($pass) {
                $this->info('PASS');

                return self::SUCCESS;
            }

            $this->error("FAIL: Expected exactly {$capacity} successes and booked count, but got {$successCount} successes, booked_count={$actualBookedCount}, appointments={$actualAppointmentsCount}.");

            return self::FAILURE;
        } finally {
            // 5. Clean up everything created
            $this->line('Cleaning up temporary test records...');
            if ($slot && $slot->exists) {
                Appointment::where('slot_id', $slot->id)->delete();
                $slot->delete();
            }

            if ($drive && $drive->exists) {
                $drive->delete();
            }

            if (! empty($createdUserIds)) {
                User::whereIn('id', $createdUserIds)->delete();
            }
        }
    }
}
