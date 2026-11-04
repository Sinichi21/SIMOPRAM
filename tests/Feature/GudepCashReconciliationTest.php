<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\GudepCashReconciliation;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GudepCashReconciliationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Kas']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);

        return [$school, $gudep, $year];
    }

    private function component(ScoutGroup $gudep, AcademicYear $year): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id);
    }

    public function test_cash_difference_prevents_closing_until_a_matching_review(): void
    {
        [$school, $gudep, $year] = $this->context();

        $this->component($gudep, $year)
            ->set('openingBalanceInput', '100000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();

        $this->component($gudep, $year)
            ->set('closingNotesInput', 'Menutup periode setelah pemeriksaan.')
            ->call('closeCashPeriod')
            ->assertStatus(409);

        $this->component($gudep, $year)
            ->set('countedCashInput', '90000')
            ->set('reconciliationNotesInput', 'Hitungan kas pertama masih kurang.')
            ->call('reconcileCash')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_cash_reconciliations', [
            'book_balance' => 100000, 'counted_cash' => 90000, 'difference' => -10000,
        ]);

        $this->component($gudep, $year)
            ->set('closingNotesInput', 'Menutup periode setelah pemeriksaan.')
            ->call('closeCashPeriod')
            ->assertStatus(409);

        $this->component($gudep, $year)
            ->set('countedCashInput', '100000')
            ->set('reconciliationNotesInput', 'Pemeriksaan ulang sudah sesuai kas.')
            ->call('reconcileCash')
            ->assertHasNoErrors();

        $this->component($gudep, $year)
            ->set('closingNotesInput', 'Saldo kas fisik dan buku telah cocok.')
            ->call('closeCashPeriod')
            ->assertHasNoErrors();

        $this->assertSame(2, GudepCashReconciliation::query()->count());
    }

    public function test_posted_transaction_invalidates_previous_reconciliation(): void
    {
        [$school, $gudep, $year] = $this->context();

        $this->component($gudep, $year)
            ->set('openingBalanceInput', '10000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();

        $this->component($gudep, $year)
            ->set('countedCashInput', '10000')
            ->set('reconciliationNotesInput', 'Kas fisik cocok pada pemeriksaan.')
            ->call('reconcileCash')
            ->assertHasNoErrors();

        GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => 'income',
            'category' => 'Iuran',
            'occurred_at' => '2026-11-01',
            'description' => 'Iuran tambahan',
            'amount' => 5000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $this->component($gudep, $year)
            ->set('closingNotesInput', 'Kas sudah diperiksa sebelum penambahan.')
            ->call('closeCashPeriod')
            ->assertStatus(409);

        $this->assertSame(1, GudepCashReconciliation::query()->count());
    }

    public function test_other_gudep_cannot_reuse_reconciliation(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Kedua']);

        $this->component($gudep, $year)
            ->set('openingBalanceInput', '5000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();
        $this->component($gudep, $year)
            ->set('countedCashInput', '5000')
            ->set('reconciliationNotesInput', 'Kas fisik Gudep pertama telah cocok.')
            ->call('reconcileCash')
            ->assertHasNoErrors();

        $this->component($other, $year)
            ->set('openingBalanceInput', '5000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();
        $this->component($other, $year)
            ->set('closingNotesInput', 'Tutup buku Gudep kedua tanpa pemeriksaan.')
            ->call('closeCashPeriod')
            ->assertStatus(409);
    }

    public function test_reconciliation_cannot_be_recorded_after_closure(): void
    {
        [, $gudep, $year] = $this->context();

        $this->component($gudep, $year)
            ->set('openingBalanceInput', '12000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();
        $this->component($gudep, $year)
            ->set('countedCashInput', '12000')
            ->set('reconciliationNotesInput', 'Hasil pemeriksaan sudah sesuai.')
            ->call('reconcileCash')
            ->assertHasNoErrors();
        $this->component($gudep, $year)
            ->set('closingNotesInput', 'Tutup buku sesudah pemeriksaan kas.')
            ->call('closeCashPeriod')
            ->assertHasNoErrors();

        $this->component($gudep, $year)
            ->set('countedCashInput', '12000')
            ->set('reconciliationNotesInput', 'Mencoba pemeriksaan setelah tutup.')
            ->call('reconcileCash')
            ->assertStatus(409);
    }
}
