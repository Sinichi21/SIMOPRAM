<?php

namespace App\View\Components;

use App\Models\School;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class AreaNavigation extends Component
{
    public function __construct(public ?School $school = null) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        $user = auth()->user();
        $navigationSchool = $this->school;
        $dashboardLabel = 'Dashboard';

        if ($user) {
            $memberships = $user->schoolMemberships()->where('is_active', true)->whereNull('left_at');
            if (! $navigationSchool) {
                $navigationSchool = School::query()->where('is_active', true)
                    ->when(! $user->isSystemAdmin(), fn ($query) => $query->whereIn('id', (clone $memberships)->select('school_id')))
                    ->when($user->isSystemAdmin(), fn ($query) => $query->whereKey(session('active_school_id')))
                    ->orderByRaw('case when id = ? then 0 else 1 end', [session('active_school_id', 0)])
                    ->orderBy('name')->first();
            }

            $roles = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_id', $user->id)->where('model_type', $user->getMorphClass())
                ->whereIn('model_has_roles.school_id', (clone $memberships)->select('school_id'))
                ->pluck('roles.name');
            $isStudent = $roles->contains('student') || Student::withoutGlobalScope('school')->where('user_id', $user->id)->exists();
            $dashboardLabel = $user->isSystemAdmin() || $roles->contains('school_admin')
                ? 'Admin Area' : ($isStudent ? 'Student Area' : 'Dashboard');
        }

        return view('components.area-navigation', compact('navigationSchool', 'dashboardLabel'));
    }
}
