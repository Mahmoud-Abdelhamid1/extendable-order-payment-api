<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/auth/register', [
            'name'                  => 'John',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson('/api/auth/register', [
            'name'     => 'John Doe',
            'email'    => 'john@example.com',
            'password' => 'password123',
            // missing password_confirmation
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email'    => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email'    => 'john@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/auth/login', [
            'email'    => 'john@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
             ->postJson('/api/auth/logout')
             ->assertOk()
             ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $this->postJson('/api/auth/logout')
             ->assertUnauthorized();
    }
}
