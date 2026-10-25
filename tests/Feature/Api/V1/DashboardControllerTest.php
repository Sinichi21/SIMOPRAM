<?php

use App\Models\School;
use App\Models\User;

test('super admin can view global dashboard without a selected school', function () {
    $activeSchool = School::factory()->create();
    School::factory()->inactive()->create();
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/dashboard/global')
        ->assertOk()
        ->assertJsonPath('data.mode', 'global')
        ->assertJsonPath('data.school', null)
        ->assertJsonPath('data.dashboard.totalSchools', 2)
        ->assertJsonPath('data.dashboard.activeSchools', 1)
        ->assertJsonPath('data.dashboard.schoolMonitoring.0.school.id', $activeSchool->id);
});

test('global dashboard returns unauthorized when no token is provided', function () {
    $this->getJson('/api/v1/dashboard/global')->assertUnauthorized();
});

test('global dashboard forbids scout admins', function () {
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'scout_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/dashboard/global')->assertForbidden();
});

test('school dashboard still requires a selected school', function () {
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/dashboard')->assertUnprocessable();
});
