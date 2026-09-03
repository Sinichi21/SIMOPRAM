<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentCriterion;
use App\Models\AssessmentFactor;
use App\Services\ActivityAssessmentService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Edit extends Component
{
    public int $assessmentId;

    public string $title = '';

    public ?int $assessmentFactorId = null;

    public string $mode = 'individual';

    public string $description = '';

    /*
    |--------------------------------------------------------------------------
    | Criterion Form
    |--------------------------------------------------------------------------
    */

    public ?int $editingCriterionId = null;

    public string $criterionName = '';

    public string $criterionDescription = '';

    public float $criterionMaxScore = 100;

    public float $criterionWeight = 0;

    public function mount(
        int $assessmentId
    ): void {
        $this->assessmentId =
            $assessmentId;

        $this->loadAssessment();
    }

    protected function assessment(): ActivityAssessment
    {
        return ActivityAssessment::query()
            ->with([
                'activity',
                'factor',
                'criteria',
                'targets',
            ])
            ->findOrFail(
                $this->assessmentId
            );
    }

    protected function loadAssessment(): void
    {
        $assessment =
            $this->assessment();

        $this->title =
            $assessment->title;

        $this->assessmentFactorId =
            $assessment
                ->assessment_factor_id;

        $this->mode =
            $assessment->mode;

        $this->description =
            $assessment
                ->description
                ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | Simpan Identitas Form
    |--------------------------------------------------------------------------
    */

    public function saveForm(): void
    {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.update'
                ),
            403
        );

        $assessment =
            $this->assessment();

        if (
            $assessment->status
            !== 'draft'
        ) {
            throw ValidationException::withMessages([
                'form' => 'Form yang sudah dipublikasikan harus dikembalikan '
                    .'ke Draft sebelum dapat diubah.',
            ]);
        }

        $validated =
            $this->validate([
                'title' => [
                    'required',
                    'string',
                    'max:200',
                ],

                'assessmentFactorId' => [
                    'required',
                    'integer',
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

        $factor =
            AssessmentFactor::query()
                ->findOrFail(
                    $validated[
                        'assessmentFactorId'
                    ]
                );

        if (
            $factor->source_type
            === 'attendance'
        ) {
            throw ValidationException::withMessages([
                'assessmentFactorId' => 'Faktor yang bersumber dari absensi tidak dapat '
                    .'digunakan sebagai tujuan Penilaian Kegiatan.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Mode tidak boleh berubah kalau sudah pernah ada nilai.
        |--------------------------------------------------------------------------
        */

        if (
            $assessment->mode
            !== $validated['mode']
            &&
            $assessment
                ->targets()
                ->whereNotNull(
                    'assessed_at'
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'mode' => 'Mode tidak dapat diubah karena form sudah memiliki nilai.',
            ]);
        }

        $modeChanged =
            $assessment->mode
            !== $validated['mode'];

        $assessment->update([
            'title' => trim(
                $validated['title']
            ),

            'assessment_factor_id' => $factor->id,

            'mode' => $validated['mode'],

            'description' => trim(
                $validated[
                    'description'
                ] ?? ''
            ) ?: null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Target lama yang belum dinilai tidak relevan jika mode berubah.
        |--------------------------------------------------------------------------
        */

        if ($modeChanged) {
            $assessment
                ->targets()
                ->delete();
        }

        session()->flash(
            'status',
            'Form penilaian berhasil diperbarui.'
        );

        $this->loadAssessment();
    }

    /*
    |--------------------------------------------------------------------------
    | Simpan Kriteria
    |--------------------------------------------------------------------------
    */

    public function saveCriterion(): void
    {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.update'
                ),
            403
        );

        $assessment =
            $this->assessment();

        if (
            $assessment->status
            !== 'draft'
        ) {
            throw ValidationException::withMessages([
                'criterion' => 'Kriteria hanya dapat diubah ketika form berstatus Draft.',
            ]);
        }

        $validated =
            $this->validate([
                'criterionName' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'criterionDescription' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'criterionMaxScore' => [
                    'required',
                    'numeric',
                    'gt:0',
                    'max:10000',
                ],

                'criterionWeight' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'max:100',
                ],
            ]);

        if (
            $this->editingCriterionId
        ) {
            $criterion =
                $assessment
                    ->criteria()
                    ->findOrFail(
                        $this
                            ->editingCriterionId
                    );
        } else {
            $criterion =
                new ActivityAssessmentCriterion;

            $criterion
                ->activity_assessment_id =
                    $assessment->id;

            $criterion
                ->sort_order =
                    (
                        (int) $assessment
                            ->criteria()
                            ->max(
                                'sort_order'
                            )
                    )
                    + 1;
        }

        $criterion->fill([
            'name' => trim(
                $validated[
                    'criterionName'
                ]
            ),

            'description' => trim(
                $validated[
                    'criterionDescription'
                ] ?? ''
            ) ?: null,

            'max_score' => $validated[
                    'criterionMaxScore'
                ],

            'weight' => $validated[
                    'criterionWeight'
                ],
        ]);

        $criterion->save();

        $this->resetCriterionForm();

        session()->flash(
            'status',
            'Kriteria penilaian berhasil disimpan.'
        );
    }

    public function editCriterion(
        int $criterionId
    ): void {
        $assessment =
            $this->assessment();

        $criterion =
            $assessment
                ->criteria()
                ->findOrFail(
                    $criterionId
                );

        $this->editingCriterionId =
            $criterion->id;

        $this->criterionName =
            $criterion->name;

        $this->criterionDescription =
            $criterion
                ->description
                ?? '';

        $this->criterionMaxScore =
            (float) $criterion
                ->max_score;

        $this->criterionWeight =
            (float) $criterion
                ->weight;
    }

    public function cancelCriterionEdit(): void
    {
        $this->resetCriterionForm();
    }

    protected function resetCriterionForm(): void
    {
        $this->editingCriterionId =
            null;

        $this->criterionName =
            '';

        $this->criterionDescription =
            '';

        $this->criterionMaxScore =
            100;

        $this->criterionWeight =
            0;

        $this->resetValidation();
    }

    public function deleteCriterion(
        int $criterionId
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.update'
                ),
            403
        );

        $assessment =
            $this->assessment();

        if (
            $assessment->status
            !== 'draft'
        ) {
            throw ValidationException::withMessages([
                'criterion' => 'Kriteria hanya dapat dihapus ketika form berstatus Draft.',
            ]);
        }

        $criterion =
            $assessment
                ->criteria()
                ->findOrFail(
                    $criterionId
                );

        if (
            $criterion
                ->scores()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'criterion' => 'Kriteria sudah memiliki nilai dan tidak dapat dihapus.',
            ]);
        }

        $criterion->delete();

        if (
            $this->editingCriterionId
            === $criterionId
        ) {
            $this->resetCriterionForm();
        }

        session()->flash(
            'status',
            'Kriteria berhasil dihapus.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Peserta
    |--------------------------------------------------------------------------
    */

    public function prepareTargets(
        ActivityAssessmentService $service
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.update'
                ),
            403
        );

        $assessment =
            $this->assessment();

        $created =
            $service->prepareTargets(
                $assessment
            );

        session()->flash(
            'status',
            "Peserta berhasil disiapkan. {$created} target baru dibuat."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Publish
    |--------------------------------------------------------------------------
    */

    public function publish(
        ActivityAssessmentService $service
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.publish'
                ),
            403
        );

        $assessment =
            $this->assessment();

        $service->publish(
            $assessment
        );

        session()->flash(
            'status',
            'Form penilaian berhasil dipublikasikan dan siap dinilai.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reopen
    |--------------------------------------------------------------------------
    */

    public function reopen(
        ActivityAssessmentService $service
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.publish'
                ),
            403
        );

        $assessment =
            $this->assessment();

        $service->reopen(
            $assessment
        );

        session()->flash(
            'status',
            'Form dikembalikan ke status Draft.'
        );
    }

    public function render()
    {
        $assessment =
            $this->assessment();

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

        $totalWeight =
            (float) $assessment
                ->criteria
                ->sum(
                    'weight'
                );

        $targetCount =
            $assessment
                ->targets
                ->count();

        $assessedCount =
            $assessment
                ->targets
                ->whereNotNull(
                    'assessed_at'
                )
                ->count();

        return view(
            'livewire.assessments.activities.edit',
            [
                'assessment' => $assessment,

                'factors' => $factors,

                'totalWeight' => $totalWeight,

                'targetCount' => $targetCount,

                'assessedCount' => $assessedCount,
            ]
        );
    }
}
