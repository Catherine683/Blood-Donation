<?php

declare(strict_types=1);

use App\Enums\DriveStatus;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\DriveService;
use App\Services\SlotGenerator;
use App\Services\SlotService;
use App\Support\DateHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('staff and admin can access staff drive routes, while donors and guests cannot', function () {
    $guest = null;
    $donor = User::factory()->donor()->create();
    $staff = User::factory()->staff()->create();
    $admin = User::factory()->admin()->create();

    $drive = Drive::factory()->draft()->create(['created_by' => $staff->id]);

    // Guests redirected to login
    $this->get(route('staff.drives'))->assertRedirect(route('login'));
    $this->get(route('staff.drives.create'))->assertRedirect(route('login'));
    $this->get(route('staff.drives.show', $drive))->assertRedirect(route('login'));

    // Donors get 403 Forbidden
    $this->actingAs($donor)->get(route('staff.drives'))->assertForbidden();
    $this->actingAs($donor)->get(route('staff.drives.create'))->assertForbidden();
    $this->actingAs($donor)->get(route('staff.drives.show', $drive))->assertForbidden();

    // Staff can access
    $this->actingAs($staff)->get(route('staff.drives'))->assertOk();
    $this->actingAs($staff)->get(route('staff.drives.create'))->assertOk();
    $this->actingAs($staff)->get(route('staff.drives.show', $drive))->assertOk();

    // Admin can access
    $this->actingAs($admin)->get(route('staff.drives'))->assertOk();
    $this->actingAs($admin)->get(route('staff.drives.create'))->assertOk();
    $this->actingAs($admin)->get(route('staff.drives.show', $drive))->assertOk();
});

test('publish is refused without an upcoming slot and the reason is shown', function () {
    $staff = User::factory()->staff()->create();
    $driveService = app(DriveService::class);

    // Drive with no slots
    $drive = Drive::factory()->draft()->create([
        'created_by' => $staff->id,
        'starts_on' => DateHelper::today()->addDays(2)->toDateString(),
        'ends_on' => DateHelper::today()->addDays(3)->toDateString(),
    ]);

    try {
        $driveService->publish($drive);
        $this->fail('Expected ValidationException when publishing drive without upcoming slots.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('publish')
            ->and($e->errors()['publish'][0])->toContain('at least one upcoming slot');
    }

    // Now add an upcoming slot and assert publish succeeds
    $slotService = app(SlotService::class);
    $slotService->create($drive, [
        'date' => DateHelper::today()->addDays(2)->toDateString(),
        'start_time' => '10:00',
        'end_time' => '10:30',
        'capacity' => 10,
    ]);

    $publishedDrive = $driveService->publish($drive);
    expect($publishedDrive->status)->toBe(DriveStatus::Published);
});

test('drive date rules: end before start and past start date are rejected', function () {
    $staff = User::factory()->staff()->create();
    $driveService = app(DriveService::class);

    $todayEat = DateHelper::today();

    // End before start rejected
    try {
        $driveService->create([
            'title' => 'Test Invalid Dates Drive',
            'venue' => 'Test Venue',
            'starts_on' => $todayEat->addDays(5)->toDateString(),
            'ends_on' => $todayEat->addDays(2)->toDateString(),
        ], $staff);
        $this->fail('Expected ValidationException for end date before start date.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('ends_on');
    }

    // Past start date rejected for new drive
    try {
        $driveService->create([
            'title' => 'Test Past Drive',
            'venue' => 'Test Venue',
            'starts_on' => $todayEat->subDays(2)->toDateString(),
            'ends_on' => $todayEat->addDays(2)->toDateString(),
        ], $staff);
        $this->fail('Expected ValidationException for past start date.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('starts_on');
    }
});

test('shortening a drive date range that excludes an existing slot is refused', function () {
    $staff = User::factory()->staff()->create();
    $driveService = app(DriveService::class);
    $slotService = app(SlotService::class);

    $todayEat = DateHelper::today();

    $drive = $driveService->create([
        'title' => 'Date Shorten Test Drive',
        'venue' => 'Test Venue',
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(6)->toDateString(),
    ], $staff);

    // Create a slot on day 5
    $slotService->create($drive, [
        'date' => $todayEat->addDays(5)->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'capacity' => 10,
    ]);

    // Try shortening ends_on to day 4 (which excludes day 5 slot)
    try {
        $driveService->update($drive, [
            'ends_on' => $todayEat->addDays(4)->toDateString(),
        ]);
        $this->fail('Expected ValidationException when shortening date range excludes existing slot.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('dates');
    }

    // Shortening to day 5 is allowed
    $updated = $driveService->update($drive, [
        'ends_on' => $todayEat->addDays(5)->toDateString(),
    ]);
    expect($updated->ends_on->toDateString())->toBe($todayEat->addDays(5)->toDateString());
});

test('only draft drives can be deleted; published and closed cannot', function () {
    $staff = User::factory()->staff()->create();
    $driveService = app(DriveService::class);

    $draftDrive = Drive::factory()->draft()->create(['created_by' => $staff->id]);
    $publishedDrive = Drive::factory()->published()->create(['created_by' => $staff->id]);
    $closedDrive = Drive::factory()->closed()->create(['created_by' => $staff->id]);

    // Delete draft drive succeeds
    $driveService->delete($draftDrive);
    expect(Drive::find($draftDrive->id))->toBeNull();

    // Delete published drive throws
    try {
        $driveService->delete($publishedDrive);
        $this->fail('Expected error deleting published drive');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('status');
    }

    // Delete closed drive throws
    try {
        $driveService->delete($closedDrive);
        $this->fail('Expected error deleting closed drive');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('status');
    }
});

test('close stops booking and closed drive can be reopened if not past', function () {
    $staff = User::factory()->staff()->create();
    $driveService = app(DriveService::class);

    $todayEat = DateHelper::today();

    $drive = Drive::factory()->published()->create([
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(4)->toDateString(),
    ]);

    // Close
    $driveService->close($drive);
    expect($drive->fresh()->status)->toBe(DriveStatus::Closed);

    // Reopen
    $driveService->reopen($drive);
    expect($drive->fresh()->status)->toBe(DriveStatus::Published);

    // Past closed drive cannot be reopened
    $pastDrive = Drive::factory()->closed()->past()->create(['created_by' => $staff->id]);
    try {
        $driveService->reopen($pastDrive);
        $this->fail('Expected error reopening past drive');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('status');
    }
});

test('slot rules: outside drive dates, capacity bounds, unique start, and past slots are rejected', function () {
    $staff = User::factory()->staff()->create();
    $slotService = app(SlotService::class);

    $todayEat = DateHelper::today();

    $drive = Drive::factory()->draft()->create([
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(4)->toDateString(),
    ]);

    // 1. Outside drive dates rejected
    try {
        $slotService->create($drive, [
            'date' => $todayEat->addDays(5)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'capacity' => 10,
        ]);
        $this->fail('Expected rejection for slot outside drive dates');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('dates');
    }

    // 2. Capacity bounds enforced (1 to 100)
    try {
        $slotService->create($drive, [
            'date' => $todayEat->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'capacity' => 0,
        ]);
        $this->fail('Expected rejection for capacity < 1');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('capacity');
    }

    try {
        $slotService->create($drive, [
            'date' => $todayEat->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'capacity' => 101,
        ]);
        $this->fail('Expected rejection for capacity > 100');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('capacity');
    }

    // 3. Create valid slot
    $slot1 = $slotService->create($drive, [
        'date' => $todayEat->addDays(2)->toDateString(),
        'start_time' => '10:00',
        'end_time' => '10:30',
        'capacity' => 15,
    ]);
    expect($slot1)->not->toBeNull();

    // 4. Unique start time enforced for same drive
    try {
        $slotService->create($drive, [
            'date' => $todayEat->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:45',
            'capacity' => 20,
        ]);
        $this->fail('Expected rejection for duplicate start time');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('starts_at');
    }
});

test('guards using booked_count: time changes, deletion and capacity reduction below booked_count are refused, while capacity increase is allowed', function () {
    $staff = User::factory()->staff()->create();
    $slotService = app(SlotService::class);

    $todayEat = DateHelper::today();

    $drive = Drive::factory()->draft()->create([
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);

    $slot = $slotService->create($drive, [
        'date' => $todayEat->addDays(2)->toDateString(),
        'start_time' => '11:00',
        'end_time' => '11:30',
        'capacity' => 10,
    ]);

    // Simulate booked donors
    $slot->booked_count = 4;
    $slot->save();

    // 1. Changing start or end time is refused
    try {
        $slotService->update($slot, [
            'date' => $todayEat->addDays(2)->toDateString(),
            'start_time' => '11:15',
            'end_time' => '11:45',
        ]);
        $this->fail('Expected error changing time of booked slot');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('booked_count');
    }

    // 2. Deleting slot is refused
    try {
        $slotService->delete($slot);
        $this->fail('Expected error deleting booked slot');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('booked_count');
    }

    // 3. Reducing capacity below booked_count (e.g. 3 < 4) is refused
    try {
        $slotService->update($slot, [
            'capacity' => 3,
        ]);
        $this->fail('Expected error reducing capacity below booked count');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('capacity');
    }

    // 4. Increasing capacity (e.g. to 20) is allowed
    $updated = $slotService->update($slot, [
        'capacity' => 20,
    ]);
    expect($updated->capacity)->toBe(20)
        ->and($updated->remaining)->toBe(16); // 20 - 4 = 16
});

test('generator: creates slots in EAT stored as UTC, respects break window, skips collisions, caps at 200, and is atomic', function () {
    $staff = User::factory()->staff()->create();
    $generator = app(SlotGenerator::class);
    $todayEat = DateHelper::today();

    $drive = Drive::factory()->draft()->create([
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(3)->toDateString(),
        'ends_on' => $todayEat->addDays(4)->toDateString(),
    ]);

    $dates = [$todayEat->addDays(3)->toDateString(), $todayEat->addDays(4)->toDateString()];

    // Generate preview
    $preview = $generator->preview($drive, [
        'dates' => $dates,
        'start_time' => '09:00',
        'end_time' => '12:00',
        'slot_length' => 30,
        'capacity' => 10,
        'break_start_time' => '10:00',
        'break_end_time' => '10:30',
    ]);

    // 09:00-09:30, 09:30-10:00, (10:00-10:30 break skipped), 10:30-11:00, 11:00-11:30, 11:30-12:00
    // 5 slots per day * 2 days = 10 candidate slots. 1 break slot skipped per day * 2 days = 2 break skipped.
    expect($preview['total_to_create'])->toBe(10)
        ->and($preview['skipped_breaks'])->toBe(2);

    // Save generator run
    $result = $generator->generate($drive, [
        'dates' => $dates,
        'start_time' => '09:00',
        'end_time' => '12:00',
        'slot_length' => 30,
        'capacity' => 10,
        'break_start_time' => '10:00',
        'break_end_time' => '10:30',
    ]);

    expect($result['created'])->toBe(10)
        ->and($drive->slots()->count())->toBe(10);

    // Duplicate run: all 10 collide and are skipped!
    $dupResult = $generator->generate($drive, [
        'dates' => $dates,
        'start_time' => '09:00',
        'end_time' => '12:00',
        'slot_length' => 30,
        'capacity' => 10,
        'break_start_time' => '10:00',
        'break_end_time' => '10:30',
    ]);

    expect($dupResult['created'])->toBe(0)
        ->and($dupResult['skipped_collisions'])->toBe(10);

    // 200 cap enforcement test
    // A drive spanning 30 days with 10-minute slots would generate thousands of slots
    $bigDrive = Drive::factory()->draft()->create([
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(5)->toDateString(),
        'ends_on' => $todayEat->addDays(15)->toDateString(),
    ]);

    $tenDays = [];
    for ($i = 5; $i <= 14; $i++) {
        $tenDays[] = $todayEat->addDays($i)->toDateString();
    }

    try {
        $generator->preview($bigDrive, [
            'dates' => $tenDays,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'slot_length' => 10, // 60 slots per day * 10 = 600 slots
            'capacity' => 10,
        ]);
        $this->fail('Expected cap of 200 slots to throw exception');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('slots')
            ->and($e->errors()['slots'][0])->toContain('200');
    }
});

test('timezone: a slot entered as 09:00 EAT is stored as 06:00 UTC and displays as 09:00', function () {
    $staff = User::factory()->staff()->create();
    $slotService = app(SlotService::class);
    $todayEat = DateHelper::today();

    $drive = Drive::factory()->draft()->create([
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);

    $slot = $slotService->create($drive, [
        'date' => $todayEat->addDays(2)->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'capacity' => 10,
    ]);

    // Raw UTC database values
    $raw = DB::table('slots')->where('id', $slot->id)->first();
    expect($raw->starts_at)->toContain('06:00:00')
        ->and($raw->ends_at)->toContain('06:30:00');

    // Display helper converts back to 09:00 EAT
    expect(DateHelper::formatTime($slot->starts_at))->toBe('09:00')
        ->and(DateHelper::formatTime($slot->ends_at))->toBe('09:30')
        ->and(DateHelper::formatRange($slot->starts_at, $slot->ends_at))->toBe('09:00 – 09:30 EAT');
});

test('/drives lists only published, not-ended drives, hides drafts and closed, and shows spots left', function () {
    $staff = User::factory()->staff()->create();
    $todayEat = DateHelper::today();

    // 1. Published upcoming drive with 20 spots
    $pubUpcoming = Drive::factory()->published()->create([
        'title' => 'Visible Published Upcoming Drive',
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);
    Slot::factory()->create([
        'drive_id' => $pubUpcoming->id,
        'starts_at' => DateHelper::toUtc($todayEat->addDays(2), '09:00'),
        'ends_at' => DateHelper::toUtc($todayEat->addDays(2), '09:30'),
        'capacity' => 20,
        'booked_count' => 5, // remaining: 15
    ]);

    // 2. Draft drive (hidden from /drives)
    Drive::factory()->draft()->create([
        'title' => 'Hidden Draft Drive',
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);

    // 3. Closed drive (hidden from /drives)
    Drive::factory()->closed()->create([
        'title' => 'Hidden Closed Drive',
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);

    // 4. Past published drive (ended, hidden from /drives)
    Drive::factory()->published()->past()->create([
        'title' => 'Hidden Past Published Drive',
        'created_by' => $staff->id,
    ]);

    $response = $this->get(route('drives.index'));
    $response->assertOk();
    $response->assertSee('Visible Published Upcoming Drive');
    $response->assertSee('15 spots left');
    $response->assertDontSee('Hidden Draft Drive');
    $response->assertDontSee('Hidden Closed Drive');
    $response->assertDontSee('Hidden Past Published Drive');
});

test('query count for /drives with 20 seeded drives has no N+1', function () {
    $staff = User::factory()->staff()->create();
    $todayEat = DateHelper::today();

    // Seed 20 upcoming published drives with slots
    for ($i = 1; $i <= 20; $i++) {
        $drive = Drive::factory()->published()->create([
            'title' => "Performance Test Drive {$i}",
            'created_by' => $staff->id,
            'starts_on' => $todayEat->addDays($i)->toDateString(),
            'ends_on' => $todayEat->addDays($i + 1)->toDateString(),
        ]);

        Slot::factory()->create([
            'drive_id' => $drive->id,
            'starts_at' => DateHelper::toUtc($todayEat->addDays($i), '10:00'),
            'ends_at' => DateHelper::toUtc($todayEat->addDays($i), '10:30'),
            'capacity' => 10,
            'booked_count' => 0,
        ]);
    }

    // Measure database query count during /drives request
    DB::enableQueryLog();

    $response = $this->get(route('drives.index'));
    $response->assertOk();

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // 1 query for paginator count + 1 query for drives with subqueries = 2 queries total
    expect($queryCount)->toBeLessThanOrEqual(2);
});

test('/drives/{drive}: draft is 404 for guests and visible to staff, closed shows banner, and no booking controls exist', function () {
    $staff = User::factory()->staff()->create();
    $todayEat = DateHelper::today();

    $draftDrive = Drive::factory()->draft()->create([
        'title' => 'Draft Drive Preview Test',
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);

    // 1. Draft returns 404 for guests
    $this->get(route('drives.show', $draftDrive))->assertNotFound();

    // 2. Draft is accessible to staff with preview banner and link to staff portal
    $staffResponse = $this->actingAs($staff)->get(route('drives.show', $draftDrive));
    $staffResponse->assertOk();
    $staffResponse->assertSee('Draft Preview');
    $staffResponse->assertSee('Return to Staff Portal');

    // 3. Closed drive displays closed banner
    $closedDrive = Drive::factory()->closed()->create([
        'title' => 'Closed Drive Notice Test',
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);

    $closedResponse = $this->get(route('drives.show', $closedDrive));
    $closedResponse->assertOk();
    $closedResponse->assertSee('This drive is closed');

    // 4. No booking controls exist anywhere in the response
    $publishedDrive = Drive::factory()->published()->create([
        'title' => 'Public Readonly Inspection Drive',
        'created_by' => $staff->id,
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(3)->toDateString(),
    ]);
    Slot::factory()->create([
        'drive_id' => $publishedDrive->id,
        'starts_at' => DateHelper::toUtc($todayEat->addDays(2), '09:00'),
        'ends_at' => DateHelper::toUtc($todayEat->addDays(2), '09:30'),
        'capacity' => 10,
        'booked_count' => 0,
    ]);

    $publicResponse = $this->get(route('drives.show', $publishedDrive));
    $publicResponse->assertOk();

    // Assert strictly no booking words / buttons / forms
    $content = $publicResponse->getContent();
    expect($content)->not->toContain('Book appointment')
        ->not->toContain('Book Slot')
        ->not->toContain('Reserve')
        ->not->toContain('action="/book')
        ->not->toContain('name="appointment');
});

test('remaining capacity accessor never goes negative', function () {
    $slot = new Slot([
        'capacity' => 10,
        'booked_count' => 4,
    ]);
    expect($slot->remaining)->toBe(6);

    // Overbooked edge case
    $overbooked = new Slot([
        'capacity' => 5,
        'booked_count' => 8,
    ]);
    expect($overbooked->remaining)->toBe(0);
});
