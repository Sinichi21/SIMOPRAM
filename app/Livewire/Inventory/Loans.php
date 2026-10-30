<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryAsset;
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

    public ?int $assetId = null;

    public array $cart = [];

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

    public function addToCart(): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        $schoolId = $this->schoolId();
        $data = $this->validate([
            'itemId' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'ownerId' => ['required', 'integer', Rule::exists('inventory_owners', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'locationId' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:99999999999', 'decimal:0,3'],
        ]);
        $item = InventoryItem::query()->where('school_id', $schoolId)->findOrFail($data['itemId']);
        $asset = null;
        if ($item->tracking_type === 'individual') {
            $this->validate(['assetId' => ['required', 'integer']]);
            $asset = InventoryAsset::query()->where('school_id', $schoolId)
                ->where('item_id', $item->id)->where('owner_id', $data['ownerId'])
                ->where('location_id', $data['locationId'])->where('status', 'available')
                ->where('condition', 'good')->findOrFail($this->assetId);
            if ((float) $data['quantity'] !== 1.0) {
                $this->addError('quantity', 'Aset individual hanya boleh satu unit per baris.');
                return;
            }
        } elseif ($this->assetId !== null) {
            $this->addError('assetId', 'Barang kuantitas tidak menggunakan kode unit.');
            return;
        }

        $key = implode(':', [$item->id, $data['ownerId'], $data['locationId'], $asset?->id ?? 'stock']);
        foreach ($this->cart as $index => $line) {
            if ($line['key'] !== $key) {
                continue;
            }
            if ($asset) {
                $this->addError('assetId', 'Unit ini sudah ada di keranjang.');
                return;
            }
            $sum = round((float) $line['quantity'] + (float) $data['quantity'], 3);
            if ($sum > 99999999999) {
                $this->addError('quantity', 'Jumlah melampaui batas.');
                return;
            }
            $this->cart[$index]['quantity'] = $sum;
            return;
        }

        $this->cart[] = [
            'key' => $key, 'item_id' => $item->id, 'owner_id' => (int) $data['ownerId'],
            'location_id' => (int) $data['locationId'], 'asset_id' => $asset?->id,
            'quantity' => (float) $data['quantity'], 'name' => $item->name,
            'asset_code' => $asset?->asset_code,
        ];
        $this->reset('itemId', 'ownerId', 'locationId', 'assetId');
        $this->quantity = '1';
    }

    public function removeFromCart(int $index): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        if (array_key_exists($index, $this->cart)) {
            array_splice($this->cart, $index, 1);
        }
    }

    public function requestLoan(InventoryLoanService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        $data = $this->validate([
            'borrowerName' => ['required', 'string', 'max:180'],
            'borrowerOrganization' => ['nullable', 'string', 'max:180'],
            'borrowerContact' => ['required', 'string', 'max:100'],
            'purpose' => ['required', 'string', 'max:2000'],
            'cart' => ['required', 'array', 'min:1', 'max:500'],
            'cart.*.item_id' => ['required', 'integer'],
            'cart.*.owner_id' => ['required', 'integer'],
            'cart.*.location_id' => ['required', 'integer'],
            'cart.*.asset_id' => ['nullable', 'integer'],
            'cart.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:99999999999', 'decimal:0,3'],
        ]);
        $lines = array_map(static fn (array $line): array => [
            'item_id' => $line['item_id'], 'owner_id' => $line['owner_id'],
            'location_id' => $line['location_id'], 'asset_id' => $line['asset_id'],
            'quantity' => $line['quantity'],
        ], $data['cart']);

        $service->requestLoan([
            'type' => 'manual_external', 'name' => trim($data['borrowerName']),
            'organization' => trim($data['borrowerOrganization'] ?? ''),
            'contact' => trim($data['borrowerContact']),
        ], $lines, trim($data['purpose']));

        $this->reset('borrowerName', 'borrowerOrganization', 'borrowerContact', 'purpose', 'itemId', 'ownerId', 'locationId', 'assetId', 'cart', 'showRequestForm');
        $this->quantity = '1';
        session()->flash('status', 'Permohonan peminjaman multi-barang berhasil dibuat.');
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
            ->with(['items.item', 'events', 'documents.issuer', 'documents.prints.printer'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('requested_at')
            ->paginate(15);

        $items = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();
        $owners = InventoryOwner::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('display_name')->get();
        $activeLoan = $this->activeLoanId
            ? InventoryLoan::query()->where('school_id', $schoolId)->with(['items.item'])->find($this->activeLoanId)
            : null;
        $locations = InventoryLocation::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();

        $availableAssets = $this->showRequestForm && $this->itemId
            ? InventoryAsset::query()->where('school_id', $schoolId)->where('item_id', $this->itemId)
                ->where('status', 'available')->where('condition', 'good')->orderBy('asset_code')->limit(500)->get()
            : collect();

        return view('livewire.inventory.loans', compact('loans', 'items', 'owners', 'locations', 'activeLoan', 'availableAssets'));
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }
}
