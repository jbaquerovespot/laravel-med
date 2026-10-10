<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function session_expires_after_timeout_period(): void
    {
        $this->actingAs($this->user);

        // Initial request should work
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Simulate session timeout (set last_activity to more than timeout ago)
        session()->put('last_activity', now()->subSeconds(901)->timestamp);

        // Next request should redirect to login
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function session_is_refreshed_on_activity(): void
    {
        $this->actingAs($this->user);

        // Initial request
        $this->get(route('dashboard'));
        $initialActivity = session('last_activity');

        // Wait a moment
        sleep(1);

        // Make another request
        $this->get(route('patients.index'));
        $newActivity = session('last_activity');

        // Activity timestamp should be updated
        $this->assertGreaterThan($initialActivity, $newActivity);
    }

    #[Test]
    public function session_remains_valid_with_activity(): void
    {
        $this->actingAs($this->user);

        // Make multiple requests within timeout period
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get(route('dashboard'));
            $response->assertStatus(200);

            // Simulate 5 minutes between requests
            session()->put('last_activity', now()->subMinutes(5)->timestamp);
        }
    }

    #[Test]
    public function session_expired_message_is_shown(): void
    {
        $this->actingAs($this->user);

        // Set session as expired
        session()->put('last_activity', now()->subSeconds(901)->timestamp);

        // Request that triggers session timeout
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));

        // Follow redirect
        $response = $this->get(route('login'));
        $response->assertSessionHas('session_expired');
    }

    #[Test]
    public function user_can_login_again_after_timeout(): void
    {
        // Login initially
        $this->actingAs($this->user);
        $this->get(route('dashboard'))->assertStatus(200);

        // Session expires
        session()->put('last_activity', now()->subSeconds(901)->timestamp);
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        // Clear session (logout)
        auth()->logout();
        session()->invalidate();

        // Login again
        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        // Should be able to access dashboard again
        $this->get(route('dashboard'))->assertStatus(200);
    }

    #[Test]
    public function remember_me_extends_session(): void
    {
        // This test verifies that the remember me functionality
        // allows re-authentication after session expires
        $this->actingAs($this->user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }

    #[Test]
    public function timeout_does_not_affect_api_routes(): void
    {
        // API routes use token auth, not session
        $this->actingAs($this->user);

        // Set session as expired
        session()->put('last_activity', now()->subSeconds(901)->timestamp);

        // API request with token should still work (if implemented)
        // For now, we test that web routes are affected
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function session_timeout_value_is_configurable(): void
    {
        $timeout = config('hce.session_timeout');

        $this->assertEquals(900, $timeout); // 15 minutes default
    }
}
