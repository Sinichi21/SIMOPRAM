<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\GudepCashCloseDecision;
use App\Models\GudepCashEntry;
use App\Models\GudepCashEvidence;
use App\Models\GudepCashPeriod;
use App\Models\GudepCashReconciliation;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class GudepFinance extends Component
{
    use WithFileUploads;

    public ?int $receiptCashEntryId = null;

    public $receiptAttachment = null;

    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public string $budgetCategory = '';

    public string $budgetAmount = '';

    public string $budgetNotes = '';

    public string $cashDirection = 'income';

    public ?int $editingCashId = null;

    public string $inventoryScope = 'all';

    public string $cashCategory = '';

    public string $cashDate = '';

    public string $cashDescription = '';

    public string $cashAmount = '';

    public string $cashReference = '';

    public string $cashNotes = '';

    public ?int $cashActivityId = null;

    public ?int $cashInventoryItemId = null;

    public string $ledgerSearch = '';

    public string $ledgerDirection = 'all';

    public string $ledgerStatus = 'all';

    public ?int $reversalEntryId = null;

    public string $reversalReason = '';

    public string $openingBalanceInput = '';

    public string $closingNotesInput = '';

    public string $closeRejectionReason = '';

    public string $countedCashInput = '';

    public string $reconciliationNotesInput = '';

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

    private function cashPeriod(): ?GudepCashPeriod
    {
        return GudepCashPeriod::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->first();
    }

    /**
     * Every ledger mutation locks the same scoped period before writing.
     * A missing period is created only after the Gudep and year are verified.
     */
    private function lockOpenPeriod(): GudepCashPeriod
    {
        $period = GudepCashPeriod::query()->firstOrCreate([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $this->selectedGudep()->id,
            'academic_year_id' => $this->selectedYear()->id,
        ], [
            'opening_balance' => 0,
            'status' => 'open',
        ]);

        $locked = GudepCashPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
        abort_unless($locked->status === 'open' && $locked->close_requested_at === null,
            409, 'Buku kas sudah ditutup atau menunggu persetujuan penutupan.');

        return $locked;
    }

    public function saveOpeningBalance(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'openingBalanceInput' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        DB::transaction(function () use ($data): void {
            $period = $this->lockOpenPeriod();
            abort_if($period->opening_set_at !== null, 409, 'Saldo awal sudah ditetapkan.');
            abort_if((int) $data['openingBalanceInput'] !== 0
                && $this->cashEntries()->where('status', 'posted')->exists(), 409,
                'Untuk buku kas yang sudah memiliki transaksi, saldo awal hanya boleh dinyatakan nol.');

            $period->update([
                'opening_balance' => (int) $data['openingBalanceInput'],
                'opening_set_by' => auth()->id(),
                'opening_set_at' => now(),
            ]);
        });

        $this->openingBalanceInput = '';
        session()->flash('status', 'Saldo awal berhasil ditetapkan untuk tahun ajaran ini.');
    }

    /**
     * Stable checksum of the full scoped ledger and budgets.
     */
    private function ledgerFingerprint(ReportVerificationService $verifier, GudepCashPeriod $period): string
    {
        $entries = $this->cashEntries()->with('evidences')
            ->orderBy('occurred_at')->orderBy('id')->get();

        return $verifier->contentChecksum([
            'opening_balance' => $period->opening_balance,
            'entries' => $entries->map(fn ($item) => $item->only([
                'id', 'status', 'direction', 'category', 'amount', 'occurred_at',
                'reversal_of_id', 'reversal_reason', 'posted_at',
            ]) + ['evidence_hashes' => $item->evidences->pluck('sha256')->all()])->all(),
            'budgets' => $this->budgets()->orderBy('category')->get()
                ->map(fn ($item) => $item->only(['category', 'allocated_amount', 'notes']))->all(),
        ]);
    }

    public function reconcileCash(ReportVerificationService $verifier): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'countedCashInput' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'reconciliationNotesInput' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        DB::transaction(function () use ($verifier, $data): void {
            $period = $this->lockOpenPeriod();
            abort_unless($period->opening_set_at !== null, 409, 'Tetapkan saldo awal terlebih dahulu.');

            $totals = $this->cashTotals();
            $bookBalance = (int) $period->opening_balance + $totals['income'] - $totals['expense'];
            $counted = (int) $data['countedCashInput'];

            GudepCashReconciliation::query()->create([
                'school_id' => $this->schoolId(),
                'scout_group_id' => $this->selectedGudep()->id,
                'academic_year_id' => $this->selectedYear()->id,
                'gudep_cash_period_id' => $period->id,
                'book_balance' => $bookBalance,
                'counted_cash' => $counted,
                'difference' => $counted - $bookBalance,
                'ledger_checksum' => $this->ledgerFingerprint($verifier, $period),
                'notes' => trim($data['reconciliationNotesInput']),
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
            ]);
        });

        $this->reset(['countedCashInput', 'reconciliationNotesInput']);
        session()->flash('status', 'Hasil pemeriksaan kas fisik berhasil dicatat.');
    }

    /**
     * Request a reviewed close. The requester cannot approve their own request.
     */
    public function closeCashPeriod(ReportVerificationService $verifier): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'closingNotesInput' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        DB::transaction(function () use ($data, $verifier): void {
            $period = $this->lockOpenPeriod();
            abort_unless($period->opening_set_at !== null, 409, 'Tetapkan saldo awal sebelum tutup buku.');
            abort_if($this->cashEntries()->where('status', 'draft')->exists(), 409,
                'Selesaikan seluruh transaksi draf sebelum menutup buku.');

            $totals = $this->cashTotals();
            $closingBalance = (int) $period->opening_balance + $totals['income'] - $totals['expense'];
            $fingerprint = $this->ledgerFingerprint($verifier, $period);
            $reconciliation = $this->latestCashReconciliation($period);

            abort_unless($reconciliation !== null, 409,
                'Catat rekonsiliasi kas fisik sebelum tutup buku.');
            abort_unless($reconciliation->difference === 0, 409,
                'Saldo kas fisik belum sesuai dengan saldo buku.');
            abort_unless($reconciliation->ledger_checksum === $fingerprint
                && $reconciliation->book_balance === $closingBalance, 409,
                'Buku kas berubah sejak rekonsiliasi. Ulangi pemeriksaan kas fisik.');

            $period->update([
                'close_requested_by' => auth()->id(),
                'close_requested_at' => now(),
                'close_requested_notes' => trim($data['closingNotesInput']),
                'close_requested_checksum' => $fingerprint,
                'close_requested_balance' => $closingBalance,
                'close_requested_reconciliation_id' => $reconciliation->id,
            ]);
            $this->recordCloseDecision($period, 'requested', trim($data['closingNotesInput']));
        });

        $this->closingNotesInput = '';
        session()->flash('status', 'Permohonan tutup buku terkirim. Menunggu persetujuan petugas lain.');
    }

    private function recordCloseDecision(GudepCashPeriod $period, string $action, ?string $notes = null): void
    {
        GudepCashCloseDecision::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $period->scout_group_id,
            'academic_year_id' => $period->academic_year_id,
            'gudep_cash_period_id' => $period->id,
            'action' => $action,
            'actor_id' => auth()->id(),
            'requester_id' => $action === 'requested' ? auth()->id() : $period->close_requested_by,
            'ledger_checksum' => $period->close_requested_checksum,
            'book_balance' => $period->close_requested_balance,
            'notes' => $notes,
            'decided_at' => now(),
        ]);
    }

    private function latestCashReconciliation(GudepCashPeriod $period): ?GudepCashReconciliation
    {
        return GudepCashReconciliation::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->where('gudep_cash_period_id', $period->id)
            ->orderByDesc('id')->first();
    }

    public function approveCashPeriodClosure(ReportVerificationService $verifier): void
    {
        $this->authorizeManage();
        abort_unless(auth()->user()?->can('gudep.finance.close.approve'), 403);

        DB::transaction(function () use ($verifier): void {
            $period = $this->cashPeriod();
            abort_unless($period !== null, 404);
            $period = GudepCashPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            abort_unless($period->status === 'open' && $period->close_requested_at !== null, 409,
                'Tidak ada permohonan penutupan yang aktif.');
            abort_if((int) $period->close_requested_by === (int) auth()->id(), 403,
                'Pemeriksa harus berbeda dari pemohon penutupan.');
            abort_if($this->cashEntries()->where('status', 'draft')->exists(), 409);

            $totals = $this->cashTotals();
            $balance = (int) $period->opening_balance + $totals['income'] - $totals['expense'];
            $fingerprint = $this->ledgerFingerprint($verifier, $period);
            $reconciliation = $this->latestCashReconciliation($period);

            abort_unless($reconciliation !== null
                && $reconciliation->difference === 0
                && $reconciliation->ledger_checksum === $fingerprint
                && $reconciliation->book_balance === $balance
                && (int) $reconciliation->id === (int) $period->close_requested_reconciliation_id
                && $period->close_requested_checksum === $fingerprint
                && $period->close_requested_balance === $balance, 409,
                'Rekonsiliasi atau data keuangan berubah. Permohonan harus diajukan ulang.');

            $checksum = $verifier->contentChecksum([
                'ledger_checksum' => $fingerprint,
                'reconciliation_id' => $reconciliation->id,
                'closing_balance' => $balance,
                'counted_cash' => $reconciliation->counted_cash,
                'requested_by' => $period->close_requested_by,
                'approved_by' => auth()->id(),
            ]);

            $period->update([
                'status' => 'closed',
                'closing_balance' => $balance,
                'closing_checksum' => $checksum,
                'closed_by' => auth()->id(),
                'closed_at' => now(),
                'closing_notes' => $period->close_requested_notes,
            ]);
            $this->recordCloseDecision($period, 'approved', $period->close_requested_notes);
        });

        session()->flash('status', 'Penutupan buku berhasil disetujui dan periode dikunci.');
    }

    public function rejectCashPeriodClosure(): void
    {
        $this->authorizeManage();
        abort_unless(auth()->user()?->can('gudep.finance.close.approve'), 403);
        $data = $this->validate([
            'closeRejectionReason' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        DB::transaction(function () use ($data): void {
            $period = $this->cashPeriod();
            abort_unless($period !== null, 404);
            $period = GudepCashPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            abort_unless($period->status === 'open' && $period->close_requested_at !== null, 409);
            abort_if((int) $period->close_requested_by === (int) auth()->id(), 403);

            $this->recordCloseDecision($period, 'rejected', trim($data['closeRejectionReason']));
            $period->update([
                'close_requested_by' => null,
                'close_requested_at' => null,
                'close_requested_notes' => null,
                'close_requested_checksum' => null,
                'close_requested_balance' => null,
                'close_requested_reconciliation_id' => null,
            ]);
        });

        $this->closeRejectionReason = '';
        session()->flash('status', 'Permohonan penutupan ditolak. Periode kembali dapat dikelola.');
    }

    public function clearLedgerFilters(): void
    {
        $this->reset(['ledgerSearch', 'ledgerDirection', 'ledgerStatus']);
        $this->ledgerDirection = 'all';
        $this->ledgerStatus = 'all';
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
        DB::transaction(function () use ($data, $gudep, $year): void {
            $this->lockOpenPeriod();
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
        });

        $this->reset(['budgetCategory', 'budgetAmount', 'budgetNotes']);
        session()->flash('status', 'Anggaran kategori berhasil disimpan.');
    }

    /**
     * Dropdown choices come from budget categories, existing transaction categories,
     * and common receipts. Historical records are kept without silent rewriting.
     *
     * @return array<int, string>
     */
    private function cashCategoryOptions(): array
    {
        $names = array_merge(
            $this->budgets()->pluck('category')->all(),
            $this->cashEntries()->distinct()->pluck('category')->all(),
            ['Iuran', 'Hibah BOS', 'Donasi', 'Lainnya']
        );

        $options = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            $key = mb_strtolower($name);
            if ($name !== '' && ! isset($options[$key])) {
                $options[$key] = $name;
            }
        }

        return array_values($options);
    }

    private function canonicalCategory(string $selected): string
    {
        $selected = trim($selected);
        foreach ($this->budgets()->pluck('category') as $budgetCategory) {
            if (mb_strtolower(trim($budgetCategory)) === mb_strtolower($selected)) {
                return $budgetCategory;
            }
        }

        return $selected;
    }

    /**
     * Available items from both school and selected Gudep owners.
     *
     * @return array<int, int>
     */
    private function inventoryOwnerIds(): array
    {
        return InventoryOwner::query()->where('school_id', $this->schoolId())
            ->where(function (Builder $query): void {
                $query->where('owner_type', 'school')
                    ->orWhere(function (Builder $nested): void {
                        $nested->where('owner_type', 'gudep')
                            ->where('scout_group_id', $this->selectedGudep()->id);
                    });
            })->pluck('id')->all();
    }

    public function editCashDraft(int $id): void
    {
        $this->authorizeManage();
        $this->lockOpenPeriod();
        $entry = $this->cashEntries()->findOrFail($id);
        abort_unless($entry->status === 'draft', 409, 'Hanya draf yang dapat diedit.');

        $this->editingCashId = $entry->id;
        $this->cashDirection = $entry->direction;
        $this->cashCategory = $this->canonicalCategory($entry->category);
        $this->cashDate = $entry->occurred_at->toDateString();
        $this->cashDescription = $entry->description;
        $this->cashAmount = (string) $entry->amount;
        $this->cashReference = $entry->reference_number ?? '';
        $this->cashNotes = $entry->notes ?? '';
        $this->cashActivityId = $entry->activity_id;
        $this->cashInventoryItemId = $entry->inventory_item_id;
    }

    public function cancelCashDraftEdit(): void
    {
        $this->reset([
            'editingCashId', 'cashCategory', 'cashDescription', 'cashAmount',
            'cashReference', 'cashNotes', 'cashActivityId', 'cashInventoryItemId',
        ]);
        $this->cashDirection = 'income';
        $this->cashDate = now()->toDateString();
    }

    public function deleteCashDraft(int $id): void
    {
        $this->authorizeManage();
        DB::transaction(function () use ($id): void {
            $this->lockOpenPeriod();
            $entry = $this->cashEntries()->lockForUpdate()->findOrFail($id);
            abort_unless($entry->status === 'draft', 409, 'Transaksi terposting tidak dapat dihapus.');
            abort_if($entry->evidences()->exists(), 409, 'Draf yang memiliki bukti tidak boleh dihapus.');
            $entry->delete();
        });

        if ($this->editingCashId === $id) {
            $this->cancelCashDraftEdit();
        }
        session()->flash('status', 'Draf transaksi dibatalkan dan dihapus.');
    }

    public function saveCash(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'cashDirection' => ['required', Rule::in(['income', 'expense'])],
            'cashCategory' => ['required', 'string', 'max:100', Rule::in($this->cashCategoryOptions())],
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
        if (filled($data['cashInventoryItemId'])) {
            abort_unless(auth()->user()?->can('inventory.view'), 403);
            $ownerIds = $this->inventoryOwnerIds();
            if (InventoryStockBalance::query()->where('school_id', $this->schoolId())
                ->whereIn('owner_id', $ownerIds)
                ->where('item_id', (int) $data['cashInventoryItemId'])->doesntExist()
                && InventoryAsset::query()->where('school_id', $this->schoolId())
                    ->whereIn('owner_id', $ownerIds)
                    ->where('item_id', (int) $data['cashInventoryItemId'])->doesntExist()) {
                throw ValidationException::withMessages([
                    'cashInventoryItemId' => 'Barang harus dimiliki sekolah atau Gudep terpilih.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $date, $gudep, $year): void {
            $this->lockOpenPeriod();

            $entry = $this->editingCashId !== null
                ? $this->cashEntries()->lockForUpdate()->findOrFail($this->editingCashId)
                : new GudepCashEntry;

            if ($this->editingCashId !== null) {
                abort_unless($entry->status === 'draft', 409, 'Hanya draf yang dapat diedit.');
            }

            $entry->fill([
                'school_id' => $this->schoolId(),
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
                'activity_id' => $data['cashActivityId'] ?: null,
                'inventory_item_id' => $data['cashInventoryItemId'] ?: null,
                'direction' => $data['cashDirection'],
                'category' => $this->canonicalCategory($data['cashCategory']),
                'occurred_at' => $date,
                'description' => trim($data['cashDescription']),
                'amount' => (int) $data['cashAmount'],
                'reference_number' => $data['cashReference'] ?: null,
                'notes' => $data['cashNotes'] ?: null,
                'status' => 'draft',
                'created_by' => $entry->created_by ?? auth()->id(),
            ]);
            $entry->save();
        });

        $this->reset(['editingCashId', 'cashCategory', 'cashDescription', 'cashAmount', 'cashReference',
            'cashNotes', 'cashActivityId', 'cashInventoryItemId']);
        $this->cashDirection = 'income';
        session()->flash('status', 'Draf transaksi berhasil disimpan. Posting agar masuk buku kas.');
    }

    public function postCash(int $id): void
    {
        $this->authorizeManage();
        DB::transaction(function () use ($id): void {
            $this->lockOpenPeriod();
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

    public function selectCashForReversal(int $id): void
    {
        $this->authorizeManage();

        $entry = $this->cashEntries()->find($id);
        abort_unless($entry !== null, 404);
        abort_unless($entry->status === 'posted' && $entry->reversal_of_id === null, 409);
        abort_if($this->cashEntries()->where('reversal_of_id', $entry->id)->exists(), 409,
            'Transaksi sudah dibalik.');

        $this->reversalEntryId = $entry->id;
        $this->reversalReason = '';
    }

    public function reverseCash(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'reversalEntryId' => ['required', 'integer'],
            'reversalReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($data): void {
            $this->lockOpenPeriod();
            $original = $this->cashEntries()->lockForUpdate()->find((int) $data['reversalEntryId']);
            abort_unless($original !== null, 404);
            abort_unless($original->status === 'posted' && $original->reversal_of_id === null, 409,
                'Hanya transaksi asli yang sudah diposting dapat dibalik.');
            abort_if($this->cashEntries()->where('reversal_of_id', $original->id)->exists(), 409,
                'Transaksi ini sudah pernah dibalik.');

            GudepCashEntry::query()->create([
                'school_id' => $this->schoolId(),
                'scout_group_id' => $original->scout_group_id,
                'academic_year_id' => $original->academic_year_id,
                'activity_id' => $original->activity_id,
                'inventory_item_id' => $original->inventory_item_id,
                'direction' => $original->direction === 'income' ? 'expense' : 'income',
                'category' => $original->category,
                'occurred_at' => $original->occurred_at,
                'description' => 'Pembalikan: '.$original->description,
                'amount' => $original->amount,
                'reference_number' => $original->reference_number,
                'notes' => 'Pembalikan transaksi #'.$original->id,
                'status' => 'posted',
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'reversal_of_id' => $original->id,
                'reversal_reason' => trim($data['reversalReason']),
            ]);
        });

        $this->reset(['reversalEntryId', 'reversalReason']);
        session()->flash('status', 'Pembalikan berhasil diposting. Transaksi asli tetap tersimpan.');
    }

    public function uploadCashEvidence(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();

        $validated = $this->validate([
            'receiptCashEntryId' => ['required', 'integer',
                Rule::exists('gudep_cash_entries', 'id')
                    ->where('school_id', $this->schoolId())
                    ->where('scout_group_id', $gudep->id)
                    ->where('academic_year_id', $year->id)],
            'receiptAttachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ]);

        $entry = $this->cashEntries()->findOrFail((int) $validated['receiptCashEntryId']);
        $file = $this->receiptAttachment;
        $temp = $file->getRealPath();
        if (! is_string($temp) || ! is_file($temp)) {
            throw ValidationException::withMessages([
                'receiptAttachment' => 'Berkas sementara tidak ditemukan.',
            ]);
        }

        $hash = hash_file('sha256', $temp);
        if (! is_string($hash)) {
            throw ValidationException::withMessages([
                'receiptAttachment' => 'Bukti tidak dapat diverifikasi.',
            ]);
        }

        $directory = 'gudep-cash-evidences/'.$this->schoolId().'/'.$gudep->id.'/'.$entry->id;
        $filename = (string) Str::uuid().'.'.strtolower((string) $file->extension());
        $path = $file->storeAs($directory, $filename, 'local');

        if (! is_string($path)) {
            throw ValidationException::withMessages([
                'receiptAttachment' => 'Bukti kas gagal disimpan.',
            ]);
        }

        try {
            DB::transaction(function () use ($entry, $gudep, $file, $path, $hash): void {
                $this->lockOpenPeriod();
                GudepCashEvidence::query()->create([
                    'school_id' => $this->schoolId(),
                    'scout_group_id' => $gudep->id,
                    'gudep_cash_entry_id' => $entry->id,
                    'uploaded_by' => auth()->id(),
                    'original_filename' => $file->getClientOriginalName(),
                    'disk' => 'local',
                    'path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => (int) $file->getSize(),
                    'sha256' => $hash,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        $this->reset(['receiptCashEntryId', 'receiptAttachment']);
        session()->flash('status', 'Bukti kas berhasil diarsipkan secara privat.');
    }

    /**
     * Posted cash totals use original entries minus reversals of the same type.
     * Reversal entries appear in the ledger but do not become operating income/expense.
     *
     * @return array{income: int, expense: int}
     */
    private function cashTotals(): array
    {
        $originalIncome = (int) $this->cashEntries()->where('status', 'posted')
            ->whereNull('reversal_of_id')->where('direction', 'income')->sum('amount');
        $originalExpense = (int) $this->cashEntries()->where('status', 'posted')
            ->whereNull('reversal_of_id')->where('direction', 'expense')->sum('amount');
        $reversedIncome = (int) $this->cashEntries()->where('status', 'posted')
            ->whereNotNull('reversal_of_id')->where('direction', 'expense')->sum('amount');
        $reversedExpense = (int) $this->cashEntries()->where('status', 'posted')
            ->whereNotNull('reversal_of_id')->where('direction', 'income')->sum('amount');

        return [
            'income' => $originalIncome - $reversedIncome,
            'expense' => $originalExpense - $reversedExpense,
        ];
    }

    /**
     * Expense realization excludes reversed expense while retaining the audit trail.
     *
     * @return array<string, int>
     */
    private function expenseByCategory(): array
    {
        $expense = $this->cashEntries()->where('status', 'posted')
            ->whereNull('reversal_of_id')->where('direction', 'expense')
            ->selectRaw('category, SUM(amount) AS total_amount')->groupBy('category')
            ->pluck('total_amount', 'category')->map(fn ($total) => (int) $total)->all();

        $reversals = $this->cashEntries()->where('status', 'posted')
            ->whereNotNull('reversal_of_id')->where('direction', 'income')
            ->selectRaw('category, SUM(amount) AS total_amount')->groupBy('category')
            ->pluck('total_amount', 'category');

        foreach ($reversals as $category => $amount) {
            $expense[$category] = ($expense[$category] ?? 0) - (int) $amount;
        }

        return $expense;
    }

    public function publishFinanceLpj(
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier,
    ): void {
        $this->authorizeExport();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();
        $period = $this->cashPeriod();

        abort_unless($period?->status === 'closed', 409,
            'LPJ resmi hanya dapat diterbitkan setelah penutupan buku disetujui.');

        $entries = $this->cashEntries()->where('status', 'posted')
            ->with('evidences')->orderBy('occurred_at')->orderBy('id')->get();
        $budgets = $this->budgets()->orderBy('category')->get();
        $totals = $this->cashTotals();
        $income = $totals['income'];
        $expense = $totals['expense'];
        $openingBalance = (int) $period->opening_balance;
        $expenseByCategory = $this->expenseByCategory();
        $reconciliation = $this->latestCashReconciliation($period);
        $evidenceCount = $entries->sum(fn ($entry) => $entry->evidences->count());

        $snapshot = [
            'source_checksum' => $period->closing_checksum,
            'year' => $year->id,
            'gudep' => $gudep->id,
            'opening_balance' => $openingBalance,
            'income' => $income,
            'expense' => $expense,
            'budgets' => $budgets->map(fn ($item) => $item->only(['category', 'allocated_amount', 'notes']))->all(),
            'expense_by_category' => $expenseByCategory,
            'evidences' => $entries->flatMap(fn ($entry) => $entry->evidences->pluck('sha256'))->all(),
            'reconciliation_id' => $reconciliation?->id,
        ];

        $document = $publisher->publishBlade(
            schoolId: $this->schoolId(),
            sourceType: ScoutGroup::class,
            sourceId: $gudep->id,
            documentType: 'gudep_finance_lpj',
            seriesKey: 'year-'.$year->id,
            snapshotChecksum: $verifier->contentChecksum($snapshot),
            filename: 'lpj-keuangan-gudep-'.$gudep->id.'-'.$year->id.'.pdf',
            contentView: 'reports.pdf.administration.gudep-finance-lpj',
            contentData: compact(
                'gudep', 'year', 'period', 'entries', 'budgets', 'income', 'expense',
                'openingBalance', 'expenseByCategory', 'reconciliation', 'evidenceCount',
            ),
            title: 'LPJ Keuangan '.$gudep->name.' '.$year->name,
            metadata: ['academic_year' => $year->name, 'security_classification' => 'Terbatas'],
        );

        $this->redirectRoute('reports.published-documents.show', ['code' => $document->code], navigate: false);
    }

    public function publishCashBook(
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier,
    ): void {
        $this->authorizeExport();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();

        $entries = $this->cashEntries()->where('status', 'posted')
            ->with(['evidences', 'reversedEntry'])->orderBy('occurred_at')->orderBy('id')->get();
        $budgets = $this->budgets()->orderBy('category')->get();
        $period = $this->cashPeriod();
        $openingBalance = (int) ($period?->opening_balance ?? 0);
        $reconciliation = $period ? GudepCashReconciliation::query()
            ->where('gudep_cash_period_id', $period->id)->orderByDesc('id')->first() : null;
        $totals = $this->cashTotals();
        $income = $totals['income'];
        $expense = $totals['expense'];
        $expenseByCategory = $this->expenseByCategory();
        $snapshot = [
            'gudep' => $gudep->name,
            'year' => $year->name,
            'entries' => $entries->map(fn ($item) => $item->only([
                'id', 'direction', 'category', 'occurred_at', 'description', 'amount',
                'reference_number', 'activity_id', 'inventory_item_id', 'posted_at',
                'reversal_of_id', 'reversal_reason',
            ]) + ['evidences' => $item->evidences->map(fn ($evidence) => [
                'id' => $evidence->id,
                'original_filename' => $evidence->original_filename,
                'sha256' => $evidence->sha256,
            ])->all()])->all(),
            'opening_balance' => $openingBalance,
            'period_status' => $period?->status ?? 'open',
            'closing_checksum' => $period?->closing_checksum,
            'closing_approved_by' => $period?->closed_by,
            'closing_requested_by' => $period?->close_requested_by,
            'close_decisions' => GudepCashCloseDecision::query()
                ->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)
                ->orderBy('id')->get()
                ->map(fn ($decision) => $decision->only([
                    'id', 'action', 'actor_id', 'requester_id', 'book_balance',
                    'ledger_checksum', 'decided_at', 'notes',
                ]))->all(),
            'reconciliation' => $reconciliation?->only(['id', 'book_balance', 'counted_cash', 'difference', 'ledger_checksum']),
            'expense_by_category' => $expenseByCategory,
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
            contentData: compact('gudep', 'year', 'entries', 'budgets', 'income', 'expense', 'expenseByCategory', 'openingBalance', 'period', 'reconciliation'),
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
        abort_unless(auth()->user()?->can('gudep.finance.view'), 403);
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

        $ledgerQuery = $gudep && $year ? $this->cashEntries() : null;
        if ($ledgerQuery !== null) {
            $search = trim($this->ledgerSearch);
            if ($search !== '') {
                $ledgerQuery->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%')
                        ->orWhere('reference_number', 'like', '%'.$search.'%');
                });
            }

            if (in_array($this->ledgerDirection, ['income', 'expense'], true)) {
                $ledgerQuery->where('direction', $this->ledgerDirection);
            }
            if (in_array($this->ledgerStatus, ['draft', 'posted'], true)) {
                $ledgerQuery->where('status', $this->ledgerStatus);
            }
        }

        $ledgerMatches = $ledgerQuery !== null ? (clone $ledgerQuery)->count() : 0;
        $cash = $ledgerQuery !== null
            ? $ledgerQuery->with(['evidences', 'reversedEntry'])->withExists('reversals')->orderByDesc('occurred_at')->orderByDesc('id')->limit(100)->get()
            : collect();
        $totals = $gudep && $year ? $this->cashTotals() : ['income' => 0, 'expense' => 0];
        $income = $totals['income'];
        $expense = $totals['expense'];
        $expenseByCategory = $gudep && $year ? $this->expenseByCategory() : [];
        $period = $gudep && $year ? $this->cashPeriod() : null;
        $latestReconciliation = $period ? GudepCashReconciliation::query()
            ->where('gudep_cash_period_id', $period->id)->orderByDesc('id')->first() : null;
        $openingBalance = (int) ($period?->opening_balance ?? 0);
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
            'ledgerMatches' => $ledgerMatches,
            'reversalSelectedEntry' => $gudep && $year && $this->reversalEntryId !== null
                ? $this->cashEntries()->find($this->reversalEntryId) : null,
            'income' => $income,
            'expense' => $expense,
            'expenseByCategory' => $expenseByCategory,
            'cashPeriod' => $period,
            'closeDecisions' => $period ? GudepCashCloseDecision::query()
                ->where('school_id', $schoolId)
                ->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)
                ->where('gudep_cash_period_id', $period->id)
                ->orderByDesc('id')->limit(30)->get() : collect(),
            'latestReconciliation' => $latestReconciliation,
            'openingBalance' => $openingBalance,
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
