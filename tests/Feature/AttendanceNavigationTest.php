<?php

use App\Models\School;
use App\Models\User;

test('super admin can open the attendance activity selector', function () {
    $school = School::query()->create([
        'npsn' => '12345678',
        'name' => 'Sekolah Uji',
        'slug' => 'sekolah-uji',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'system_role' => 'super_admin',
    ]);

    $this->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('attendances.index'))
        ->assertOk()
        ->assertSee('Agenda / Kegiatan')
        ->assertSee('Absensi')
        ->assertSee('Pilih kegiatan yang akan dikelola absensinya.');
});
