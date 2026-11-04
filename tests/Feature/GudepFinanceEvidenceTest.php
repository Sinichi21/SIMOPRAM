<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\GudepCashEvidence;
use App\Models\GudepFinanceBudget;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepFinanceEvidenceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear, GudepCashEntry} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Buku Kas',
        ]);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $entry = GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => 'expense',
            'category' => 'Latihan',
            'occurred_at' => '2026-11-02',
            'description' => 'Beli tali pramuka',
            'amount' => 30000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        return [$school, $gudep, $year, $entry];
    }

    public function test_receipt_upload_download_and_checksum_integrity(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year, $entry] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receiptCashEntryId', $entry->id)
            ->set('receiptAttachment', UploadedFile::fake()->create('nota.pdf', 32, 'application/pdf'))
            ->call('uploadCashEvidence')
            ->assertHasNoErrors();

        $evidence = GudepCashEvidence::query()->firstOrFail();
        $this->assertSame($school->id, $evidence->school_id);
        $this->assertSame($gudep->id, $evidence->scout_group_id);
        $this->assertSame($entry->id, $evidence->gudep_cash_entry_id);
        Storage::disk('local')->assertExists($evidence->path);

        $this->get(route('administration.finance.evidence.download', $evidence->id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        Storage::disk('local')->put($evidence->path, 'altered contents');
        $this->get(route('administration.finance.evidence.download', $evidence->id))
            ->assertStatus(409);
    }

    public function test_receipt_cannot_be_attached_to_another_gudep(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Lain',
        ]);
        $foreign = GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $other->id,
            'academic_year_id' => $year->id,
            'direction' => 'expense',
            'category' => 'Peralatan',
            'occurred_at' => '2026-11-02',
            'description' => 'Pengeluaran Gudep Lain',
            'amount' => 5000,
            'status' => 'draft',
        ]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receiptCashEntryId', $foreign->id)
            ->set('receiptAttachment', UploadedFile::fake()->create('nota.pdf', 32, 'application/pdf'))
            ->call('uploadCashEvidence')
            ->assertHasErrors(['receiptCashEntryId']);

        $this->assertDatabaseCount('gudep_cash_evidences', 0);
    }

    public function test_another_school_cannot_download_receipt(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year, $entry] = $this->context();
        $path = 'gudep-cash-evidences/'.$school->id.'/'.$gudep->id.'/'.$entry->id.'/nota.pdf';
        $bytes = '%PDF-1.4 sample';
        Storage::disk('local')->put($path, $bytes);

        $evidence = GudepCashEvidence::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'gudep_cash_entry_id' => $entry->id,
            'original_filename' => 'nota.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
            'uploaded_by' => auth()->id(),
        ]);

        $foreignSchool = School::factory()->create();
        app(SchoolContext::class)->set($foreignSchool);
        setPermissionsTeamId($foreignSchool->id);
        $this->withSession(['active_school_id' => $foreignSchool->id]);

        $this->get(route('administration.finance.evidence.download', $evidence->id))
            ->assertNotFound();
    }

    public function test_budget_realization_only_counts_posted_expenses_across_entire_year(): void
    {
        [$school, $gudep, $year, $entry] = $this->context();

        GudepFinanceBudget::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'category' => 'Latihan',
            'allocated_amount' => 100000,
        ]);

        GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => 'expense',
            'category' => 'Latihan',
            'occurred_at' => '2026-11-02',
            'description' => 'Draf biaya latihan',
            'amount' => 20000,
            'status' => 'draft',
        ]);

        GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => 'income',
            'category' => 'Latihan',
            'occurred_at' => '2026-11-02',
            'description' => 'Penerimaan latihan',
            'amount' => 15000,
            'status' => 'posted',
        ]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertViewHas('expenseByCategory', ['Latihan' => 30000])
            ->assertViewHas('income', 15000)
            ->assertViewHas('expense', 30000);
    }

    public function test_new_receipt_creates_revision_without_changing_posted_cash_amount(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year, $entry] = $this->context();

        $publish = fn () => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishCashBook')
            ->assertHasNoErrors();

        $publish();
        $first = ReportVerification::query()->where('document_type', 'gudep_cash_book')->firstOrFail();
        $originalPdf = Storage::disk('local')->get($first->file_path);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receiptCashEntryId', $entry->id)
            ->set('receiptAttachment', UploadedFile::fake()->create('bukti.pdf', 32, 'application/pdf'))
            ->call('uploadCashEvidence')
            ->assertHasNoErrors();

        $publish();
        $this->assertSame(2, ReportVerification::query()->where('document_type', 'gudep_cash_book')->count());
        $this->assertSame('superseded', $first->fresh()->publicStatus());
        $this->assertSame($originalPdf, Storage::disk('local')->get($first->file_path));
        $this->assertSame(30000, $entry->fresh()->amount);
    }
}
