<?php

use App\Models\School;
use App\Models\User;
use App\Services\UserApprovalService;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;

test('pending student registration can be approved', function () {
    $this->seed(RolePermissionSeeder::class);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $approver = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $pendingUser = User::factory()->create([
        'requested_school_id' => $school->id,
        'requested_role' => 'student',
        'approval_status' => 'pending',
        'is_active' => false,
    ]);

    app(UserApprovalService::class)->approve(
        $pendingUser,
        $approver,
        $school->id
    );

    $pendingUser->refresh();

    expect($pendingUser->is_active)->toBeFalse()
        ->and($pendingUser->activation_pending)->toBeTrue()
        ->and($pendingUser->approval_status)->toBe('approved')
        ->and($pendingUser->student)->not->toBeNull();
    $this->assertDatabaseHas('school_user_memberships', [
        'school_id' => $school->id,
        'user_id' => $pendingUser->id,
        'is_active' => true,
    ]);
});

test('registration cannot be approved from another school', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $approver = User::factory()->create(['system_role' => 'super_admin']);
    $pendingUser = User::factory()->create([
        'requested_school_id' => $otherSchool->id,
        'requested_role' => 'coach',
        'approval_status' => 'pending',
        'is_active' => false,
    ]);

    expect(fn () => app(UserApprovalService::class)->approve(
        $pendingUser,
        $approver,
        $school->id
    ))->toThrow(ValidationException::class);
});

test('school approval page only shows pending users from the active school', function () {
    $this->seed(RolePermissionSeeder::class);
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $admin = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    User::factory()->create([
        'name' => 'Pendaftar Sekolah Aktif',
        'requested_school_id' => $school->id,
        'requested_role' => 'coach',
        'approval_status' => 'pending',
        'is_active' => false,
    ]);
    User::factory()->create([
        'name' => 'Pendaftar Sekolah Lain',
        'requested_school_id' => $otherSchool->id,
        'requested_role' => 'student',
        'approval_status' => 'pending',
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('user-approvals.index'))
        ->assertOk()
        ->assertSee('Pendaftar Sekolah Aktif')
        ->assertDontSee('Pendaftar Sekolah Lain');
});
