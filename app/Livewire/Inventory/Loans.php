<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\InventoryStockBalance;
use App\Services\Inventory\InventoryLoanService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
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
            ->with(['items.item'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('requested_at')
            ->paginate(15);

        $items = InventoryItem::query()->where('school_id', $schoolId)->where('tracking_type', 'quantity')->where('is_active', true)->orderBy('name')->get();
        $owners = InventoryOwner::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('display_name')->get();
        $locations = InventoryLocation::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();

        return view('livewire.inventory.loans', compact('loans', 'items', 'owners', 'locations'));
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }
}
