<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashCloseDecision;
use App\Models\GudepCashPeriod;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

        $this->financeComponent($gudep, $year)->set('closeRejectionReason', 'Kuitansi belum lengkap, lakukan verifikasi ulang.')
            ->call('rejectCashPeriodClosure')->assertHasNoErrors();
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

    public function test_rejection_requires_a_meaningful_reason_and_keeps_request_pending(): void
    {
        [$school, $gudep, $year] = $this->context();
        $this->requestClose($gudep, $year);

        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $this->financeComponent($gudep, $year)
            ->call('rejectCashPeriodClosure')
            ->assertHasErrors(['closeRejectionReason' => 'required']);

        $this->financeComponent($gudep, $year)
            ->set('closeRejectionReason', 'salah')
            ->call('rejectCashPeriodClosure')
            ->assertHasErrors(['closeRejectionReason' => 'min']);

        $this->assertNotNull(GudepCashPeriod::query()->firstOrFail()->close_requested_at);
        $this->assertSame(['requested'], GudepCashCloseDecision::query()->orderBy('id')->pluck('action')->all());

        $reason = 'Lampiran kuitansi perlu diperiksa ulang sebelum tutup buku.';
        $this->financeComponent($gudep, $year)
            ->set('closeRejectionReason', $reason)
            ->call('rejectCashPeriodClosure')
            ->assertHasNoErrors();

        $decision = GudepCashCloseDecision::query()->where('action', 'rejected')->firstOrFail();
        $this->assertSame($reason, $decision->notes);
        $this->assertSame('open', GudepCashPeriod::query()->firstOrFail()->status);
    }

    public function test_every_decision_is_preserved_after_rejection_and_reapplication(): void
    {
        [$school, $gudep, $year, $requester] = $this->context();
        $this->requestClose($gudep, $year);
        $this->assertSame(['requested'], GudepCashCloseDecision::query()->orderBy('id')->pluck('action')->all());

        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);
        $this->financeComponent($gudep, $year)->set('closeRejectionReason', 'Kuitansi belum lengkap, lakukan verifikasi ulang.')
            ->call('rejectCashPeriodClosure')->assertHasNoErrors();

        $this->actingAs($requester)->withSession(['active_school_id' => $school->id]);
        $this->financeComponent($gudep, $year)
            ->set('closingNotesInput', 'Pengajuan ulang setelah dilakukan pemeriksaan.')
            ->call('closeCashPeriod')->assertHasNoErrors();

        $this->assertSame(
            ['requested', 'rejected', 'requested'],
            GudepCashCloseDecision::query()->orderBy('id')->pluck('action')->all()
        );
        $this->assertDatabaseCount('gudep_cash_close_decisions', 3);
    }

    public function test_lpj_can_only_be_published_after_a_different_reviewer_approves(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();

        $this->financeComponent($gudep, $year)->call('publishFinanceLpj')->assertStatus(409);

        $this->requestClose($gudep, $year);
        $this->financeComponent($gudep, $year)->call('publishFinanceLpj')->assertStatus(409);

        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $this->financeComponent($gudep, $year)->call('approveCashPeriodClosure')->assertHasNoErrors();
        $this->financeComponent($gudep, $year)->call('publishFinanceLpj')->assertHasNoErrors();
        $this->financeComponent($gudep, $year)->call('publishFinanceLpj')->assertHasNoErrors();

        $this->assertSame(1, ReportVerification::query()
            ->where('document_type', 'gudep_finance_lpj')->count());
        $this->assertSame(
            ['requested', 'approved'],
            GudepCashCloseDecision::query()->orderBy('id')->pluck('action')->all()
        );

        $document = ReportVerification::query()->where('document_type', 'gudep_finance_lpj')->firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
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
