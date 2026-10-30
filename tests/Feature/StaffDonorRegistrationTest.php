<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\DonorActivationLink;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StaffDonorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_donor_registration_form(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->get(route('staff.donors.create'));

        $response->assertOk();
        $response->assertSee('Register Voluntary Donor');
    }

    public function test_admin_can_view_donor_registration_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('staff.donors.create'));

        $response->assertOk();
        $response->assertSee('Register Voluntary Donor');
    }

    public function test_staff_donor_list_displays_blood_group_string(): void
    {
        $staff = User::factory()->staff()->create();
        $donor = User::factory()->donor()->create();
        $donor->donorProfile()->update(['blood_group' => 'O+']);

        $response = $this->actingAs($staff)->get(route('staff.donors'));

        $response->assertOk();
        $response->assertSee('O+');
    }

    public function test_staff_can_resend_activation_link_to_unverified_donor(): void
    {
        Notification::fake();

        $staff = User::factory()->staff()->create();
        $donor = User::factory()->unverified()->create([
            'role' => Role::Donor,
            'is_active' => false,
        ]);

        $response = $this->actingAs($staff)->post(route('staff.donors.activation-email', $donor));

        Notification::assertSentTo($donor, DonorActivationLink::class, function (DonorActivationLink $notification): bool {
            return URL::hasValidSignature(Request::create($notification->activationUrl));
        });
        $response->assertRedirect(route('staff.donors.show', $donor));
        $response->assertSessionHas('status');
    }

    public function test_staff_or_admin_can_register_new_donor(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('staff.donors.store'), [
            'name' => 'Kato Isaac',
            'email' => 'kato.isaac@example.com',
            'phone' => '0772123456',
            'date_of_birth' => '1995-05-15',
            'blood_group' => 'O+',
            'notes_for_staff' => 'Registered at Central Blood Bank',
            'password' => 'a-password-the-admin-should-not-set',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Kato Isaac',
            'email' => 'kato.isaac@example.com',
            'role' => Role::Donor->value,
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        $donor = User::where('email', 'kato.isaac@example.com')->first();
        $this->assertNotNull($donor);
        $this->assertEquals('+256772123456', $donor->phone);

        $this->assertDatabaseHas('donor_profiles', [
            'user_id' => $donor->id,
            'blood_group' => 'O+',
            'notes_for_staff' => 'Registered at Central Blood Bank',
        ]);

        $this->assertFalse(Hash::check('a-password-the-admin-should-not-set', $donor->password));
        Notification::assertSentTo($donor, DonorActivationLink::class);
        $response->assertRedirect(route('staff.donors.show', $donor));
        $response->assertSessionHas('status');
    }

    public function test_donor_can_activate_account_and_set_password_from_signed_link(): void
    {
        $donor = User::factory()->unverified()->create([
            'role' => Role::Donor,
            'is_active' => false,
        ]);
        $url = URL::temporarySignedRoute('donor.activation.accept', now()->addHours(72), ['user' => $donor->id]);

        $this->get($url)->assertOk();
        $this->post($url, [
            'password' => 'DonorSecurePassword2026!',
            'password_confirmation' => 'DonorSecurePassword2026!',
        ])->assertRedirect(route('dashboard'));

        $donor->refresh();
        $this->assertTrue($donor->hasVerifiedEmail());
        $this->assertTrue($donor->is_active);
        $this->assertTrue(Hash::check('DonorSecurePassword2026!', $donor->password));
        $this->assertAuthenticatedAs($donor);
    }
}
