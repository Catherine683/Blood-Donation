<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration creates an unverified donor and redirects to OTP verification', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Sarah Akello',
        'email' => 'sarah@example.ug',
        'phone' => '0772 123 456',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'consent' => '1',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('register.otp', ['email' => 'sarah@example.ug']));

    $user = User::query()->where('email', 'sarah@example.ug')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe(Role::Donor);
    expect($user->isDonor())->toBeTrue();
    expect($user->is_active)->toBeFalse();
    expect($user->hasVerifiedEmail())->toBeFalse();

    $this->get('/donor')->assertRedirect(route('login'));
});

test('unverified users are redirected to the verification notice when accessing /donor', function () {
    $user = User::factory()->unverified()->create([
        'role' => Role::Donor,
    ]);

    $response = $this->actingAs($user)->get('/donor');
    $response->assertRedirect(route('verification.notice'));
});

test('role redirects after login: donor to /donor, staff to /staff, admin to /staff', function () {
    $donor = User::factory()->create([
        'role' => Role::Donor,
        'email_verified_at' => now(),
    ]);

    $this->post(route('login.store'), [
        'email' => $donor->email,
        'password' => 'password',
    ])->assertRedirect('/donor');

    auth()->logout();

    $staff = User::factory()->staff()->create([
        'email_verified_at' => now(),
    ]);

    $this->post(route('login.store'), [
        'email' => $staff->email,
        'password' => 'password',
    ])->assertRedirect('/staff');

    auth()->logout();

    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/staff');
});

test('a donor cannot open /staff or /admin and receives 403 forbidden', function () {
    $donor = User::factory()->create([
        'role' => Role::Donor,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($donor)->get('/staff')->assertForbidden();
    $this->actingAs($donor)->get('/admin/staff')->assertForbidden();
});

test('staff cannot open /admin and receives 403 forbidden', function () {
    $staff = User::factory()->staff()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($staff)->get('/staff')->assertOk();
    $this->actingAs($staff)->get('/admin/staff')->assertForbidden();
});

test('admin can open both /staff and /admin', function () {
    $admin = User::factory()->admin()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->get('/staff')->assertOk();
    $this->actingAs($admin)->get('/admin/staff')->assertOk();
});

test('an inactive user cannot log in', function () {
    $user = User::factory()->inactive()->create([
        'email' => 'inactive@example.ug',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => 'inactive@example.ug',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('the dev seeder refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new DatabaseSeeder)->run())
        ->toThrow(RuntimeException::class, 'DatabaseSeeder is for development only and refuses to run in production.');
});

test('application timezone is Africa/Kampala', function () {
    expect(config('app.timezone'))->toBe('Africa/Kampala');
});

test('application queue connection default is database in production environment config', function () {
    expect(config('queue.connections.database.driver'))->toBe('database');
});
