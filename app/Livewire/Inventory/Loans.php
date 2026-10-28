<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Services\Inventory\InventoryLoanService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Loans extends Component
{
    use WithPagination;

    public bool $showRequestForm = false;

    public string $borrowerName = '';

    public string $borrowerOrganization = '';

    public string $borrowerContact = '';

    public string $purpose = '';

    public ?int $itemId = null;

    public ?int $ownerId = null;

    public ?int $locationId = null;

    public string $quantity = '1';

    public string $statusFilter = '';

    public ?int $activeLoanId = null;

    public ?int $activeLoanItemId = null;

    public string $handoverQuantity = '1';

    public string $returnQuantity = '1';

    public string $missingQuantity = '0';

    public string $returnCondition = 'good';

    public string $recordingMethod = 'online';

    public string $transactionNotes = '';

    public string $occurredAt = '';

    public string $transactionMode = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
        $this->schoolId();
    }

    public function requestLoan(InventoryLoanService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        $schoolId = $this->schoolId();

        $data = $this->validate([
            'borrowerName' => ['required', 'string', 'max:180'],
            'borrowerOrganization' => ['nullable', 'string', 'max:180'],
            'borrowerContact' => ['nullable', 'string', 'max:100'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'itemId' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('school_id', $schoolId)->where('tracking_type', 'quantity')->where('is_active', true)],
            'ownerId' => ['required', 'integer', Rule::exists('inventory_owners', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'locationId' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:99999999999', 'decimal:0,3'],
        ]);

        $service->requestLoan([
            'type' => 'manual_external',
            'name' => trim($data['borrowerName']),
            'organization' => trim($data['borrowerOrganization'] ?? ''),
            'contact' => trim($data['borrowerContact'] ?? ''),
        ], [[
            'item_id' => $data['itemId'],
            'owner_id' => $data['ownerId'],
            'location_id' => $data['locationId'],
            'quantity' => $data['quantity'],
        ]], $data['purpose'] ?: null);

        $this->reset('borrowerName', 'borrowerOrganization', 'borrowerContact', 'purpose', 'itemId', 'ownerId', 'locationId', 'quantity', 'showRequestForm');
        $this->quantity = '1';
        session()->flash('status', 'Permohonan peminjaman berhasil dibuat.');
    }

    public function approveLoan(int $loanId, InventoryLoanService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.approve'), 403);
        $service->approve($loanId);
        session()->flash('status', 'Peminjaman berhasil disetujui.');
    }

    public function openTransaction(int $loanId, string $mode): void
    {
        $permission = $mode === 'handover' ? 'inventory.handover.process' : 'inventory.returns.process';
        abort_unless(in_array($mode, ['handover', 'return'], true) && auth()->user()?->can($permission), 403);

        $loan = InventoryLoan::query()->where('school_id', $this->schoolId())->with('items')->findOrFail($loanId);
        $allowed = $mode === 'handover'
            ? in_array($loan->status, ['approved', 'partially_handed_over'], true)
            : in_array($loan->status, ['handed_over', 'partially_handed_over', 'partially_returned', 'discrepancy'], true);
        abort_unless($allowed, 422, 'Status peminjaman tidak memungkinkan transaksi ini.');

        $this->reset('activeLoanItemId', 'handoverQuantity', 'returnQuantity', 'missingQuantity', 'returnCondition', 'recordingMethod', 'transactionNotes', 'occurredAt');
        $this->handoverQuantity = '1';
        $this->returnQuantity = '1';
        $this->missingQuantity = '0';
        $this->returnCondition = 'good';
        $this->recordingMethod = 'online';
        $this->occurredAt = now()->format('Y-m-d\\TH:i');
        $this->activeLoanId = $loan->id;
        $this->transactionMode = $mode;
    }

    public function saveTransaction(InventoryLoanService $service): void
    {
        abort_unless(in_array($this->transactionMode, ['handover', 'return'], true), 422);
        $permission = $this->transactionMode === 'handover' ? 'inventory.handover.process' : 'inventory.returns.process';
        abort_unless(auth()->user()?->can($permission), 403);

        $data = $this->validate([
            'activeLoanId' => ['required', 'integer'],
            'activeLoanItemId' => ['required', 'integer'],
            'handoverQuantity' => ['required', 'numeric', 'min:0.001', 'decimal:0,3'],
            'returnQuantity' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'missingQuantity' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'returnCondition' => ['required', Rule::in(['good', 'damaged'])],
            'recordingMethod' => ['required', Rule::in(['online', 'offline'])],
            'transactionNotes' => ['nullable', 'string', 'max:2000'],
            'occurredAt' => [Rule::requiredIf($this->recordingMethod === 'offline'), 'nullable', 'date_format:Y-m-d\\TH:i', 'before_or_equal:now'],
        ]);

        $loan = InventoryLoan::query()->where('school_id', $this->schoolId())->findOrFail($data['activeLoanId']);
        $line = $loan->items()->whereKey($data['activeLoanItemId'])->firstOrFail();
        $notes = trim($data['transactionNotes']) ?: null;
        $occurredAt = $data['recordingMethod'] === 'offline'
            ? Carbon::createFromFormat('!Y-m-d\\TH:i', $data['occurredAt'], config('app.timezone'))
            : now();

        if ($this->transactionMode === 'handover') {
            $service->recordHandover($loan->id, [[
                'loan_item_id' => $line->id,
                'quantity' => $data['handoverQuantity'],
                'condition' => 'good',
            ]], $occurredAt, $data['recordingMethod'], $notes);
        } else {
            $service->recordReturn($loan->id, [[
                'loan_item_id' => $line->id,
                'quantity_returned' => $data['returnQuantity'],
                'quantity_missing' => $data['missingQuantity'],
                'condition' => $data['returnCondition'],
                'condition_notes' => $notes,
            ]], $occurredAt, $data['recordingMethod'], $notes);
        }

        $this->reset('activeLoanId', 'activeLoanItemId', 'transactionMode', 'transactionNotes');
        session()->flash('status', 'Transaksi inventaris berhasil dicatat.');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
        $schoolId = $this->schoolId();

        $loans = InventoryLoan::query()
            ->where('school_id', $schoolId)
            ->with(['items.item', 'events', 'documents'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('requested_at')
            ->paginate(15);

        $items = InventoryItem::query()->where('school_id', $schoolId)->where('tracking_type', 'quantity')->where('is_active', true)->orderBy('name')->get();
        $owners = InventoryOwner::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('display_name')->get();
        $activeLoan = $this->activeLoanId
            ? InventoryLoan::query()->where('school_id', $schoolId)->with(['items.item'])->find($this->activeLoanId)
            : null;
        $locations = InventoryLocation::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();

        return view('livewire.inventory.loans', compact('loans', 'items', 'owners', 'locations', 'activeLoan'));
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }
}
