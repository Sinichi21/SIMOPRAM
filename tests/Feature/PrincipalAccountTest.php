<?php

use App\Livewire\UserApprovals\Index;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

test('admin creates a principal who activates and logs in with school scoped approval access', function () {
    $this->seed(RolePermissionSeeder::class);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);

    Livewire::test(Index::class)
        ->set('principalName', 'Kepala Sekolah Baru')
        ->set('principalEmail', 'kepala@example.test')
        ->call('createPrincipal')->assertHasNoErrors()->assertSee('Kepala Sekolah Baru');

    $user = User::query()->where('email', 'kepala@example.test')->firstOrFail();
    expect($user->system_role)->toBe('principal');
    expect($user->activation_pending)->toBeTrue();
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $school->id, 'user_id' => $user->id, 'is_active' => true]);
    $this->assertDatabaseHas('document_signatory_profiles', ['school_id' => $school->id, 'user_id' => $user->id, 'position' => 'Kepala Sekolah']);

    auth()->logout();
    $token = Password::createToken($user);
    $this->post(route('password.update'), [
        'email' => $user->email, 'token' => $token,
        'password' => 'principal-password-123', 'password_confirmation' => 'principal-password-123',
    ])->assertSessionHasNoErrors();
    expect($user->fresh()->is_active)->toBeTrue();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'principal-password-123'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertSee('Dokumen Terbit');
    expect(auth()->user()->can('documents.approve'))->toBeTrue();
    expect(auth()->user()->can('user_approvals.manage'))->toBeFalse();
});

test('principal creation rejects duplicate and invalid details without creating memberships', function () {
    $this->seed(RolePermissionSeeder::class);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

    Livewire::test(Index::class)->call('createPrincipal')
        ->assertHasErrors(['principalName', 'principalEmail'])
        ->set('principalName', 'Kepala Sekolah')->set('principalEmail', $admin->email)
        ->call('createPrincipal')->assertHasErrors(['principalEmail' => 'unique']);

    $this->assertDatabaseCount('school_user_memberships', 0);
    $this->assertDatabaseCount('users', 1);
});

test('ordinary users cannot create principal accounts', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $this->actingAs(User::factory()->create());

    Livewire::test(Index::class)->assertForbidden();
    $this->assertDatabaseMissing('users', ['system_role' => 'principal']);
});
