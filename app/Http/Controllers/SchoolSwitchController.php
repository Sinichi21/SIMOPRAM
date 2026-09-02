<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SchoolSwitchController extends Controller
{
    public function __invoke(
        Request $request
    ): RedirectResponse {
        $user =
            $request->user();

        abort_unless(
            $user,
            401
        );

        $schoolValue =
            $request->input(
                'school_id'
            );

        /*
        |--------------------------------------------------------------------------
        | GLOBAL MODE
        |--------------------------------------------------------------------------
        |
        | Hanya Super Admin.
        |
        */

        if (
            $schoolValue === 'global'
        ) {

            abort_unless(
                $user->isSuperAdmin(),
                403,
                'Hanya Super Admin yang dapat membuka Dashboard Global.'
            );

            session()->forget(
                'active_school_id'
            );

            app(
                SchoolContext::class
            )->clear();

            setPermissionsTeamId(
                null
            );

            $user->unsetRelation(
                'roles'
            );

            $user->unsetRelation(
                'permissions'
            );

            return redirect()
                ->route('dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi school_id
        |--------------------------------------------------------------------------
        */

        if (
            ! is_numeric(
                $schoolValue
            )
        ) {
            throw ValidationException::withMessages([
                'school_id' => 'Sekolah yang dipilih tidak valid.',
            ]);
        }

        $schoolId =
            (int) $schoolValue;

        $school =
            School::query()
                ->whereKey(
                    $schoolId
                )
                ->where(
                    'is_active',
                    true
                )
                ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | SYSTEM ADMIN
        |--------------------------------------------------------------------------
        |
        | Super Admin dan Scout Admin boleh masuk
        | ke seluruh sekolah aktif.
        |
        */

        if (
            ! $user->isSystemAdmin()
        ) {

            $hasMembership =
                $user
                    ->schoolMemberships()
                    ->where(
                        'school_id',
                        $school->id
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->whereNull(
                        'left_at'
                    )
                    ->exists();

            abort_unless(
                $hasMembership,
                403,
                'Anda tidak memiliki akses ke sekolah ini.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aktifkan sekolah
        |--------------------------------------------------------------------------
        */

        session([
            'active_school_id' => $school->id,
        ]);

        app(
            SchoolContext::class
        )->set(
            $school
        );

        setPermissionsTeamId(
            $school->id
        );

        $user->unsetRelation(
            'roles'
        );

        $user->unsetRelation(
            'permissions'
        );

        /*
        |--------------------------------------------------------------------------
        | Setelah switch SELALU ke Dashboard.
        |
        | Ini penting karena ketika dari global ke sekolah
        | atau sebaliknya kita tidak mau tetap berada di
        | tenant route yang membutuhkan school.required.
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('dashboard');
    }
}
