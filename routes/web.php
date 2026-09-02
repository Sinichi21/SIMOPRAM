<?php

use App\Http\Controllers\SchoolSwitchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    if (auth()->check()) {
        return redirect()
            ->route('dashboard');
    }

    return redirect()
        ->route('login');

})->name('home');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::view(
    '/dashboard',
    'dashboard'
)
    ->middleware([
        'auth',
        'school',
    ])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'school',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Global Admin
    |--------------------------------------------------------------------------
    |
    | Tidak membutuhkan school.required.
    |
    */

    Route::view(
        '/admin/sekolah',
        'admin.schools'
    )
        ->middleware(
            'can:schools.view'
        )
        ->name(
            'schools.index'
        );

    /*
    |--------------------------------------------------------------------------
    | Switch Sekolah
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/school/switch',
        SchoolSwitchController::class
    )->name(
        'school.switch'
    );

    /*
    |--------------------------------------------------------------------------
    | Tenant Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'school.required',
    ])->group(function () {

        Route::view(
            '/master/tahun-ajaran',
            'master.academic-years'
        )
            ->middleware(
                'can:academic_years.view'
            )
            ->name(
                'academic-years.index'
            );

        Route::view(
            '/master/semester',
            'master.semesters'
        )
            ->middleware(
                'can:semesters.view'
            )
            ->name(
                'semesters.index'
            );

        Route::view(
            '/master/kelas',
            'master.classrooms'
        )
            ->middleware(
                'can:classrooms.view'
            )
            ->name(
                'classrooms.index'
            );

        Route::view(
            '/master/gugus-depan',
            'master.scout-groups'
        )
            ->middleware(
                'can:gudep.view'
            )
            ->name(
                'scout-groups.index'
            );

        Route::view(
            '/master/pembina',
            'master.coaches'
        )
            ->middleware(
                'can:coaches.view'
            )
            ->name(
                'coaches.index'
            );

        Route::view(
            '/master/siswa',
            'master.students'
        )
            ->middleware(
                'can:students.view'
            )
            ->name(
                'students.index'
            );

        Route::view(
            '/master/regu-barung',
            'master.scout-units'
        )
            ->middleware(
                'can:scout_units.view'
            )
            ->name(
                'scout-units.index'
            );

        Route::view(
            '/kegiatan',
            'activities.index'
        )
            ->middleware(
                'can:activities.view'
            )
            ->name(
                'activities.index'
            );

        Route::view(
            '/absensi',
            'attendances.index'
        )
            ->middleware(
                'can:attendance_sessions.view'
            )
            ->name(
                'attendances.index'
            );

        Route::get(
            '/kegiatan/{activityId}/absensi',
            function (int $activityId) {
                return view(
                    'attendances.manage',
                    compact('activityId')
                );
            }
        )
            ->middleware(
                'can:attendance_sessions.view'
            )
            ->name(
                'attendances.manage'
            );

        Route::get(
            '/master/siswa/{studentId}/akun',
            function (int $studentId) {
                return view(
                    'master.student-account',
                    compact('studentId')
                );
            }
        )
            ->middleware(
                'can:student_accounts.manage'
            )
            ->name(
                'student-accounts.manage'
            );

        Route::view(
            '/absensi-saya',
            'attendances.self'
        )
            ->middleware(
                'can:attendances.self'
            )
            ->name(
                'attendances.self'
            );
            
        /*
        |--------------------------------------------------------------------------
        | Jurnal Kegiatan
        |--------------------------------------------------------------------------
        */

        Route::view(
            '/jurnal',
            'journals.index'
        )
            ->middleware(
                'can:journals.view'
            )
            ->name(
                'journals.index'
            );


        Route::get(
            '/kegiatan/{activityId}/jurnal',
            function (int $activityId) {
                return view(
                    'journals.manage',
                    compact('activityId')
                );
            }
        )
            ->middleware(
                'can:journals.view'
            )
            ->name(
                'journals.manage'
            );

        /*
        |--------------------------------------------------------------------------
        | Pengumuman
        |--------------------------------------------------------------------------
        */

        Route::view(
            '/pengumuman',
            'announcements.index'
        )
            ->middleware(
                'can:announcements.view'
            )
            ->name(
                'announcements.index'
            );


        Route::get(
            '/pengumuman/buat',
            function () {
                return view(
                    'announcements.manage',
                    [
                        'announcementId' =>
                            null,
                    ]
                );
            }
        )
            ->middleware(
                'can:announcements.create'
            )
            ->name(
                'announcements.create'
            );


        Route::get(
            '/pengumuman/{announcementId}/edit',
            function (int $announcementId) {
                return view(
                    'announcements.manage',
                    compact(
                        'announcementId'
                    )
                );
            }
        )
            ->middleware(
                'can:announcements.update'
            )
            ->name(
                'announcements.edit'
            );


        /*
        |--------------------------------------------------------------------------
        | Pengumuman yang diterima pengguna
        |--------------------------------------------------------------------------
        */

        Route::view(
            '/pengumuman-saya',
            'announcements.my'
        )
            ->name(
                'announcements.my'
            );

        /*
        |--------------------------------------------------------------------------
        | Pengaturan Notifikasi
        |--------------------------------------------------------------------------
        */

        Route::view(
            '/pengaturan-notifikasi',
            'notification-settings.manage'
        )
            ->name(
                'notification-settings.manage'
            );

        /*
        |--------------------------------------------------------------------------
        | Penilaian
        |--------------------------------------------------------------------------
        */

        Route::view(
            '/penilaian/pengaturan',
            'assessments.settings'
        )
            ->middleware(
                'can:assessments.view'
            )
            ->name(
                'assessments.settings'
            );
    });
});

/*
|--------------------------------------------------------------------------
| Starter Kit
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';
