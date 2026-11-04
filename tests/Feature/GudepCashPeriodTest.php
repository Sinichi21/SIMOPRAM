<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\GudepCashPeriod;
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

class GudepCashPeriodTest extends TestCase
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
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        return [$school, $gudep, $year];
    }

    private function cash(int $schoolId, int $gudepId, int $yearId, string $status = 'posted'): GudepCashEntry
    {
        return GudepCashEntry::query()->create([
            'school_id' => $schoolId,
            'scout_group_id' => $gudepId,
            'academic_year_id' => $yearId,
            'direction' => 'income',
            'category' => 'Iuran',
            'occurred_at' => '2026-11-01',
            'description' => 'Iuran Gudep',
            'amount' => 25000,
            'status' => $status,
            'posted_at' => $status === 'posted' ? now() : null,
        ]);
    }

    private function component(int $gudepId, int $yearId): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudepId)
            ->set('academicYearId', $yearId);
    }

    public function test_opening_balance_is_set_once_and_included_in_running_balance(): void
    {
        [$school, $gudep, $year] = $this->context();

        $this->component($gudep->id, $year->id)
            ->set('openingBalanceInput', '100000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors()
            ->assertViewHas('openingBalance', 100000);

        $period = GudepCashPeriod::query()->firstOrFail();
        $this->assertSame('open', $period->status);
        $this->assertSame(100000, $period->opening_balance);

        $this->component($gudep->id, $year->id)
            ->set('openingBalanceInput', '150000')
            ->call('saveOpeningBalance')
            ->assertStatus(409);

        $this->assertSame(100000, $period->fresh()->opening_balance);
    }

    public function test_opening_balance_cannot_be_set_after_posting(): void
    {
        [$school, $gudep, $year] = $this->context();
        $this->cash($school->id, $gudep->id, $year->id);

        $this->component($gudep->id, $year->id)
            ->set('openingBalanceInput', '50000')
            ->call('saveOpeningBalance')
            ->assertStatus(409);
    }

    public function test_closing_requires_opening_balance_and_no_drafts(): void
    {
        [$school, $gudep, $year] = $this->context();

        $this->component($gudep->id, $year->id)
            ->set('closingNotesInput', 'Kas tahunan telah diperiksa.')
            ->call('closeCashPeriod')
            ->assertStatus(409);

        $this->component($gudep->id, $year->id)
            ->set('openingBalanceInput', '0')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();

        $draft = $this->cash($school->id, $gudep->id, $year->id, 'draft');
        $this->component($gudep->id, $year->id)
            ->set('closingNotesInput', 'Kas tahunan telah diperiksa.')
            ->call('closeCashPeriod')
            ->assertStatus(409);

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('open', GudepCashPeriod::query()->firstOrFail()->status);
    }

    public function test_closed_period_keeps_checksum_and_blocks_financial_mutation(): void
    {
        [$school, $gudep, $year] = $this->context();

        $this->component($gudep->id, $year->id)
            ->set('openingBalanceInput', '75000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();

        $this->cash($school->id, $gudep->id, $year->id);

        $this->component($gudep->id, $year->id)
            ->set('closingNotesInput', 'Buku kas telah diperiksa dan ditutup.')
            ->call('closeCashPeriod')
            ->assertHasNoErrors();

        $period = GudepCashPeriod::query()->firstOrFail();
        $this->assertSame('closed', $period->status);
        $this->assertSame(100000, $period->closing_balance);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $period->closing_checksum);

        $this->component($gudep->id, $year->id)
            ->set('cashDirection', 'expense')
            ->set('cashCategory', 'ATK')
            ->set('cashDate', '2026-11-02')
            ->set('cashDescription', 'Pembelian alat tulis')
            ->set('cashAmount', '12000')
            ->call('saveCash')
            ->assertStatus(409);

        $this->component($gudep->id, $year->id)
            ->set('budgetCategory', 'Latihan')
            ->set('budgetAmount', '50000')
            ->call('saveBudget')
            ->assertStatus(409);

        $entry = GudepCashEntry::query()->firstOrFail();
        $this->component($gudep->id, $year->id)
            ->call('selectCashForReversal', $entry->id)
            ->set('reversalReason', 'Koreksi karena data tercatat salah.')
            ->call('reverseCash')
            ->assertStatus(409);

        $this->component($gudep->id, $year->id)
            ->set('closingNotesInput', 'Permintaan penutupan ulang buku kas.')
            ->call('closeCashPeriod')
            ->assertStatus(409);

        $this->assertSame(1, GudepCashEntry::query()->count());
        $this->assertSame(100000, $period->fresh()->closing_balance);
    }

    public function test_periods_are_independent_between_gudep(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Kedua']);

        $this->component($gudep->id, $year->id)
            ->set('openingBalanceInput', '12000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();

        $this->component($other->id, $year->id)
            ->assertViewHas('openingBalance', 0);

        $this->assertSame(1, GudepCashPeriod::query()->count());
    }

    public function test_published_cash_book_revises_with_opening_balance_and_closing_state(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();

        $publish = fn () => $this->component($gudep->id, $year->id)
            ->call('publishCashBook')
            ->assertHasNoErrors();

        $this->component($gudep->id, $year->id)
            ->set('openingBalanceInput', '20000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();

        $publish();
        $first = ReportVerification::query()->where('document_type', 'gudep_cash_book')->firstOrFail();
        $originalPdf = Storage::disk('local')->get($first->file_path);

        $this->component($gudep->id, $year->id)
            ->set('closingNotesInput', 'Periode tahunan ditutup secara resmi.')
            ->call('closeCashPeriod')
            ->assertHasNoErrors();

        $publish();
        $this->assertSame(2, ReportVerification::query()->where('document_type', 'gudep_cash_book')->count());
        $this->assertSame($originalPdf, Storage::disk('local')->get($first->file_path));
        $this->assertSame('superseded', $first->fresh()->publicStatus());
    }
}
