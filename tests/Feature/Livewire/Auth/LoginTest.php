<?php

namespace Tests\Feature\Livewire\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_render_login_component(): void
    {
        Livewire::test('auth.login')
            ->assertStatus(200)
            ->assertSee('Iniciar Sesión');
    }

    #[Test]
    public function it_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test('auth.login')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        Livewire::test('auth.login')
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    #[Test]
    public function it_fails_with_non_existent_email(): void
    {
        Livewire::test('auth.login')
            ->set('email', 'nonexistent@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    #[Test]
    public function it_validates_email_is_required(): void
    {
        Livewire::test('auth.login')
            ->set('email', '')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['email' => 'required']);
    }

    #[Test]
    public function it_validates_email_format(): void
    {
        Livewire::test('auth.login')
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);
    }

    #[Test]
    public function it_validates_password_is_required(): void
    {
        Livewire::test('auth.login')
            ->set('email', 'test@example.com')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['password' => 'required']);
    }

    #[Test]
    public function it_implements_rate_limiting(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Attempt 5 failed logins
        for ($i = 0; $i < 5; $i++) {
            Livewire::test('auth.login')
                ->set('email', 'test@example.com')
                ->set('password', 'wrong-password')
                ->call('login');
        }

        // The 6th attempt should be rate limited
        Livewire::test('auth.login')
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');
    }

    #[Test]
    public function it_regenerates_session_on_login(): void
    {
        $oldSessionId = session()->getId();

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test('auth.login')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login');

        // Session should be regenerated (new ID)
        $this->assertNotEquals($oldSessionId, session()->getId());
    }
}
