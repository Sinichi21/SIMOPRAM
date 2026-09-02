<?php

namespace App\Providers;

use App\Http\Middleware\RequireCurrentSchool;
use App\Http\Middleware\SetCurrentSchool;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(
            SchoolContext::class,
            fn () => new SchoolContext
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Livewire::addPersistentMiddleware([
            SetCurrentSchool::class,
            RequireCurrentSchool::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        Gate::before(
            function (
                User $user,
                string $ability
            ): ?bool {

                if ($user->isSuperAdmin()) {
                    return true;
                }

                if (
                    $user->isScoutAdmin() &&
                    in_array(
                        $ability,
                        config(
                            'simopram.scout_admin_permissions',
                            []
                        ),
                        true
                    )
                ) {
                    return true;
                }

                return null;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | School Switcher
        |--------------------------------------------------------------------------
        */

        View::composer(
            'components.sidebar',
            function ($view): void {

                $user =
                    Auth::user();

                if (! $user) {
                    $view->with([
                        'schools' => collect(),
                        'activeSchool' => null,
                    ]);

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | System Admin
                |--------------------------------------------------------------------------
                */

                if (
                    $user->isSystemAdmin()
                ) {
                    $schools =
                        School::query()
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy('name')
                            ->get();
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | User biasa
                    |--------------------------------------------------------------------------
                    */

                    $schoolIds =
                        $user
                            ->schoolMemberships()
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNull(
                                'left_at'
                            )
                            ->pluck(
                                'school_id'
                            );

                    $schools =
                        School::query()
                            ->whereIn(
                                'id',
                                $schoolIds
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy('name')
                            ->get();
                }

                $view->with([
                    'schools' => $schools,
                    'activeSchool' => app(SchoolContext::class)->school(),
                ]);
            }
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
