<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentFactor;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public ?int $activityId = null;

    public ?int $assessmentFactorId = null;

    public string $title = '';

    public string $mode = 'individual';

    public string $description = '';

    public string $search = '';

    public function create(): void
    {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.create'
                ),
            403
        );

        $validated =
            $this->validate([
                'activityId' => [
                    'required',
                    'integer',
                ],

                'assessmentFactorId' => [
                    'required',
                    'integer',
                ],

                'title' => [
                    'required',
                    'string',
                    'max:200',
                ],

                'mode' => [
                    'required',
                    Rule::in([
                        'individual',
                        'team',
                    ]),
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tenant validation
        |--------------------------------------------------------------------------
        */

        Activity::query()
            ->findOrFail(
                $validated[
                    'activityId'
                ]
            );

        AssessmentFactor::query()
            ->where(
                'source_type',
                '!=',
                'attendance'
            )
            ->findOrFail(
                $validated[
                    'assessmentFactorId'
                ]
            );

        $assessment =
            ActivityAssessment::query()
                ->create([
                    'activity_id' => $validated[
                            'activityId'
                        ],

                    'assessment_factor_id' => $validated[
                            'assessmentFactorId'
                        ],

                    'title' => trim(
                        $validated[
                            'title'
                        ]
                    ),

                    'mode' => $validated[
                            'mode'
                        ],

                    'status' => 'draft',

                    'description' => trim(
                        $validated[
                            'description'
                        ] ?? ''
                    ) ?: null,

                    'created_by' => auth()->id(),
                ]);

        $this->reset([
            'activityId',
            'assessmentFactorId',
            'title',
            'description',
        ]);

        $this->mode =
            'individual';

        session()->flash(
            'status',
            'Form penilaian kegiatan berhasil dibuat.'
        );

        $this->redirectRoute(
            'activity-assessments.edit',
            [
                'assessment' => $assessment->id,
            ],
            navigate: true
        );
    }

    public function render()
    {
        $activities =
            Activity::query()
                ->orderByDesc(
                    'start_at'
                )
                ->get([
                    'id',
                    'title',
                    'start_at',
                ]);

        $factors =
            AssessmentFactor::query()
                ->where(
                    'source_type',
                    '!=',
                    'attendance'
                )
                ->orderBy(
                    'name'
                )
                ->get();

        $assessments =
            ActivityAssessment::query()
                ->with([
                    'activity',
                    'factor',
                ])
                ->when(
                    trim(
                        $this->search
                    ) !== '',
                    function ($query): void {
                        $search =
                            '%'
                            .trim(
                                $this->search
                            )
                            .'%';

                        $query->where(
                            function ($query) use (
                                $search
                            ): void {
                                $query
                                    ->where(
                                        'title',
                                        'like',
                                        $search
                                    )
                                    ->orWhereHas(
                                        'activity',
                                        fn ($query) => $query->where(
                                            'title',
                                            'like',
                                            $search
                                        )
                                    );
                            }
                        );
                    }
                )
                ->latest()
                ->get();

        return view(
            'livewire.assessments.activities.index',
            [
                'activities' => $activities,

                'factors' => $factors,

                'assessments' => $assessments,
            ]
        );
    }
}
