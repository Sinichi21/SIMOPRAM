<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryStockBalance;
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

    public string $itemCode = '';

    public string $quantity = '1';

    public ?int $assetId = null;

    public array $cart = [];

    public string $statusFilter = '';

    public string $loanSearch = '';

    public string $cancellationReason = '';

    public ?int $cancellingLoanId = null;

    public ?int $activeLoanId = null;

    public ?int $selectedLoanId = null;

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

    public function findByCode(): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        $this->reset('itemId', 'assetId');
        $this->resetValidation();
        $this->validate(['itemCode' => ['required', 'string', 'max:2048']]);
        $code = trim($this->itemCode);
        $schoolId = $this->schoolId();
        $asset = null;
        $item = null;

        if (filter_var($code, FILTER_VALIDATE_URL)) {
            $path = parse_url($code, PHP_URL_PATH);
            if (parse_url($code, PHP_URL_HOST) === parse_url(url('/'), PHP_URL_HOST)
                && preg_match('~^/inventaris/(verifikasi|verifikasi-aset)/(\d+)/(\d+)$~', $path ?? '', $matches)
                && (int) $matches[2] === $schoolId) {
                if ($matches[1] === 'verifikasi-aset') {
                    $asset = InventoryAsset::query()->where('school_id', $schoolId)->find($matches[3]);
                } else {
                    $item = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->find($matches[3]);
                }
            }
        } else {
            $asset = InventoryAsset::query()->where('school_id', $schoolId)->where('asset_code', $code)->first();
            if (! $asset) {
                $item = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->where('item_code', $code)->first();
            }
        }

        if ($asset) {
            $item = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->find($asset->item_id);
            if ($asset->status !== 'available' || $asset->condition !== 'good') {
                $this->addError('itemCode', 'Unit aset tidak tersedia atau kondisinya tidak baik.');

                return;
            }
        }
        if (! $item) {
            $this->addError('itemCode', 'Kode barang / QR tidak ditemukan di sekolah aktif.');

            return;
        }
        $this->itemId = $item->id;
        $this->assetId = $asset?->id;
        if ($asset) {
            $this->quantity = '1';
        }
    }

    public function updatedItemId(): void
    {
        $this->reset('assetId', 'itemCode');
        $this->resetValidation();
    }

    public function addToCart(): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        $schoolId = $this->schoolId();
        $data = $this->validate([
            'itemId' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('school_id', $schoolId)->where('is_active', true)->whereNull('deleted_at')],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:99999999999', 'decimal:0,3'],
        ]);
        $item = InventoryItem::query()->where('school_id', $schoolId)->findOrFail($data['itemId']);
        $remaining = (int) round((float) $data['quantity'] * 1000);
        $pending = [];
        if ($item->tracking_type === 'individual') {
            if ($remaining % 1000 !== 0 || ($this->assetId && $remaining !== 1000)) {
                $this->addError('quantity', 'Jumlah unit harus bulat; satu untuk kode unit tertentu.');

                return;
            }
            $sources = InventoryAsset::query()->where('school_id', $schoolId)->where('item_id', $item->id)
                ->where('status', 'available')->where('condition', 'good')
                ->when($this->assetId, fn ($query) => $query->whereKey($this->assetId))
                ->whereNotIn('id', array_filter(array_column($this->cart, 'asset_id')))
                ->orderBy('id')->limit(500)->get();
        } else {
            $sources = InventoryStockBalance::query()->where('school_id', $schoolId)->where('item_id', $item->id)
                ->whereColumn('quantity_on_hand', '>', 'quantity_reserved')->orderBy('id')->get();
        }
        $sources->load(['owner', 'location']);
        foreach ($sources as $source) {
            if (! $source->owner?->is_active || ! $source->location?->is_active
                || $source->owner->school_id !== $schoolId || $source->location->school_id !== $schoolId) {
                continue;
            }
            $asset = $source instanceof InventoryAsset ? $source : null;
            $key = implode(':', [$item->id, $source->owner_id, $source->location_id, $asset?->id ?? 'stock']);
            $existing = collect($this->cart)->firstWhere('key', $key);
            $available = $asset ? 1000 : (int) round(((float) $source->quantity_on_hand - (float) $source->quantity_reserved - (float) ($existing['quantity'] ?? 0)) * 1000);
            $allocated = min($remaining, max(0, $available));
            if ($allocated === 0) {
                continue;
            }
            $pending[] = [
                'key' => $key, 'item_id' => $item->id, 'owner_id' => $source->owner_id,
                'location_id' => $source->location_id, 'asset_id' => $asset?->id,
                'quantity' => $allocated / 1000, 'name' => $item->name,
                'asset_code' => $asset?->asset_code,
                'owner_name' => $source->owner->display_name, 'location_name' => $source->location->name,
            ];
            $remaining -= $allocated;
            if ($remaining === 0) {
                break;
            }
        }
        if ($remaining > 0) {
            $this->addError('quantity', 'Stok / unit tersedia tidak mencukupi (termasuk barang di keranjang).');

            return;
        }
        if (count($this->cart) + count($pending) > 500) {
            $this->addError('cart', 'Keranjang maksimal 500 baris.');

            return;
        }
        foreach ($pending as $line) {
            $index = array_search($line['key'], array_column($this->cart, 'key'), true);
            if ($index === false) {
                $this->cart[] = $line;
            } else {
                $this->cart[$index]['quantity'] = round($this->cart[$index]['quantity'] + $line['quantity'], 3);
            }
        }
        $this->reset('itemId', 'assetId', 'itemCode');
        $this->quantity = '1';
        $this->resetValidation();
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

        $this->reset('borrowerName', 'borrowerOrganization', 'borrowerContact', 'purpose', 'itemId', 'itemCode', 'assetId', 'cart', 'showRequestForm');
        $this->quantity = '1';
        session()->flash('status', 'Permohonan peminjaman multi-barang berhasil dibuat.');
    }

    public function openCancellation(int $loanId): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        $loan = InventoryLoan::query()->where('school_id', $this->schoolId())
            ->where('status', 'requested')->findOrFail($loanId);
        abort_unless(auth()->user()?->can('inventory.loans.approve')
            || ($loan->requested_by !== null && (int) $loan->requested_by === (int) auth()->id()), 403);
        $this->cancellingLoanId = $loanId;
        $this->cancellationReason = '';
        $this->resetValidation();
    }

    public function cancelLoan(InventoryLoanService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.request'), 403);
        $this->validate([
            'cancellingLoanId' => ['required', 'integer'],
            'cancellationReason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $service->cancelPending($this->cancellingLoanId, $this->cancellationReason);
        $this->reset('cancellingLoanId', 'cancellationReason');
        session()->flash('status', 'Permohonan dibatalkan dan alasannya tersimpan dalam riwayat.');
    }

    public function approveLoan(int $loanId, InventoryLoanService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.loans.approve'), 403);
        $service->approve($loanId);
        session()->flash('status', 'Peminjaman berhasil disetujui.');
    }

    public function showLoanDetails(int $loanId): void
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
        InventoryLoan::query()->where('school_id', $this->schoolId())->findOrFail($loanId);
        $this->selectedLoanId = $loanId;
    }

    public function closeLoanDetails(): void
    {
        $this->selectedLoanId = null;
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

    public function updatedLoanSearch(): void
    {
        $this->resetPage();
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
            ->when(trim($this->loanSearch) !== '', function ($query): void {
                $term = '%'.addcslashes(trim($this->loanSearch), '%_\\\\').'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('loan_number', 'like', $term)
                        ->orWhere('borrower_name', 'like', $term)
                        ->orWhere('borrower_organization', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('requested_at')
            ->paginate(15);

        $items = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();
        $activeLoan = $this->activeLoanId
            ? InventoryLoan::query()->where('school_id', $schoolId)->with(['items.item'])->find($this->activeLoanId)
            : null;

        $selectedLoan = $this->selectedLoanId
            ? InventoryLoan::query()->where('school_id', $schoolId)
                ->with(['items.item', 'items.asset', 'items.owner', 'items.location', 'events.loanItem.item', 'events.recorder', 'documents.issuer'])
                ->find($this->selectedLoanId)
            : null;

        $availableAssets = $this->showRequestForm && $this->itemId
            ? InventoryAsset::query()->where('school_id', $schoolId)->where('item_id', $this->itemId)
                ->with(['owner', 'location'])->where('status', 'available')->where('condition', 'good')->orderBy('asset_code')->limit(500)->get()
            : collect();

        return view('livewire.inventory.loans', compact('loans', 'items', 'activeLoan', 'availableAssets', 'selectedLoan'));
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }
}
