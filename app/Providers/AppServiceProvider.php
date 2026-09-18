<?php

namespace App\Providers;

use App\Http\Middleware\RequireCurrentSchool;
use App\Http\Middleware\SetCurrentSchool;
use App\Http\Middleware\SetGlobalContentContext;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\ActivityEntry;
use App\Models\School;
use App\Models\User;
use App\Services\ActivityNotificationService;
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

        Activity::updated(fn (Activity $activity) => app(ActivityNotificationService::class)->updated($activity));
        ActivityEntry::updated(fn (ActivityEntry $entry) => app(ActivityNotificationService::class)->approved($entry));
        ActivityAssessment::updated(function (ActivityAssessment $assessment): void {
            if ($assessment->wasChanged('results_published_at') && $assessment->results_published_at !== null) {
                $activity = Activity::withoutGlobalScope('school')->find($assessment->activity_id);
                if ($activity && $activity->school_id === null && $activity->is_public && $activity->approval_status === 'approved'
                    && in_array($activity->status, ['published', 'ongoing', 'completed'], true)
                    && (! $activity->published_at || $activity->published_at->lte(now())) && $assessment->status === 'published') {
                    app(ActivityNotificationService::class)->queue($activity, 'Hasil penilaian diterbitkan',
                        'Hasil penilaian '.$assessment->title.' untuk '.$activity->title.' telah diterbitkan.'
                        ."\n\n".route('public.activities.results', ['activityId' => $activity->id, 'assessmentId' => $assessment->id]));
                }
            }
        });

        Gate::define('landing.manage', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('school-landing.manage', function (User $user, School $school): bool {
            return $user->schoolMemberships()->where('school_id', $school->id)
                ->where('is_active', true)->whereNull('left_at')->exists()
                && DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_id', $user->id)->where('model_type', $user->getMorphClass())
                    ->where('model_has_roles.school_id', $school->id)->where('roles.name', 'school_admin')->exists();
        });

        Gate::define('messaging.manage', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('schools.manage', fn (User $user): bool => $user->isSuperAdmin());

        Livewire::addPersistentMiddleware([
            SetCurrentSchool::class,
            RequireCurrentSchool::class,
            SetGlobalContentContext::class,
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
                            'simpram.scout_admin_permissions',
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
