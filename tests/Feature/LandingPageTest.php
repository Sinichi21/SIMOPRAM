<?php

use App\Models\School;

test('global landing page lists and searches active schools', function () {
    $visibleSchool = School::factory()->create(['name' => 'SDN 16 Pemecutan']);
    School::factory()->inactive()->create(['name' => 'Sekolah Tidak Aktif']);

    $this->get(route('home', ['q' => 'Pemecutan']))
        ->assertOk()
        ->assertSee($visibleSchool->name)
        ->assertDontSee('Sekolah Tidak Aktif');
});

test('school landing page is resolved by slug and inactive schools stay private', function () {
    $school = School::factory()->create([
        'name' => 'SDN 16 Pemecutan',
        'slug' => 'sdn16pemecutan',
        'tagline' => 'Berani, mandiri, peduli.',
    ]);

    $this->get(route('schools.landing', $school))
        ->assertOk()
        ->assertSee($school->name)
        ->assertSee('Berani, mandiri, peduli.')
        ->assertSee(route('register', ['school' => $school->id]));

    $inactiveSchool = School::factory()->inactive()->create();
    $this->get(route('schools.landing', $inactiveSchool))->assertNotFound();
});

test('a school can submit a tenant registration request', function () {
    $payload = [
        'school_name' => 'SD Harapan Bangsa',
        'npsn' => '50123456',
        'level' => 'SD',
        'city' => 'Denpasar',
        'contact_name' => 'Ni Putu Ayu',
        'contact_phone' => '081234567890',
        'contact_email' => 'ayu@example.com',
        'notes' => 'Siap mengikuti onboarding.',
    ];

    $this->post(route('school-registrations.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('school-registration-success');

    $this->assertDatabaseHas('school_registration_requests', [
        'npsn' => '50123456',
        'contact_email' => 'ayu@example.com',
    ]);
});

test('tenant registration rejects an npsn already used by a school', function () {
    $school = School::factory()->create();

    $this->post(route('school-registrations.store'), [
        'school_name' => 'Sekolah Duplikat',
        'npsn' => $school->npsn,
        'level' => 'SD',
        'city' => 'Denpasar',
        'contact_name' => 'Pengelola',
        'contact_phone' => '081234567890',
        'contact_email' => 'admin@example.com',
    ])->assertSessionHasErrors('npsn');
});
