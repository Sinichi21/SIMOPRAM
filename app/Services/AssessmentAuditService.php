<?php

namespace App\Services;

use App\Models\AssessmentAuditLog;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\Model;

class AssessmentAuditService
{
    public function record(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?string $module = null
    ): AssessmentAuditLog {
        $schoolId =
            app(
                SchoolContext::class
            )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        return AssessmentAuditLog::query()
            ->create([
                'user_id' => auth()->id(),

                'action' => $action,

                'module' => $module,

                'subject_type' => $subject
                        ? get_class(
                            $subject
                        )
                        : null,

                'subject_id' => $subject
                        ? $subject->getKey()
                        : null,

                'description' => $description,

                'old_values' => $oldValues,

                'new_values' => $newValues,

                'metadata' => $metadata,

                'created_at' => now(),
            ]);
    }
}
