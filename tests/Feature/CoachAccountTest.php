<?php

use App\Models\Coach;
use App\Models\School;
use App\Models\User;
use App\Services\CoachAccountService;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;

test('admin can create a login account for an existing coach', function () {
    $this->seed(RolePermissionSeeder::class);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $admin = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $this->actingAs($admin);
    $coach = Coach::query()->create([
        'name' => 'Pembina Dengan Akun',
        'is_active' => true,
    ]);

    $user = app(CoachAccountService::class)->createAccount(
        $coach,
        'pembina@example.com'
    );

    expect($coach->refresh()->user_id)->toBe($user->id)
        ->and($user->system_role)->toBe('coach')
        ->and($user->is_active)->toBeFalse()
        ->and($user->activation_pending)->toBeTrue();
    $this->assertDatabaseHas('school_user_memberships', [
        'school_id' => $school->id,
        'user_id' => $user->id,
        'is_active' => true,
    ]);
});
