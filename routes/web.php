<?php

use App\Http\Controllers\ReportPdfController;
use App\Http\Controllers\SchoolSwitchController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportVerificationController;

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
| PUBLIC REPORT VERIFICATION
|--------------------------------------------------------------------------
|
|
*/

Route::get(
    '/verify/report/{code}',
    [
        ReportVerificationController::class,
        'show',
    ]
)
    ->where(
        'code',
        '[a-f0-9]{48}'
    )
    ->middleware(
        'throttle:60,1'
    )
    ->name(
        'reports.verify'
    );


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

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::view(
        '/dashboard',
        'dashboard'
    )
        ->name(
            'dashboard'
        );

    /*
    |--------------------------------------------------------------------------
    | Switch Sekolah / Global
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/school/switch',
        SchoolSwitchController::class
    )
        ->name(
            'school.switch'
        );

    /*
    |--------------------------------------------------------------------------
    | Administrasi sekolah
    |--------------------------------------------------------------------------
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
                        'announcementId' => null,
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

        Route::view(
            '/penilaian/input',
            'assessments.scores'
        )
            ->middleware(
                'can:assessments.scores.view'
            )
            ->name(
                'assessments.scores'
            );

        /*
        |--------------------------------------------------------------------------
        | Laporan
        |--------------------------------------------------------------------------
        */

        Route::view(
            '/laporan/nilai',
            'reports.grades'
        )
            ->middleware(
                'can:reports.grades.view'
            )
            ->name(
                'reports.grades'
            );

        Route::get(
            '/laporan/nilai/pdf',
            [
                ReportPdfController::class,
                'grades',
            ]
        )
            ->middleware(
                'can:reports.export'
            )
            ->name(
                'reports.grades.pdf'
            );

        Route::view(
            '/laporan/absensi',
            'reports.attendance'
        )
            ->middleware(
                'can:reports.attendance.view'
            )
            ->name(
                'reports.attendance'
            );

        Route::get(
            '/laporan/absensi/pdf',
            [
                ReportPdfController::class,
                'attendance',
            ]
        )
            ->middleware(
                'can:reports.export'
            )
            ->name(
                'reports.attendance.pdf'
            );

        Route::view(
            '/pengaturan/dokumen-sekolah',
            'settings.school-documents'
        )
            ->middleware(
                'can:school_documents.view'
            )
            ->name(
                'settings.school-documents'
            );

        Route::view(
            '/pengaturan/bobot-kehadiran',
            'settings.attendance-scoring'
        )
            ->middleware(
                'can:attendance_score_settings.view'
            )
            ->name(
                'settings.attendance-scoring'
            );

        Route::view(
            '/penilaian/kegiatan',
            'assessments.activity-index'
        )
            ->middleware(
                'can:activity_assessments.view'
            )
            ->name(
                'activity-assessments.index'
            );

        Route::view(
            '/penilaian/kegiatan/{assessment}/kelola',
            'assessments.activity-edit'
        )
            ->middleware(
                'can:activity_assessments.view'
            )
            ->whereNumber(
                'assessment'
            )
            ->name(
                'activity-assessments.edit'
            );

        Route::view(
            '/penilaian/kegiatan/{assessment}/nilai',
            'assessments.activity-score'
        )
            ->middleware(
                'can:activity_assessments.score'
            )
            ->whereNumber(
                'assessment'
            )
            ->name(
                'activity-assessments.score'
            );

        Route::view(
            '/penilaian/sinkronisasi',
            'assessments.synchronization'
        )
            ->middleware(
                'can:assessment_sync.view'
            )
            ->name(
                'assessment-sync.index'
            );

        Route::view(
            '/penilaian/audit',
            'assessments.audit'
        )
            ->middleware(
                'can:assessment_audit.view'
            )
            ->name(
                'assessment-audit.index'
            );

        Route::view(
            '/penilaian/kunci-semester',
            'assessments.semester-closures'
        )
            ->middleware(
                'can:semester_closures.view'
            )
            ->name(
                'semester-closures.index'
            );
    });
});

/*
|--------------------------------------------------------------------------
| Starter Kit
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';
