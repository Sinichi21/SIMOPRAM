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
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $schoolId = session('active_school_id');

        /*
        |--------------------------------------------------------------------------
        | System Admin
        |--------------------------------------------------------------------------
        |
        | Super Admin dan Admin Pramuka boleh tidak memilih sekolah.
        |
        */

        if ($user->isSystemAdmin()) {
            if ($schoolId) {
                $school = School::query()
                    ->where('is_active', true)
                    ->find($schoolId);

                if ($school) {
                    $this->schoolContext->set($school);
                } else {
                    session()->forget('active_school_id');
                    $schoolId = null;
                }
            }

            setPermissionsTeamId($schoolId);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | User Sekolah
        |--------------------------------------------------------------------------
        */

        $membershipQuery = $user
            ->schoolMemberships()
            ->where('is_active', true);

        if ($schoolId) {
            $hasMembership = (clone $membershipQuery)
                ->where('school_id', $schoolId)
                ->exists();

            if (! $hasMembership) {
                $schoolId = null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Tentukan Sekolah Default
        |--------------------------------------------------------------------------
        */

        if (! $schoolId) {
            $schoolId = (clone $membershipQuery)
                ->value('school_id');

            abort_if(
                ! $schoolId,
                403,
                'User tidak terhubung dengan sekolah aktif.'
            );

            session([
                'active_school_id' => $schoolId,
            ]);
        }

        $school = School::query()
            ->where('is_active', true)
            ->findOrFail($schoolId);

        $this->schoolContext->set($school);

        /*
        |--------------------------------------------------------------------------
        | Aktifkan Spatie Team
        |--------------------------------------------------------------------------
        */

        setPermissionsTeamId($school->id);

        /*
        |--------------------------------------------------------------------------
        | Hindari cache role sekolah sebelumnya
        |--------------------------------------------------------------------------
        */

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return $next($request);
    }
}