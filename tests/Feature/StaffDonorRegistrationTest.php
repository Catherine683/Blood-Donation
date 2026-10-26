<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_staff_or_admin_can_register_new_donor(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('staff.donors.store'), [
            'name' => 'Kato Isaac',
            'email' => 'kato.isaac@example.com',
            'phone' => '0772123456',
            'date_of_birth' => '1995-05-15',
            'blood_group' => 'O+',
            'notes_for_staff' => 'Registered at Central Blood Bank',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Kato Isaac',
            'email' => 'kato.isaac@example.com',
            'role' => Role::Donor->value,
            'is_active' => true,
        ]);

        $donor = User::where('email', 'kato.isaac@example.com')->first();
        $this->assertNotNull($donor);
        $this->assertEquals('+256772123456', $donor->phone);

        $this->assertDatabaseHas('donor_profiles', [
            'user_id' => $donor->id,
            'blood_group' => 'O+',
            'notes_for_staff' => 'Registered at Central Blood Bank',
        ]);

        $response->assertRedirect(route('staff.donors.show', $donor));
        $response->assertSessionHas('status');
    }
}
