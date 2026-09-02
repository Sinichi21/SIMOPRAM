<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_context_only_exposes_students_from_active_school(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Buat Super Admin
        |--------------------------------------------------------------------------
        */

        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',
                    'is_active' => true,
                ]);

        /*
        |--------------------------------------------------------------------------
        | Buat dua sekolah
        |--------------------------------------------------------------------------
        */

        $schoolA =
            School::factory()
                ->create([
                    'name' => 'Sekolah A',
                    'is_active' => true,
                ]);

        $schoolB =
            School::factory()
                ->create([
                    'name' => 'Sekolah B',
                    'is_active' => true,
                ]);

        /*
        |--------------------------------------------------------------------------
        | Seed data langsung ke database
        |--------------------------------------------------------------------------
        |
        | Kita menggunakan DB::table() dengan sengaja.
        |
        | school_id TIDAK dimasukkan ke $fillable Student karena school_id
        | merupakan tenant boundary dan normalnya diisi otomatis oleh
        | BelongsToSchool.
        |
        | Dalam test ini kita memang perlu membuat data dari dua tenant
        | sekaligus, sehingga kita bypass Eloquent HANYA untuk setup test.
        |
        */

        DB::table('students')->insert([
            [
                'school_id' => $schoolA->id,

                'nis' => 'A001',

                'name' => 'Siswa Sekolah A',

                'gender' => 'male',

                'status' => 'active',

                'created_at' => now(),

                'updated_at' => now(),
            ],

            [
                'school_id' => $schoolB->id,

                'nis' => 'B001',

                'name' => 'Siswa Sekolah B',

                'gender' => 'male',

                'status' => 'active',

                'created_at' => now(),

                'updated_at' => now(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Aktifkan Sekolah A melalui middleware
        |--------------------------------------------------------------------------
        */

        $response =
            $this
                ->actingAs($user)
                ->withSession([
                    'active_school_id' => $schoolA->id,
                ])
                ->get(
                    route('dashboard')
                );

        $response->assertOk();

        /*
        |--------------------------------------------------------------------------
        | Query Student normal
        |--------------------------------------------------------------------------
        |
        | BelongsToSchool seharusnya otomatis menambahkan:
        |
        | WHERE school_id = $schoolA->id
        |
        */

        $students =
            Student::query()
                ->orderBy('name')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Hanya siswa Sekolah A yang boleh terlihat
        |--------------------------------------------------------------------------
        */

        $this->assertCount(
            1,
            $students
        );

        $this->assertSame(
            'Siswa Sekolah A',
            $students->first()->name
        );

        $this->assertSame(
            $schoolA->id,
            $students->first()->school_id
        );

        /*
        |--------------------------------------------------------------------------
        | Pastikan siswa Sekolah B tidak bocor
        |--------------------------------------------------------------------------
        */

        $this->assertFalse(
            $students->contains(
                fn (Student $student): bool => $student->school_id === $schoolB->id
            )
        );

        $this->assertFalse(
            $students->contains(
                fn (Student $student): bool => $student->name === 'Siswa Sekolah B'
            )
        );
    }

    public function test_switching_school_changes_tenant_query_result(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',
                    'is_active' => true,
                ]);

        $schoolA =
            School::factory()
                ->create([
                    'name' => 'Sekolah A',
                ]);

        $schoolB =
            School::factory()
                ->create([
                    'name' => 'Sekolah B',
                ]);

        DB::table('students')->insert([
            [
                'school_id' => $schoolA->id,
                'nis' => 'A001',
                'name' => 'Siswa A',
                'gender' => 'male',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'school_id' => $schoolB->id,
                'nis' => 'B001',
                'name' => 'Siswa B',
                'gender' => 'male',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Sekolah A
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => $schoolA->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $studentsA =
            Student::query()
                ->get();

        $this->assertCount(
            1,
            $studentsA
        );

        $this->assertSame(
            'Siswa A',
            $studentsA->first()->name
        );

        /*
        |--------------------------------------------------------------------------
        | Switch ke Sekolah B
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route('school.switch'),
                [
                    'school_id' => $schoolB->id,
                ]
            )
            ->assertRedirect(
                route('dashboard')
            );

        /*
        |--------------------------------------------------------------------------
        | Buat request baru agar middleware memasang SchoolContext B
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $studentsB =
            Student::query()
                ->get();

        $this->assertCount(
            1,
            $studentsB
        );

        $this->assertSame(
            'Siswa B',
            $studentsB->first()->name
        );

        $this->assertSame(
            $schoolB->id,
            $studentsB->first()->school_id
        );
    }
}
