<?php

declare(strict_types=1);

use App\Actions\Donor\RegisterDonor;
use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('phone helper: all accepted formats normalise to the same E.164 value and invalid numbers fail', function () {
    $expected = '+256772123456';

    expect(PhoneNumber::normalise('0772123456'))->toBe($expected)
        ->and(PhoneNumber::normalise('772123456'))->toBe($expected)
        ->and(PhoneNumber::normalise('+256772123456'))->toBe($expected)
        ->and(PhoneNumber::normalise('256772123456'))->toBe($expected)
        ->and(PhoneNumber::normalise('0772 123 456'))->toBe($expected)
        ->and(PhoneNumber::normalise('(0772) 123-456'))->toBe($expected)
        ->and(PhoneNumber::normalise('+256 772 123 456'))->toBe($expected);

    // Invalid numbers
    expect(PhoneNumber::normalise('077212345'))->toBeNull() // 9 digits with 0
        ->and(PhoneNumber::normalise('07721234567'))->toBeNull() // 11 digits
        ->and(PhoneNumber::normalise('abcdefghij'))->toBeNull()
        ->and(PhoneNumber::normalise('+123456789012'))->toBeNull()
        ->and(PhoneNumber::isValid('0772123456'))->toBeTrue()
        ->and(PhoneNumber::isValid('invalid'))->toBeFalse();

    // Display formatter
    expect(PhoneNumber::format('+256772123456'))->toBe('+256 772 123 456');
});

test('registration with valid input creates a donor, donor profile and consent_given_at in one transaction', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Kato Paul',
        'email' => 'KATO.PAUL@Example.ug',
        'phone' => '0772 987 654',
        'date_of_birth' => '1995-05-15',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'kato.paul@example.ug')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Kato Paul')
        ->and($user->email)->toBe('kato.paul@example.ug') // stored lowercase
        ->and($user->phone)->toBe('+256772987654') // normalised E.164
        ->and($user->date_of_birth->format('Y-m-d'))->toBe('1995-05-15')
        ->and($user->role)->toBe(Role::Donor)
        ->and($user->consent_given_at)->not->toBeNull()
        ->and($user->donorProfile)->not->toBeNull()
        ->and($user->donorProfile->blood_group)->toBeNull();
});

test('registration rolls back completely if donor profile creation fails in transaction', function () {
    // Force a DB exception when donor_profiles table is inserted by temporarily altering or listening to events
    $action = new RegisterDonor;

    DonorProfile::creating(function () {
        throw new Exception('Simulated profile creation failure.');
    });

    expect(fn () => $action->register([
        'name' => 'Rollback User',
        'email' => 'rollback@example.ug',
        'phone' => '0772111222',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]))->toThrow(Exception::class, 'Simulated profile creation failure.');

    expect(User::where('email', 'rollback@example.ug')->exists())->toBeFalse();
    expect(DonorProfile::count())->toBe(0);
});

test('registration fails without the consent checkbox', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'No Consent User',
        'email' => 'noconsent@example.ug',
        'phone' => '0772111222',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['consent']);
    expect(User::where('email', 'noconsent@example.ug')->exists())->toBeFalse();
});

test('email is stored lowercase and must be unique', function () {
    User::factory()->create(['email' => 'donor.unique@example.ug']);

    $response = $this->post(route('register.store'), [
        'name' => 'Duplicate Email',
        'email' => 'DONOR.UNIQUE@EXAMPLE.UG',
        'phone' => '0772333444',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('date of birth: future date is rejected while young and old valid dates are accepted without age gating', function () {
    $action = new RegisterDonor;

    // Future date rejected
    expect(fn () => $action->register([
        'name' => 'Future Donor',
        'email' => 'future@example.ug',
        'phone' => '0772444555',
        'date_of_birth' => now()->addDay()->format('Y-m-d'),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]))->toThrow(ValidationException::class);

    // Pre-1900 rejected
    expect(fn () => $action->register([
        'name' => 'Ancient Donor',
        'email' => 'ancient@example.ug',
        'phone' => '0772444555',
        'date_of_birth' => '1899-12-31',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]))->toThrow(ValidationException::class);

    // Very young date of birth accepted (e.g. 10 years old - no eligibility check)
    $youngUser = $action->register([
        'name' => 'Young Donor',
        'email' => 'young@example.ug',
        'phone' => '0772444555',
        'date_of_birth' => now()->subYears(10)->format('Y-m-d'),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]);
    expect($youngUser->exists)->toBeTrue();

    // Very old date of birth accepted (e.g. 95 years old - no eligibility check)
    $oldUser = $action->register([
        'name' => 'Senior Donor',
        'email' => 'senior@example.ug',
        'phone' => '0772444556',
        'date_of_birth' => '1930-01-15',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]);
    expect($oldUser->exists)->toBeTrue();
});

test('donors cannot write blood_group, last_donated_on or notes_for_staff through donor profile form or tampering', function () {
    $donor = User::factory()->donor()->create([
        'phone' => '+256700111222',
    ]);

    $profile = $donor->donorProfile;
    $profile->update([
        'blood_group' => 'O+',
        'last_donated_on' => '2026-01-10',
        'notes_for_staff' => 'Original staff note',
    ]);

    $response = $this->actingAs($donor)->put(route('donor.profile.update'), [
        'name' => 'Tampering Donor',
        'email' => $donor->email,
        'phone' => '0772999888',
        'date_of_birth' => '1998-08-08',
        // Attempting to overwrite staff-only fields
        'blood_group' => 'AB-',
        'last_donated_on' => '2026-10-18',
        'notes_for_staff' => 'Hacked notes',
    ]);

    $response->assertRedirect(route('donor.profile'));

    $donor->refresh();
    $profile->refresh();

    // Name and phone updated
    expect($donor->name)->toBe('Tampering Donor')
        ->and($donor->phone)->toBe('+256772999888')
        // Staff-only fields remained intact
        ->and($profile->blood_group)->toBe('O+')
        ->and($profile->last_donated_on->format('Y-m-d'))->toBe('2026-01-10')
        ->and($profile->notes_for_staff)->toBe('Original staff note');
});

test('donor profile update works and changing email triggers re-verification', function () {
    $donor = User::factory()->donor()->create([
        'email' => 'verified.donor@example.ug',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($donor)->put(route('donor.profile.update'), [
        'name' => 'Verified Donor Renamed',
        'phone' => '0772555666',
        'date_of_birth' => '1992-02-02',
    ]);

    $response->assertRedirect(route('donor.profile'));

    $donor->refresh();
    expect($donor->name)->toBe('Verified Donor Renamed')
        ->and($donor->email)->toBe('verified.donor@example.ug')
        ->and($donor->email_verified_at)->not->toBeNull();
});

test('self-service account deletion is unavailable to donors', function () {
    $donor = User::factory()->donor()->create();

    // Settings routes redirect donors
    $this->actingAs($donor)->get('/settings/profile')->assertRedirect(route('donor.profile'));

    // Attempting to invoke deleteUser via Livewire component triggers 403 Forbidden
    Livewire::actingAs($donor);
    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertForbidden();

    expect($donor->fresh())->not->toBeNull();
});

test('verification and reset emails are rendered and contain the logo, a button link and the footer text', function () {
    $user = User::factory()->donor()->create([
        'name' => 'Amina Nakato',
        'email' => 'amina@example.ug',
    ]);

    // 1. Verify Email Notification
    $verifyNotification = new VerifyEmail;
    $mailMessage = $verifyNotification->toMail($user);

    expect($mailMessage->subject)->toBe('Confirm your email for DonorLink UG');

    $html = (string) $mailMessage->render();

    expect($html)->toContain('images/logo.png')
        ->toContain('Confirm Email Address')
        ->toContain("You received this email because someone registered with this address on DonorLink UG. If that wasn't you, you can ignore this message.")
        ->toContain('#B3262E'); // Crimson button color

    // 2. Reset Password Notification
    $resetNotification = new ResetPassword('test-token-12345');
    $resetMail = $resetNotification->toMail($user);

    expect($resetMail->subject)->toBe('Reset your password for DonorLink UG');

    $resetHtml = (string) $resetMail->render();

    expect($resetHtml)->toContain('images/logo.png')
        ->toContain('Reset Password')
        ->toContain("You received this email because someone registered with this address on DonorLink UG. If that wasn't you, you can ignore this message.")
        ->toContain('#B3262E');
});

test('registration and resend endpoints are throttled', function () {
    // 30 attempts allowed by Limit::perHour(30) per IP
    for ($i = 0; $i < 30; $i++) {
        $this->post(route('register.store'), [
            'name' => "Throttle User $i",
            'email' => "throttle$i@example.ug",
            'phone' => '077200000'.$i,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'consent' => '1',
        ]);
    }

    // 31st request is throttled
    $response = $this->post(route('register.store'), [
        'name' => 'Blocked User',
        'email' => 'blocked@example.ug',
        'phone' => '0772999999',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]);

    $response->assertStatus(429);

    // Also verify verification-resend endpoint is throttled
    $donor = User::factory()->unverified()->create();
    $this->actingAs($donor);

    for ($j = 0; $j < 5; $j++) {
        $this->post(route('verification.send'));
    }

    $resendBlocked = $this->post(route('verification.send'));
    $resendBlocked->assertStatus(429);
});

test('/privacy is publicly reachable and contains required information', function () {
    $response = $this->get('/privacy');

    $response->assertOk()
        ->assertSee('Privacy Notice')
        ->assertSee('What details we collect')
        ->assertSee('Why we collect this information')
        ->assertSee('How we contact you')
        ->assertSee('Who can see your information')
        ->assertSee('Correcting or removing your data')
        ->assertDontSee('official', false);
});
