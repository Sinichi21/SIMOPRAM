<?php

namespace App\Livewire\Assessments;

use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\AssessmentConfigItem;
use App\Models\AssessmentFactor;
use App\Models\Semester;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Settings extends Component
{
    /*
    |--------------------------------------------------------------------------
    | Faktor Penilaian
    |--------------------------------------------------------------------------
    */

    public ?int $editingFactorId = null;

    public string $factor_name = '';

    public string $factor_code = '';

    public string $factor_description = '';

    public int $factor_sort_order = 0;

    public bool $factor_is_active = true;

    public string $factor_source_type = 'manual';

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi
    |--------------------------------------------------------------------------
    */

    public ?int $editingConfigId = null;

    public ?int $academic_year_id = null;

    public ?int $semester_id = null;

    public string $config_name = '';

    /*
    |--------------------------------------------------------------------------
    | Bobot
    |--------------------------------------------------------------------------
    */

    public array $weights = [];

    public function mount(): void
    {
        $year = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        $semester = Semester::query()
            ->where('is_active', true)
            ->first();

        $this->academic_year_id =
            $year?->id;

        $this->semester_id =
            $semester?->id;
    }

    protected function schoolId(): int
    {
        $schoolId = app(
            SchoolContext::class
        )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        return $schoolId;
    }

    /*
    |--------------------------------------------------------------------------
    | Faktor
    |--------------------------------------------------------------------------
    */

    public function saveFactor(): void
    {
        abort_unless(
            auth()->user()->can(
                'assessment_factors.manage'
            ),
            403
        );

        $schoolId =
            $this->schoolId();

        if (
            trim($this->factor_code) === ''
            &&
            trim($this->factor_name) !== ''
        ) {
            $this->factor_code =
                Str::slug(
                    $this->factor_name,
                    '_'
                );
        }

        $validated = $this->validate([
            'factor_name' => [
                'required',
                'string',
                'max:100',
            ],

            'factor_code' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'assessment_factors',
                    'code'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'school_id',
                            $schoolId
                        )
                    )
                    ->ignore(
                        $this->editingFactorId
                    ),
            ],

            'factor_description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'factor_source_type' => [
                'required',
                Rule::in([
                    'manual',
                    'attendance',
                ]),
            ],

            'factor_sort_order' => [
                'required',
                'integer',
                'min:0',
            ],

            'factor_is_active' => [
                'boolean',
            ],
        ]);

        $data = [
            'name' => trim(
                $validated[
                    'factor_name'
                ]
            ),

            'code' => trim(
                $validated[
                    'factor_code'
                ]
            ),

            'description' => filled(
                $validated[
                    'factor_description'
                ]
            )
                    ? trim(
                        $validated[
                            'factor_description'
                        ]
                    )
                    : null,

            'source_type' => $validated[
                    'factor_source_type'
                ],

            'sort_order' => $validated[
                    'factor_sort_order'
                ],

            'is_active' => $validated[
                    'factor_is_active'
                ],
        ];

        if ($this->editingFactorId) {
            AssessmentFactor::query()
                ->findOrFail(
                    $this->editingFactorId
                )
                ->update($data);

            $message =
                'Faktor penilaian berhasil diperbarui.';
        } else {
            AssessmentFactor::query()
                ->create($data);

            $message =
                'Faktor penilaian berhasil ditambahkan.';
        }

        $this->resetFactorForm();

        session()->flash(
            'success',
            $message
        );
    }

    public function editFactor(
        int $id
    ): void {
        $factor =
            AssessmentFactor::query()
                ->findOrFail($id);

        $this->editingFactorId =
            $factor->id;

        $this->factor_name =
            $factor->name;

        $this->factor_code =
            $factor->code;

        $this->factor_description =
            $factor->description ?? '';

        $this->factor_source_type =
            $factor->source_type;

        $this->factor_sort_order =
            $factor->sort_order;

        $this->factor_is_active =
            $factor->is_active;
    }

    public function toggleFactor(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'assessment_factors.manage'
            ),
            403
        );

        $factor =
            AssessmentFactor::query()
                ->findOrFail($id);

        $factor->update([
            'is_active' => ! $factor->is_active,
        ]);
    }

    public function cancelFactorEdit(): void
    {
        $this->resetFactorForm();
    }

    protected function resetFactorForm(): void
    {
        $this->editingFactorId = null;

        $this->factor_name = '';

        $this->factor_code = '';

        $this->factor_description = '';

        $this->factor_source_type = 'manual';

        $this->factor_sort_order = 0;

        $this->factor_is_active = true;

        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Penilaian
    |--------------------------------------------------------------------------
    */

    public function createConfig(): void
    {
        abort_unless(
            auth()->user()->can(
                'assessments.manage'
            ),
            403
        );

        $validated = $this->validate([
            'academic_year_id' => [
                'required',
                'integer',
            ],

            'semester_id' => [
                'required',
                'integer',
            ],

            'config_name' => [
                'required',
                'string',
                'max:150',
            ],
        ]);

        $year =
            AcademicYear::query()
                ->findOrFail(
                    $validated[
                        'academic_year_id'
                    ]
                );

        $semester =
            Semester::query()
                ->where(
                    'academic_year_id',
                    $year->id
                )
                ->findOrFail(
                    $validated[
                        'semester_id'
                    ]
                );

        $config =
            AssessmentConfig::query()
                ->create([
                    'academic_year_id' => $year->id,

                    'semester_id' => $semester->id,

                    'name' => trim(
                        $validated[
                            'config_name'
                        ]
                    ),

                    'is_active' => false,
                ]);

        $this->editingConfigId =
            $config->id;

        $this->weights = [];

        session()->flash(
            'success',
            'Konfigurasi penilaian berhasil dibuat. Atur bobot hingga total 100%.'
        );
    }

    public function editConfig(
        int $id
    ): void {
        $config =
            AssessmentConfig::query()
                ->with('items')
                ->findOrFail($id);

        $this->editingConfigId =
            $config->id;

        $this->academic_year_id =
            $config->academic_year_id;

        $this->semester_id =
            $config->semester_id;

        $this->config_name =
            $config->name;

        $this->weights =
            $config
                ->items
                ->mapWithKeys(
                    fn ($item) => [
                        (string)
                        $item
                            ->assessment_factor_id => (float) $item->weight,
                    ]
                )
                ->toArray();
    }

    public function saveWeights(): void
    {
        abort_unless(
            auth()->user()->can(
                'assessments.manage'
            ),
            403
        );

        if (! $this->editingConfigId) {
            throw ValidationException::withMessages([
                'weights' => 'Pilih konfigurasi penilaian terlebih dahulu.',
            ]);
        }

        $config =
            AssessmentConfig::query()
                ->findOrFail(
                    $this->editingConfigId
                );

        $activeFactors =
            AssessmentFactor::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'sort_order'
                )
                ->get();

        $cleanWeights = [];

        foreach ($activeFactors as $factor) {
            $weight =
                (float) (
                    $this->weights[
                        $factor->id
                    ] ?? 0
                );

            if ($weight < 0 || $weight > 100) {
                throw ValidationException::withMessages([
                    "weights.{$factor->id}" => 'Bobot harus berada antara 0 dan 100.',
                ]);
            }

            if ($weight > 0) {
                $cleanWeights[
                    $factor->id
                ] = $weight;
            }
        }

        if (empty($cleanWeights)) {
            throw ValidationException::withMessages([
                'weights' => 'Minimal satu faktor penilaian harus memiliki bobot.',
            ]);
        }

        $total =
            array_sum(
                $cleanWeights
            );

        if (
            abs(
                $total - 100
            ) > 0.01
        ) {
            throw ValidationException::withMessages([
                'weights' => 'Total bobot harus tepat 100%. Total saat ini: '
                    .number_format(
                        $total,
                        2
                    )
                    .'%.',
            ]);
        }

        DB::transaction(
            function () use (
                $config,
                $cleanWeights
            ): void {
                $config
                    ->items()
                    ->delete();

                $order = 0;

                foreach (
                    $cleanWeights as $factorId => $weight
                ) {
                    AssessmentConfigItem::query()
                        ->create([
                            'assessment_config_id' => $config->id,

                            'assessment_factor_id' => $factorId,

                            'weight' => $weight,

                            'sort_order' => $order++,
                        ]);
                }
            }
        );

        session()->flash(
            'success',
            'Bobot penilaian berhasil disimpan.'
        );
    }

    public function activateConfig(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'assessments.manage'
            ),
            403
        );

        $config =
            AssessmentConfig::query()
                ->with('items')
                ->findOrFail($id);

        $total =
            (float)
            $config
                ->items
                ->sum('weight');

        if (
            abs(
                $total - 100
            ) > 0.01
        ) {
            throw ValidationException::withMessages([
                'weights' => 'Konfigurasi tidak dapat diaktifkan karena total bobot belum 100%.',
            ]);
        }

        DB::transaction(
            function () use (
                $config
            ): void {
                AssessmentConfig::query()
                    ->where(
                        'academic_year_id',
                        $config
                            ->academic_year_id
                    )
                    ->where(
                        'semester_id',
                        $config
                            ->semester_id
                    )
                    ->where(
                        'id',
                        '!=',
                        $config->id
                    )
                    ->update([
                        'is_active' => false,
                    ]);

                $config->update([
                    'is_active' => true,
                ]);
            }
        );

        session()->flash(
            'success',
            'Konfigurasi penilaian berhasil diaktifkan.'
        );
    }

    public function render()
    {
        $academicYears =
            AcademicYear::query()
                ->orderByDesc(
                    'start_date'
                )
                ->get();

        $semesters =
            Semester::query()
                ->when(
                    $this->academic_year_id,
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $this
                            ->academic_year_id
                    )
                )
                ->orderBy(
                    'semester_number'
                )
                ->get();

        $factors =
            AssessmentFactor::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

        $configs =
            AssessmentConfig::query()
                ->with([
                    'academicYear',
                    'semester',
                    'items.factor',
                ])
                ->latest()
                ->get();

        $editingConfig =
            $this->editingConfigId
                ? $configs->firstWhere(
                    'id',
                    $this->editingConfigId
                )
                : null;

        return view(
            'livewire.assessments.settings',
            compact(
                'academicYears',
                'semesters',
                'factors',
                'configs',
                'editingConfig'
            )
        );
    }
}
