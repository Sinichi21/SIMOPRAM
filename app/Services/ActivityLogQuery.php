<?php

namespace App\Services;

use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ActivityLogQuery
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'school' => ['nullable', 'integer', 'min:1'],
            'user' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', 'string', 'max:50'],
            'module' => ['nullable', Rule::in(array_keys(config('activity-log.modules')))],
            'action' => ['nullable', Rule::in(array_keys(config('activity-log.actions')))],
            'status' => ['nullable', Rule::in(['success', 'failed'])],
            'type' => ['nullable', Rule::in(['audit', 'security', 'system'])],
            'request_id' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @param array<string, mixed> $filters
     * @return Builder<ActivityLog>
     */
    public function build(array $filters): Builder
    {
        $filters = Validator::make($filters, $this->rules())->validate();
        $zone = config('activity-log.timezone');
        $storageZone = config('app.timezone');
        $from = CarbonImmutable::parse($filters['from'], $zone)->startOfDay()->setTimezone($storageZone);
        $until = CarbonImmutable::parse($filters['to'], $zone)->addDay()->startOfDay()->setTimezone($storageZone);
        $query = ActivityLog::query()->where('occurred_at', '>=', $from)->where('occurred_at', '<', $until);

        foreach (['school' => 'school_id', 'role' => 'role', 'module' => 'module', 'action' => 'action', 'status' => 'status', 'type' => 'log_type', 'request_id' => 'request_id'] as $filter => $column) {
            if (filled($filters[$filter] ?? null)) {
                $query->where($column, $filters[$filter]);
            }
        }
        if (filled($filters['user'] ?? null)) {
            ctype_digit($filters['user'])
                ? $query->where('user_id', (int) $filters['user'])
                : $query->where('user_name', 'like', '%'.$filters['user'].'%');
        }

        return $query->orderByDesc('occurred_at')->orderByDesc('id');
    }
}
