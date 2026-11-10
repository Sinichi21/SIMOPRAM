<?php

declare(strict_types=1);

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\GudepCashEntry;
use App\Models\GudepFormation;
use App\Models\GudepProgramSheet;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\Semester;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\GudepAnnualProgram;
use App\Support\GudepDocumentLetterhead;
use App\Support\GudepProgramProgress;
use App\Support\GudepProgramSemesterProjection;
use App\Support\GudepProgramSignatories;
use App\Support\GudepWeeklyPersonnel;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GudepProgramPlanner extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public ?int $scoutLevelId = null;

    public ?int $formationId = null;

    public ?int $parentId = null;

    public ?int $parentItemIndex = null;

    public ?int $activityId = null;

    public string $acknowledgedByRef = '';

    public string $signedByRef = '';

    public ?int $editingId = null;

    public ?int $previewId = null;

    public string $kind = 'annual';

    public string $letterheadType = 'male';

    public string $documentLayout = 'narrative';

    /** @var array<int, string> */
    public array $ccRecipients = [];

    public string $title = '';

    public string $scheduledFor = '';

    public string $notes = '';

    public string $saveNotice = '';

    /** @var array<int, array<string, mixed>> */
    public array $entries = [];

    /** @return array<string, string> */
    public static function annualSections(): array
    {
        return GudepAnnualProgram::sections();
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
        $this->previewId = null;
        $this->resetDraft();
    }

    public function updatedAcademicYearId(): void
    {
        $this->previewId = null;
        $this->resetDraft();
    }

    public function updatedScoutLevelId(): void
    {
        $this->letterheadType = 'mabigus';
        $this->formationId = null;
        if ($this->scoutGroupId === null || $this->academicYearId === null || $this->scoutLevelId === null) {
            return;
        }
        $genders = GudepFormation::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->where('scout_level_id', $this->scoutLevelId)
            ->where('is_active', true)->pluck('gender')->filter()->unique()->values()->all();
        $this->letterheadType = match ($genders) {
            ['putra'] => 'male',
            ['putri'] => 'female',
            default => 'mabigus',
        };
        $formations = GudepFormation::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->where('scout_level_id', $this->scoutLevelId)
            ->where('is_active', true)->get();
        if ($formations->count() === 1) {
            $this->formationId = $formations->first()->id;
        }
    }

    public function updatedParentId(): void
    {
        $this->parentItemIndex = null;
    }

    public function updatedSemesterId(): void
    {
        if ($this->kind !== 'semester' || $this->semesterId === null) {
            return;
        }

        $semester = $this->selectedSemester();
        $annual = $this->parentId === null ? null : GudepProgramSheet::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->where('kind', 'annual')->find($this->parentId);
        $count = count(GudepProgramSemesterProjection::months($semester));
        $this->entries = array_map(static function (array $row) use ($annual, $semester, $count): array {
            if ($annual !== null && empty($row['source_key']) === false) {
                return GudepProgramSemesterProjection::synchronizeRow($row, $annual->entries ?? [], $semester) ?? $row;
            }

            $row['months'] = array_slice(array_pad($row['months'] ?? [], $count, false), 0, $count);

            return $row;
        }, $this->entries);
    }

    private function selectedSemester(): ?Semester
    {
        if ($this->semesterId === null) {
            return null;
        }

        return Semester::query()->where('school_id', $this->schoolId())
            ->where('academic_year_id', $this->selectedYear()->id)
            ->find($this->semesterId);
    }

    /** @return array<int, int> */
    private function semesterMonths(): array
    {
        $semester = $this->semesterId === null ? null : Semester::query()
            ->where('school_id', $this->schoolId())
            ->where('academic_year_id', $this->selectedYear()->id)
            ->find($this->semesterId);

        return GudepProgramSemesterProjection::months($semester);
    }

    /** @return array<int, string> */
    private function semesterMonthLabels(): array
    {
        $semester = $this->semesterId === null ? null : Semester::query()
            ->where('school_id', $this->schoolId())
            ->where('academic_year_id', $this->selectedYear()->id)
            ->find($this->semesterId);

        return GudepProgramSemesterProjection::monthLabels($semester);
    }

    public function updatedFormationId(): void
    {
        if ($this->formationId === null) {
            $this->letterheadType = 'mabigus';

            return;
        }
        $formation = GudepFormation::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->where('scout_level_id', $this->scoutLevelId)->find($this->formationId);
        $this->letterheadType = match ($formation?->gender) {
            'putra' => 'male',
            'putri' => 'female',
            default => 'mabigus',
        };
    }

    public function updatedKind(): void
    {
        $this->resetDraft();
    }

    public function addCopyRecipient(): void
    {
        $this->authorizeManage();
        if (count($this->ccRecipients) < 4) {
            $this->ccRecipients[] = '';
        }
    }

    public function removeCopyRecipient(int $index): void
    {
        $this->authorizeManage();
        if (array_key_exists($index, $this->ccRecipients)) {
            array_splice($this->ccRecipients, $index, 1);
        }
    }

    public function resetDraft(): void
    {
        $this->reset(['editingId', 'semesterId', 'scoutLevelId', 'formationId', 'parentId', 'parentItemIndex', 'activityId', 'scheduledFor', 'notes', 'acknowledgedByRef', 'signedByRef']);
        $this->title = '';
        $this->saveNotice = '';
        $this->resetValidation();
        $this->letterheadType = 'male';
        $this->documentLayout = 'narrative';
        $this->ccRecipients = [];
        $this->entries = [];
    }

    /** @return array<string, string> */
    private static function weeklyEntry(string $description = ''): array
    {
        return [
            'key' => (string) Str::uuid(),
            'time' => '',
            'description' => $description,
            'target' => '',
            'sku' => '',
            'skk' => '',
            'equipment' => '',
            'officer' => '',
            'interlude' => '',
            'notes' => '',
        ];
    }

    public function addRow(?string $section = null): void
    {
        $this->authorizeManage();

        if ($this->kind === 'annual') {
            $section = array_key_exists((string) $section, self::annualSections()) ? $section : 'bersama';
            $this->entries[] = ['key' => (string) Str::uuid(), 'parent_key' => null,
                'section' => $section, 'description' => '', 'detail' => '',
                'quantity' => null, 'unit' => 'kali', 'currency' => 'IDR',
                'finance_type' => '', 'amount' => null, 'months' => array_fill(0, 12, false)];
        } elseif ($this->kind === 'semester') {
            $this->entries[] = ['description' => '', 'months' => array_fill(0, count($this->semesterMonths()), false), 'participants' => '', 'notes' => ''];
        } else {
            $this->entries[] = self::weeklyEntry();
        }
    }

    public function removeRow(int $index): void
    {
        $this->authorizeManage();
        if (array_key_exists($index, $this->entries) === false) {
            return;
        }
        if ($this->kind === 'annual') {
            $removedKey = (string) ($this->entries[$index]['key'] ?? '');
            if ($removedKey === '') {
                array_splice($this->entries, $index, 1);

                return;
            }
            $removed = [$removedKey];
            for ($i = 0; $i < 3; $i++) {
                foreach ($this->entries as $entry) {
                    if (in_array($entry['parent_key'] ?? null, $removed, true)) {
                        $removed[] = (string) ($entry['key'] ?? '');
                    }
                }
            }
            $this->entries = array_values(array_filter($this->entries,
                fn (array $entry): bool => in_array((string) ($entry['key'] ?? ''), $removed, true) === false));

            return;
        }
        array_splice($this->entries, $index, 1);
    }

    public function loadAnnualTemplate(): void
    {
        $this->authorizeManage();
        abort_unless($this->kind === 'annual', 422);
        if ($this->entries !== []) {
            $this->addError('entries', 'Hapus daftar yang sudah ada sebelum menggunakan contoh format tahunan.');

            return;
        }

        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();
        $levels = GudepFormation::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
            ->where('is_active', true)
            ->when($this->letterheadType === 'male', fn ($query) => $query->where(
                fn ($scoped) => $scoped->whereIn('gender', ['putra', 'campuran'])->orWhereNull('gender')
            ))
            ->when($this->letterheadType === 'female', fn ($query) => $query->where(
                fn ($scoped) => $scoped->whereIn('gender', ['putri', 'campuran'])->orWhereNull('gender')
            ))
            ->with('scoutLevel')->get()->pluck('scoutLevel.name')->filter()->all();

        $this->entries = array_map(static fn (array $item): array => $item + [
            'months' => array_fill(0, 12, false),
        ], GudepAnnualProgram::template($levels));
    }

    public function addChildRow(int $parentIndex): void
    {
        $this->authorizeManage();
        abort_unless($this->kind === 'annual', 422);
        abort_unless(isset($this->entries[$parentIndex]), 422);

        $parent = $this->entries[$parentIndex];
        $section = (string) ($parent['section'] ?? '');
        abort_unless(array_key_exists($section, self::annualSections()), 422);
        if (count($this->entries) >= 150) {
            $this->addError('entries', 'Maksimal 150 baris.');

            return;
        }

        if (empty($this->entries[$parentIndex]['key'])) {
            $this->entries[$parentIndex]['key'] = (string) Str::uuid();
        }
        $key = $this->entries[$parentIndex]['key'];
        // Preserve numeric wire:model paths of all existing rows. Inserting into
        // the middle changes every later index and can make Livewire reuse input
        // state across siblings (e.g. Siaga Mula/Bantu/Tata).
        $this->entries[] = [
            'key' => (string) Str::uuid(), 'parent_key' => $key,
            'section' => $section, 'description' => '', 'detail' => '',
            'quantity' => null, 'unit' => 'kali', 'currency' => 'IDR',
            'finance_type' => '', 'amount' => null, 'months' => array_fill(0, 12, false),
        ];
    }

    public function useBudgetSuggestion(int $index, int $suggestionIndex): void
    {
        $this->authorizeManage();
        abort_unless(auth()->user()?->can('gudep.finance.view'), 403);
        abort_unless($this->kind === 'annual' && ($this->entries[$index]['section'] ?? null) === 'keuangan', 422);
        abort_unless(in_array($this->entries[$index]['finance_type'] ?? '', ['income', 'expense'], true), 422);

        $suggestions = $this->historicalBudgetSuggestions();
        $direction = $this->entries[$index]['finance_type'];
        $matching = $suggestions[$suggestionIndex] ?? null;
        abort_unless($matching !== null && $matching['direction'] === $direction, 422);

        $this->entries[$index]['amount'] = (int) $matching['amount'];
    }

    /** @return array<int, array{direction:string, category:string, amount:int, source_year:string}> */
    private function historicalBudgetSuggestions(): array
    {
        if ($this->scoutGroupId === null || $this->academicYearId === null) {
            return [];
        }

        $year = $this->selectedYear();
        $previous = AcademicYear::query()->where('school_id', $this->schoolId())
            ->where('start_date', '<', $year->start_date)
            ->orderByDesc('start_date')->first();
        if ($previous === null) {
            return [];
        }

        $entries = GudepCashEntry::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $previous->id)
            ->where('status', 'posted')->get();

        $summary = [];
        foreach ($entries as $entry) {
            $direction = $entry->direction;
            // Reversal directions cancel their original counterpart.
            if ($entry->reversal_of_id !== null) {
                $direction = $direction === 'income' ? 'expense' : 'income';
                $sign = -1;
            } else {
                $sign = 1;
            }
            $key = $direction.'|'.mb_strtolower(trim((string) $entry->category));
            $summary[$key] ??= [
                'direction' => $direction, 'category' => trim((string) $entry->category),
                'amount' => 0, 'source_year' => $previous->name,
            ];
            $summary[$key]['amount'] += $sign * (int) $entry->amount;
        }

        return array_values(array_filter($summary, fn (array $row): bool => $row['amount'] > 0));
    }

    /**
     * Open a new unsaved child plan using the saved program as its source.
     * Existing activity, attendance and journal records are never duplicated.
     */
    public function prepareChild(int $id): void
    {
        $this->authorizeManage();

        $parent = GudepProgramSheet::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->find($id);
        abort_unless($parent !== null && in_array($parent->kind, ['annual', 'semester'], true), 404);

        $childKind = $parent->kind === 'annual' ? 'semester' : 'weekly';

        $this->resetDraft();
        $this->previewId = null;
        $this->kind = $childKind;
        $this->parentId = $parent->id;
        $this->letterheadType = $parent->letterhead_type ?: 'male';
        $this->title = Str::limit(
            ($childKind === 'semester' ? 'Program Semester' : 'Program Latihan Mingguan')
                .' — '.$parent->title,
            200,
            ''
        );

        if ($childKind === 'weekly') {
            // A weekly program is scoped to the same semester and formation
            // as its parent, never inferred from the title.
            $this->semesterId = $parent->semester_id;
            $this->scoutLevelId = $parent->scout_level_id;
            $this->formationId = $parent->gudep_formation_id;
            $this->letterheadType = $parent->letterhead_type ?: 'mabigus';
        }

        $this->entries = $childKind === 'semester'
            ? [[
                'description' => '', 'months' => array_fill(0, count($this->semesterMonths()), false),
                'participants' => null, 'notes' => '',
            ]]
            : [self::weeklyEntry()];

        $this->saveNotice = '';
    }

    /**
     * Bring one selected planning item forward. This only updates the current
     * form draft; it never inserts an Activity or changes the parent program.
     */
    public function importParentItem(): void
    {
        $this->authorizeManage();
        abort_unless($this->parentId !== null && $this->parentItemIndex !== null, 422);

        $parent = GudepProgramSheet::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->find($this->parentId);
        $expectedKind = match ($this->kind) {
            'semester' => 'annual',
            'weekly' => 'semester',
            default => null,
        };
        abort_unless($parent !== null && $parent->kind === $expectedKind, 404);
        abort_unless($this->kind !== 'semester'
            || in_array($parent->letterhead_type, ['male', 'female'], true) === false
            || $parent->letterhead_type === $this->letterheadType, 422);

        if ($this->kind === 'weekly') {
            abort_unless((int) $parent->semester_id === (int) $this->semesterId
                && (int) $parent->scout_level_id === (int) $this->scoutLevelId
                && ($parent->gudep_formation_id === null
                    || (int) $parent->gudep_formation_id === (int) $this->formationId), 422);
        }

        $item = ($parent->entries ?? [])[$this->parentItemIndex] ?? null;
        abort_unless(is_array($item)
            && trim((string) ($item['description'] ?? '')) !== ''
            && ($parent->kind !== 'annual' || ($item['section'] ?? '') !== 'keuangan'), 422);
        if (count($this->entries) >= 150) {
            $this->addError('entries', 'Maksimal 150 baris.');

            return;
        }

        $description = trim((string) $item['description']);
        if ($this->kind === 'semester') {
            $sourceKey = (string) ($item['key'] ?? 'legacy-'.$this->parentItemIndex);
            if (collect($this->entries)->contains(fn (array $row): bool => ($row['source_key'] ?? '') === $sourceKey)) {
                $this->addError('parentItemIndex', 'Butir program tahunan ini sudah masuk ke Program Semester.');

                return;
            }

            $this->entries[] = [
                'source_key' => $sourceKey,
                'description' => $description,
                'months' => GudepProgramSemesterProjection::fromAnnual($item['months'] ?? [], $this->selectedSemester()),
                'participants' => null,
                'notes' => '',
            ];
        } else {
            $this->entries[] = self::weeklyEntry($description);
        }
        $this->parentItemIndex = null;
        $this->saveNotice = '';
    }

    /**
     * Populate a semester draft only with scheduled annual activities from
     * its five/seven calendar months. This never creates a second annual plan.
     */
    public function importScheduledAnnualItems(): void
    {
        $this->authorizeManage();
        abort_unless($this->kind === 'semester' && $this->parentId !== null && $this->semesterId !== null, 422);

        $annual = GudepProgramSheet::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->where('kind', 'annual')->find($this->parentId);
        abort_unless($annual !== null && (
            in_array($annual->letterhead_type, ['male', 'female'], true) === false
            || $annual->letterhead_type === $this->letterheadType
        ), 422);

        $previous = collect($this->entries)->keyBy('source_key');
        $rows = [];
        foreach ($annual->entries ?? [] as $index => $item) {
            if (($item['section'] ?? '') === 'keuangan'
                || trim((string) ($item['description'] ?? '')) === '') {
                continue;
            }

            $months = GudepProgramSemesterProjection::fromAnnual($item['months'] ?? [], $this->selectedSemester());
            if (in_array(true, $months, true) === false) {
                continue;
            }

            $key = (string) ($item['key'] ?? 'legacy-'.$index);
            $existing = $previous->get($key);
            $rows[] = [
                'source_key' => $key,
                'description' => trim((string) $item['description']),
                'months' => $months,
                'participants' => $existing['participants'] ?? null,
                'notes' => $existing['notes'] ?? '',
            ];
        }

        // Keep independently authored semester rows and existing annotations.
        foreach ($this->entries as $row) {
            if (empty($row['source_key']) && trim((string) ($row['description'] ?? '')) !== '') {
                $rows[] = $row;
            }
        }

        if (count($rows) > 150) {
            $this->addError('entries', 'Maksimal 150 kegiatan dalam Program Semester.');

            return;
        }

        if ($rows === []) {
            $this->addError('entries', 'Belum ada kegiatan Tahunan yang ditandai pada bulan semester ini.');

            return;
        }

        $this->entries = $rows;
        $this->resetValidation();
        $this->saveNotice = 'Rencana semester diperbarui dari Program Tahunan (belum disimpan).';
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
        $this->letterheadType = $sheet->letterhead_type ?: 'mabigus';
        $this->documentLayout = $sheet->document_layout ?: 'narrative';
        $this->ccRecipients = $sheet->cc_recipients ?? [];
        $this->editingId = $sheet->id;
        $this->saveNotice = '';
        $this->resetValidation();
        $this->title = $sheet->title;
        $this->semesterId = $sheet->semester_id;
        $this->scoutLevelId = $sheet->scout_level_id;
        $this->formationId = $sheet->gudep_formation_id;
        $this->parentId = $sheet->parent_id;
        $this->activityId = $sheet->activity_id;
        $this->acknowledgedByRef = $sheet->acknowledged_by_ref ?? '';
        $this->signedByRef = $sheet->signed_by_ref ?? '';
        $this->scheduledFor = $sheet->scheduled_for?->format('Y-m-d') ?? '';
        $this->notes = $sheet->notes ?? '';
        $this->entries = $sheet->entries ?? [];
        if ($sheet->kind === 'semester' && $sheet->parent_id !== null) {
            $annual = GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $sheet->scout_group_id)
                ->where('academic_year_id', $sheet->academic_year_id)
                ->where('kind', 'annual')->find($sheet->parent_id);
            if ($annual !== null) {
                $this->entries = array_map(
                    static fn (array $row): array => GudepProgramSemesterProjection::synchronizeRow(
                        $row, $annual->entries ?? [], $sheet->semester
                    ) ?? $row,
                    $this->entries
                );
            }
        }
        if ($sheet->kind === 'annual') {
            $this->entries = array_map(static fn (array $item): array => $item + [
                'months' => array_fill(0, 12, false),
            ], $this->entries);
        }
        if ($sheet->kind === 'weekly') {
            $this->entries = array_map(
                fn (array $row): array => array_replace(self::weeklyEntry(), $row),
                $this->entries
            );
        }
    }

    public function save(): void
    {
        $this->authorizeManage();
        $this->saveNotice = '';
        $this->resetValidation();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();

        $rules = [
            'kind' => ['required', Rule::in(['annual', 'semester', 'weekly'])],
            'documentLayout' => ['required', Rule::in(['narrative', 'monthly_matrix'])],
            'ccRecipients' => ['array', 'max:4'],
            'ccRecipients.*' => ['nullable', 'string', 'max:180'],
            'letterheadType' => ['required', Rule::in(GudepDocumentLetterhead::TYPES)],
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'semesterId' => ['nullable', 'integer', Rule::exists('semesters', 'id')
                ->where('school_id', $this->schoolId())->where('academic_year_id', $year->id)],
            'scoutLevelId' => ['nullable', 'integer', Rule::exists('scout_levels', 'id')],
            'formationId' => ['nullable', 'integer', Rule::exists('gudep_formations', 'id')
                ->where('school_id', $this->schoolId())->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)->where('is_active', true)],
            'parentId' => ['nullable', 'integer', Rule::exists('gudep_program_sheets', 'id')
                ->where('school_id', $this->schoolId())->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)],
            'activityId' => ['nullable', 'integer', Rule::exists('activities', 'id')
                ->where('school_id', $this->schoolId())->where('academic_year_id', $year->id)
                ->whereNull('deleted_at')],
            'scheduledFor' => ['nullable', 'date'],
            'acknowledgedByRef' => ['nullable', 'string', 'max:64'],
            'signedByRef' => ['nullable', 'string', 'max:64'],
            'entries' => ['required', 'array', 'min:1', 'max:150'],
        ];

        if ($this->kind === 'annual') {
            $rules['entries.*.section'] = ['required', Rule::in(array_keys(self::annualSections()))];
            $rules['entries.*.description'] = ['required', 'string', 'max:500'];
            $rules['entries.*.quantity'] = ['nullable', 'integer', 'min:0', 'max:999999'];
            $rules['entries.*.key'] = ['nullable', 'string', 'max:80'];
            $rules['entries.*.parent_key'] = ['nullable', 'string', 'max:80'];
            $rules['entries.*.detail'] = ['nullable', 'string', 'max:1500'];
            $rules['entries.*.unit'] = ['nullable', Rule::in(['kali', 'buah', 'orang', 'paket'])];
            $rules['entries.*.finance_type'] = ['nullable', Rule::in(['', 'income', 'expense', 'fundraising'])];
            $rules['entries.*.currency'] = ['nullable', Rule::in(['IDR'])];
            $rules['entries.*.amount'] = ['nullable', 'integer', 'min:0', 'max:999999999999'];
            $rules['entries.*.months'] = ['nullable', 'array', 'size:12'];
            $rules['entries.*.months.*'] = ['boolean'];
            $rules['entries.*.notes'] = ['nullable', 'string', 'max:500'];
        } elseif ($this->kind === 'semester') {
            $rules['semesterId'] = ['required', 'integer', Rule::exists('semesters', 'id')->where('school_id', $this->schoolId())->where('academic_year_id', $year->id)];
            $rules['scoutLevelId'] = ['required', 'integer', Rule::exists('scout_levels', 'id')];
            $rules['entries.*.description'] = ['required', 'string', 'max:500'];
            $rules['entries.*.months'] = ['required', 'array', 'size:'.count($this->semesterMonths())];
            $rules['entries.*.source_key'] = ['nullable', 'string', 'max:80'];
            $rules['entries.*.months.*'] = ['boolean'];
            $rules['entries.*.participants'] = ['nullable', 'integer', 'min:0', 'max:999999'];
            $rules['entries.*.notes'] = ['nullable', 'string', 'max:500'];
        } else {
            $rules['semesterId'] = ['required', ...$rules['semesterId']];
            $rules['scoutLevelId'] = ['required', ...$rules['scoutLevelId']];
            $rules['scheduledFor'] = ['required', 'date'];
            $rules['entries.*.description'] = ['required', 'string', 'max:500'];
            $rules['entries.*.time'] = ['nullable', 'string', 'max:80'];
            $rules['entries.*.target'] = ['nullable', 'string', 'max:500'];
            $rules['entries.*.equipment'] = ['nullable', 'string', 'max:500'];
            $rules['entries.*.officer'] = ['nullable', 'string', 'max:500'];
            $rules['entries.*.interlude'] = ['nullable', 'string', 'max:500'];
            $rules['entries.*.sku'] = ['nullable', 'string', 'max:150'];
            $rules['entries.*.skk'] = ['nullable', 'string', 'max:150'];
            $rules['entries.*.notes'] = ['nullable', 'string', 'max:500'];
        }

        if ($this->kind === 'semester') {
            $expectedCount = count($this->semesterMonths());
            $this->entries = array_map(static function (array $row) use ($expectedCount): array {
                // Older drafts stored six slots irrespective of actual semester length.
                $row['months'] = array_slice(array_pad($row['months'] ?? [], $expectedCount, false), 0, $expectedCount);

                return $row;
            }, $this->entries);
        }

        $this->validate($rules);

        if ($this->kind === 'annual') {
            // Subitems are appended to keep Livewire input indices stable. Resolve
            // parent relationships independently of their positions in the array.
            $byKey = [];
            foreach ($this->entries as $i => $entry) {
                $key = (string) ($entry['key'] ?? 'legacy-'.$i);
                if (isset($byKey[$key])) {
                    $this->addError('entries', 'Identitas baris program tidak boleh sama.');

                    return;
                }
                $byKey[$key] = $entry;
            }

            foreach ($this->entries as $i => $entry) {
                $key = (string) ($entry['key'] ?? 'legacy-'.$i);
                $ancestor = $entry['parent_key'] ?? null;
                $seen = [$key => true];
                $depth = 0;
                while ($ancestor !== null && $ancestor !== '') {
                    if (isset($seen[$ancestor]) || isset($byKey[$ancestor]) === false
                        || $byKey[$ancestor]['section'] !== $entry['section'] || ++$depth > 2) {
                        $this->addError('entries', 'Hierarki subbutir tidak valid: induk harus berada di bagian yang sama, tanpa siklus dan maksimal tiga tingkat.');

                        return;
                    }

                    $seen[$ancestor] = true;
                    $ancestor = $byKey[$ancestor]['parent_key'] ?? null;
                }

                if ($entry['section'] !== 'keuangan' && empty($entry['finance_type']) === false) {
                    $this->addError('entries', 'Nominal keuangan hanya boleh ditempatkan di bagian F.');

                    return;
                }
            }
        }

        $validLevel = $this->scoutLevelId === null || GudepFormation::query()
            ->where('school_id', $this->schoolId())->where('scout_group_id', $gudep->id)
            ->where('academic_year_id', $year->id)->where('scout_level_id', $this->scoutLevelId)
            ->where('is_active', true)->exists();
        if ($validLevel === false) {
            $this->addError('scoutLevelId', 'Golongan ini tidak tersedia pada Gudep dan tahun ajaran yang dipilih.');

            return;
        }

        if ($this->parentId !== null) {
            $parent = GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->findOrFail($this->parentId);
            $expectedKind = $this->kind === 'semester' ? 'annual' : 'semester';
            if ($parent->kind !== $expectedKind || $parent->id === $this->editingId
                || ($this->kind === 'semester'
                    && in_array($parent->letterhead_type, ['male', 'female'], true)
                    && $parent->letterhead_type !== $this->letterheadType)
                || ($this->kind === 'weekly' && (
                    $parent->semester_id !== $this->semesterId
                    || $parent->scout_level_id !== $this->scoutLevelId
                    || ($parent->gudep_formation_id !== null && $parent->gudep_formation_id !== $this->formationId)
                ))) {
                $this->addError('parentId', 'Program induk tidak sesuai jenjang atau semester.');

                return;
            }
        }

        if ($this->kind === 'semester' && $this->parentId !== null) {
            $annual = GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->where('kind', 'annual')->findOrFail($this->parentId);
            $keyList = [];
            foreach ($this->entries as $index => $row) {
                if (empty($row['source_key'])) {
                    continue;
                }
                $key = (string) $row['source_key'];
                if (in_array($key, $keyList, true)) {
                    $this->addError('entries', 'Satu butir tahunan tidak boleh dihubungkan dua kali.');

                    return;
                }
                $keyList[] = $key;
                $synced = GudepProgramSemesterProjection::synchronizeRow($row, $annual->entries ?? [], $this->selectedSemester());
                if ($synced === null) {
                    $this->addError('entries.'.$index.'.description', 'Butir tahunan tidak tersedia lagi; pilih ulang sumbernya.');

                    return;
                }
                $this->entries[$index] = $synced;
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

        if ($this->kind !== 'annual' && $this->formationId === null) {
            $formationCandidates = GudepFormation::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->where('scout_level_id', $this->scoutLevelId)->where('is_active', true)->get();
            if ($formationCandidates->count() === 1) {
                $this->formationId = $formationCandidates->first()->id;
                $this->letterheadType = match ($formationCandidates->first()->gender) {
                    'putra' => 'male',
                    'putri' => 'female',
                    default => 'mabigus',
                };
            }
        }

        $this->validateLetterheadScope($gudep, $year);
        if ($this->getErrorBag()->has('letterheadType') || $this->getErrorBag()->has('formationId')) {
            return;
        }

        if (in_array($this->kind, ['annual', 'semester'], true)) {
            $personnel = $this->programPersonnelOptions();
            if ($this->acknowledgedByRef !== '' && GudepWeeklyPersonnel::find(GudepProgramSignatories::eligible($personnel, 'kamabigus'), $this->acknowledgedByRef) === null) {
                $this->addError('acknowledgedByRef', 'Kamabigus harus berasal dari pengurus Mabigus yang aktif.');

                return;
            }
            if ($this->signedByRef !== '' && GudepWeeklyPersonnel::find(GudepProgramSignatories::eligible($personnel, 'pembina'), $this->signedByRef) === null) {
                $this->addError('signedByRef', 'Pembina Gudep harus berasal dari pengurus yang aktif.');

                return;
            }
        }

        if ($this->kind === 'weekly') {
            $personnel = $this->weeklyPersonnelOptions();
            $acknowledgers = GudepWeeklyPersonnel::acknowledgers($personnel);
            $signers = GudepWeeklyPersonnel::signers(
                $personnel,
                ScoutLevel::query()->find($this->scoutLevelId)?->name
            );

            if ($this->acknowledgedByRef !== '' && GudepWeeklyPersonnel::find($acknowledgers, $this->acknowledgedByRef) === null) {
                $this->addError('acknowledgedByRef', 'Pengurus yang mengetahui harus berasal dari data pengurus Gudep yang aktif.');

                return;
            }

            if ($this->signedByRef !== '' && GudepWeeklyPersonnel::find($signers, $this->signedByRef) === null) {
                $this->addError('signedByRef', 'Penandatangan harus sesuai jenjang dan jabatan satuan yang aktif.');

                return;
            }
        }

        $entries = array_map(function (array $entry): array {
            $entry['description'] = trim((string) ($entry['description'] ?? ''));
            if ($this->kind === 'annual') {
                $entry['quantity'] = ($entry['quantity'] ?? '') === '' || ($entry['quantity'] ?? null) === null
                    ? null : (int) $entry['quantity'];
                $entry['amount'] = ($entry['amount'] ?? '') === '' || ($entry['amount'] ?? null) === null
                    ? null : (int) $entry['amount'];
                $entry['detail'] = trim((string) ($entry['detail'] ?? ''));
                $entry['key'] = (string) ($entry['key'] ?? Str::uuid());
                $entry['parent_key'] = ($entry['parent_key'] ?? null) ?: null;
                $entry['currency'] = 'IDR';
                if (isset($entry['months'])) {
                    $entry['months'] = array_map(static fn ($v): bool => (bool) $v, $entry['months']);
                }
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
            'letterhead_type' => $this->letterheadType,
            'title' => trim($this->title),
            'semester_id' => $this->kind === 'annual' ? null : $this->semesterId,
            'scout_level_id' => $this->kind === 'annual' ? null : $this->scoutLevelId,
            'gudep_formation_id' => $this->kind === 'annual' ? null : $this->formationId,
            'parent_id' => $this->kind === 'annual' ? null : $this->parentId,
            'activity_id' => $this->kind === 'weekly' ? $this->activityId : null,
            'acknowledged_by_ref' => $this->acknowledgedByRef ?: null,
            'signed_by_ref' => $this->signedByRef ?: null,
            'document_layout' => $this->kind === 'annual' ? $this->documentLayout : 'narrative',
            'cc_recipients' => array_values(array_filter(array_map(trim(...), $this->ccRecipients), static fn (string $v): bool => $v !== '')),
            'scheduled_for' => $this->kind === 'weekly' ? $this->scheduledFor : null,
            'entries' => $entries,
            'notes' => $this->notes ?: null,
        ]);
        if ($sheet->exists === false) {
            $sheet->created_by = auth()->id();
        }
        $sheet->save();
        $this->editingId = $sheet->id;
        $this->previewId = $sheet->id;
        $this->entries = $entries;
        $this->saveNotice = 'Perubahan berhasil disimpan pada '.now()->format('d/m/Y H:i:s').'.';
        session()->flash('status', $this->saveNotice);
    }

    public function publishProgram(
        int $id,
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier
    ): void {
        $this->authorizeManage();
        abort_unless(auth()->user()?->can('reports.export'), 403);
        $this->resetValidation();

        $sheet = GudepProgramSheet::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->with(['scoutGroup', 'academicYear', 'semester', 'scoutLevel', 'gudepFormation', 'activity'])
            ->find($id);
        abort_unless($sheet !== null, 404);

        $letterheadType = $sheet->letterhead_type ?: 'mabigus';
        $this->validateLetterheadScope($sheet->scoutGroup, $sheet->academicYear, $letterheadType, $sheet->scout_level_id, $sheet->kind, $sheet->gudep_formation_id, false);
        abort_if($this->getErrorBag()->has('letterheadType') || $this->getErrorBag()->has('formationId'), 422, 'Jenis kop dokumen tidak sesuai formasi Gudep.');
        $school = School::query()->findOrFail($this->schoolId());
        $letterhead = GudepDocumentLetterhead::resolve($school, $sheet->scoutGroup, $letterheadType, true);

        $activity = $sheet->activity;
        // Never publish a document with a dangling or cross-context activity reference.
        abort_unless($sheet->activity_id === null || (
            $activity !== null
            && (int) $activity->school_id === $this->schoolId()
            && (int) $activity->academic_year_id === (int) $sheet->academic_year_id
            && ($activity->semester_id === null || (int) $activity->semester_id === (int) $sheet->semester_id)
        ), 409, 'Agenda tertaut tidak lagi valid.');

        $personnel = GudepProgramSignatories::candidates(
            $this->schoolId(), $sheet->scout_group_id, $sheet->academic_year_id,
            $sheet->kind === 'annual' ? null : $sheet->gudep_formation_id,
            $sheet->scheduled_for?->toDateString(),
            $sheet->kind, $letterheadType
        );
        $signatures = GudepProgramSignatories::snapshot($sheet, $personnel);

        if ($signatures['acknowledger'] === null || $signatures['signer'] === null) {
            $this->addError('publication', $sheet->kind === 'weekly'
                ? 'Penerbitan mingguan memerlukan Pembina Gudep dan pemimpin satuan sesuai golongan (Pratama/Pradana/Ketua Racana). Pilih penandatangan pada formulir, simpan, lalu terbitkan kembali.'
                : 'Penerbitan memerlukan Ketua Mabigus dan Pembina Gudep aktif. Pilih keduanya pada formulir, simpan, lalu terbitkan kembali.');

            return;
        }

        $snapshot = [
            'id' => $sheet->id,
            'kind' => $sheet->kind,
            'letterhead' => $letterhead,
            'title' => $sheet->title,
            'gudep_id' => $sheet->scout_group_id,
            'gudep_name' => $sheet->scoutGroup?->name,
            'gudep_kwarcab' => $sheet->scoutGroup?->kwarcab,
            'gudep_male_number' => $sheet->scoutGroup?->male_number,
            'gudep_female_number' => $sheet->scoutGroup?->female_number,
            'gudep_program_number' => match ($letterheadType) {
                'male' => $letterhead['male_number'],
                'female' => $letterhead['female_number'],
                default => trim($letterhead['male_number'].' - '.$letterhead['female_number']),
            },
            'year_id' => $sheet->academic_year_id,
            'year_name' => $sheet->academicYear?->name,
            'semester_id' => $sheet->semester_id,
            'semester_name' => $sheet->semester?->name,
            'semester_month_labels' => GudepProgramSemesterProjection::monthLabels($sheet->semester),
            'level_id' => $sheet->scout_level_id,
            'gudep_formation_id' => $sheet->gudep_formation_id,
            'level_name' => $sheet->scoutLevel?->name,
            'parent_id' => $sheet->parent_id,
            'scheduled_for' => $sheet->scheduled_for?->toDateString(),
            'activity_id' => $sheet->activity_id,
            'activity_title' => $activity?->title,
            'activity_start_at' => $activity?->start_at?->toIso8601String(),
            'activity_end_at' => $activity?->end_at?->toIso8601String(),
            'activity_location' => $activity?->location,
            'entries' => $sheet->entries ?? [],
            'weekly_signatures' => $sheet->kind === 'weekly' ? $signatures : null,
            'signatures' => $signatures,
            'document_layout' => $sheet->document_layout ?: 'narrative',
            'cc_recipients' => $sheet->cc_recipients ?? [],
            'program_date' => $sheet->updated_at?->format('d/m/Y'),
            'signing_city' => $school->city ?: ($sheet->scoutGroup?->kwarcab ?? ''),
            'formation_name' => $this->annualFormationName($sheet),
            'notes' => $sheet->notes,
        ];

        $document = $publisher->publishBlade(
            schoolId: $this->schoolId(),
            sourceType: GudepProgramSheet::class,
            sourceId: $sheet->id,
            documentType: 'gudep_program_sheet',
            snapshotChecksum: $verifier->contentChecksum($snapshot),
            filename: 'program-gudep-'.$sheet->id.'.pdf',
            contentView: 'reports.pdf.administration.gudep-program-sheet',
            contentData: ['snapshot' => $snapshot, 'sections' => self::annualSections()],
            title: 'Program Kerja '.$sheet->title,
            metadata: [
                'academic_year' => $sheet->academicYear?->name,
                'program_kind' => $sheet->kind,
                'security_classification' => 'Terbatas',
            ],
            seriesKey: 'program-'.$sheet->id,
        );

        $this->redirectRoute(
            'reports.published-documents.show',
            ['code' => $document->code],
            navigate: false
        );
    }

    private function validateLetterheadScope(
        ScoutGroup $gudep,
        AcademicYear $year,
        ?string $type = null,
        ?int $levelId = null,
        ?string $kind = null,
        ?int $formationId = null,
        bool $useFormState = true
    ): void {
        $type ??= $this->letterheadType;
        $kind ??= $this->kind;
        if ($kind === 'annual') {
            if (in_array($type, ['male', 'female', 'mabigus'], true) === false) {
                $this->addError('letterheadType', 'Pilih Gudep Putra atau Putri untuk Program Tahunan.');
            }

            return;
        }

        $levelId ??= $useFormState ? $this->scoutLevelId : null;
        if ($useFormState) {
            $formationId ??= $this->formationId;
        }
        $query = GudepFormation::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)
            ->where('academic_year_id', $year->id)
            ->where('scout_level_id', $levelId)
            ->where('is_active', true);
        $formations = $query->get();

        if ($formationId === null && $formations->count() > 1) {
            $this->addError('formationId', 'Pilih satuan asal agar kop Putra atau Putri tidak tertukar.');

            return;
        }
        $formation = $formationId !== null
            ? $formations->firstWhere('id', $formationId)
            : $formations->first();
        if ($formation === null) {
            $this->addError('formationId', 'Satuan tidak tersedia dalam Gudep/tahun/golongan ini.');

            return;
        }

        $required = match ($formation->gender) {
            'putra' => 'male',
            'putri' => 'female',
            default => 'mabigus',
        };
        if ($type !== $required) {
            $this->addError('letterheadType', 'Jenis kop harus sesuai jenis satuan: '.$required.'.');
        }
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

    private function annualFormationName(GudepProgramSheet $sheet): ?string
    {
        if ($sheet->kind !== 'annual' || in_array($sheet->letterhead_type, ['male', 'female'], true) === false) {
            return null;
        }

        $gender = $sheet->letterhead_type === 'male' ? 'putra' : 'putri';
        $names = GudepFormation::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $sheet->scout_group_id)
            ->where('academic_year_id', $sheet->academic_year_id)
            ->where('gender', $gender)->where('is_active', true)
            ->pluck('name')->unique()->values();

        return $names->count() === 1 ? (string) $names->first() : null;
    }

    /** @return array<int, array{ref: string, name: string, position: string, scope: string}> */
    private function programPersonnelOptions(): array
    {
        if ($this->scoutGroupId === null || $this->academicYearId === null) {
            return [];
        }

        return GudepProgramSignatories::candidates(
            $this->schoolId(), $this->selectedGudep()->id, $this->selectedYear()->id,
            $this->kind === 'annual' ? null : $this->formationId,
            $this->scheduledFor !== '' ? $this->scheduledFor : null,
            $this->kind, $this->letterheadType
        );
    }

    /** @return array<int, array{ref: string, name: string, position: string, scope: string}> */
    private function weeklyPersonnelOptions(): array
    {
        if ($this->kind !== 'weekly' || $this->scoutGroupId === null || $this->academicYearId === null) {
            return [];
        }

        return GudepWeeklyPersonnel::candidates(
            $this->schoolId(), $this->selectedGudep()->id, $this->selectedYear()->id,
            $this->formationId, $this->scheduledFor !== '' ? $this->scheduledFor : null
        );
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

        $sheets = $gudep && $year
            ? GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->with(['activity.journal', 'scoutLevel', 'semester', 'parent'])
                ->orderByDesc('id')->get()
            : collect();

        $weeklyPersonnel = $this->weeklyPersonnelOptions();
        $previewSheet = $this->previewId !== null && $gudep && $year
            ? GudepProgramSheet::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                ->with('scoutLevel')->find($this->previewId)
            : null;
        $previewSignatures = $previewSheet !== null
            ? GudepProgramSignatories::snapshot(
                $previewSheet,
                GudepProgramSignatories::candidates(
                    $this->schoolId(), $gudep->id, $year->id,
                    $previewSheet->kind === 'annual' ? null : $previewSheet->gudep_formation_id,
                    $previewSheet->scheduled_for?->toDateString(),
                    $previewSheet->kind, $previewSheet->letterhead_type ?: 'mabigus'
                )
            ) : null;

        return view('livewire.administration.gudep-program-planner', [
            'gudep' => $gudep,
            'signingCity' => School::query()->find($this->schoolId())?->city ?: ($gudep?->kwarcab ?? ''),
            'weeklyPersonnel' => $weeklyPersonnel,
            'annualSemesterKamabigus' => GudepProgramSignatories::eligible($this->programPersonnelOptions(), 'kamabigus'),
            'annualSemesterPembina' => GudepProgramSignatories::eligible($this->programPersonnelOptions(), 'pembina'),
            'weeklyAcknowledgers' => GudepWeeklyPersonnel::acknowledgers($weeklyPersonnel),
            'weeklySigners' => GudepWeeklyPersonnel::signers($weeklyPersonnel, $levels->firstWhere('id', $this->scoutLevelId)?->name),
            'previewSignatures' => $previewSignatures,
            'previewAnnualFormationName' => $previewSheet !== null ? $this->annualFormationName($previewSheet) : null,
            'year' => $year,
            'gudepOptions' => ScoutGroup::query()->where('school_id', $this->schoolId())->orderBy('name')->get(),
            'years' => AcademicYear::query()->where('school_id', $this->schoolId())->orderByDesc('start_date')->get(),
            'semesters' => $year ? Semester::query()->where('school_id', $this->schoolId())
                ->where('academic_year_id', $year->id)->orderBy('semester_number')->get() : collect(),
            'levels' => $levels,
            'formations' => $gudep && $year && $this->scoutLevelId !== null
                ? GudepFormation::query()->where('school_id', $this->schoolId())
                    ->where('scout_group_id', $gudep->id)->where('academic_year_id', $year->id)
                    ->where('scout_level_id', $this->scoutLevelId)
                    ->where('is_active', true)->get() : collect(),
            'letterheadProfile' => $gudep && $year ? GudepDocumentLetterhead::resolve(
                School::query()->findOrFail($this->schoolId()), $gudep,
                $this->letterheadType
            ) : null,
            'previewLetterhead' => $this->previewId !== null && $gudep && $year
                ? GudepDocumentLetterhead::resolve(
                    School::query()->findOrFail($this->schoolId()), $gudep,
                    (function (): string {
                        $program = GudepProgramSheet::query()->where('school_id', $this->schoolId())
                            ->where('scout_group_id', $this->selectedGudep()->id)
                            ->where('academic_year_id', $this->selectedYear()->id)
                            ->find($this->previewId);

                        return $program?->letterhead_type ?: 'mabigus';
                    })()
                ) : null,
            'annualSections' => self::annualSections(),
            'historicalSuggestions' => $gudep && $year && $this->kind === 'annual'
                && (auth()->user()?->can('gudep.finance.view') ?? false)
                ? $this->historicalBudgetSuggestions() : [],
            'annualPreviewRows' => $this->kind === 'annual'
                ? collect(array_keys(self::annualSections()))->mapWithKeys(fn (string $section): array => [
                    $section => GudepAnnualProgram::numbered($this->entries, $section),
                ])->all() : [],
            'linkedActivity' => $this->activityId !== null && $year
                ? Activity::query()->where('school_id', $this->schoolId())
                    ->where('academic_year_id', $year->id)->find($this->activityId) : null,
            'activities' => $year
                ? Activity::query()->where('school_id', $this->schoolId())
                    ->where('academic_year_id', $year->id)->orderBy('start_at')->get() : collect(),
            'previewSheet' => $previewSheet,
            'sheets' => $sheets,
            'parentItems' => $gudep && $year && $this->parentId !== null
                ? collect(($sheets->firstWhere('id', $this->parentId)?->entries) ?? [])
                    ->map(fn (array $entry, int $index): array => [
                        'index' => $index,
                        'description' => (string) ($entry['description'] ?? ''),
                        'section' => (string) ($entry['section'] ?? ''),
                    ])
                    ->filter(fn (array $item): bool => $item['description'] !== ''
                        && ($this->kind !== 'semester' || $item['section'] !== 'keuangan'))
                    ->values()
                : collect(),
            'parentOptions' => $this->kind === 'semester'
                ? $sheets->where('kind', 'annual')->filter(fn (GudepProgramSheet $parent): bool => $parent->letterhead_type === $this->letterheadType
                    || in_array($parent->letterhead_type, ['male', 'female'], true) === false)
                : ($this->kind === 'weekly'
                    ? $sheets->where('kind', 'semester')->filter(fn (GudepProgramSheet $parent): bool => ($this->semesterId === null || (int) $parent->semester_id === (int) $this->semesterId)
                        && ($this->scoutLevelId === null || (int) $parent->scout_level_id === (int) $this->scoutLevelId)
                        && ($this->formationId === null || $parent->gudep_formation_id === null
                            || (int) $parent->gudep_formation_id === (int) $this->formationId))
                    : collect()),
            'semesterMonthLabels' => $this->kind === 'semester' && $gudep && $year ? $this->semesterMonthLabels() : [],
            'programProgress' => GudepProgramProgress::summary($sheets),
        ]);
    }
}
