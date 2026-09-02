<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolDocumentSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_super_admin_cannot_open_school_document_settings(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'settings.school-documents'
                )
            )
            ->assertStatus(
                409
            );
    }

    public function test_document_settings_follow_active_school_context(): void
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

        DB::table(
            'school_document_settings'
        )->insert([
            [
                'school_id' => $schoolA->id,

                'principal_name' => 'Kepala Sekolah A',

                'created_at' => now(),

                'updated_at' => now(),
            ],

            [
                'school_id' => $schoolB->id,

                'principal_name' => 'Kepala Sekolah B',

                'created_at' => now(),

                'updated_at' => now(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | School A
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => $schoolA->id,
            ])
            ->get(
                route(
                    'settings.school-documents'
                )
            )
            ->assertOk();

        $settingA =
            SchoolDocumentSetting::query()
                ->first();

        $this->assertNotNull(
            $settingA
        );

        $this->assertSame(
            'Kepala Sekolah A',
            $settingA->principal_name
        );

        /*
        |--------------------------------------------------------------------------
        | Switch ke School B
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route(
                    'school.switch'
                ),
                [
                    'school_id' => $schoolB->id,
                ]
            )
            ->assertRedirect(
                route('dashboard')
            );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'settings.school-documents'
                )
            )
            ->assertOk();

        $settingB =
            SchoolDocumentSetting::query()
                ->first();

        $this->assertNotNull(
            $settingB
        );

        $this->assertSame(
            'Kepala Sekolah B',
            $settingB->principal_name
        );

        $this->assertNotSame(
            $settingA->school_id,
            $settingB->school_id
        );
    }
}
