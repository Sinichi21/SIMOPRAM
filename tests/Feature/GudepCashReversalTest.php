<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\GudepFinanceBudget;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepCashReversalTest extends TestCase
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

        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Koreksi']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        return [$school, $gudep, $year];
    }

    private function entry(School $school, ScoutGroup $gudep, AcademicYear $year, string $direction = 'expense'): GudepCashEntry
    {
        return GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => $direction,
            'category' => 'Latihan',
            'occurred_at' => '2026-11-01',
            'description' => 'Pembelian bahan latihan',
            'amount' => 25000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);
    }

    public function test_reversing_posted_expense_keeps_original_and_restores_budget(): void
    {
        [$school, $gudep, $year] = $this->context();
        $original = $this->entry($school, $gudep, $year);
        GudepFinanceBudget::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'category' => 'Latihan',
            'allocated_amount' => 100000,
        ]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForReversal', $original->id)
            ->set('reversalReason', 'Nota transaksi tidak sesuai.')
            ->call('reverseCash')
            ->assertHasNoErrors()
            ->assertViewHas('income', 0)
            ->assertViewHas('expense', 0)
            ->assertViewHas('expenseByCategory', ['Latihan' => 0]);

        $this->assertDatabaseCount('gudep_cash_entries', 2);
        $this->assertSame('posted', $original->fresh()->status);
        $this->assertSame(25000, $original->fresh()->amount);

        $reversal = GudepCashEntry::query()->where('reversal_of_id', $original->id)->firstOrFail();
        $this->assertSame('income', $reversal->direction);
        $this->assertSame(25000, $reversal->amount);
        $this->assertSame('posted', $reversal->status);
        $this->assertSame('Nota transaksi tidak sesuai.', $reversal->reversal_reason);
    }

    public function test_reversing_income_removes_income_without_creating_operating_expense(): void
    {
        [$school, $gudep, $year] = $this->context();
        $income = $this->entry($school, $gudep, $year, 'income');

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForReversal', $income->id)
            ->set('reversalReason', 'Penerimaan tercatat dua kali.')
            ->call('reverseCash')
            ->assertHasNoErrors()
            ->assertViewHas('income', 0)
            ->assertViewHas('expense', 0);
    }

    public function test_second_reversal_and_reversal_of_reversal_are_rejected(): void
    {
        [$school, $gudep, $year] = $this->context();
        $original = $this->entry($school, $gudep, $year);

        $component = fn () => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id);

        $component()->call('selectCashForReversal', $original->id)
            ->set('reversalReason', 'Koreksi karena salah pencatatan.')
            ->call('reverseCash')
            ->assertHasNoErrors();

        $component()->call('selectCashForReversal', $original->id)->assertStatus(409);
        $reversal = GudepCashEntry::query()->where('reversal_of_id', $original->id)->firstOrFail();
        $component()->call('selectCashForReversal', $reversal->id)->assertStatus(409);
        $this->assertDatabaseCount('gudep_cash_entries', 2);
    }

    public function test_unauthorized_cross_gudep_reversal_is_rejected(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Lain']);
        $foreign = $this->entry($school, $other, $year);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForReversal', $foreign->id)
            ->assertNotFound();

        $this->assertDatabaseCount('gudep_cash_entries', 1);
    }

    public function test_reversing_draft_is_rejected(): void
    {
        [$school, $gudep, $year] = $this->context();
        $draft = $this->entry($school, $gudep, $year);
        $draft->update(['status' => 'draft', 'posted_at' => null]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForReversal', $draft->id)
            ->assertStatus(409);
    }

    public function test_pdf_is_revised_and_original_archive_remains_unchanged(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $original = $this->entry($school, $gudep, $year);

        $publish = fn () => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishCashBook')
            ->assertHasNoErrors();

        $publish();
        $first = ReportVerification::query()->where('document_type', 'gudep_cash_book')->firstOrFail();
        $pdf = Storage::disk('local')->get($first->file_path);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForReversal', $original->id)
            ->set('reversalReason', 'Transaksi dibatalkan berdasarkan kuitansi.')
            ->call('reverseCash')
            ->assertHasNoErrors();

        $publish();
        $this->assertSame(2, ReportVerification::query()->where('document_type', 'gudep_cash_book')->count());
        $this->assertSame('superseded', $first->fresh()->publicStatus());
        $this->assertSame($pdf, Storage::disk('local')->get($first->file_path));
    }

    public function test_reversal_requires_a_meaningful_reason(): void
    {
        [$school, $gudep, $year] = $this->context();
        $original = $this->entry($school, $gudep, $year);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForReversal', $original->id)
            ->set('reversalReason', 'salah')
            ->call('reverseCash')
            ->assertHasErrors(['reversalReason']);

        $this->assertDatabaseCount('gudep_cash_entries', 1);
    }
}
