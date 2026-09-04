<?php

use App\Models\School;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $school = School::factory()->create(['is_active' => true]);

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone' => '08123456789',
        'requested_school_id' => $school->id,
        'requested_role' => 'student',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'requested_school_id' => $school->id,
        'requested_role' => 'student',
        'approval_status' => 'pending',
        'is_active' => false,
    ]);
});

test('self registration cannot request a system administrator role', function () {
    $school = School::factory()->create(['is_active' => true]);

    $this->post(route('register.store'), [
        'name' => 'Unauthorized Admin',
        'email' => 'unauthorized@example.com',
        'requested_school_id' => $school->id,
        'requested_role' => 'super_admin',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('requested_role');

    $this->assertDatabaseMissing('users', [
        'email' => 'unauthorized@example.com',
    ]);
});
