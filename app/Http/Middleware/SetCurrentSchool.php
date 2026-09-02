<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Support\SchoolContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentSchool
{
    public function __construct(
        protected SchoolContext $schoolContext
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $activeSchoolId =
            session('active_school_id');

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        |
        | Tidak ada active_school_id berarti GLOBAL MODE.
        |
        */

        if ($user->isSuperAdmin()) {

            if (! $activeSchoolId) {
                $this->useGlobalContext(
                    $user
                );

                return $next($request);
            }

            $school =
                School::query()
                    ->whereKey(
                        $activeSchoolId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->first();

            /*
            |--------------------------------------------------------------------------
            | Sekolah sudah tidak tersedia.
            | Kembalikan Super Admin ke Global Mode.
            |--------------------------------------------------------------------------
            */

            if (! $school) {
                session()->forget(
                    'active_school_id'
                );

                $this->useGlobalContext(
                    $user
                );

                return $next($request);
            }

            $this->useSchoolContext(
                $user,
                $school
            );

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | SCOUT ADMIN
        |--------------------------------------------------------------------------
        |
        | Scout admin boleh memilih sekolah,
        | tetapi tidak mendapat Global Dashboard.
        |
        */

        if ($user->isScoutAdmin()) {

            if ($activeSchoolId) {
                $school =
                    School::query()
                        ->whereKey(
                            $activeSchoolId
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();

                if ($school) {
                    $this->useSchoolContext(
                        $user,
                        $school
                    );

                    return $next($request);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Default ke sekolah aktif pertama
            |--------------------------------------------------------------------------
            */

            $school =
                School::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy('name')
                    ->first();

            if ($school) {
                session([
                    'active_school_id' => $school->id,
                ]);

                $this->useSchoolContext(
                    $user,
                    $school
                );
            }

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | USER SEKOLAH
        |--------------------------------------------------------------------------
        |
        | school_admin / coach / student hanya boleh memilih
        | sekolah yang memiliki membership aktif.
        |
        */

        if ($activeSchoolId) {

            $membership =
                $user
                    ->schoolMemberships()
                    ->where(
                        'school_id',
                        $activeSchoolId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->whereNull(
                        'left_at'
                    )
                    ->first();

            if ($membership) {

                $school =
                    School::query()
                        ->whereKey(
                            $activeSchoolId
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();

                if ($school) {
                    $this->useSchoolContext(
                        $user,
                        $school
                    );

                    return $next($request);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Cari membership pertama sebagai default
        |--------------------------------------------------------------------------
        */

        $membership =
            $user
                ->schoolMemberships()
                ->where(
                    'is_active',
                    true
                )
                ->whereNull(
                    'left_at'
                )
                ->with('school')
                ->get()
                ->first(
                    fn ($membership) => $membership->school
                        &&
                        $membership
                            ->school
                            ->is_active
                );

        if ($membership?->school) {

            $school =
                $membership->school;

            session([
                'active_school_id' => $school->id,
            ]);

            $this->useSchoolContext(
                $user,
                $school
            );

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | User tidak mempunyai sekolah
        |--------------------------------------------------------------------------
        */

        session()->forget(
            'active_school_id'
        );

        $this->schoolContext
            ->clear();

        setPermissionsTeamId(
            null
        );

        $this->clearPermissionRelations(
            $user
        );

        return $next($request);
    }

    protected function useGlobalContext(
        $user
    ): void {
        $this->schoolContext
            ->clear();

        setPermissionsTeamId(
            null
        );

        $this->clearPermissionRelations(
            $user
        );
    }

    protected function useSchoolContext(
        $user,
        School $school
    ): void {
        $this->schoolContext
            ->set($school);

        setPermissionsTeamId(
            $school->id
        );

        $this->clearPermissionRelations(
            $user
        );
    }

    protected function clearPermissionRelations(
        $user
    ): void {
        $user->unsetRelation(
            'roles'
        );

        $user->unsetRelation(
            'permissions'
        );
    }
}
