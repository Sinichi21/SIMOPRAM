<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GlobalActivityAttendance
{
    public const STATUSES = ['present' => 'Hadir', 'excused' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Alpa'];

    public function activity(int $id): Activity
    {
        $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->findOrFail($id);
        app(GlobalActivityAccess::class)->authorize($activity);

        return $activity;
    }

    public function participants(Activity $activity, string $search = '', string $role = '', string $status = ''): Builder
    {
        return ActivityRegistration::where('activity_id', $activity->id)->where('status', 'active')
            ->whereHas('entry', fn (Builder $query) => $query->where('activity_id', $activity->id)->where('status', 'active')->where('validation_status', 'validated'))
            ->with('entry:id,name,category')->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->where('name', 'like', '%'.$search.'%')->orWhere('identifier', 'like', '%'.$search.'%')->orWhere('school_name', 'like', '%'.$search.'%')
            ->orWhereHas('entry', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))))
            ->when($role !== '', fn (Builder $query) => $query->where('role', $role))
            ->when($status !== '', fn (Builder $query) => $status === 'unmarked' ? $query->whereNull('attendance_status') : $query->where('attendance_status', $status))
            ->orderBy('entry_id')->orderBy('role')->orderBy('name')->orderBy('id');
    }

    public function mark(int $activityId, int $registrationId, string $status): void
    {
        validator(['attendance' => $status], ['attendance' => ['required', Rule::in([...array_keys(self::STATUSES), 'unmarked'])]])->validate();
        DB::transaction(function () use ($activityId, $registrationId, $status): void {
            $activity = $this->activity($activityId);
            $registration = $this->participants($activity)->lockForUpdate()->findOrFail($registrationId);
            $registration->forceFill(['attendance_status' => $status === 'unmarked' ? null : $status,
                'checked_in_at' => $status === 'present' ? ($registration->checked_in_at ?? now()) : null,
                'attendance_marked_by' => auth()->id()])->save();
        });
    }
}
