<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\GudepCashEntry;
use App\Models\GudepFinanceBudget;
use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Models\InventoryLoanItem;
use App\Models\InventoryOwner;
use App\Models\InventoryStockBalance;
use App\Models\ScoutGroup;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GudepFinance extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public string $budgetCategory = '';

    public string $budgetAmount = '';

    public string $budgetNotes = '';

    public string $cashDirection = 'income';

    public string $cashCategory = '';

    public string $cashDate = '';

    public string $cashDescription = '';

    public string $cashAmount = '';

    public string $cashReference = '';

    public string $cashNotes = '';

    public ?int $cashActivityId = null;

    public ?int $cashInventoryItemId = null;

    public function mount(): void
    {
        $this->authorizeView();
        $schoolId = $this->schoolId();
        $this->scoutGroupId = ScoutGroup::query()->where('school_id', $schoolId)
            ->orderBy('id')->value('id');
        $this->academicYearId = AcademicYear::query()->where('school_id', $schoolId)
            ->where('is_active', true)->value('id');
        $this->cashDate = now()->toDateString();
    }

    public function saveBudget(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'budgetCategory' => ['required', 'string', 'max:100'],
            'budgetAmount' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'budgetNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $year = $this->selectedYear();
        $gudep = $this->selectedGudep();

        GudepFinanceBudget::query()->updateOrCreate(
            [
                'school_id' => $this->schoolId(),
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
                'category' => trim($data['budgetCategory']),
            ],
            [
                'allocated_amount' => (int) $data['budgetAmount'],
                'notes' => $data['budgetNotes'] ?: null,
                'created_by' => auth()->id(),
            ]
        );

        $this->reset(['budgetCategory', 'budgetAmount', 'budgetNotes']);
        session()->flash('status', 'Anggaran kategori berhasil disimpan.');
    }

    public function saveCash(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'cashDirection' => ['required', Rule::in(['income', 'expense'])],
            'cashCategory' => ['required', 'string', 'max:100'],
            'cashDate' => ['required', 'date'],
            'cashDescription' => ['required', 'string', 'max:255'],
            'cashAmount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'cashReference' => ['nullable', 'string', 'max:100'],
            'cashNotes' => ['nullable', 'string', 'max:3000'],
            'cashActivityId' => ['nullable', 'integer', Rule::exists('activities', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'cashInventoryItemId' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
        ]);

        $year = $this->selectedYear();
        $date = Carbon::parse($data['cashDate'])->toDateString();
        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'cashDate' => 'Tanggal transaksi harus berada dalam tahun ajaran yang dipilih.',
            ]);
        }

        if (filled($data['cashActivityId'])) {
            $activity = Activity::query()->where('school_id', $this->schoolId())
                ->findOrFail((int) $data['cashActivityId']);
            if ((int) $activity->academic_year_id !== (int) $year->id) {
                throw ValidationException::withMessages([
                    'cashActivityId' => 'Kegiatan harus berasal dari tahun ajaran yang dipilih.',
                ]);
            }
        }

        $gudep = $this->selectedGudep();
        GudepCashEntry::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'activity_id' => $data['cashActivityId'] ?: null,
            'inventory_item_id' => $data['cashInventoryItemId'] ?: null,
            'direction' => $data['cashDirection'],
            'category' => trim($data['cashCategory']),
            'occurred_at' => $date,
            'description' => trim($data['cashDescription']),
            'amount' => (int) $data['cashAmount'],
            'reference_number' => $data['cashReference'] ?: null,
            'notes' => $data['cashNotes'] ?: null,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $this->reset(['cashCategory', 'cashDescription', 'cashAmount', 'cashReference',
            'cashNotes', 'cashActivityId', 'cashInventoryItemId']);
        $this->cashDirection = 'income';
        session()->flash('status', 'Transaksi tersimpan sebagai draf. Posting agar masuk buku kas.');
    }

    public function postCash(int $id): void
    {
        $this->authorizeManage();
        DB::transaction(function () use ($id): void {
            $entry = $this->cashEntries()->lockForUpdate()->find($id);
            abort_unless($entry !== null, 404);
            abort_unless($entry->status === 'draft', 409, 'Transaksi sudah diposting.');

            $entry->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);
        });

        session()->flash('status', 'Transaksi telah diposting ke buku kas.');
    }

    public function publishCashBook(
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier,
    ): void {
        $this->authorizeExport();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();

        $entries = $this->cashEntries()->where('status', 'posted')
            ->orderBy('occurred_at')->orderBy('id')->get();
        $budgets = $this->budgets()->orderBy('category')->get();
        $income = (int) $entries->where('direction', 'income')->sum('amount');
        $expense = (int) $entries->where('direction', 'expense')->sum('amount');
        $snapshot = [
            'gudep' => $gudep->name,
            'year' => $year->name,
            'entries' => $entries->map(fn ($item) => $item->only([
                'id', 'direction', 'category', 'occurred_at', 'description', 'amount',
                'reference_number', 'activity_id', 'inventory_item_id', 'posted_at',
            ]))->all(),
            'budgets' => $budgets->map(fn ($item) => $item->only([
                'id', 'category', 'allocated_amount', 'notes',
            ]))->all(),
        ];

        $document = $publisher->publishBlade(
            schoolId: $this->schoolId(),
            sourceType: ScoutGroup::class,
            sourceId: $gudep->id,
            documentType: 'gudep_cash_book',
            seriesKey: 'year-'.$year->id,
            snapshotChecksum: $verifier->contentChecksum($snapshot),
            filename: 'buku-kas-gudep-'.$gudep->id.'-'.$year->id.'.pdf',
            contentView: 'reports.pdf.administration.gudep-cash-book',
            contentData: compact('gudep', 'year', 'entries', 'budgets', 'income', 'expense'),
            title: 'Buku Kas '.$gudep->name.' '.$year->name,
            metadata: ['academic_year' => $year->name, 'security_classification' => 'Terbatas'],
        );

        $this->redirectRoute('reports.published-documents.show', ['code' => $document->code], navigate: false);
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

        return ScoutGroup::query()->where('school_id', $this->schoolId())
            ->findOrFail($this->scoutGroupId);
    }

    private function selectedYear(): AcademicYear
    {
        abort_unless($this->academicYearId !== null, 422);

        return AcademicYear::query()->where('school_id', $this->schoolId())
            ->findOrFail($this->academicYearId);
    }

    /** @return Builder<GudepCashEntry> */
    private function cashEntries(): Builder
    {
        return GudepCashEntry::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id);
    }

    /** @return Builder<GudepFinanceBudget> */
    private function budgets(): Builder
    {
        return GudepFinanceBudget::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id);
    }

    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);
        $this->schoolId();
    }

    private function authorizeManage(): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('gudep.finance.manage') || auth()->user()?->can('gudep.manage'), 403);
    }

    private function authorizeExport(): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('reports.export'), 403);
    }

    public function render(): View
    {
        $this->authorizeView();
        $schoolId = $this->schoolId();
        $gudep = $this->scoutGroupId !== null ? $this->selectedGudep() : null;
        $year = $this->academicYearId !== null ? $this->selectedYear() : null;

        $cash = $gudep && $year ? $this->cashEntries()->orderByDesc('occurred_at')->limit(100)->get() : collect();
        $posted = $cash->where('status', 'posted');
        $ownerIds = $gudep ? InventoryOwner::query()->where('school_id', $schoolId)
            ->where('owner_type', 'gudep')->where('scout_group_id', $gudep->id)
            ->pluck('id')->all() : [];

        return view('livewire.administration.gudep-finance', [
            'gudep' => $gudep,
            'year' => $year,
            'gudepOptions' => ScoutGroup::query()->where('school_id', $schoolId)->orderBy('name')->get(),
            'years' => AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('start_date')->get(),
            'budgets' => $gudep && $year ? $this->budgets()->orderBy('category')->get() : collect(),
            'entries' => $cash,
            'income' => (int) $posted->where('direction', 'income')->sum('amount'),
            'expense' => (int) $posted->where('direction', 'expense')->sum('amount'),
            'activities' => $year ? Activity::query()->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)->orderBy('title')->get() : collect(),
            'inventoryOptions' => $gudep && auth()->user()?->can('inventory.view')
                ? InventoryItem::query()->where('school_id', $schoolId)
                    ->whereHas('stockBalances', fn ($q) => $q->whereIn('owner_id', $ownerIds))
                    ->orderBy('name')->get() : collect(),
            'assets' => $gudep && auth()->user()?->can('inventory.view')
                ? InventoryAsset::query()->where('school_id', $schoolId)
                    ->whereIn('owner_id', $ownerIds)->with('item')->orderBy('asset_code')->limit(100)->get() : collect(),
            'stocks' => $gudep && auth()->user()?->can('inventory.view')
                ? InventoryStockBalance::query()->where('school_id', $schoolId)
                    ->whereIn('owner_id', $ownerIds)->with('item')->orderBy('id')->limit(100)->get() : collect(),
            'loanCount' => $gudep && auth()->user()?->can('inventory.view')
                ? InventoryLoanItem::query()->where('school_id', $schoolId)
                    ->whereIn('owner_id', $ownerIds)->distinct()->count('loan_id') : null,
        ]);
    }
}
