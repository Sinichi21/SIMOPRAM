<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\GudepFinanceBudget;
use App\Models\InventoryAsset;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryOwner;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepFinancePhaseFiveTest extends TestCase
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

        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Keuangan',
        ]);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        return [$school, $gudep, $year];
    }

    private function cashEntry(School $school, ScoutGroup $gudep, AcademicYear $year): GudepCashEntry
    {
        return GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => 'income',
            'category' => 'Iuran',
            'occurred_at' => '2026-11-02',
            'description' => 'Iuran anggota',
            'amount' => 50000,
            'status' => 'draft',
        ]);
    }

    public function test_legacy_case_variations_are_counted_in_budget_realization(): void
    {
        [$school, $gudep, $year] = $this->context();

        GudepFinanceBudget::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'category' => 'PERSARI',
            'allocated_amount' => 3000000,
        ]);

        foreach (['Persari' => 149985, 'PERSARI' => 1500000] as $category => $amount) {
            GudepCashEntry::query()->create([
                'school_id' => $school->id,
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
                'direction' => 'expense',
                'category' => $category,
                'occurred_at' => '2026-11-05',
                'description' => 'Pengeluaran '.$category,
                'amount' => $amount,
                'status' => 'posted',
            ]);
        }

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertViewHas('expenseByCategory', ['PERSARI' => 1649985])
            ->assertSee('1.649.985');

        $this->assertDatabaseCount('gudep_cash_entries', 2);
    }

    public function test_category_dropdown_canonicalizes_name_from_budget(): void
    {
        [$school, $gudep, $year] = $this->context();
        GudepFinanceBudget::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'category' => 'PERSARI',
            'allocated_amount' => 3000000,
        ]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('cashCategory', 'PERSARI')
            ->set('cashDescription', 'Pembelian konsumsi')
            ->set('cashAmount', '100000')
            ->set('cashDate', '2026-11-05')
            ->call('saveCash')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_cash_entries', [
            'category' => 'PERSARI',
            'amount' => 100000,
            'status' => 'draft',
        ]);
    }

    public function test_draft_can_be_edited_and_deleted_before_posting(): void
    {
        [$school, $gudep, $year] = $this->context();
        $draft = $this->cashEntry($school, $gudep, $year);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('editCashDraft', $draft->id)
            ->assertSet('editingCashId', $draft->id)
            ->set('cashDescription', 'Iuran dikoreksi')
            ->set('cashAmount', '55000')
            ->call('saveCash')
            ->assertHasNoErrors()
            ->assertSet('editingCashId', null);

        $this->assertDatabaseHas('gudep_cash_entries', [
            'id' => $draft->id,
            'amount' => 55000,
            'description' => 'Iuran dikoreksi',
        ]);
        $this->assertDatabaseCount('gudep_cash_entries', 1);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('deleteCashDraft', $draft->id)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('gudep_cash_entries', 0);
    }

    public function test_posted_transactions_cannot_be_edited_or_deleted_as_drafts(): void
    {
        [$school, $gudep, $year] = $this->context();
        $posted = $this->cashEntry($school, $gudep, $year);
        $posted->update(['status' => 'posted', 'posted_at' => now()]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('editCashDraft', $posted->id)
            ->assertStatus(409);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('deleteCashDraft', $posted->id)
            ->assertStatus(409);

        $this->assertSame(50000, $posted->fresh()->amount);
    }

    public function test_school_owned_inventory_assets_are_visible_to_finance_without_changing_ownership(): void
    {
        [$school, $gudep, $year] = $this->context();
        $category = InventoryCategory::query()->create([
            'school_id' => $school->id,
            'name' => 'Peralatan',
            'is_active' => true,
        ]);
        $item = InventoryItem::query()->create([
            'school_id' => $school->id,
            'category_id' => $category->id,
            'item_code' => 'TONGKAT-01',
            'name' => 'Tongkat',
            'tracking_type' => 'individual',
            'unit' => 'buah',
            'is_active' => true,
        ]);
        $owner = InventoryOwner::query()->create([
            'school_id' => $school->id,
            'owner_type' => 'school',
            'display_name' => 'Inventaris Sekolah',
            'is_active' => true,
        ]);
        InventoryAsset::query()->create([
            'school_id' => $school->id,
            'item_id' => $item->id,
            'owner_id' => $owner->id,
            'asset_code' => 'TONGKAT-0001',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $component = Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertSee('Tongkat')
            ->assertSee('Inventaris Sekolah');

        $component->set('inventoryScope', 'gudep')->assertDontSee('TONGKAT-0001');

        $this->assertSame($owner->id, InventoryAsset::query()->firstOrFail()->owner_id);
    }

    public function test_finance_page_requires_authentication(): void
    {
        $this->get(route('administration.finance'))->assertRedirect();
    }

    public function test_budget_is_unique_per_gudep_year_and_category(): void
    {
        [$school, $gudep, $year] = $this->context();

        $save = fn (int $amount) => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('budgetCategory', 'Latihan')
            ->set('budgetAmount', (string) $amount)
            ->call('saveBudget')
            ->assertHasNoErrors();

        $save(150000);
        $save(200000);

        $this->assertSame(1, GudepFinanceBudget::query()->count());
        $this->assertDatabaseHas('gudep_finance_budgets', [
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'category' => 'Latihan',
            'allocated_amount' => 200000,
        ]);
    }

    public function test_cash_cannot_be_recorded_outside_selected_academic_year(): void
    {
        [, $gudep, $year] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('cashDirection', 'expense')
            ->set('cashCategory', 'Kegiatan')
            ->set('cashDate', '2025-05-01')
            ->set('cashDescription', 'Pengeluaran tahun lalu')
            ->set('cashAmount', '12000')
            ->call('saveCash')
            ->assertHasErrors(['cashDate']);

        $this->assertDatabaseCount('gudep_cash_entries', 0);
    }

    public function test_cash_is_created_as_draft_and_posted_exactly_once(): void
    {
        [$school, $gudep, $year] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('cashDirection', 'expense')
            ->set('cashCategory', 'ATK')
            ->set('cashDate', '2026-11-01')
            ->set('cashDescription', 'Pembelian kertas')
            ->set('cashAmount', '35000')
            ->call('saveCash')
            ->assertHasNoErrors();

        $entry = GudepCashEntry::query()->firstOrFail();
        $this->assertSame('draft', $entry->status);
        $this->assertSame(35000, $entry->amount);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('postCash', $entry->id)
            ->assertHasNoErrors();

        $this->assertSame('posted', $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->posted_at);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('postCash', $entry->id)
            ->assertStatus(409);
    }

    public function test_another_gudep_cannot_post_cash(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Lain',
        ]);
        $entry = $this->cashEntry($school, $other, $year);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('postCash', $entry->id)
            ->assertNotFound();

        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_cash_book_ignores_drafts_and_revises_when_posted_data_changes(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();

        $entry = $this->cashEntry($school, $gudep, $year);

        $publish = fn () => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishCashBook')
            ->assertHasNoErrors();

        $publish();
        $publish();

        $first = ReportVerification::query()->where('document_type', 'gudep_cash_book')->firstOrFail();
        $this->assertSame(1, ReportVerification::query()->where('document_type', 'gudep_cash_book')->count());
        $originalPdf = Storage::disk('local')->get($first->file_path);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('postCash', $entry->id)
            ->assertHasNoErrors();

        $publish();
        $this->assertSame(2, ReportVerification::query()->where('document_type', 'gudep_cash_book')->count());
        $this->assertSame('superseded', $first->fresh()->publicStatus());
        $this->assertSame($originalPdf, Storage::disk('local')->get($first->file_path));
    }

    public function test_separate_gudep_years_do_not_share_finance_series(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Kedua',
        ]);

        foreach ([$gudep, $other] as $group) {
            Livewire::test(GudepFinance::class)
                ->set('scoutGroupId', $group->id)
                ->set('academicYearId', $year->id)
                ->call('publishCashBook')
                ->assertHasNoErrors();
        }

        $this->assertSame(2, ReportVerification::query()->where('document_type', 'gudep_cash_book')->count());
        $this->assertSame([1, 1], ReportVerification::query()->where('document_type', 'gudep_cash_book')
            ->orderBy('id')->pluck('revision')->all());
    }

    public function test_balance_includes_all_posted_entries_beyond_preview_limit(): void
    {
        [$school, $gudep, $year] = $this->context();

        for ($i = 0; $i < 101; $i++) {
            GudepCashEntry::query()->create([
                'school_id' => $school->id,
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
                'direction' => 'income',
                'category' => 'Iuran',
                'occurred_at' => '2026-11-02',
                'description' => 'Iuran '.$i,
                'amount' => 1000,
                'status' => 'posted',
            ]);
        }

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertViewHas('income', 101000)
            ->assertViewHas('expense', 0);
    }

    public function test_ledger_filters_do_not_modify_the_financial_balance(): void
    {
        [$school, $gudep, $year] = $this->context();

        foreach ([
            ['income', 'Iuran', 'Iuran anggota', 'KAS-01', 100000, 'posted'],
            ['expense', 'ATK', 'Pembelian kertas', 'KAS-02', 30000, 'posted'],
            ['income', 'Iuran', 'Iuran belum disetujui', 'KAS-03', 50000, 'draft'],
        ] as [$direction, $category, $description, $reference, $amount, $status]) {
            GudepCashEntry::query()->create([
                'school_id' => $school->id,
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
                'direction' => $direction,
                'category' => $category,
                'occurred_at' => '2026-11-02',
                'description' => $description,
                'reference_number' => $reference,
                'amount' => $amount,
                'status' => $status,
            ]);
        }

        $component = Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertViewHas('ledgerMatches', 3)
            ->assertViewHas('income', 100000)
            ->assertViewHas('expense', 30000);

        $component->set('ledgerDirection', 'expense')
            ->assertViewHas('ledgerMatches', 1)
            ->assertViewHas('income', 100000)
            ->assertViewHas('expense', 30000);

        $component->set('ledgerDirection', 'all')
            ->set('ledgerStatus', 'draft')
            ->set('ledgerSearch', 'KAS-03')
            ->assertViewHas('ledgerMatches', 1)
            ->assertViewHas('income', 100000)
            ->assertViewHas('expense', 30000);

        $component->call('clearLedgerFilters')
            ->assertSet('ledgerSearch', '')
            ->assertSet('ledgerStatus', 'all')
            ->assertSet('ledgerDirection', 'all')
            ->assertViewHas('ledgerMatches', 3);
    }

    public function test_ledger_filters_remain_scoped_to_selected_gudep(): void
    {
        [$school, $gudep, $year] = $this->context();
        $foreign = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Lain',
        ]);

        foreach ([$gudep, $foreign] as $group) {
            GudepCashEntry::query()->create([
                'school_id' => $school->id,
                'scout_group_id' => $group->id,
                'academic_year_id' => $year->id,
                'direction' => 'income',
                'category' => 'Iuran',
                'occurred_at' => '2026-11-02',
                'description' => 'Iuran anggota',
                'amount' => 10000,
                'status' => 'posted',
            ]);
        }

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('ledgerSearch', 'Iuran')
            ->assertViewHas('ledgerMatches', 1)
            ->assertViewHas('income', 10000);
    }
}
