<?php

namespace App\Support;

use App\Models\School;
use App\Models\User;
use App\Services\GlobalActivityAccess;

final class ModuleWorkspace
{
    public static function all(): array
    {
        return [
            'keanggotaan' => [
                'title' => 'Keanggotaan',
                'description' => 'Anggota muda, pembina, dan satuan.',
                'icon' => 'users',
                'links' => [
                    self::link('Anggota muda', 'students.index', 'students.view', ['students.*']),
                    self::link('Pembina', 'coaches.index', 'coaches.view', ['coaches.*']),
                    self::link('Regu / Barung', 'scout-units.index', 'scout_units.view', ['scout-units.*']),
                ],
            ],
            'kegiatan' => [
                'title' => 'Kegiatan & Pembinaan',
                'description' => 'Agenda, absensi, jurnal, pengumuman, dan perencanaan program.',
                'icon' => 'calendar-days',
                'links' => [
                    self::link('Agenda kegiatan', 'activities.index', 'activities.view', ['activities.*']),
                    self::link('Absensi', 'attendances.index', 'attendance_sessions.view', ['attendances.manage', 'attendances.print']),
                    self::link('Jurnal Kegiatan', 'journals.index', 'journals.view', ['journals.*']),
                    self::link('Pengumuman', 'announcements.index', 'announcements.view', ['announcements.create', 'announcements.edit']),
                    self::link('Program Kerja Tahunan/Semester/Mingguan', 'administration.programs', 'gudep.view', ['administration.programs.*']),
                    self::link('Kegiatan Umum', 'admin.public-activities', null, ['admin.activity-*'], scope: 'global', globalActivity: true),
                    self::link('Pengumuman Umum', 'admin.public-announcements', null, scope: 'global', superAdmin: true, globalActivity: true),
                ],
            ],
            'penilaian' => [
                'title' => 'Penilaian & Kecakapan',
                'description' => 'Nilai, SKU/SKK, prestasi, dan pengaturan penilaian.',
                'icon' => 'academic-cap',
                'links' => [
                    self::link('Input Nilai', 'assessments.scores', 'assessments.scores.view'),
                    self::link('Penilaian Kegiatan', 'activity-assessments.index', 'activity_assessments.view', ['activity-assessments.*', 'activity-participation.*', 'assessment-reports.show']),
                    self::link('Pembinaan SKU/SKK & Prestasi', 'administration.development', 'gudep.view', ['administration.development.*']),
                    self::link('Sinkronisasi Penilaian', 'assessment-sync.index', 'assessment_sync.view', ['assessment-sync.*']),
                    self::link('Audit Penilaian', 'assessment-audit.index', 'assessment_audit.view', ['assessment-audit.*']),
                    self::link('Kunci Semester', 'semester-closures.index', 'semester_closures.view', ['semester-closures.*']),
                    self::link('Pengaturan Penilaian', 'assessments.settings', 'assessments.view'),
                    self::link('Bobot Kehadiran', 'settings.attendance-scoring', 'attendance_score_settings.view'),
                    self::link('Penilaian Umum', 'admin.public-assessments', null, scope: 'global', globalActivity: true),
                ],
            ],
            'administrasi' => [
                'title' => 'Administrasi Gudep',
                'description' => 'Akreditasi, organisasi, persuratan, dan identitas dokumen.',
                'icon' => 'folder-open',
                'links' => [
                    self::link('Portofolio Akreditasi', 'administration.accreditation', 'gudep.view'),
                    self::link('Buku Operasional', 'administration.operations', 'gudep.view'),
                    self::link('Organisasi & Buku Induk', 'administration.organization', 'gudep.view', ['administration.officers.*']),
                    self::link('Gugusdepan', 'scout-groups.index', 'gudep.view', ['scout-groups.*']),
                    self::link('Surat Masuk', 'letters.incoming', 'letters.view'),
                    self::link('Surat Keluar', 'letters.outgoing', 'letters.view'),
                    self::link('Template Surat', 'letters.templates', 'letters.templates'),
                    self::link('Pengaturan Persuratan', 'letters.settings', 'letters.settings'),
                    self::link('Dokumen Sekolah', 'settings.school-documents', 'school_documents.view'),
                ],
            ],
            'keuangan' => [
                'title' => 'Keuangan',
                'description' => 'Anggaran, buku kas, penerimaan terkait inventaris, dan rekonsiliasi Gudep.',
                'icon' => 'wallet',
                'links' => [
                    self::link('Dashboard & Buku Kas', 'finance.index', 'gudep.finance.view', ['finance.*', 'administration.finance', 'administration.finance.*']),
                ],
            ],
            'inventaris' => [
                'title' => 'Inventaris',
                'description' => 'Sarana, barang, aset, stok, dan peminjaman.',
                'icon' => 'archive-box',
                'links' => [
                    self::link('Kelola inventaris', 'inventory.index', 'inventory.view', ['inventory.items.*', 'inventory.assets.*']),
                    self::link('Peminjaman', 'inventory.loans.index', 'inventory.view', ['inventory.loans.*']),
                ],
            ],
            'laporan' => [
                'title' => 'Laporan',
                'description' => 'Rekap nilai dan absensi, LPJ, serta penerbitan dan verifikasi dokumen.',
                'icon' => 'chart-bar-square',
                'links' => [
                    self::link('Rekap Nilai', 'reports.grades', 'reports.grades.view', ['reports.grades.*']),
                    self::link('Rekap Absensi', 'reports.attendance', 'reports.attendance.view', ['reports.attendance.*']),
                    self::link('LPJ Kegiatan', 'reports.lpj', 'reports.lpj.view', ['reports.lpj.*']),
                    self::link('Dokumen Terbit & Verifikasi', 'reports.published-documents.index', 'report_verifications.view', ['reports.published-documents.*', 'reports.verify']),
                ],
            ],
            'sistem' => [
                'title' => 'Manajemen Sistem',
                'description' => 'Data sekolah, akun pengguna, peran, dan konfigurasi sistem.',
                'icon' => 'cog-6-tooth',
                'links' => [
                    self::link('Data Sekolah', 'schools.index', 'schools.manage', scope: 'global'),
                    self::link('Permohonan Sekolah', 'school-registrations.index', 'schools.manage', scope: 'global'),
                    self::link('Log Aktivitas', 'activity-logs.index', 'activity-logs.view', ['activity-logs.*'], scope: 'global', superAdmin: true),
                    self::link('Role & Permission Bawaan', 'system-roles.index', 'schools.manage', ['system-roles.*'], scope: 'global'),
                    self::link('Integrasi Pesan', 'settings.messaging', 'messaging.manage', scope: 'global'),
                    self::link('Landing Page', 'settings.landing-content', 'landing.manage', scope: 'global'),
                    self::link('Gabung Profil', 'admin.people.merge', null, scope: 'global', superAdmin: true),
                    self::link('Tahun Ajaran', 'academic-years.index', 'academic_years.view', ['academic-years.*']),
                    self::link('Semester', 'semesters.index', 'semesters.view', ['semesters.*']),
                    self::link('Kelas', 'classrooms.index', 'classrooms.view', ['classrooms.*']),
                    self::link('Akun dan Persetujuan', 'user-approvals.index', 'user_approvals.manage', ['user-approvals.*']),
                    self::link('Akun Pembina', 'coach-accounts.index', 'coach_accounts.manage', ['coach-accounts.*']),
                    self::link('Akun Siswa', 'student-accounts.index', 'student_accounts.manage', ['student-accounts.*']),
                    self::link('Role & Permission', 'roles.index', 'roles.manage', ['roles.*']),
                    self::link('Halaman Sekolah', 'settings.tenant-content', 'school-landing.manage', schoolParameter: true),
                ],
            ],
        ];
    }

    public static function activeSlug(): ?string
    {
        if (request()->routeIs('modules.show')) {
            return (string) request()->route('module');
        }

        foreach (self::all() as $slug => $module) {
            foreach ($module['links'] as $link) {
                if (self::isCurrent($link)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    public static function isCurrent(array $link): bool
    {
        return request()->routeIs(...$link['patterns']);
    }

    public static function find(string $slug): ?array
    {
        $modules = self::all();

        return isset($modules[$slug]) ? ['slug' => $slug, ...$modules[$slug]] : null;
    }

    public static function requiresSchool(array $module): bool
    {
        return ! in_array('global', array_column($module['links'], 'scope'), true);
    }

    public static function available(User $user): array
    {
        $available = [];

        foreach (self::all() as $slug => $module) {
            $links = self::accessibleLinks($user, $module);

            if ($links !== []) {
                $available[$slug] = ['slug' => $slug, ...$module, 'links' => $links];
            }
        }

        return $available;
    }

    public static function permitted(User $user, array $module): bool
    {
        return self::accessibleLinks($user, $module) !== [];
    }

    public static function accessibleLinks(User $user, array $module): array
    {
        $school = app(SchoolContext::class)->school();
        $links = [];
        $canEnterGlobalActivity = null;

        foreach ($module['links'] as $link) {
            // The tenant landing editor authorizes its bound school independently.
            $linkSchool = $link['schoolParameter'] && request()->route('school') instanceof School
                ? request()->route('school')
                : $school;

            if ($link['scope'] === 'school' && ! $linkSchool) {
                continue;
            }

            if ($link['superAdmin'] && ! $user->isSuperAdmin()) {
                continue;
            }

            $parameters = $link['schoolParameter'] ? ['school' => $linkSchool] : [];

            if ($link['permission'] && ! $user->can($link['permission'], array_values($parameters))) {
                continue;
            }

            if ($link['globalActivity']) {
                $canEnterGlobalActivity ??= app(GlobalActivityAccess::class)->canEnter($user);

                if (! $canEnterGlobalActivity) {
                    continue;
                }
            }

            $links[] = [...$link, 'parameters' => $parameters];
        }

        return $links;
    }

    private static function link(
        string $label,
        string $route,
        ?string $permission,
        array $patterns = [],
        string $scope = 'school',
        bool $superAdmin = false,
        bool $globalActivity = false,
        bool $schoolParameter = false,
    ): array {
        return [
            'label' => $label,
            'route' => $route,
            'permission' => $permission,
            'patterns' => [$route, ...$patterns],
            'scope' => $scope,
            'superAdmin' => $superAdmin,
            'globalActivity' => $globalActivity,
            'schoolParameter' => $schoolParameter,
        ];
    }
}
