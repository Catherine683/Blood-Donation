<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_display_settings_profile_page(): void
    {
        $this->actingAs($staff = User::factory()->staff()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_donor_visiting_settings_profile_is_redirected_to_donor_profile(): void
    {
        $this->actingAs($donor = User::factory()->donor()->create());

        $this->get(route('profile.edit'))->assertRedirect(route('donor.profile'));
    }

    public function test_staff_profile_information_can_be_updated(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Staff Member')
            ->set('email', 'staff.updated@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $staff->refresh();

        $this->assertEquals('Staff Member', $staff->name);
        $this->assertEquals('staff.updated@example.com', $staff->email);
        $this->assertNull($staff->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Staff Member')
            ->set('email', $staff->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($staff->refresh()->email_verified_at);
    }

    public function test_staff_can_delete_their_account(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($staff->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($staff->fresh());
    }
}
