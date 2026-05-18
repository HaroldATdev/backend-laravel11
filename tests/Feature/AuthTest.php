<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_200(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_login_returns_token_on_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ])
        ->assertStatus(200)
        ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_fails_on_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'user@test.com', 'password' => bcrypt('correct')]);

        $this->postJson('/api/login', [
            'email'    => 'user@test.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_login_validates_required_fields(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_revokes_token(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertStatus(200);

        // Token should no longer work
        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }
}
