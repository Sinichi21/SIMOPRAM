<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashPeriod;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class GudepCashCloseApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear, User} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $requester = User::factory()->create(['system_role' => 'super_admin']);
        $this->actingAs($requester)->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Audit']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);

        return [$school, $gudep, $year, $requester];
    }

    private function financeComponent(ScoutGroup $gudep, AcademicYear $year): Testable
    {
        return Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id);
    }

    private function requestClose(ScoutGroup $gudep, AcademicYear $year): void
    {
        $this->financeComponent($gudep, $year)
            ->set('openingBalanceInput', '50000')
            ->call('saveOpeningBalance')
            ->assertHasNoErrors();

        $this->financeComponent($gudep, $year)
            ->set('countedCashInput', '50000')
            ->set('reconciliationNotesInput', 'Pemeriksaan kas awal sudah lengkap.')
            ->call('reconcileCash')
            ->assertHasNoErrors();

        $this->financeComponent($gudep, $year)
            ->set('closingNotesInput', 'Rekonsiliasi sudah sesuai, mohon persetujuan.')
            ->call('closeCashPeriod')
            ->assertHasNoErrors();
    }

    public function test_requester_cannot_approve_own_close_and_period_is_pending(): void
    {
        [, $gudep, $year, $requester] = $this->context();
        $this->requestClose($gudep, $year);

        $period = GudepCashPeriod::query()->firstOrFail();
        $this->assertSame('open', $period->status);
        $this->assertSame($requester->id, $period->close_requested_by);
        $this->assertNotNull($period->close_requested_at);

        $this->financeComponent($gudep, $year)->call('approveCashPeriodClosure')->assertForbidden();

        $this->financeComponent($gudep, $year)
            ->set('budgetCategory', 'Latihan')
            ->set('budgetAmount', '10000')
            ->call('saveBudget')
            ->assertStatus(409);

        $this->assertSame('open', $period->fresh()->status);
    }

    public function test_second_reviewer_closes_period_and_cannot_approve_twice(): void
    {
        [$school, $gudep, $year] = $this->context();
        $this->requestClose($gudep, $year);

        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $this->financeComponent($gudep, $year)->call('approveCashPeriodClosure')->assertHasNoErrors();

        $period = GudepCashPeriod::query()->firstOrFail();
        $this->assertSame('closed', $period->status);
        $this->assertSame(50000, $period->closing_balance);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $period->closing_checksum);

        $this->financeComponent($gudep, $year)->call('approveCashPeriodClosure')->assertStatus(409);
    }

    public function test_second_reviewer_can_reject_and_requester_can_retry(): void
    {
        [$school, $gudep, $year, $requester] = $this->context();
        $this->requestClose($gudep, $year);

        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $this->financeComponent($gudep, $year)->call('rejectCashPeriodClosure')->assertHasNoErrors();
        $period = GudepCashPeriod::query()->firstOrFail();
        $this->assertNull($period->close_requested_at);
        $this->assertSame('open', $period->status);

        $this->actingAs($requester)->withSession(['active_school_id' => $school->id]);
        $this->financeComponent($gudep, $year)
            ->set('closingNotesInput', 'Diajukan kembali setelah diperiksa ulang.')
            ->call('closeCashPeriod')
            ->assertHasNoErrors();

        $this->assertNotNull($period->fresh()->close_requested_at);
    }

    public function test_another_gudep_cannot_approve_first_gudep_close(): void
    {
        [$school, $gudep, $year] = $this->context();
        $this->requestClose($gudep, $year);
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Lain']);

        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $this->financeComponent($other, $year)->call('approveCashPeriodClosure')->assertNotFound();
        $this->assertSame('open', GudepCashPeriod::query()->firstOrFail()->status);
    }
}
