<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Enums\DriveStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\DonorProfile;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\SlotGenerator;
use App\Support\DateHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for development only.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DatabaseSeeder is for development only and refuses to run in production.');
        }

        $this->command?->info('Seeding development users and donor profiles...');

        // Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@donorlink.test'],
            [
                'name' => 'UBTS Administrator',
                'phone' => '+256756788112',
                'role' => Role::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // Staff User
        $staff = User::updateOrCreate(
            ['email' => 'staff@donorlink.test'],
            [
                'name' => 'UBTS Staff Nurse',
                'phone' => '+256700000002',
                'role' => Role::Staff,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // Donor User
        $donor = User::updateOrCreate(
            ['email' => 'donor@donorlink.test'],
            [
                'name' => 'Grace Atuhaire',
                'phone' => '+256700000003',
                'date_of_birth' => '1996-04-12',
                'consent_given_at' => now(),
                'role' => Role::Donor,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        DonorProfile::updateOrCreate(
            ['user_id' => $donor->id],
            [
                'blood_group' => 'O+',
                'last_donated_on' => '2026-06-15',
                'notes_for_staff' => 'Regular voluntary donor at Nakasero headquarters.',
            ]
        );

        $this->command?->info('Seeding Phase 2 demo blood drives and time slots...');

        $today = DateHelper::today();
        $generator = app(SlotGenerator::class);

        // 1. One past closed drive
        $pastDrive = Drive::updateOrCreate(
            ['title' => '[DEMO] Sample Past Blood Donation Camp (Closed)'],
            [
                'description' => 'Demonstration sample historical blood donation drive that is closed.',
                'venue' => 'Demo Community Centre A',
                'address' => 'Demo Plot 101, Old Port Road, Kampala',
                'starts_on' => $today->subDays(15)->toDateString(),
                'ends_on' => $today->subDays(14)->toDateString(),
                'status' => DriveStatus::Closed,
                'created_by' => $staff->id,
            ]
        );
        $this->command?->info(" - Created past closed drive: {$pastDrive->title}");

        // 2. Published upcoming drive 1 (with generated slots, no break window)
        $upcoming1 = Drive::updateOrCreate(
            ['title' => '[DEMO] Central Kampala Blood Drive (Sample Schedule)'],
            [
                'description' => 'Demonstration voluntary blood donation drive at central sample location. Please arrive on time and bring identification.',
                'venue' => 'Demo City Health Ground',
                'address' => 'Demo Sector 4, Jinja Road, Kampala',
                'starts_on' => $today->toDateString(),
                'ends_on' => $today->addDays(1)->toDateString(),
                'status' => DriveStatus::Published,
                'created_by' => $staff->id,
            ]
        );
        if ($upcoming1->slots()->count() === 0) {
            $gen1 = $generator->generate($upcoming1, [
                'dates' => [$today->toDateString(), $today->addDays(1)->toDateString()],
                'start_time' => '09:00',
                'end_time' => '13:00',
                'slot_length' => 30,
                'capacity' => 12,
            ]);
            $this->command?->info(" - Created published drive with {$gen1['created']} slots: {$upcoming1->title}");
        }

        // 3. Published upcoming drive 2 (with generated slots, with a break window)
        $upcoming2 = Drive::updateOrCreate(
            ['title' => '[DEMO] Makerere Innovation Blood Camp (Sample with Break)'],
            [
                'description' => 'Demonstration full-day voluntary blood donation camp featuring a scheduled lunch break window (13:00 - 14:00 EAT).',
                'venue' => 'Demo University Hall B',
                'address' => 'Demo Campus Avenue, Makerere, Kampala',
                'starts_on' => $today->addDays(5)->toDateString(),
                'ends_on' => $today->addDays(6)->toDateString(),
                'status' => DriveStatus::Published,
                'created_by' => $staff->id,
            ]
        );
        if ($upcoming2->slots()->count() === 0) {
            $gen2 = $generator->generate($upcoming2, [
                'dates' => [$today->addDays(5)->toDateString(), $today->addDays(6)->toDateString()],
                'start_time' => '09:00',
                'end_time' => '16:00',
                'slot_length' => 30,
                'capacity' => 10,
                'break_start_time' => '13:00',
                'break_end_time' => '14:00',
            ]);
            $this->command?->info(" - Created published drive with break window ({$gen2['created']} slots, {$gen2['skipped_breaks']} break slots skipped): {$upcoming2->title}");
        }

        // 4. One draft drive with no slots
        $draftDrive = Drive::updateOrCreate(
            ['title' => '[DEMO] Kololo Community Drive (Draft - No Slots)'],
            [
                'description' => 'Demonstration draft drive with no time slots yet. Intended for testing draft state behavior.',
                'venue' => 'Demo Community Ground C',
                'address' => 'Demo Lane 12, Kololo, Kampala',
                'starts_on' => $today->addDays(10)->toDateString(),
                'ends_on' => $today->addDays(11)->toDateString(),
                'status' => DriveStatus::Draft,
                'created_by' => $staff->id,
            ]
        );
        $this->command?->info(" - Created draft drive with 0 slots: {$draftDrive->title}");

        // 5. Phase 3: Sample active appointment for demo donor
        $this->command?->info('Seeding Phase 3 demo appointment for donor@donorlink.test...');
        $sampleSlot = $upcoming1->slots()->orderBy('starts_at')->first();
        if ($sampleSlot && $donor->appointments()->where('status', AppointmentStatus::Booked->value)->count() === 0) {
            Appointment::updateOrCreate(
                [
                    'user_id' => $donor->id,
                    'slot_id' => $sampleSlot->id,
                ],
                [
                    'status' => AppointmentStatus::Booked,
                    'cancelled_at' => null,
                ]
            );
            $sampleSlot->update(['booked_count' => 1]);
            $this->command?->info(" - Created sample booked appointment for {$donor->name} ({$donor->email})");
        }

        // 6. Phase 5: Past demo drive outcomes & walk-ins
        $this->command?->info('Seeding Phase 5 past demo drive attendance records and outcomes...');

        if ($pastDrive->slots()->count() === 0) {
            $pastSlotTimes = [
                ['09:00', '09:30'],
                ['09:30', '10:00'],
                ['10:00', '10:30'],
                ['10:30', '11:00'],
            ];

            foreach ($pastSlotTimes as $times) {
                Slot::create([
                    'drive_id' => $pastDrive->id,
                    'starts_at' => Carbon::parse($today->subDays(15)->toDateString().' '.$times[0], 'Africa/Kampala')->setTimezone('UTC'),
                    'ends_at' => Carbon::parse($today->subDays(15)->toDateString().' '.$times[1], 'Africa/Kampala')->setTimezone('UTC'),
                    'capacity' => 8,
                    'booked_count' => 1,
                ]);
            }
            $this->command?->info(' - Created 4 historical slots for past drive');
        }

        $pastSlots = $pastDrive->slots()->orderBy('starts_at')->get();
        $slot1 = $pastSlots->get(0);
        $slot2 = $pastSlots->get(1);
        $slot3 = $pastSlots->get(2);
        $slot4 = $pastSlots->get(3);

        // A. Donated record for main demo donor (Grace Atuhaire)
        if ($slot1) {
            $pastAppt1 = Appointment::updateOrCreate(
                ['user_id' => $donor->id, 'slot_id' => $slot1->id],
                [
                    'status' => AppointmentStatus::Attended,
                    'checked_in_at' => $today->subDays(15)->setTime(9, 5),
                ]
            );
            DonationRecord::updateOrCreate(
                ['user_id' => $donor->id, 'drive_id' => $pastDrive->id],
                [
                    'appointment_id' => $pastAppt1->id,
                    'outcome' => DonationOutcome::Donated,
                    'recorded_by' => $staff->id,
                    'recorded_at' => $today->subDays(15)->setTime(9, 30),
                    'staff_note' => 'Completed standard whole blood donation without incident.',
                ]
            );
            // Refresh derived last_donated_on date to match this record
            DonorProfile::refreshLastDonation($donor);
            $this->command?->info(" - [DEMO] Created 'Donated' record for {$donor->name}; derived last_donated_on updated.");
        }

        // B. Deferred record for demo donor 2 (Moses Kigozi)
        $donor2 = User::updateOrCreate(
            ['email' => 'donor2@donorlink.test'],
            [
                'name' => 'Moses Kigozi',
                'phone' => '+256700000004',
                'date_of_birth' => '1992-08-20',
                'consent_given_at' => now(),
                'role' => Role::Donor,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
        DonorProfile::updateOrCreate(
            ['user_id' => $donor2->id],
            [
                'blood_group' => 'A+',
                'last_donated_on' => null,
                'notes_for_staff' => 'First-time voluntary donor.',
            ]
        );
        if ($slot2) {
            $pastAppt2 = Appointment::updateOrCreate(
                ['user_id' => $donor2->id, 'slot_id' => $slot2->id],
                [
                    'status' => AppointmentStatus::Attended,
                    'checked_in_at' => $today->subDays(15)->setTime(9, 35),
                ]
            );
            DonationRecord::updateOrCreate(
                ['user_id' => $donor2->id, 'drive_id' => $pastDrive->id],
                [
                    'appointment_id' => $pastAppt2->id,
                    'outcome' => DonationOutcome::Deferred,
                    'recorded_by' => $staff->id,
                    'recorded_at' => $today->subDays(15)->setTime(9, 50),
                    'staff_note' => 'Advised to return after sufficient personal hydration and rest.',
                ]
            );
            DonorProfile::refreshLastDonation($donor2);
            $this->command?->info(" - [DEMO] Created 'Deferred' record for {$donor2->name}.");
        }

        // C. No-show appointment for demo donor 3 (Amina Namubiru)
        $donor3 = User::updateOrCreate(
            ['email' => 'donor3@donorlink.test'],
            [
                'name' => 'Amina Namubiru',
                'phone' => '+256700000005',
                'role' => Role::Donor,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
        if ($slot3) {
            Appointment::updateOrCreate(
                ['user_id' => $donor3->id, 'slot_id' => $slot3->id],
                [
                    'status' => AppointmentStatus::NoShow,
                    'checked_in_at' => null,
                ]
            );
            $this->command?->info(" - [DEMO] Created 'No-show' appointment for {$donor3->name}.");
        }

        // D. Cancelled booking appointment for demo donor 4 (David Ochieng)
        $donor4 = User::updateOrCreate(
            ['email' => 'donor4@donorlink.test'],
            [
                'name' => 'David Ochieng',
                'phone' => '+256700000006',
                'role' => Role::Donor,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
        if ($slot4) {
            Appointment::updateOrCreate(
                ['user_id' => $donor4->id, 'slot_id' => $slot4->id],
                [
                    'status' => AppointmentStatus::Cancelled,
                    'cancelled_at' => $today->subDays(16)->setTime(14, 0),
                ]
            );
            $this->command?->info(" - [DEMO] Created 'Cancelled' appointment for {$donor4->name}.");
        }

        // E. Walk-in donation record for demo donor 5 (Sarah Nassali)
        $donor5 = User::updateOrCreate(
            ['email' => 'donor5@donorlink.test'],
            [
                'name' => 'Sarah Nassali',
                'phone' => '+256700000007',
                'role' => Role::Donor,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
        DonorProfile::updateOrCreate(
            ['user_id' => $donor5->id],
            ['blood_group' => 'B+']
        );
        DonationRecord::updateOrCreate(
            ['user_id' => $donor5->id, 'drive_id' => $pastDrive->id],
            [
                'appointment_id' => null, // Walk-in
                'outcome' => DonationOutcome::Donated,
                'recorded_by' => $staff->id,
                'recorded_at' => $today->subDays(15)->setTime(11, 15),
                'staff_note' => 'Voluntary walk-in donor registered at on-site mobile reception.',
            ]
        );
        DonorProfile::refreshLastDonation($donor5);
        $this->command?->info(" - [DEMO] Created 'Walk-in' donation record for {$donor5->name}.");

        // ---------------------------------------------------------------------
        // Phase 6: Analytics Demo Drives (6 past drives across 90 days + low-fill upcoming drive)
        // ---------------------------------------------------------------------
        $this->command?->info('Seeding Phase 6 demo historical drives and analytics test cases...');

        // Upcoming Drive 2: Starts in 2 days, low fill rate (< 25%), no announcement sent
        $upcomingLowFill = Drive::updateOrCreate(
            ['title' => '[DEMO] Ntinda Shopping Centre Mobile Drive (Low Fill)'],
            [
                'description' => 'Upcoming published mobile drive scheduled in Ntinda. Low registration turnout, requiring staff marketing attention.',
                'venue' => 'Ntinda Complex Mall Parking',
                'address' => 'Plot 44, Ntinda Road, Kampala',
                'starts_on' => $today->addDays(2)->toDateString(),
                'ends_on' => $today->addDays(3)->toDateString(),
                'status' => DriveStatus::Published,
                'created_by' => $staff->id,
            ]
        );

        if ($upcomingLowFill->slots()->count() === 0) {
            $lowSlots = [
                ['starts_at' => $today->addDays(2)->setTime(9, 0)->setTimezone('UTC'), 'ends_at' => $today->addDays(2)->setTime(10, 0)->setTimezone('UTC'), 'capacity' => 10],
                ['starts_at' => $today->addDays(2)->setTime(10, 0)->setTimezone('UTC'), 'ends_at' => $today->addDays(2)->setTime(11, 0)->setTimezone('UTC'), 'capacity' => 10],
            ];
            foreach ($lowSlots as $sData) {
                Slot::create(array_merge($sData, ['drive_id' => $upcomingLowFill->id, 'booked_count' => 0]));
            }

            // Book only 2 appointments total (2 / 20 = 10% fill rate, strictly < 25%)
            $firstLowSlot = $upcomingLowFill->slots()->first();
            Appointment::create([
                'user_id' => $donor->id,
                'slot_id' => $firstLowSlot->id,
                'status' => AppointmentStatus::Booked,
            ]);
            $firstLowSlot->increment('booked_count');

            Appointment::create([
                'user_id' => $donor2->id,
                'slot_id' => $firstLowSlot->id,
                'status' => AppointmentStatus::Booked,
            ]);
            $firstLowSlot->increment('booked_count');

            $this->command?->info(" - Created upcoming low-fill drive with 10% bookings (no announcement): {$upcomingLowFill->title}");
        }

        // 5 Additional Past Drives spread across the last 90 days (Days: 25, 40, 55, 70, 85 ago)
        $pastDrivesData = [
            ['title' => '[DEMO] Entebbe Municipal Health Outreach (Closed)', 'days_ago' => 25, 'venue' => 'Entebbe Mayor\'s Gardens', 'capacity' => 15, 'booked' => 14, 'attended' => 12, 'donated' => 11, 'deferred' => 1, 'no_show' => 2, 'walk_ins' => 3],
            ['title' => '[DEMO] Mukono Community Health Centre Drive (Closed)', 'days_ago' => 40, 'venue' => 'Mukono Health Centre IV', 'capacity' => 20, 'booked' => 16, 'attended' => 13, 'donated' => 12, 'deferred' => 1, 'no_show' => 3, 'walk_ins' => 2],
            ['title' => '[DEMO] Jinja Regional Blood Donation Camp (Closed)', 'days_ago' => 55, 'venue' => 'Jinja Town Hall Grounds', 'capacity' => 25, 'booked' => 22, 'attended' => 18, 'donated' => 16, 'deferred' => 2, 'no_show' => 4, 'walk_ins' => 4],
            ['title' => '[DEMO] Kyambogo Technical Institute Blood Drive (Closed)', 'days_ago' => 70, 'venue' => 'Kyambogo Main Pavilion', 'capacity' => 30, 'booked' => 25, 'attended' => 20, 'donated' => 19, 'deferred' => 1, 'no_show' => 5, 'walk_ins' => 5],
            ['title' => '[DEMO] Ggaba Youth Centre Community Camp (Closed)', 'days_ago' => 85, 'venue' => 'Ggaba Community Hall', 'capacity' => 15, 'booked' => 12, 'attended' => 9, 'donated' => 8, 'deferred' => 1, 'no_show' => 3, 'walk_ins' => 1],
        ];

        foreach ($pastDrivesData as $pData) {
            $pDrive = Drive::updateOrCreate(
                ['title' => $pData['title']],
                [
                    'description' => 'Historical demonstration blood drive for period analytics and reporting validation.',
                    'venue' => $pData['venue'],
                    'address' => 'Kampala Region Outreach Sector',
                    'starts_on' => $today->subDays($pData['days_ago'])->toDateString(),
                    'ends_on' => $today->subDays($pData['days_ago'])->toDateString(),
                    'status' => DriveStatus::Closed,
                    'created_by' => $staff->id,
                ]
            );

            if ($pDrive->slots()->count() === 0) {
                $pSlot = Slot::create([
                    'drive_id' => $pDrive->id,
                    'starts_at' => $today->subDays($pData['days_ago'])->setTime(9, 0)->setTimezone('UTC'),
                    'ends_at' => $today->subDays($pData['days_ago'])->setTime(13, 0)->setTimezone('UTC'),
                    'capacity' => $pData['capacity'],
                    'booked_count' => $pData['booked'],
                ]);

                // Generate individual distinct donors for this historical drive
                // Donated appointments
                for ($i = 0; $i < $pData['donated']; $i++) {
                    $dUser = User::factory()->create([
                        'role' => Role::Donor,
                        'created_at' => $today->subDays($pData['days_ago'] + 5)->setTime(10, 0)->setTimezone('UTC'),
                    ]);
                    DonorProfile::create(['user_id' => $dUser->id]);
                    $dAppt = Appointment::create([
                        'user_id' => $dUser->id,
                        'slot_id' => $pSlot->id,
                        'status' => AppointmentStatus::Attended,
                        'checked_in_at' => $today->subDays($pData['days_ago'])->setTime(9, 15)->setTimezone('UTC'),
                    ]);
                    DonationRecord::create([
                        'user_id' => $dUser->id,
                        'drive_id' => $pDrive->id,
                        'appointment_id' => $dAppt->id,
                        'outcome' => DonationOutcome::Donated,
                        'recorded_by' => $staff->id,
                        'recorded_at' => $today->subDays($pData['days_ago'])->setTime(9, 35)->setTimezone('UTC'),
                        'staff_note' => 'Standard voluntary historical donation.',
                    ]);
                    DonorProfile::refreshLastDonation($dUser);
                }

                // Deferred appointments
                for ($i = 0; $i < $pData['deferred']; $i++) {
                    $dUser = User::factory()->create([
                        'role' => Role::Donor,
                        'created_at' => $today->subDays($pData['days_ago'] + 5)->setTime(10, 0)->setTimezone('UTC'),
                    ]);
                    DonorProfile::create(['user_id' => $dUser->id]);
                    $dAppt = Appointment::create([
                        'user_id' => $dUser->id,
                        'slot_id' => $pSlot->id,
                        'status' => AppointmentStatus::Attended,
                        'checked_in_at' => $today->subDays($pData['days_ago'])->setTime(10, 0)->setTimezone('UTC'),
                    ]);
                    DonationRecord::create([
                        'user_id' => $dUser->id,
                        'drive_id' => $pDrive->id,
                        'appointment_id' => $dAppt->id,
                        'outcome' => DonationOutcome::Deferred,
                        'recorded_by' => $staff->id,
                        'recorded_at' => $today->subDays($pData['days_ago'])->setTime(10, 20)->setTimezone('UTC'),
                        'staff_note' => 'Donor advised to rest and hydrate.',
                    ]);
                    DonorProfile::refreshLastDonation($dUser);
                }

                // No-show appointments
                for ($i = 0; $i < $pData['no_show']; $i++) {
                    $dUser = User::factory()->create([
                        'role' => Role::Donor,
                        'created_at' => $today->subDays($pData['days_ago'] + 5)->setTime(10, 0)->setTimezone('UTC'),
                    ]);
                    DonorProfile::create(['user_id' => $dUser->id]);
                    Appointment::create([
                        'user_id' => $dUser->id,
                        'slot_id' => $pSlot->id,
                        'status' => AppointmentStatus::NoShow,
                        'checked_in_at' => null,
                    ]);
                }

                // Walk-in donation records (no appointment)
                for ($i = 0; $i < $pData['walk_ins']; $i++) {
                    $dUser = User::factory()->create([
                        'role' => Role::Donor,
                        'created_at' => $today->subDays($pData['days_ago'] + 5)->setTime(10, 0)->setTimezone('UTC'),
                    ]);
                    DonorProfile::create(['user_id' => $dUser->id]);
                    DonationRecord::create([
                        'user_id' => $dUser->id,
                        'drive_id' => $pDrive->id,
                        'appointment_id' => null,
                        'outcome' => DonationOutcome::Donated,
                        'recorded_by' => $staff->id,
                        'recorded_at' => $today->subDays($pData['days_ago'])->setTime(11, 0)->setTimezone('UTC'),
                        'staff_note' => 'Walk-in voluntary donor on site.',
                    ]);
                    DonorProfile::refreshLastDonation($dUser);
                }

                $this->command?->info(" - Created past drive ({$pData['days_ago']}d ago): {$pDrive->title}");
            }
        }
    }
}
