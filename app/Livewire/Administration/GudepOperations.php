<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\GudepGuestEntry;
use App\Models\GudepMeeting;
use App\Models\GudepMeetingDecision;
use App\Models\Letter;
use App\Models\ScoutGroup;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GudepOperations extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public string $guestName = '';

    public string $guestOrganization = '';

    public string $guestPurpose = '';

    public string $guestReceivedBy = '';

    public string $guestVisitedAt = '';

    public string $guestNotes = '';

    public string $meetingTitle = '';

    public string $meetingHeldAt = '';

    public string $meetingLocation = '';

    public string $meetingChair = '';

    public string $meetingSecretary = '';

    public string $meetingAttendees = '';

    public string $meetingAgenda = '';

    public ?int $meetingActivityId = null;

    public ?int $selectedMeetingId = null;

    public string $meetingMinutes = '';

    public string $decisionText = '';

    public string $decisionResponsible = '';

    public string $decisionDue = '';

    public function mount(): void
    {
        $this->authorizeView();
        $schoolId = $this->schoolId();

        $this->scoutGroupId = ScoutGroup::query()
            ->where('school_id', $schoolId)->orderBy('id')->value('id');
        $this->academicYearId = AcademicYear::query()
            ->where('school_id', $schoolId)->where('is_active', true)->value('id');
        $this->guestVisitedAt = now()->format('Y-m-d\TH:i');
        $this->meetingHeldAt = now()->format('Y-m-d\TH:i');
    }

    public function updatedScoutGroupId(): void
    {
        $this->selectedMeetingId = null;
        $this->meetingMinutes = '';
    }

    public function updatedAcademicYearId(): void
    {
        $this->selectedMeetingId = null;
        $this->meetingMinutes = '';
    }

    public function saveGuest(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'guestName' => ['required', 'string', 'max:150'],
            'guestOrganization' => ['nullable', 'string', 'max:180'],
            'guestPurpose' => ['required', 'string', 'max:255'],
            'guestReceivedBy' => ['nullable', 'string', 'max:150'],
            'guestVisitedAt' => ['required', 'date'],
            'guestNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $year = $this->selectedYear();
        $visitedAt = Carbon::parse($data['guestVisitedAt'])->toDateString();
        if ($visitedAt < $year->start_date->toDateString() || $visitedAt > $year->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'guestVisitedAt' => 'Tanggal kunjungan harus berada dalam tahun ajaran yang dipilih.',
            ]);
        }

        GudepGuestEntry::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $this->selectedGudep()->id,
            'academic_year_id' => $this->selectedYear()->id,
            'visited_at' => $data['guestVisitedAt'],
            'guest_name' => trim($data['guestName']),
            'organization' => $data['guestOrganization'] ?: null,
            'purpose' => trim($data['guestPurpose']),
            'received_by' => $data['guestReceivedBy'] ?: null,
            'notes' => $data['guestNotes'] ?: null,
            'recorded_by' => auth()->id(),
        ]);

        $this->reset(['guestName', 'guestOrganization', 'guestPurpose', 'guestReceivedBy', 'guestNotes']);
        session()->flash('status', 'Buku tamu berhasil diperbarui.');
    }

    public function saveMeeting(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'meetingTitle' => ['required', 'string', 'max:200'],
            'meetingHeldAt' => ['required', 'date'],
            'meetingLocation' => ['nullable', 'string', 'max:200'],
            'meetingChair' => ['nullable', 'string', 'max:150'],
            'meetingSecretary' => ['nullable', 'string', 'max:150'],
            'meetingAttendees' => ['nullable', 'string', 'max:5000'],
            'meetingAgenda' => ['nullable', 'string', 'max:5000'],
            'meetingActivityId' => ['nullable', 'integer', Rule::exists('activities', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
        ]);

        $year = $this->selectedYear();
        $heldDate = Carbon::parse($data['meetingHeldAt'])->toDateString();
        if ($heldDate < $year->start_date->toDateString() || $heldDate > $year->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'meetingHeldAt' => 'Tanggal rapat harus berada dalam tahun ajaran yang dipilih.',
            ]);
        }

        if (filled($data['meetingActivityId'])) {
            $activity = Activity::query()->where('school_id', $this->schoolId())
                ->findOrFail((int) $data['meetingActivityId']);

            if ((int) $activity->academic_year_id !== $this->selectedYear()->id) {
                throw ValidationException::withMessages([
                    'meetingActivityId' => 'Agenda harus berasal dari tahun ajaran rapat.',
                ]);
            }
        }

        $meeting = GudepMeeting::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $this->selectedGudep()->id,
            'academic_year_id' => $this->selectedYear()->id,
            'activity_id' => $data['meetingActivityId'] ?: null,
            'title' => trim($data['meetingTitle']),
            'held_at' => $data['meetingHeldAt'],
            'location' => $data['meetingLocation'] ?: null,
            'chairperson' => $data['meetingChair'] ?: null,
            'minute_taker' => $data['meetingSecretary'] ?: null,
            'attendees' => $data['meetingAttendees'] ?: null,
            'agenda' => $data['meetingAgenda'] ?: null,
            'status' => 'draft',
            'recorded_by' => auth()->id(),
        ]);

        $this->reset(['meetingTitle', 'meetingLocation', 'meetingChair', 'meetingSecretary',
            'meetingAttendees', 'meetingAgenda', 'meetingActivityId']);
        $this->selectedMeetingId = $meeting->id;
        $this->meetingMinutes = '';
        session()->flash('status', 'Rapat dibuat sebagai draf. Tambahkan notulen dan keputusan sebelum finalisasi.');
    }

    public function selectMeeting(int $id): void
    {
        $this->authorizeView();
        $meeting = $this->meeting($id);
        $this->selectedMeetingId = $meeting->id;
        $this->meetingMinutes = $meeting->minutes ?? '';
    }

    public function saveMinutes(): void
    {
        $this->authorizeManage();
        $meeting = $this->meeting($this->requiredMeetingId());
        abort_unless($meeting->status === 'draft', 409, 'Rapat sudah difinalisasi.');

        $data = $this->validate([
            'meetingMinutes' => ['required', 'string', 'max:20000'],
        ]);
        $meeting->update(['minutes' => $data['meetingMinutes']]);
        session()->flash('status', 'Notulen draf berhasil disimpan.');
    }

    public function addDecision(): void
    {
        $this->authorizeManage();
        $meeting = $this->meeting($this->requiredMeetingId());
        abort_unless($meeting->status === 'draft', 409, 'Keputusan rapat sudah dibekukan.');

        $data = $this->validate([
            'decisionText' => ['required', 'string', 'max:4000'],
            'decisionResponsible' => ['nullable', 'string', 'max:150'],
            'decisionDue' => ['nullable', 'date'],
        ]);

        GudepMeetingDecision::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $meeting->scout_group_id,
            'gudep_meeting_id' => $meeting->id,
            'decision' => trim($data['decisionText']),
            'person_responsible' => $data['decisionResponsible'] ?: null,
            'due_at' => $data['decisionDue'] ?: null,
            'status' => 'open',
        ]);

        $this->reset(['decisionText', 'decisionResponsible', 'decisionDue']);
        session()->flash('status', 'Keputusan dan tindak lanjut rapat ditambahkan.');
    }

    public function finalizeMeeting(): void
    {
        $this->authorizeManage();
        $meeting = $this->meeting($this->requiredMeetingId());
        abort_unless($meeting->status === 'draft', 409);
        if (blank($meeting->minutes)) {
            throw ValidationException::withMessages([
                'meetingMinutes' => 'Notulen wajib disimpan sebelum rapat difinalisasi.',
            ]);
        }

        $meeting->update(['status' => 'finalized', 'finalized_at' => now()]);
        session()->flash('status', 'Notulen difinalisasi dan siap diterbitkan sebagai PDF resmi.');
    }

    public function updateDecisionStatus(int $id, string $status): void
    {
        $this->authorizeManage();
        abort_unless(in_array($status, ['open', 'done'], true), 422);

        $decision = GudepMeetingDecision::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->whereHas('meeting', fn ($q) => $q->where('academic_year_id', $this->selectedYear()->id))
            ->find($id);
        abort_unless($decision !== null, 404);

        $decision->update(['status' => $status]);
        session()->flash('status', 'Status tindak lanjut diperbarui.');
    }

    public function publishGuestBook(
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier,
    ): void {
        $this->authorizeExport();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();
        $entries = $this->guests()->orderBy('visited_at')->orderBy('id')->get();
        $snapshot = [
            'gudep' => $gudep->name, 'year' => $year->name,
            'entries' => $entries->map(fn ($entry) => $entry->only([
                'id', 'visited_at', 'guest_name', 'organization', 'purpose', 'received_by', 'notes',
            ]))->all(),
        ];

        $document = $publisher->publishBlade(
            schoolId: $this->schoolId(),
            sourceType: ScoutGroup::class,
            sourceId: $gudep->id,
            documentType: 'gudep_guest_book',
            seriesKey: 'year-'.$year->id,
            snapshotChecksum: $verifier->contentChecksum($snapshot),
            filename: 'buku-tamu-gudep-'.$gudep->id.'-'.$year->id.'.pdf',
            contentView: 'reports.pdf.administration.gudep-guest-book',
            contentData: compact('gudep', 'year', 'entries'),
            title: 'Buku Tamu '.$gudep->name.' '.$year->name,
            metadata: ['academic_year' => $year->name, 'security_classification' => 'Terbatas'],
        );

        $this->redirectRoute('reports.published-documents.show', ['code' => $document->code], navigate: false);
    }

    public function publishMeeting(
        int $id,
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier,
    ): void {
        $this->authorizeExport();
        $meeting = $this->meeting($id);
        abort_unless($meeting->status === 'finalized', 409, 'Finalisasi rapat sebelum diterbitkan.');

        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();
        $decisions = $meeting->decisions()->orderBy('id')->get();

        $snapshot = [
            'gudep' => $gudep->name,
            'year' => $year->name,
            'meeting' => $meeting->only([
                'id', 'title', 'held_at', 'location', 'chairperson', 'minute_taker',
                'attendees', 'agenda', 'minutes', 'status', 'finalized_at', 'activity_id',
            ]),
            'decisions' => $decisions->map(fn ($decision) => $decision->only([
                'id', 'decision', 'person_responsible', 'due_at', 'status',
            ]))->all(),
        ];

        $document = $publisher->publishBlade(
            schoolId: $this->schoolId(),
            sourceType: GudepMeeting::class,
            sourceId: $meeting->id,
            documentType: 'gudep_meeting_minutes',
            seriesKey: 'year-'.$year->id,
            snapshotChecksum: $verifier->contentChecksum($snapshot),
            filename: 'notulen-gudep-'.$meeting->id.'.pdf',
            contentView: 'reports.pdf.administration.gudep-meeting-minutes',
            contentData: compact('gudep', 'year', 'meeting', 'decisions'),
            title: 'Notulen '.$meeting->title,
            metadata: ['academic_year' => $year->name, 'security_classification' => 'Terbatas'],
        );

        $this->redirectRoute('reports.published-documents.show', ['code' => $document->code], navigate: false);
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id !== null, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }

    private function selectedGudep(): ScoutGroup
    {
        abort_unless($this->scoutGroupId !== null, 422, 'Pilih Gudep.');

        return ScoutGroup::query()->where('school_id', $this->schoolId())
            ->findOrFail($this->scoutGroupId);
    }

    private function selectedYear(): AcademicYear
    {
        abort_unless($this->academicYearId !== null, 422, 'Pilih tahun ajaran.');

        return AcademicYear::query()->where('school_id', $this->schoolId())
            ->findOrFail($this->academicYearId);
    }

    private function requiredMeetingId(): int
    {
        abort_unless($this->selectedMeetingId !== null, 422, 'Pilih rapat terlebih dahulu.');

        return $this->selectedMeetingId;
    }

    private function meeting(int $id): GudepMeeting
    {
        $meeting = GudepMeeting::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->find($id);
        abort_unless($meeting !== null, 404);

        return $meeting;
    }

    /** @return Builder<GudepGuestEntry> */
    private function guests(): Builder
    {
        return GudepGuestEntry::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id);
    }

    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);
        $this->schoolId();
    }

    private function authorizeManage(): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('gudep.manage')
            || auth()->user()?->can('gudep.operations.manage'), 403);
    }

    private function authorizeExport(): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('reports.export'), 403);
    }

    public function render(): View
    {
        $this->authorizeView();
        $schoolId = $this->schoolId();
        $gudep = $this->scoutGroupId !== null ? $this->selectedGudep() : null;
        $year = $this->academicYearId !== null ? $this->selectedYear() : null;

        return view('livewire.administration.gudep-operations', [
            'gudep' => $gudep,
            'year' => $year,
            'gudepOptions' => ScoutGroup::query()->where('school_id', $schoolId)->orderBy('name')->get(),
            'years' => AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('start_date')->get(),
            'guests' => $gudep && $year ? $this->guests()->orderByDesc('visited_at')->limit(100)->get() : collect(),
            'meetings' => $gudep && $year ? GudepMeeting::query()
                ->where('school_id', $schoolId)->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)
                ->withCount('decisions')->orderByDesc('held_at')->limit(100)->get() : collect(),
            'currentMeeting' => $gudep && $year && $this->selectedMeetingId !== null
                ? $this->meeting($this->selectedMeetingId)->load('decisions') : null,
            'activities' => $year ? Activity::query()->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)->orderBy('title')->get() : collect(),
            // Letters are school-owned in the existing module. Never mislabel
            // them as Gudep-specific without an explicit source relationship.
            'letters' => $gudep && $year && auth()->user()?->can('letters.view')
                ? Letter::query()->where('school_id', $schoolId)
                    ->whereBetween('letter_date', [$year->start_date, $year->end_date])
                    ->orderByDesc('letter_date')->limit(15)->get() : collect(),
        ]);
    }
}
