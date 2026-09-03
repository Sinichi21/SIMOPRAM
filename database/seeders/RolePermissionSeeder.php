<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'school.profile.view',
            'school.profile.update',

            'students.view',
            'students.create',
            'students.update',
            'students.delete',
            'students.import',

            'coaches.view',
            'coaches.create',
            'coaches.update',
            'coaches.toggle',
            'coaches.delete',

            'activities.view',
            'activities.create',
            'activities.update',
            'activities.delete',
            'activities.publish',
            'activities.cancel',

            'activity_assessments.view',
            'activity_assessments.create',
            'activity_assessments.update',
            'activity_assessments.score',
            'activity_assessments.publish',
            'activity_assessments.delete',

            'attendance.view',
            'attendance.open',
            'attendance.close',
            'attendance.checkin',
            'attendance.manual',
            'attendance.verify',
            'attendance.update',
            'attendances.self',
            'attendance_sessions.view',
            'attendance_sessions.manage',

            'journals.view',
            'journals.create',
            'journals.update',
            'journals.submit',
            'journals.approve',
            'journals.publish',
            'journals.attachments',

            'announcements.view',
            'announcements.create',
            'announcements.update',
            'announcements.publish',
            'announcements.archive',
            'announcements.my',

            'notifications.logs.view',

            'assessments.view',
            'assessments.input',
            'assessments.configure',
            'assessments.finalize',
            'assessments.manage',
            'assessment_factors.view',
            'assessment_factors.manage',
            'assessments.scores.view',
            'assessments.scores.manage',
            'assessments.calculate',
            'assessment_sync.view',
            'assessment_sync.manage',
            'assessment_audit.view',

            'reports.attendance',
            'reports.grades',
            'reports.activities',
            'reports.grades.view',
            'reports.attendance.view',
            'reports.export',
            'report_verifications.view',
            'report_verifications.manage',

            'notifications.telegram',
            'notifications.whatsapp',

            'academic_years.view',
            'academic_years.manage',

            'semesters.view',
            'semesters.manage',

            'gudep.view',
            'gudep.manage',

            'classrooms.view',
            'classrooms.manage',

            'scout_units.view',
            'scout_units.manage',

            'academic_years.view',
            'academic_years.manage',

            'semesters.view',
            'semesters.manage',

            'gudep.view',
            'gudep.manage',

            'classrooms.view',
            'classrooms.manage',

            'schools.view',
            'schools.create',
            'schools.update',
            'schools.toggle',

            'student_accounts.manage',
            'attendances.self',

            'school_documents.view',
            'school_documents.manage',

            'attendance_score_settings.view',
            'attendance_score_settings.manage',

            'semester_closures.view',
            'semester_closures.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Global Role Definitions
        |--------------------------------------------------------------------------
        |
        | Assignment user tetap menggunakan school_id.
        |
        */

        setPermissionsTeamId(null);

        $scoutAdmin = Role::firstOrCreate([
            'name' => 'scout_admin',
            'guard_name' => 'web',
            'school_id' => null,
        ]);

        $schoolAdmin = Role::firstOrCreate([
            'name' => 'school_admin',
            'guard_name' => 'web',
            'school_id' => null,
        ]);

        $coach = Role::firstOrCreate([
            'name' => 'coach',
            'guard_name' => 'web',
            'school_id' => null,
        ]);

        $student = Role::firstOrCreate([
            'name' => 'student',
            'guard_name' => 'web',
            'school_id' => null,
        ]);

        $scoutAdmin->syncPermissions(
            Permission::all()
        );

        $schoolAdmin->syncPermissions(
            Permission::all()
        );

        $coach->syncPermissions([
            'dashboard.view',

            'students.view',
            'coaches.view',

            'activities.view',
            'activities.create',
            'activities.update',

            'activity_assessments.view',
            'activity_assessments.score',
            'activity_assessments.create',
            'activity_assessments.update',

            'attendance.view',
            'attendance.open',
            'attendance.close',
            'attendance.manual',
            'attendance.verify',
            'attendance.update',

            'journals.view',
            'journals.create',
            'journals.update',
            'journals.submit',
            'journals.publish',
            'journals.attachments',

            'announcements.view',
            'announcements.create',
            'announcements.update',
            'announcements.my',

            'reports.attendance',
            'reports.grades',
            'reports.activities',
            'reports.grades.view',
            'reports.attendance.view',
            'reports.export',

            'academic_years.view',
            'semesters.view',
            'gudep.view',
            'classrooms.view',
            'attendance_sessions.view',
            'attendance_sessions.manage',
            'scout_units.view',
            'scout_units.manage',

            'assessments.view',
            'assessment_factors.view',
            'assessments.scores.view',
            'assessments.scores.manage',
            'assessments.calculate',
            'assessments.input',
            'assessment_sync.view',
            'assessment_sync.manage',
            'assessment_audit.view',

            'attendance_score_settings.view',
            'semester_closures.view',
            'report_verifications.view'
        ]);

        $student->syncPermissions([
            'dashboard.view',
            'activities.view',
            'attendance.checkin',
            'announcements.view',
            'assessments.view',
            'attendance_sessions.view',
            'attendances.self',
            'announcements.my',
        ]);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
