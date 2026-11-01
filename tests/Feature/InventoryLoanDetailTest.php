<?php

use App\Livewire\Inventory\Loans;
use App\Models\InventoryLoan;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('shows a loan detail only within the active school', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);

    $own = InventoryLoan::query()->create([
        'loan_number' => 'INV-DETAIL-001',
        'borrower_type' => 'manual_external',
        'borrower_name' => 'Peminjam Sekolah',
        'requested_at' => now(),
        'status' => 'requested',
    ]);

    $component = Livewire::test(Loans::class)
        ->call('showLoanDetails', $own->id)
        ->assertSet('selectedLoanId', $own->id)
        ->assertSee('INV-DETAIL-001')
        ->call('closeLoanDetails')
        ->assertSet('selectedLoanId', null);

    $foreignSchool = School::factory()->create();
    $foreign = InventoryLoan::query()->forceCreate([
        'school_id' => $foreignSchool->id,
        'loan_number' => 'INV-DETAIL-FOREIGN',
        'borrower_type' => 'manual_external',
        'borrower_name' => 'Peminjam Lain',
        'requested_at' => now(),
        'status' => 'requested',
    ]);

    expect($foreign->school_id)->toBe($foreignSchool->id);
    expect($foreign->school_id)->not->toBe($school->id);

    expect(fn () => $component->call('showLoanDetails', $foreign->id))
        ->toThrow(ModelNotFoundException::class);
});

it('filters loans by number and borrower without exposing another school', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);

    InventoryLoan::query()->create([
        'loan_number' => 'SEARCH-ALPHA-001',
        'borrower_type' => 'manual_external',
        'borrower_name' => 'Peminjam Alpha',
        'requested_at' => now(),
        'status' => 'requested',
    ]);
    InventoryLoan::query()->create([
        'loan_number' => 'SEARCH-BETA-002',
        'borrower_type' => 'manual_external',
        'borrower_name' => 'Peminjam Beta',
        'requested_at' => now(),
        'status' => 'requested',
    ]);

    $foreign = School::factory()->create();
    InventoryLoan::query()->forceCreate([
        'school_id' => $foreign->id,
        'loan_number' => 'SEARCH-ALPHA-FOREIGN',
        'borrower_type' => 'manual_external',
        'borrower_name' => 'Peminjam Alpha Asing',
        'requested_at' => now(),
        'status' => 'requested',
    ]);

    Livewire::test(Loans::class)
        ->set('loanSearch', 'SEARCH-ALPHA')
        ->assertSee('SEARCH-ALPHA-001')
        ->assertDontSee('SEARCH-BETA-002')
        ->assertDontSee('SEARCH-ALPHA-FOREIGN')
        ->set('loanSearch', 'Peminjam Beta')
        ->assertSee('SEARCH-BETA-002')
        ->assertDontSee('SEARCH-ALPHA-001');
});
