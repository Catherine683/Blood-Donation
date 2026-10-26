<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_donor_is_redirected_to_donor_home(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('donor.home'));
    }

    public function test_authenticated_staff_is_redirected_to_staff_dashboard(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('staff.dashboard'));
    }
}
