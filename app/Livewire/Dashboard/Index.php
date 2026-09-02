<?php

namespace App\Livewire\Dashboard;

use App\Services\GlobalDashboardService;
use App\Services\GudepDashboardService;
use App\Support\SchoolContext;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $user =
            auth()->user();

        $schoolContext =
            app(
                SchoolContext::class
            );

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN GLOBAL MODE
        |--------------------------------------------------------------------------
        */

        if (
            $user->isSuperAdmin()
            &&
            ! $schoolContext->hasSchool()
        ) {
            return view(
                'livewire.dashboard.index',
                [
                    'mode' => 'global',

                    'school' => null,

                    'dashboard' => app(
                        GlobalDashboardService::class
                    )->data(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD SEKOLAH
        |--------------------------------------------------------------------------
        */

        if (
            $schoolContext->hasSchool()
        ) {
            return view(
                'livewire.dashboard.index',
                [
                    'mode' => 'school',

                    'school' => $schoolContext
                        ->school(),

                    'dashboard' => app(
                        GudepDashboardService::class
                    )->data(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tidak memiliki sekolah
        |--------------------------------------------------------------------------
        */

        return view(
            'livewire.dashboard.index',
            [
                'mode' => 'none',

                'school' => null,

                'dashboard' => null,
            ]
        );
    }
}
