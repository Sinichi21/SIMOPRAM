<?php

use App\Models\School;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users can see the active school in the sidebar', function () {
    $school = School::query()->create([
        'npsn' => '87654321',
        'name' => 'Sekolah Aktif Pengujian',
        'slug' => 'sekolah-aktif-pengujian',
    ]);
    $user = User::factory()->create([
        'system_role' => 'super_admin',
    ]);

    $response = $this
        ->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee('Sekolah Aktif Pengujian');
});

test('users without an active school can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pilih sekolah aktif terlebih dahulu.');
});
