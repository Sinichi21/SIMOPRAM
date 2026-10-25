<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('approved user can create and revoke a sanctum bearer token', function () {
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Android',
    ])->assertOk()->assertJsonPath('data.token_type', 'Bearer')->assertJsonPath('data.user.id', $user->id);

    $this->withToken($response->json('data.token'))->getJson('/api/v1/auth/me')->assertOk();
    $plainToken = $user->createToken('second device')->plainTextToken;
    $this->withToken($plainToken)->postJson('/api/v1/auth/logout')->assertOk();
    expect($user->tokens()->count())->toBe(1);
});

test('inactive user cannot log in and registration remains pending without a token', function () {
    $inactive = User::factory()->create(['is_active' => false]);
    $this->postJson('/api/v1/auth/login', ['email' => $inactive->email, 'password' => 'password', 'device_name' => 'Android'])
        ->assertForbidden();

    Event::fake();
    $school = School::factory()->create();
    $response = $this->postJson('/api/v1/auth/register', [
        'role' => 'coach',
        'school_id' => $school->id,
        'name' => 'Pembina API',
        'email' => 'coach-api@example.test',
        'phone' => '081234567890',
        'coach_position' => 'Pembina',
        'password' => 'StrongPass123!',
        'password_confirmation' => 'StrongPass123!',
    ]);

    $response->assertStatus(202)->assertJsonMissingPath('data.token');
    $this->assertDatabaseHas('users', ['email' => 'coach-api@example.test', 'approval_status' => 'pending', 'is_active' => false]);
    expect(User::query()->where('email', 'coach-api@example.test')->firstOrFail()->tokens()->count())->toBe(0);
});

test('protected school endpoints require a token and active school membership', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['is_active' => true]);
    $token = $user->createToken('Android')->plainTextToken;

    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/dashboard')->assertForbidden();
});
