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

        // Restore the most recently selected school after the browser session
        // expires or a new authenticated session starts. Never trust a saved
        // school identifier without checking current tenant access again.
        if (! $activeSchoolId && $user->last_active_school_id) {
            $savedId = (int) $user->last_active_school_id;
            $savedSchool = School::query()
                ->whereKey($savedId)
                ->where('is_active', true)
                ->first();

            $canRestore = $savedSchool !== null && (
                $user->isSystemAdmin()
                || $user->schoolMemberships()
                    ->where('school_id', $savedId)
                    ->where('is_active', true)
                    ->whereNull('left_at')
                    ->exists()
            );

            if ($canRestore) {
                session(['active_school_id' => $savedId]);
                $activeSchoolId = $savedId;
            } else {
                // Deleted/inactive school or revoked membership cannot survive
                // as a preference; never fall back to an unauthorized tenant.
                $user->last_active_school_id = null;
                $user->save();
            }
        }

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
        // Also migrate an already-active session into the durable preference.
        // This writes only when the selected school actually changes.
        if ((int) $user->last_active_school_id !== (int) $school->id) {
            $user->last_active_school_id = $school->id;
            $user->save();
        }

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
