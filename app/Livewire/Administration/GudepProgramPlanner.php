<?php

declare(strict_types=1);

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\GudepFormation;
use App\Models\GudepProgramSheet;
use App\Models\ScoutGroup;
use App\Models\Semester;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GudepProgramPlanner extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public ?int $scoutLevelId = null;

    public ?int $parentId = null;

    public ?int $activityId = null;

    public ?int $editingId = null;

    public ?int $previewId = null;

    public string $kind = 'annual';

    public string $title = '';

    public string $scheduledFor = '';

    public string $notes = '';

    /** @var array<int, array<string, mixed>> */
    public array $entries = [];

    /** @return array<string, string> */
    public static function annualSections(): array
    {
        return [
            'siaga' => 'A.1 Bidang Kegiatan dan Latihan — Siaga',
            'penggalang' => 'A.2 Bidang Kegiatan dan Latihan — Penggalang',
            'penegak' => 'A.3 Bidang Kegiatan dan Latihan — Penegak',
            'pandega' => 'A.4 Bidang Kegiatan dan Latihan — Pandega',
            'bersama' => 'B. Kegiatan Bersama Antar Satuan',
            'dewasa' => 'C. Pendidikan Orang Dewasa',
            'penghargaan' => 'D. Tanda Penghargaan',
            'sarana' => 'E. Sarana dan Administrasi',
            'keuangan' => 'F. Keuangan dan Usaha Dana',
        ];
    }

    public function mount(): void
    {
        $this->authorizeView();
        $this->scoutGroupId = ScoutGroup::query()->where('school_id', $this->schoolId())->orderBy('id')->value('id');
        $this->academicYearId = AcademicYear::query()->where('school_id', $this->schoolId())
            ->where('is_active', true)->value('id');
        $this->resetDraft();
    }

    public function updatedScoutGroupId(): void
    {
        $this->resetDraft();
    }

    public function updatedAcademicYearId(): void
    {
        $this->resetDraft();
    }

    public function updatedKind(): void
    {
        $this->resetDraft();
    }

    public function resetDraft(): void
    {
        $this->reset(['editingId', 'semesterId', 'scoutLevelId', 'parentId', 'activityId', 'scheduledFor', 'notes']);
        $this->title = '';
        $this->entries = [];
    }

    public function addRow(?string $section = null): void
    {
        if ($this->kind === 'annual') {
            $section = array_key_exists((string) $section, self::annualSections()) ? $section : 'bersama';
            $this->entries[] = ['section' => $section, 'description' => '', 'quantity' => ''];
        } elseif ($this->kind === 'semester') {
            $this->entries[] = ['description' => '', 'months' => [false, false, false, false, false, false], 'participants' => '', 'notes' => ''];
        } else {
            $this->entries[] = ['description' => '', 'sku' => '', 'skk' => '', 'notes' => ''];
        }
    }

    public function removeRow(int $index): void
    {
        $this->authorizeManage();
        if (! array_key_exists($index, $this->entries)) {
            return;
        }
        array_splice($this->entries, $index, 1);
    }

    public function preview(int $id): void
    {
        $this->authorizeView();
        $sheet = GudepProgramSheet::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)->find($id);
        abort_unless($sheet !== null, 404);
        $this->previewId = $id;
    }

    public function edit(int $id): void
    {
        $this->authorizeManage();
        $sheet = GudepProgramSheet::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)->find($id);

        abort_unless($sheet !== null, 404);

        $this->kind = $sheet->kind;
        $this->editingId = $sheet->id;
        $this->title = $sheet->title;
        $this->semesterId = $sheet->semester_id;
        $this->scoutLevelId = $sheet->scout_level_id;
        $this->parentId = $sheet->parent_id;
        $this->activityId = $sheet->activity_id;
        $this->scheduledFor = $sheet->scheduled_for?->format('Y-m-d') ?? '';
        $this->notes = $sheet->notes ?? '';
        $this->entries = $sheet->entries ?? [];
    }

    public function save(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();

        $rules = [
            'kind' => ['required', Rule::in(['annual', 'semester', 'weekly'])],
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'semesterId' => ['nullable', 'integer', Rule::exists('semesters', 'id')
                ->where('school_id', $this->schoolId())->where('academic_year_id', $year->id)],
            'scoutLevelId' => ['nullable', 'integer', Rule::exists('scout_levels', 'id')],
            'parentId' => ['nullable', 'integer', Rule::exists('gudep_program_sheets', 'id')
                ->where('school_id', $this->schoolId())->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)],
            'activityId' => ['nullable', 'integer', Rule::exists('activities', 'id')
                ->where('school_id', $this->schoolId())->where('academic_year_id', $year->id)
                ->whereNull('deleted_at')],
            'scheduledFor' => ['nullable', 'date'],
            'entries' => ['required', 'array', 'min:1', 'max:150'],
        ];

        if ($this->kind === 'annual') {
            $rules['entries.*.section'] = ['required', Rule::in(array_keys(self::annualSections()))];
            $rules['entries.*.description'] = ['required', 'string', 'max:500'];
            $rules['entries.*.quantity'] = ['nullable', 'integer', 'min:0', 'max:999999'];
        } elseif ($this->kind === 'semester') {
            $rules['semesterId'] = ['required', 'integer', Rule::exists('semesters', 'id')->where('school_id', $this->schoolId())->where('academic_year_id', $year->id)];
            $rules['scoutLevelId'] = ['required', 'integer', Rule::exists('scout_levels', 'id')];
            $rules['entries.*.description'] = ['required', 'string', 'max:500'];
            $rules['entries.*.months'] = ['required', 'array', 'size:6'];
            $rules['entries.*.months.*'] = ['boolean'];
            $rules['entries.*.participants'] = ['nullable', 'integer', 'min:0', 'max:999999'];
            $rules['entries.*.notes'] = ['nullable', 'string', 'max:500'];
        } else {
            $rules['semesterId'] = ['required', ...$rules['semesterId']];
            $rules['scoutLevelId'] = ['required', ...$rules['scoutLevelId']];
            $rules['scheduledFor'] = ['required', 'date'];
            $rules['entries.*.description'] = ['required', 'string', 'max:500'];
            $rules['entries.*.sku'] = ['nullable', 'string', 'max:150'];
            $rules['entries.*.skk'] = ['nullable', 'string', 'max:150'];
            $rules['entries.*.notes'] = ['nullable', 'string', 'max:500'];
        }

        $this->validate($rules);

        $validLevel = $this->scoutLevelId === null || GudepFormation::query()
            ->where('school_id', $this->schoolId())->where('scout_group_id', $gudep->id)
            ->where('academic_year_id', $year->id)->where('scout_level_id', $this->scoutLevelId)
            ->where('is_active', true)->exists();
        if (! $validLevel) {
            $this->addError('scoutLevelId', 'Golongan ini tidak tersedia pada Gudep dan tahun ajaran yang dipilih.');

            return;
        }

        if ($this->parentId !== null) {
            $parent = GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->findOrFail($this->parentId);
            $expectedKind = $this->kind === 'semester' ? 'annual' : 'semester';
            if ($parent->kind !== $expectedKind || $parent->id === $this->editingId
                || ($this->kind === 'weekly' && (
                    $parent->semester_id !== $this->semesterId
                    || $parent->scout_level_id !== $this->scoutLevelId
                ))) {
                $this->addError('parentId', 'Program induk tidak sesuai jenjang atau semester.');

                return;
            }
        }

        if ($this->kind === 'weekly') {
            if ($this->activityId !== null) {
                $activity = Activity::query()->where('school_id', $this->schoolId())
                    ->where('academic_year_id', $year->id)->findOrFail($this->activityId);

                // An activity assigned to another semester must never be linked here.
                if ($activity->semester_id !== null && (int) $activity->semester_id !== (int) $this->semesterId) {
                    $this->addError('activityId', 'Kegiatan harus berada pada semester yang dipilih.');

                    return;
                }

                if ($activity->start_at !== null && $activity->start_at->toDateString() !== $this->scheduledFor) {
                    $this->addError('activityId', 'Tanggal latihan harus sama dengan tanggal kegiatan yang ditautkan.');

                    return;
                }
            }

            $semester = Semester::query()->where('school_id', $this->schoolId())
                ->where('academic_year_id', $year->id)->findOrFail($this->semesterId);
            if ($this->scheduledFor < $semester->start_date->format('Y-m-d')
                || $this->scheduledFor > $semester->end_date->format('Y-m-d')) {
                $this->addError('scheduledFor', 'Tanggal latihan harus berada dalam semester terpilih.');

                return;
            }
        }

        $entries = array_map(function (array $entry): array {
            $entry['description'] = trim((string) ($entry['description'] ?? ''));
            if ($this->kind === 'annual') {
                $entry['quantity'] = ($entry['quantity'] ?? '') === '' ? null : (int) $entry['quantity'];
            } elseif ($this->kind === 'semester') {
                $entry['participants'] = ($entry['participants'] ?? '') === '' ? null : (int) $entry['participants'];
                $entry['months'] = array_map(fn ($v): bool => (bool) $v, $entry['months']);
            }

            return $entry;
        }, $this->entries);

        $sheet = $this->editingId === null
            ? new GudepProgramSheet
            : GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->findOrFail($this->editingId);

        $sheet->fill([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'kind' => $this->kind,
            'title' => trim($this->title),
            'semester_id' => $this->kind === 'annual' ? null : $this->semesterId,
            'scout_level_id' => $this->kind === 'annual' ? null : $this->scoutLevelId,
            'parent_id' => $this->kind === 'annual' ? null : $this->parentId,
            'activity_id' => $this->kind === 'weekly' ? $this->activityId : null,
            'scheduled_for' => $this->kind === 'weekly' ? $this->scheduledFor : null,
            'entries' => $entries,
            'notes' => $this->notes ?: null,
        ]);
        if (! $sheet->exists) {
            $sheet->created_by = auth()->id();
        }
        $sheet->save();
        $this->previewId = $sheet->id;

        session()->flash('status', 'Program kerja tersimpan.');
        $this->resetDraft();
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id !== null, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }

    private function selectedGudep(): ScoutGroup
    {
        abort_unless($this->scoutGroupId !== null, 422);

        return ScoutGroup::query()->where('school_id', $this->schoolId())->findOrFail($this->scoutGroupId);
    }

    private function selectedYear(): AcademicYear
    {
        abort_unless($this->academicYearId !== null, 422);

        return AcademicYear::query()->where('school_id', $this->schoolId())->findOrFail($this->academicYearId);
    }

    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);
        $this->schoolId();
    }

    private function authorizeManage(): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'), 403);
    }

    public function render(): View
    {
        $this->authorizeView();
        $gudep = $this->scoutGroupId !== null ? $this->selectedGudep() : null;
        $year = $this->academicYearId !== null ? $this->selectedYear() : null;
        $levels = $gudep && $year
            ? GudepFormation::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->where('is_active', true)->with('scoutLevel')->get()
                ->pluck('scoutLevel')->filter()->unique('id')->values()
            : collect();

        return view('livewire.administration.gudep-program-planner', [
            'gudep' => $gudep,
            'year' => $year,
            'gudepOptions' => ScoutGroup::query()->where('school_id', $this->schoolId())->orderBy('name')->get(),
            'years' => AcademicYear::query()->where('school_id', $this->schoolId())->orderByDesc('start_date')->get(),
            'semesters' => $year ? Semester::query()->where('school_id', $this->schoolId())
                ->where('academic_year_id', $year->id)->orderBy('semester_number')->get() : collect(),
            'levels' => $levels,
            'linkedActivity' => $this->activityId !== null && $year
                ? Activity::query()->where('school_id', $this->schoolId())
                    ->where('academic_year_id', $year->id)->find($this->activityId) : null,
            'activities' => $year
                ? Activity::query()->where('school_id', $this->schoolId())
                    ->where('academic_year_id', $year->id)->orderBy('start_at')->get() : collect(),
            'previewSheet' => $this->previewId !== null && $gudep && $year
                ? GudepProgramSheet::query()->where('school_id', $this->schoolId())
                    ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                    ->find($this->previewId) : null,
            'sheets' => $gudep && $year ? GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->with(['activity', 'scoutLevel', 'semester'])->orderByDesc('id')->get() : collect(),
        ]);
    }
}
