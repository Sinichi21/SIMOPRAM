<?php

namespace App\Models\Concerns;

use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(
            'school',
            function (Builder $builder): void {

                $schoolId = app(
                    SchoolContext::class
                )->id();

                if ($schoolId) {
                    $builder->where(
                        $builder->getModel()
                            ->qualifyColumn('school_id'),
                        $schoolId
                    );
                }
            }
        );

        static::creating(
            function ($model): void {

                $schoolId = app(
                    SchoolContext::class
                )->id();

                if (
                    $schoolId &&
                    empty($model->school_id)
                ) {
                    $model->school_id =
                        $schoolId;
                }
            }
        );
    }
}
