<?php

namespace App\Services;

use App\Models\Activity;
use App\Support\SchoolContext;
use Illuminate\Validation\ValidationException;

class ActivityHierarchyService
{
    public function assignParent(Activity $activity, ?int $parentId): void
    {
        if ($activity->exists && $activity->getOriginal('parent_activity_id') === $parentId) {
            return;
        }
        if ($parentId === null) {
            $activity->parent_activity_id = null;

            return;
        }
        $parent = Activity::withoutGlobalScope('school')->where('school_id', $activity->school_id)
            ->whereNull('parent_activity_id')->lockForUpdate()->find($parentId);
        if (! $parent || $parentId === $activity->id || ($activity->exists && $activity->subActivities()->withoutGlobalScope('school')->exists())) {
            throw ValidationException::withMessages(['parentActivityId' => 'Pilih agenda utama yang valid. Agenda yang sudah memiliki subagenda tidak dapat dijadikan subagenda.']);
        }
        if ($activity->school_id === null) {
            app(GlobalActivityAccess::class)->authorize($parent);
        } else {
            abort_unless(app(SchoolContext::class)->id() === $activity->school_id && auth()->user()?->can('activities.update'), 403);
        }
        $activity->parent_activity_id = $parent->id;
    }
}
