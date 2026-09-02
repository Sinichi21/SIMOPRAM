<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolSwitchController extends Controller
{
    public function __invoke(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'school_id' => [
                'required',
                'integer',
                'exists:schools,id',
            ],
        ]);

        $user = $request->user();

        $school = School::query()
            ->where('is_active', true)
            ->findOrFail(
                $validated['school_id']
            );

        /*
        |--------------------------------------------------------------------------
        | Non System Admin
        |--------------------------------------------------------------------------
        */

        if (! $user->isSystemAdmin()) {

            $allowed = $user
                ->schoolMemberships()
                ->where(
                    'school_id',
                    $school->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->exists();

            abort_unless(
                $allowed,
                403,
                'Anda tidak memiliki akses ke sekolah ini.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Tenant Aktif
        |--------------------------------------------------------------------------
        */

        session([
            'active_school_id' =>
                $school->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Spatie Team
        |--------------------------------------------------------------------------
        */

        setPermissionsTeamId(
            $school->id
        );

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Sekolah aktif diubah menjadi '
                . $school->name
                . '.'
            );
    }
}