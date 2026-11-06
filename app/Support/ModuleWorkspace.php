<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

final class ModuleWorkspace
{
    /**
     * Permissions are checked against the currently active school.
     * A module is navigation only; destination routes keep their own policies.
     */
    public static function all(): array
    {
        return [
            'administrasi' => [
                'title' => 'Administrasi Gudep',
                'description' => 'Profil Gudep, persuratan, arsip, dan dokumen terbit.',
                'icon' => 'folder-open',
                'permissions' => ['gudep.view', 'letters.view', 'letters.templates', 'report_verifications.view'],
                'links' => [
                    ['label' => 'Portofolio Akreditasi', 'route' => 'administration.accreditation', 'permission' => 'gudep.view'],
                    ['label' => 'Buku Operasional', 'route' => 'administration.operations', 'permission' => 'gudep.view'],
                    ['label' => 'Program & Pembinaan', 'route' => 'administration.development', 'permission' => 'gudep.view'],
                    ['label' => 'Organisasi & Buku Induk', 'route' => 'administration.organization', 'permission' => 'gudep.view'],
                    ['label' => 'Gugusdepan', 'route' => 'scout-groups.index', 'permission' => 'gudep.view'],
                    ['label' => 'Surat masuk', 'route' => 'letters.incoming', 'permission' => 'letters.view'],
                    ['label' => 'Surat keluar', 'route' => 'letters.outgoing', 'permission' => 'letters.view'],
                    ['label' => 'Template Surat', 'route' => 'letters.templates', 'permission' => 'letters.templates'],
                    ['label' => 'Dokumen terbit', 'route' => 'reports.published-documents.index', 'permission' => 'report_verifications.view'],
                ],
            ],
            'keuangan' => [
                'title' => 'Keuangan',
                'description' => 'Anggaran, buku kas, penerimaan terkait inventaris, dan rekonsiliasi Gudep.',
                'icon' => 'wallet',
                'permissions' => ['gudep.finance.view'],
                'links' => [
                    ['label' => 'Dashboard & Buku Kas', 'route' => 'finance.index', 'permission' => 'gudep.finance.view'],
                ],
            ],
            'inventaris' => [
                'title' => 'Inventaris',
                'description' => 'Barang, aset, stok, dan transaksi peminjaman.',
                'icon' => 'archive-box',
                'permissions' => ['inventory.view'],
                'links' => [
                    ['label' => 'Kelola inventaris', 'route' => 'inventory.index', 'permission' => 'inventory.view'],
                    ['label' => 'Peminjaman', 'route' => 'inventory.loans.index', 'permission' => 'inventory.view'],
                ],
            ],
            'kegiatan' => [
                'title' => 'Kegiatan & Pembinaan',
                'description' => 'Agenda, jurnal, dan dokumentasi kegiatan.',
                'icon' => 'calendar-days',
                'permissions' => ['activities.view', 'attendance_sessions.view', 'journals.view', 'announcements.view'],
                'links' => [
                    ['label' => 'Agenda kegiatan', 'route' => 'activities.index', 'permission' => 'activities.view'],
                    ['label' => 'Absensi', 'route' => 'attendances.index', 'permission' => 'attendance_sessions.view'],
                    ['label' => 'Jurnal kegiatan', 'route' => 'journals.index', 'permission' => 'journals.view'],
                    ['label' => 'Pengumuman', 'route' => 'announcements.index', 'permission' => 'announcements.view'],
                ],
            ],
            'keanggotaan' => [
                'title' => 'Keanggotaan',
                'description' => 'Anggota muda, pembina, dan satuan.',
                'icon' => 'users',
                'permissions' => ['students.view', 'coaches.view', 'scout_units.view'],
                'links' => [
                    ['label' => 'Anggota muda', 'route' => 'students.index', 'permission' => 'students.view'],
                    ['label' => 'Pembina', 'route' => 'coaches.index', 'permission' => 'coaches.view'],
                    ['label' => 'Satuan', 'route' => 'scout-units.index', 'permission' => 'scout_units.view'],
                ],
            ],
        ];
    }

    public static function activeSlug(): ?string
    {
        if (request()->routeIs('modules.show')) {
            return (string) request()->route('module');
        }

        $routes = [
            // Specific finance aliases must be checked before the broad administration.* pattern.
            'keuangan' => ['finance.*', 'administration.finance*'],
            'administrasi' => ['administration.*', 'scout-groups.*', 'letters.*', 'reports.published-documents.*'],
            'inventaris' => ['inventory.*'],
            'kegiatan' => ['activities.*', 'attendances.*', 'journals.*', 'announcements.index', 'announcements.create', 'announcements.edit'],
            'keanggotaan' => ['students.*', 'coaches.*', 'scout-units.*'],
        ];

        foreach ($routes as $slug => $patterns) {
            if (request()->routeIs(...$patterns)) {
                return $slug;
            }
        }

        return null;
    }

    public static function find(string $slug): ?array
    {
        $modules = self::all();

        return isset($modules[$slug]) ? ['slug' => $slug, ...$modules[$slug]] : null;
    }

    public static function permitted(Authenticatable $user, array $module): bool
    {
        foreach ($module['permissions'] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function accessibleLinks(Authenticatable $user, array $module): array
    {
        return array_values(array_filter(
            $module['links'],
            fn (array $link): bool => $user->can($link['permission'])
        ));
    }
}
