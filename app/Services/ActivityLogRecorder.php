<?php

namespace App\Services;

use App\Models\FinalGrade;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ActivityLogRecorder
{
    public function __construct(private ActivityLogger $logger) {}

    public function model(string $event, Model $model): void
    {
        $name = class_basename($model);
        $module = config('activity-log.models.'.$name);
        if (! $module || (! str_starts_with($model::class, 'App\\Models\\') && ! str_starts_with($model::class, 'Spatie\\Permission\\Models\\'))) {
            return;
        }

        $attributes = Arr::except($model->getAttributes(), ['created_at', 'updated_at', 'remember_token']);
        $old = null;
        $new = $attributes;
        $action = $event;
        $type = 'audit';
        $status = 'success';

        if ($event === 'updated') {
            $changes = Arr::except($model->getChanges(), ['created_at', 'updated_at', 'remember_token']);
            if ($changes === [] || array_keys($changes) === ['deleted_at'] && $changes['deleted_at'] === null) {
                return;
            }
            if ($model instanceof User && array_key_exists('password', $changes)) {
                $this->logger->record('users', 'password_changed', $model, type: 'security');
                unset($changes['password']);
                if ($changes === []) {
                    return;
                }
            }
            $new = $changes;
            $old = Arr::only($model->getRawOriginal(), array_keys($changes));
            $decision = $changes['approval_status'] ?? ($changes['status'] ?? null);
            if (in_array($decision, ['approved', 'rejected'], true)) {
                $action = $decision;
            }
            if ($name === 'UserTransfer' && $decision === 'accepted'
                || $name === 'ReportVerification' && array_key_exists('signatory_approvals', $changes)) {
                $action = 'approved';
            }
        } elseif ($event === 'deleted') {
            $old = $attributes;
            $new = null;
        }

        if ($event === 'created' && in_array($name, ['JournalAttachment', 'LetterAttachment'], true)) {
            $action = 'uploaded';
        }
        if ($model instanceof NotificationLog) {
            if (! in_array($model->status, ['sent', 'failed'], true)) {
                return;
            }
            $action = $model->status;
            $status = $action === 'failed' ? 'failed' : 'success';
        }

        $description = config('activity-log.actions.'.$action).' '.$name.' #'.$model->getKey();
        if ($model instanceof StudentScore || $model instanceof FinalGrade) {
            $student = Student::withoutGlobalScopes()->whereKey($model->student_id)->first();
            $description = config('activity-log.actions.'.$action).' nilai siswa '.($student->name ?? '#'.$model->student_id).' ('.$name.' #'.$model->getKey().')';
        }
        $this->logger->record($module, $action, $model, $old, $new, $status, $type, description: Str::limit($description, 495));
    }
}
