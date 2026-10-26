<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '0772 123 456',
            'password' => 'password',
            'password_confirmation' => 'password',
            'consent' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isDonor());
        $this->assertFalse($user->hasVerifiedEmail());

        // Confirm unverified user cannot reach /donor
        $this->get('/donor')->assertRedirect(route('verification.notice'));
    }
}
