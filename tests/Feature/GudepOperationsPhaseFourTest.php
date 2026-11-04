<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepOperations;
use App\Models\AcademicYear;
use App\Models\GudepGuestEntry;
use App\Models\GudepMeeting;
use App\Models\GudepMeetingDecision;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepOperationsPhaseFourTest extends TestCase
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
            'school_id' => $school->id, 'name' => 'Gudep Administrasi',
        ]);

        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        return [$school, $gudep, $year];
    }

    private function draftMeeting(School $school, ScoutGroup $gudep, AcademicYear $year): GudepMeeting
    {
        return GudepMeeting::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'title' => 'Rapat Dewan Gudep',
            'held_at' => '2026-11-02 09:00:00',
            'status' => 'draft',
            'recorded_by' => auth()->id(),
        ]);
    }

    public function test_operational_dashboard_requires_authentication(): void
    {
        $this->get(route('administration.operations'))->assertRedirect();
    }

    public function test_guest_book_is_scoped_to_selected_gudep_and_year(): void
    {
        [$school, $gudep, $year] = $this->context();

        Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('guestName', 'Tamu Kwartir')
            ->set('guestVisitedAt', '2026-11-02T08:30')
            ->set('guestPurpose', 'Kunjungan pembinaan')
            ->call('saveGuest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_guest_entries', [
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'guest_name' => 'Tamu Kwartir',
        ]);
    }

    public function test_guest_date_outside_selected_year_is_rejected(): void
    {
        [, $gudep, $year] = $this->context();

        Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('guestName', 'Tamu Lama')
            ->set('guestPurpose', 'Arsip')
            ->set('guestVisitedAt', '2025-11-02T08:00')
            ->call('saveGuest')
            ->assertHasErrors(['guestVisitedAt']);

        $this->assertDatabaseCount('gudep_guest_entries', 0);
    }

    public function test_meeting_can_be_finalized_only_after_minutes_are_saved(): void
    {
        [$school, $gudep, $year] = $this->context();
        $meeting = $this->draftMeeting($school, $gudep, $year);

        $draft = Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectMeeting', $meeting->id);

        $draft->call('finalizeMeeting')->assertHasErrors(['meetingMinutes']);

        $draft->set('meetingMinutes', 'Rapat membahas kegiatan semester.')
            ->call('saveMinutes')
            ->assertHasNoErrors()
            ->call('finalizeMeeting')
            ->assertHasNoErrors();

        $this->assertSame('finalized', $meeting->fresh()->status);
        $this->assertNotNull($meeting->fresh()->finalized_at);
    }

    public function test_other_gudep_cannot_open_or_change_meeting_decisions(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id, 'name' => 'Gudep Kedua',
        ]);
        $meeting = $this->draftMeeting($school, $other, $year);
        $decision = GudepMeetingDecision::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $other->id,
            'gudep_meeting_id' => $meeting->id,
            'decision' => 'Mempersiapkan kegiatan',
            'status' => 'open',
        ]);

        Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectMeeting', $meeting->id)
            ->assertNotFound();

        Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('updateDecisionStatus', $decision->id, 'done')
            ->assertNotFound();

        $this->assertSame('open', $decision->fresh()->status);
    }

    public function test_minutes_cannot_be_modified_after_finalization(): void
    {
        [$school, $gudep, $year] = $this->context();
        $meeting = $this->draftMeeting($school, $gudep, $year);
        $meeting->update([
            'minutes' => 'Disahkan dalam rapat.',
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectMeeting', $meeting->id)
            ->set('meetingMinutes', 'Perubahan tidak sah.')
            ->call('saveMinutes')
            ->assertStatus(409);

        $this->assertSame('Disahkan dalam rapat.', $meeting->fresh()->minutes);
    }

    public function test_guest_book_publication_reuses_snapshot_then_creates_revision(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();

        $publish = fn () => Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishGuestBook')
            ->assertHasNoErrors();

        $publish();
        $publish();

        $first = ReportVerification::query()->where('document_type', 'gudep_guest_book')->firstOrFail();
        $this->assertSame(1, ReportVerification::query()->where('document_type', 'gudep_guest_book')->count());
        $originalPdf = Storage::disk('local')->get($first->file_path);

        GudepGuestEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'visited_at' => '2026-11-02 08:00:00',
            'guest_name' => 'Tamu Baru',
            'purpose' => 'Kunjungan',
        ]);

        $publish();

        $this->assertSame(2, ReportVerification::query()->where('document_type', 'gudep_guest_book')->count());
        $this->assertSame('superseded', $first->fresh()->publicStatus());
        $this->assertSame($originalPdf, Storage::disk('local')->get($first->file_path));
    }

    public function test_finalized_meeting_publishes_verified_minutes(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $meeting = $this->draftMeeting($school, $gudep, $year);
        $meeting->update([
            'minutes' => 'Rapat menyepakati program.',
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishMeeting', $meeting->id)
            ->assertHasNoErrors();

        $document = ReportVerification::query()
            ->where('document_type', 'gudep_meeting_minutes')->firstOrFail();

        $this->assertSame($meeting->id, $document->source_id);
        $this->assertSame(GudepMeeting::class, $document->source_type);
        $this->assertTrue($document->hasArchivedPdf());
        $this->assertSame('Terbatas', data_get($document->metadata, 'security_classification'));
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_draft_meeting_cannot_be_published(): void
    {
        [$school, $gudep, $year] = $this->context();
        $meeting = $this->draftMeeting($school, $gudep, $year);

        Livewire::test(GudepOperations::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishMeeting', $meeting->id)
            ->assertStatus(409);

        $this->assertDatabaseCount('report_verifications', 0);
    }
}
