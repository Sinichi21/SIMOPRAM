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
use App\Models\Semester;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\GudepAnnualProgram;
use App\Support\GudepDocumentLetterhead;
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

    public ?int $parentId = null;

    public ?int $activityId = null;

    public ?int $editingId = null;

    public ?int $previewId = null;

    public string $kind = 'annual';

    public string $letterheadType = 'mabigus';

    public string $title = '';

    public string $scheduledFor = '';

    public string $notes = '';

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

    public function updatedKind(): void
    {
        $this->resetDraft();
    }

    public function resetDraft(): void
    {
        $this->reset(['editingId', 'semesterId', 'scoutLevelId', 'parentId', 'activityId', 'scheduledFor', 'notes']);
        $this->title = '';
        $this->letterheadType = 'mabigus';
        $this->entries = [];
    }

    public function addRow(?string $section = null): void
    {
        $this->authorizeManage();

        if ($this->kind === 'annual') {
            $section = array_key_exists((string) $section, self::annualSections()) ? $section : 'bersama';
            $this->entries[] = ['key' => (string) Str::uuid(), 'parent_key' => null,
                'section' => $section, 'description' => '', 'detail' => '',
                'quantity' => null, 'unit' => 'kali', 'currency' => 'IDR',
                'finance_type' => '', 'amount' => null];
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
                fn (array $entry): bool => ! in_array((string) ($entry['key'] ?? ''), $removed, true)));

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
            ->where('is_active', true)->with('scoutLevel')->get()
            ->pluck('scoutLevel.name')->filter()->all();

        $this->entries = GudepAnnualProgram::template($levels);
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
        array_splice($this->entries, $parentIndex + 1, 0, [[
            'key' => (string) Str::uuid(), 'parent_key' => $key,
            'section' => $section, 'description' => '', 'detail' => '',
            'quantity' => null, 'unit' => 'kali', 'currency' => 'IDR',
            'finance_type' => '', 'amount' => null,
        ]]);
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
            'letterheadType' => ['required', Rule::in(GudepDocumentLetterhead::TYPES)],
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
            $rules['entries.*.key'] = ['nullable', 'string', 'max:80'];
            $rules['entries.*.parent_key'] = ['nullable', 'string', 'max:80'];
            $rules['entries.*.detail'] = ['nullable', 'string', 'max:1500'];
            $rules['entries.*.unit'] = ['nullable', Rule::in(['kali', 'buah', 'orang', 'paket'])];
            $rules['entries.*.finance_type'] = ['nullable', Rule::in(['', 'income', 'expense', 'fundraising'])];
            $rules['entries.*.currency'] = ['nullable', Rule::in(['IDR'])];
            $rules['entries.*.amount'] = ['nullable', 'integer', 'min:0', 'max:999999999999'];
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

        if ($this->kind === 'annual') {
            $positions = [];
            foreach ($this->entries as $i => $entry) {
                $key = (string) ($entry['key'] ?? 'legacy-'.$i);
                if (isset($positions[$key])) {
                    $this->addError('entries', 'Identitas baris program tidak boleh sama.');

                    return;
                }
                $parent = $entry['parent_key'] ?? null;
                if ($parent !== null && $parent !== '') {
                    if (! isset($positions[$parent])
                        || $positions[$parent]['section'] !== $entry['section']
                        || $positions[$parent]['depth'] >= 2) {
                        $this->addError('entries', 'Subdaftar harus mengikuti induk pada bagian yang sama, maksimal tiga tingkat.');

                        return;
                    }
                    $depth = $positions[$parent]['depth'] + 1;
                } else {
                    $depth = 0;
                }
                $positions[$key] = ['section' => $entry['section'], 'depth' => $depth];
                if ($entry['section'] !== 'keuangan' && ! empty($entry['finance_type'])) {
                    $this->addError('entries', 'Nominal keuangan hanya boleh ditempatkan di bagian F.');

                    return;
                }
            }
        }

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

        $this->validateLetterheadScope($gudep, $year);
        if ($this->getErrorBag()->has('letterheadType')) {
            return;
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
            'letterhead_type' => $this->kind === 'annual' ? 'mabigus' : $this->letterheadType,
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

    public function publishProgram(
        int $id,
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier
    ): void {
        $this->authorizeManage();
        abort_unless(auth()->user()?->can('reports.export'), 403);

        $sheet = GudepProgramSheet::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->with(['scoutGroup', 'academicYear', 'semester', 'scoutLevel', 'activity'])
            ->find($id);
        abort_unless($sheet !== null, 404);

        $letterheadType = $sheet->kind === 'annual' ? 'mabigus' : ($sheet->letterhead_type ?: 'mabigus');
        $this->validateLetterheadScope($sheet->scoutGroup, $sheet->academicYear, $letterheadType, $sheet->scout_level_id, $sheet->kind);
        abort_if($this->getErrorBag()->has('letterheadType'), 422, 'Jenis kop dokumen tidak sesuai formasi Gudep.');
        $school = School::query()->findOrFail($this->schoolId());
        $letterhead = GudepDocumentLetterhead::resolve($school, $sheet->scoutGroup, $letterheadType);

        $activity = $sheet->activity;
        // Never publish a document with a dangling or cross-context activity reference.
        abort_unless($sheet->activity_id === null || (
            $activity !== null
            && (int) $activity->school_id === $this->schoolId()
            && (int) $activity->academic_year_id === (int) $sheet->academic_year_id
            && ($activity->semester_id === null || (int) $activity->semester_id === (int) $sheet->semester_id)
        ), 409, 'Agenda tertaut tidak lagi valid.');

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
            'year_id' => $sheet->academic_year_id,
            'year_name' => $sheet->academicYear?->name,
            'semester_id' => $sheet->semester_id,
            'semester_name' => $sheet->semester?->name,
            'level_id' => $sheet->scout_level_id,
            'level_name' => $sheet->scoutLevel?->name,
            'parent_id' => $sheet->parent_id,
            'scheduled_for' => $sheet->scheduled_for?->toDateString(),
            'activity_id' => $sheet->activity_id,
            'activity_title' => $activity?->title,
            'activity_start_at' => $activity?->start_at?->toIso8601String(),
            'activity_end_at' => $activity?->end_at?->toIso8601String(),
            'activity_location' => $activity?->location,
            'entries' => $sheet->entries ?? [],
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
        ?string $kind = null
    ): void {
        $type ??= $this->letterheadType;
        $kind ??= $this->kind;
        if ($kind === 'annual') {
            if ($type !== 'mabigus') {
                $this->addError('letterheadType', 'Program tahunan seluruh Gudep wajib menggunakan kop Mabigus.');
            }

            return;
        }

        $levelId ??= $this->scoutLevelId;
        $formations = GudepFormation::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)
            ->where('academic_year_id', $year->id)
            ->where('scout_level_id', $levelId)
            ->where('is_active', true)
            ->get();

        // An unspecified/campuran formation represents a joint unit, never infer a gender from its title.
        $genders = $formations->pluck('gender')->filter()->unique()->values()->all();
        if ($genders === ['putra'] && $type !== 'male') {
            $this->addError('letterheadType', 'Satuan putra harus memakai kop Gudep Putra.');

            return;
        }
        if ($genders === ['putri'] && $type !== 'female') {
            $this->addError('letterheadType', 'Satuan putri harus memakai kop Gudep Putri.');

            return;
        }
        if ($type === 'male' && ! in_array('putra', $genders, true)
            || $type === 'female' && ! in_array('putri', $genders, true)) {
            $this->addError('letterheadType', 'Jenis kop tidak sesuai formasi yang tercatat.');
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
            'letterheadProfile' => $gudep && $year ? GudepDocumentLetterhead::resolve(
                School::query()->findOrFail($this->schoolId()), $gudep,
                $this->kind === 'annual' ? 'mabigus' : $this->letterheadType
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
