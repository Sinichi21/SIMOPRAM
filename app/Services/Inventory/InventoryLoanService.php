<?php

namespace App\Services\Inventory;

use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryLoanEvent;
use App\Models\InventoryLoanItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\InventoryStockBalance;
use App\Models\ScoutGroup;
use App\Support\SchoolContext;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryLoanService
{
    public function __construct(
        private readonly InventoryStockService $stockService,
        private readonly SchoolContext $schoolContext,
    ) {}

    /**
     * Register a loan request. Physical stock is changed only at actual handover.
     *
     * @param  array<string, mixed>  $borrower
     * @param  array<int, array<string, mixed>>  $items
     */
    public function requestLoan(array $borrower, array $items, ?string $purpose = null, ?string $notes = null): InventoryLoan
    {
        $schoolId = $this->schoolId();

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Tambahkan minimal satu barang ke permohonan.']);
        }

        $type = $borrower['type'] ?? '';
        if (! in_array($type, ['internal', 'registered_gudep', 'manual_external'], true)) {
            throw ValidationException::withMessages(['borrower.type' => 'Jenis peminjam tidak valid.']);
        }

        if ($type === 'internal') {
            $userId = (int) ($borrower['user_id'] ?? 0);
            $memberName = DB::table('users')
                ->join('school_user_memberships', 'school_user_memberships.user_id', '=', 'users.id')
                ->where('school_user_memberships.school_id', $schoolId)
                ->where('school_user_memberships.user_id', $userId)
                ->where('school_user_memberships.is_active', true)
                ->whereNull('school_user_memberships.left_at')
                ->value('users.name');

            if (! $userId || ! $memberName) {
                throw ValidationException::withMessages(['borrower.user_id' => 'Peminjam internal harus merupakan anggota aktif sekolah ini.']);
            }
            $borrower['name'] = $memberName;
        } elseif ($type === 'registered_gudep') {
            if (empty($borrower['scout_group_id'])) {
                throw ValidationException::withMessages(['borrower.scout_group_id' => 'Pilih Gudep peminjam yang terdaftar.']);
            }

            $registeredGroup = ScoutGroup::query()->where('school_id', $schoolId)->findOrFail((int) $borrower['scout_group_id']);
            $borrower['name'] = $borrower['name'] ?? $registeredGroup->name;
        } elseif (trim((string) ($borrower['name'] ?? '')) === '') {
            throw ValidationException::withMessages(['borrower.name' => 'Nama peminjam eksternal wajib diisi.']);
        }

        return DB::transaction(function () use ($schoolId, $borrower, $items, $purpose, $notes, $type): InventoryLoan {
            $loan = InventoryLoan::query()->create([
                'school_id' => $schoolId,
                'loan_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'borrower_type' => $type,
                'borrower_user_id' => $type === 'internal' ? ($borrower['user_id'] ?? null) : null,
                'borrower_scout_group_id' => $type === 'registered_gudep' ? ($borrower['scout_group_id'] ?? null) : null,
                'borrower_name' => trim((string) ($borrower['name'] ?? '')),
                'borrower_organization' => trim((string) ($borrower['organization'] ?? '')) ?: null,
                'borrower_contact' => trim((string) ($borrower['contact'] ?? '')) ?: null,
                'requested_by' => auth()->id(),
                'requested_at' => now(),
                'status' => 'requested',
                'purpose' => $purpose,
                'notes' => $notes,
            ]);

            $selectedAssetIds = [];
            $requestedStockQuantities = [];

            foreach ($items as $index => $line) {
                $item = InventoryItem::query()->where('school_id', $schoolId)->findOrFail((int) ($line['item_id'] ?? 0));
                $owner = InventoryOwner::query()->where('school_id', $schoolId)->findOrFail((int) ($line['owner_id'] ?? 0));
                $location = InventoryLocation::query()->where('school_id', $schoolId)->findOrFail((int) ($line['location_id'] ?? 0));
                $quantity = (float) ($line['quantity'] ?? 0);
                $assetId = isset($line['asset_id']) ? (int) $line['asset_id'] : null;

                if (! $item->is_active || ! $owner->is_active || ! $location->is_active || ! is_finite($quantity) || $quantity <= 0 || round($quantity, 3) !== $quantity) {
                    throw ValidationException::withMessages(["items.$index" => 'Barang, pemilik, lokasi, atau jumlah tidak valid.']);
                }

                if ($owner->owner_type === 'gudep' && ! $owner->scout_group_id) {
                    throw ValidationException::withMessages(["items.$index.owner_id" => 'Pemilik Gudep tidak memiliki Gudep yang valid.']);
                }

                if ($owner->scout_group_id && $location->scout_group_id && $owner->scout_group_id !== $location->scout_group_id) {
                    throw ValidationException::withMessages(["items.$index.location_id" => 'Lokasi tidak sesuai dengan Gudep pemilik.']);
                }

                if ($item->tracking_type === 'individual') {
                    if (! $assetId || $quantity !== 1.0) {
                        throw ValidationException::withMessages(["items.$index.asset_id" => 'Aset individual harus dipilih satu per satu.']);
                    }

                    if (in_array($assetId, $selectedAssetIds, true)) {
                        throw ValidationException::withMessages(["items.$index.asset_id" => 'Aset yang sama tidak boleh dimasukkan lebih dari sekali.']);
                    }
                    $selectedAssetIds[] = $assetId;

                    $asset = InventoryAsset::query()
                        ->where('school_id', $schoolId)
                        ->where('item_id', $item->id)
                        ->where('owner_id', $owner->id)
                        ->where('location_id', $location->id)
                        ->where('status', 'available')
                        ->findOrFail($assetId);
                } else {
                    if ($assetId !== null) {
                        throw ValidationException::withMessages(["items.$index.asset_id" => 'Barang stok kuantitas tidak boleh menunjuk aset individual.']);
                    }

                    $balance = InventoryStockBalance::query()
                        ->where('school_id', $schoolId)
                        ->where('item_id', $item->id)
                        ->where('owner_id', $owner->id)
                        ->where('location_id', $location->id)
                        ->first();

                    $stockKey = $item->id.':'.$owner->id.':'.$location->id;
                    $requestedStockQuantities[$stockKey] = ($requestedStockQuantities[$stockKey] ?? 0) + $quantity;

                    if (! $balance || (float) $balance->quantity_on_hand - (float) $balance->quantity_reserved < $requestedStockQuantities[$stockKey]) {
                        throw ValidationException::withMessages(["items.$index.quantity" => 'Stok yang tersedia tidak mencukupi untuk permohonan ini.']);
                    }
                }

                InventoryLoanItem::query()->create([
                    'school_id' => $schoolId,
                    'loan_id' => $loan->id,
                    'item_id' => $item->id,
                    'asset_id' => $assetId,
                    'owner_id' => $owner->id,
                    'location_id' => $location->id,
                    'quantity_requested' => $quantity,
                    'quantity_handed_over' => 0,
                    'quantity_returned' => 0,
                    'quantity_missing' => 0,
                ]);
            }

            return $loan->load('items');
        }, 3);
    }

    public function approve(int $loanId): InventoryLoan
    {
        $schoolId = $this->schoolId();

        return DB::transaction(function () use ($schoolId, $loanId): InventoryLoan {
            $loan = InventoryLoan::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($loanId);

            if ($loan->status !== 'requested') {
                throw ValidationException::withMessages(['status' => 'Hanya permohonan berstatus menunggu yang dapat disetujui.']);
            }

            // Serialize approvals against the same school stock balance. Approved loans
            // consume approval capacity until their quantities are handed over.
            foreach ($loan->items()->orderBy('id')->get() as $line) {
                $item = InventoryItem::query()->where('school_id', $schoolId)->findOrFail($line->item_id);

                if ($item->tracking_type === 'individual') {
                    $alreadyApproved = InventoryLoanItem::query()
                        ->join('inventory_loans', 'inventory_loans.id', '=', 'inventory_loan_items.loan_id')
                        ->where('inventory_loan_items.school_id', $schoolId)
                        ->where('inventory_loan_items.asset_id', $line->asset_id)
                        ->where('inventory_loans.id', '!=', $loan->id)
                        ->whereIn('inventory_loans.status', ['approved', 'partially_handed_over', 'handed_over', 'partially_returned', 'discrepancy'])
                        ->whereColumn('inventory_loan_items.quantity_requested', '>', 'inventory_loan_items.quantity_handed_over')
                        ->exists();

                    if ($alreadyApproved) {
                        throw ValidationException::withMessages(['items' => 'Aset sudah dialokasikan untuk peminjaman lain.']);
                    }

                    continue;
                }

                $balance = InventoryStockBalance::query()
                    ->where('school_id', $schoolId)
                    ->where('item_id', $line->item_id)
                    ->where('owner_id', $line->owner_id)
                    ->where('location_id', $line->location_id)
                    ->lockForUpdate()
                    ->first();

                $otherPending = (float) InventoryLoanItem::query()
                    ->join('inventory_loans', 'inventory_loans.id', '=', 'inventory_loan_items.loan_id')
                    ->where('inventory_loan_items.school_id', $schoolId)
                    ->where('inventory_loan_items.item_id', $line->item_id)
                    ->where('inventory_loan_items.owner_id', $line->owner_id)
                    ->where('inventory_loan_items.location_id', $line->location_id)
                    ->where('inventory_loans.id', '!=', $loan->id)
                    ->whereIn('inventory_loans.status', ['approved', 'partially_handed_over'])
                    ->selectRaw('COALESCE(SUM(inventory_loan_items.quantity_requested - inventory_loan_items.quantity_handed_over), 0) AS pending')
                    ->value('pending');

                $thisPending = (float) $loan->items()
                    ->where('item_id', $line->item_id)
                    ->where('owner_id', $line->owner_id)
                    ->where('location_id', $line->location_id)
                    ->sum('quantity_requested');

                if (! $balance || (float) $balance->quantity_on_hand - (float) $balance->quantity_reserved < $otherPending + $thisPending) {
                    throw ValidationException::withMessages(['items' => 'Stok tidak cukup untuk menyetujui seluruh permohonan.']);
                }
            }

            $loan->forceFill([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])->save();

            return $loan->refresh();
        }, 3);
    }

    /**
     * Record physical handover. Each entry: loan_item_id, quantity, condition.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function recordHandover(int $loanId, array $lines, DateTimeInterface $occurredAt, string $recordingMethod = 'online', ?string $notes = null): InventoryLoan
    {
        $schoolId = $this->schoolId();
        $this->validateRecordingMethod($recordingMethod);

        return DB::transaction(function () use ($schoolId, $loanId, $lines, $occurredAt, $recordingMethod, $notes): InventoryLoan {
            $loan = InventoryLoan::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($loanId);

            if (! in_array($loan->status, ['approved', 'partially_handed_over'], true)) {
                throw ValidationException::withMessages(['status' => 'Permohonan harus disetujui sebelum serah-terima fisik.']);
            }

            foreach ($lines as $index => $line) {
                $loanItem = InventoryLoanItem::query()
                    ->where('school_id', $schoolId)
                    ->where('loan_id', $loan->id)
                    ->lockForUpdate()
                    ->findOrFail((int) ($line['loan_item_id'] ?? 0));
                $quantity = (float) ($line['quantity'] ?? 0);
                $remaining = (float) $loanItem->quantity_requested - (float) $loanItem->quantity_handed_over;

                if (! is_finite($quantity) || $quantity <= 0 || round($quantity, 3) !== $quantity || $quantity > $remaining) {
                    throw ValidationException::withMessages(["lines.$index.quantity" => 'Jumlah serah-terima harus lebih besar dari nol dan tidak melebihi sisa permohonan.']);
                }

                $item = InventoryItem::query()->where('school_id', $schoolId)->findOrFail($loanItem->item_id);
                if ($item->tracking_type === 'individual' && $quantity !== 1.0) {
                    throw ValidationException::withMessages(["lines.$index.quantity" => 'Aset individual hanya dapat diserahterimakan satu unit per transaksi.']);
                }

                if ($item->tracking_type === 'quantity') {
                    $this->stockService->recordMovement(
                        $item->id, $loanItem->owner_id, $loanItem->location_id,
                        'issue', $quantity, $loan->loan_number, 'Serah-terima peminjaman '.$loan->loan_number,
                        $occurredAt,
                    );
                } else {
                    $asset = InventoryAsset::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($loanItem->asset_id);
                    if ($asset->status !== 'available') {
                        throw ValidationException::withMessages(["lines.$index.asset_id" => 'Aset sudah tidak tersedia.']);
                    }
                    $asset->forceFill(['status' => 'on_loan', 'location_id' => null])->save();
                }

                $loanItem->quantity_handed_over = (float) $loanItem->quantity_handed_over + $quantity;
                $loanItem->condition_out = $line['condition'] ?? 'good';
                $loanItem->save();

                InventoryLoanEvent::query()->create([
                    'school_id' => $schoolId,
                    'loan_id' => $loan->id,
                    'loan_item_id' => $loanItem->id,
                    'event_type' => 'handover',
                    'quantity' => $quantity,
                    'condition' => $line['condition'] ?? 'good',
                    'notes' => $notes,
                    'occurred_at' => $occurredAt,
                    'recorded_at' => now(),
                    'recorded_by' => auth()->id(),
                    'recording_method' => $recordingMethod,
                ]);
            }

            $loan->refresh()->load('items');
            $allHandedOver = $loan->items->every(fn (InventoryLoanItem $item): bool => (float) $item->quantity_handed_over >= (float) $item->quantity_requested);
            $loan->forceFill([
                'status' => $allHandedOver ? 'handed_over' : 'partially_handed_over',
                'actual_handover_at' => $loan->actual_handover_at ?? $occurredAt,
            ])->save();

            return $loan->refresh();
        }, 3);
    }

    /**
     * Record a partial or complete return. Missing quantities are explicitly recorded as discrepancies.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function recordReturn(int $loanId, array $lines, DateTimeInterface $occurredAt, string $recordingMethod = 'online', ?string $notes = null): InventoryLoan
    {
        $schoolId = $this->schoolId();
        $this->validateRecordingMethod($recordingMethod);

        return DB::transaction(function () use ($schoolId, $loanId, $lines, $occurredAt, $recordingMethod, $notes): InventoryLoan {
            $loan = InventoryLoan::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($loanId);

            if (! in_array($loan->status, ['handed_over', 'partially_handed_over', 'partially_returned', 'discrepancy'], true)) {
                throw ValidationException::withMessages(['status' => 'Belum ada barang yang diserahterimakan untuk dikembalikan.']);
            }

            foreach ($lines as $index => $line) {
                $loanItem = InventoryLoanItem::query()
                    ->where('school_id', $schoolId)
                    ->where('loan_id', $loan->id)
                    ->lockForUpdate()
                    ->findOrFail((int) ($line['loan_item_id'] ?? 0));
                $returned = (float) ($line['quantity_returned'] ?? 0);
                $missing = (float) ($line['quantity_missing'] ?? 0);
                $outstanding = (float) $loanItem->quantity_handed_over - (float) $loanItem->quantity_returned - (float) $loanItem->quantity_missing;

                if ($returned < 0 || $missing < 0 || ! is_finite($returned) || ! is_finite($missing) || round($returned, 3) !== $returned || round($missing, 3) !== $missing || ($returned + $missing) <= 0 || ($returned + $missing) > $outstanding) {
                    throw ValidationException::withMessages(["lines.$index" => 'Jumlah kembali dan hilang tidak valid atau melebihi jumlah yang masih dipinjam.']);
                }

                $item = InventoryItem::query()->where('school_id', $schoolId)->findOrFail($loanItem->item_id);
                if ($item->tracking_type === 'individual' && (($returned + $missing) !== 1.0 || ! in_array($returned, [0.0, 1.0], true) || ! in_array($missing, [0.0, 1.0], true))) {
                    throw ValidationException::withMessages(["lines.$index.quantity_returned" => 'Aset individual harus dicatat sebagai satu unit utuh: kembali atau hilang.']);
                }

                if ($returned > 0) {
                    if ($item->tracking_type === 'quantity') {
                        $this->stockService->recordMovement(
                            $item->id, $loanItem->owner_id, $loanItem->location_id,
                            'receipt', $returned, $loan->loan_number, 'Pengembalian peminjaman '.$loan->loan_number,
                            $occurredAt,
                        );
                    } else {
                        $asset = InventoryAsset::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($loanItem->asset_id);
                        $asset->forceFill([
                            'status' => ($line['condition'] ?? 'good') === 'good' ? 'available' : 'damaged',
                            'location_id' => $loanItem->location_id,
                            'condition' => $line['condition'] ?? 'good',
                        ])->save();
                    }

                    $loanItem->quantity_returned = (float) $loanItem->quantity_returned + $returned;
                    $loanItem->condition_in = $line['condition'] ?? 'good';
                    $loanItem->condition_notes = $line['condition_notes'] ?? null;

                    InventoryLoanEvent::query()->create([
                        'school_id' => $schoolId,
                        'loan_id' => $loan->id,
                        'loan_item_id' => $loanItem->id,
                        'event_type' => 'return',
                        'quantity' => $returned,
                        'condition' => $line['condition'] ?? 'good',
                        'notes' => $line['condition_notes'] ?? $notes,
                        'occurred_at' => $occurredAt,
                        'recorded_at' => now(),
                        'recorded_by' => auth()->id(),
                        'recording_method' => $recordingMethod,
                    ]);
                }

                if ($missing > 0) {
                    if ($item->tracking_type === 'individual') {
                        InventoryAsset::query()->where('school_id', $schoolId)->whereKey($loanItem->asset_id)->lockForUpdate()->firstOrFail()
                            ->forceFill(['status' => 'missing', 'location_id' => null, 'condition' => 'missing'])
                            ->save();
                    }
                    $loanItem->quantity_missing = (float) $loanItem->quantity_missing + $missing;
                    InventoryLoanEvent::query()->create([
                        'school_id' => $schoolId,
                        'loan_id' => $loan->id,
                        'loan_item_id' => $loanItem->id,
                        'event_type' => 'loss',
                        'quantity' => $missing,
                        'condition' => 'missing',
                        'notes' => $line['condition_notes'] ?? $notes,
                        'occurred_at' => $occurredAt,
                        'recorded_at' => now(),
                        'recorded_by' => auth()->id(),
                        'recording_method' => $recordingMethod,
                    ]);
                }

                $loanItem->save();
            }

            $loan->refresh()->load('items');
            $hasOutstanding = $loan->items->contains(fn (InventoryLoanItem $item): bool => (float) $item->quantity_handed_over - (float) $item->quantity_returned - (float) $item->quantity_missing > 0
            );
            $hasDiscrepancy = $loan->items->contains(fn (InventoryLoanItem $item): bool => (float) $item->quantity_missing > 0 || ($item->condition_in !== null && $item->condition_in !== 'good')
            );

            $nextStatus = $hasDiscrepancy ? 'discrepancy' : ($hasOutstanding ? 'partially_returned' : 'returned');
            $loan->forceFill([
                'status' => $nextStatus,
                'actual_return_at' => $nextStatus === 'returned' ? $occurredAt : null,
            ])->save();

            return $loan->refresh();
        }, 3);
    }

    private function schoolId(): int
    {
        $schoolId = $this->schoolContext->id();
        if (! $schoolId) {
            throw ValidationException::withMessages(['school' => 'Pilih sekolah aktif sebelum mengelola peminjaman inventaris.']);
        }

        return (int) $schoolId;
    }

    private function validateRecordingMethod(string $method): void
    {
        if (! in_array($method, ['online', 'offline'], true)) {
            throw ValidationException::withMessages(['recording_method' => 'Metode pencatatan harus online atau offline.']);
        }
    }
}
