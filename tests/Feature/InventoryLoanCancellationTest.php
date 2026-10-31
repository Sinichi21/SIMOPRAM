<?php

use App\Livewire\Inventory\Loans;
use App\Models\InventoryLoan;
use App\Models\School;
use App\Models\User;
use App\Services\Inventory\InventoryLoanService;
use App\Support\SchoolContext;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function pendingLoanForCancellation(): InventoryLoan
{
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    test()->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);

    return InventoryLoan::query()->create([
        'loan_number' => 'INV-CANCEL-'.uniqid(),
        'borrower_type' => 'manual_external',
        'borrower_name' => 'Peminjam',
        'requested_at' => now(),
        'status' => 'requested',
    ]);
}

it('cancels a pending loan and preserves its audit trail', function () {
    $loan = pendingLoanForCancellation();

    Livewire::test(Loans::class)
        ->call('openCancellation', $loan->id)
        ->set('cancellationReason', 'Kegiatan dibatalkan oleh peminjam')
        ->call('cancelLoan')
        ->assertHasNoErrors();

    expect($loan->fresh()->status)->toBe('cancelled');
    $this->assertDatabaseHas('inventory_loan_events', [
        'loan_id' => $loan->id,
        'event_type' => 'cancellation',
        'notes' => 'Kegiatan dibatalkan oleh peminjam',
    ]);
});

it('rejects cancelling an approved loan without changing its status', function () {
    $loan = pendingLoanForCancellation();
    $loan->update(['status' => 'approved']);

    try {
        app(InventoryLoanService::class)->cancelPending($loan->id, 'Permohonan tidak digunakan');
        test()->fail('Approved loans must not be cancelled');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('status');
    }

    expect($loan->fresh()->status)->toBe('approved');
    expect($loan->events()->count())->toBe(0);
});

it('requires a meaningful cancellation reason', function () {
    $loan = pendingLoanForCancellation();

    Livewire::test(Loans::class)
        ->call('openCancellation', $loan->id)
        ->set('cancellationReason', 'singkat')
        ->call('cancelLoan')
        ->assertHasErrors('cancellationReason');

    expect($loan->fresh()->status)->toBe('requested');
});
