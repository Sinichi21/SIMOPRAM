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

it('denies cancellation by a different requester even within the same school', function () {
    $loan = pendingLoanForCancellation();
    $schoolId = $loan->school_id;
    $owner = User::factory()->create(['system_role' => null]);
    $other = User::factory()->create(['system_role' => null]);

    \App\Models\SchoolUserMembership::query()->create([
        'school_id' => $schoolId, 'user_id' => $owner->id, 'is_active' => true,
    ]);
    \App\Models\SchoolUserMembership::query()->create([
        'school_id' => $schoolId, 'user_id' => $other->id, 'is_active' => true,
    ]);
    setPermissionsTeamId($schoolId);
    \Spatie\Permission\Models\Permission::findOrCreate('inventory.loans.request', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('inventory.view', 'web');
    $owner->givePermissionTo('inventory.loans.request');
    $other->givePermissionTo('inventory.loans.request');
    $other->givePermissionTo('inventory.view');
    $loan->forceFill(['requested_by' => $owner->id])->save();

    $this->actingAs($other);
    Livewire::test(Loans::class)->call('openCancellation', $loan->id)->assertForbidden();

    try {
        app(InventoryLoanService::class)->cancelPending($loan->id, 'Permohonan ini bukan milik saya');
        test()->fail('Another requester must not cancel this loan');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    expect($loan->fresh()->status)->toBe('requested');
    expect($loan->events()->count())->toBe(0);
});
