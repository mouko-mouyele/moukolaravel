<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('project', 'AutoChain Emma+');
    }

    public function test_user_can_login_and_receive_token(): void
    {
        User::factory()->create([
            'email' => 'test@autochain.local',
            'role' => UserRole::FleetManager,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@autochain.local',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@autochain.local']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@autochain.local',
            'password' => 'wrong',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->role(UserRole::Driver)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_register_rejects_role_field_and_assigns_driver(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nouveau Chauffeur',
            'email' => 'newdriver@autochain.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super_admin',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nouveau Chauffeur',
            'email' => 'newdriver@autochain.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('user.role', 'driver');
    }

    public function test_forgot_password_accepts_email(): void
    {
        User::factory()->create(['email' => 'reset@autochain.local']);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@autochain.local',
        ])->assertOk();
    }
}
