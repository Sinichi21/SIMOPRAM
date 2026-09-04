<?php

namespace App\Livewire\Journals;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Journal;
use App\Models\JournalAttachment;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Manage extends Component
{
    use WithFileUploads;

    public int $activityId;

    public ?int $journalId = null;

    public ?int $attendance_session_id = null;

    public string $objective = '';

    public string $material = '';

    public string $activity_description = '';

    public string $result = '';

    public string $evaluation = '';

    public string $follow_up = '';

    public string $notes = '';

    public array $attachments = [];

    public function mount(
        int $activityId
    ): void {
        $activity = Activity::query()
            ->findOrFail($activityId);

        $this->activityId =
            $activity->id;

        $journal = Journal::query()
            ->where(
                'activity_id',
                $activity->id
            )
            ->first();

        if ($journal) {
            $this->loadJournal(
                $journal
            );

            return;
        }

        $firstSession =
            AttendanceSession::query()
                ->where(
                    'activity_id',
                    $activity->id
                )
                ->orderBy('open_at')
                ->first();

        $this->attendance_session_id =
            $firstSession?->id;
    }

    protected function rules(): array
    {
        $schoolId =
            app(SchoolContext::class)
                ->id();

        return [
            'attendance_session_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'attendance_sessions',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'school_id',
                            $schoolId
                        )
                        ->where(
                            'activity_id',
                            $this->activityId
                        )
                ),
            ],

            'objective' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'material' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'activity_description' => [
                'required',
                'string',
                'max:20000',
            ],

            'result' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'evaluation' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'follow_up' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'attachments' => [
                'array',
                'max:10',
            ],

            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx',
                'max:5120',
            ],
        ];
    }

    public function save(): void
    {
        $permission =
            $this->journalId
                ? 'journals.update'
                : 'journals.create';

        abort_unless(
            auth()->user()->can(
                $permission
            ),
            403
        );

        if ($this->journalId) {
            $existingJournal =
                Journal::query()
                    ->findOrFail(
                        $this->journalId
                    );

            abort_if(
                $existingJournal->status ===
                    'published'
                &&
                ! auth()->user()->can(
                    'journals.publish'
                ),
                403,
                'Jurnal yang sudah dipublikasikan tidak dapat diubah.'
            );
        }

        $validated =
            $this->validate();

        $activity =
            Activity::query()
                ->findOrFail(
                    $this->activityId
                );

        $journal =
            DB::transaction(
                function () use (
                    $activity,
                    $validated
                ): Journal {

                    $data = [
                        'activity_id' => $activity->id,

                        'attendance_session_id' => $validated[
                                'attendance_session_id'
                            ] ?: null,

                        'objective' => $this->nullableText(
                            $validated['objective']
                        ),

                        'material' => $this->nullableText(
                            $validated['material']
                        ),

                        'activity_description' => trim(
                            $validated[
                                'activity_description'
                            ]
                        ),

                        'result' => $this->nullableText(
                            $validated['result']
                        ),

                        'evaluation' => $this->nullableText(
                            $validated['evaluation']
                        ),

                        'follow_up' => $this->nullableText(
                            $validated['follow_up']
                        ),

                        'notes' => $this->nullableText(
                            $validated['notes']
                        ),

                        'updated_by' => auth()->id(),
                    ];

                    if ($this->journalId) {

                        $journal =
                            Journal::query()
                                ->findOrFail(
                                    $this->journalId
                                );

                        $journal->update(
                            $data
                        );

                        return $journal;
                    }

                    $data['created_by'] =
                        auth()->id();

                    $data['status'] =
                        'draft';

                    return Journal::query()
                        ->create($data);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Lampiran
        |--------------------------------------------------------------------------
        */

        $this->storeAttachments(
            $journal
        );

        $this->journalId =
            $journal->id;

        $this->attachments = [];

        session()->flash(
            'success',
            'Jurnal kegiatan berhasil disimpan.'
        );
    }

    protected function storeAttachments(
        Journal $journal
    ): void {
        if (empty($this->attachments)) {
            return;
        }

        $schoolId =
            app(SchoolContext::class)
                ->id();

        foreach (
            $this->attachments as $attachment
        ) {
            $path =
                $attachment->store(
                    "journals/{$schoolId}/{$journal->id}",
                    'public'
                );

            JournalAttachment::query()
                ->create([
                    'journal_id' => $journal->id,

                    'uploaded_by' => auth()->id(),

                    'original_name' => $attachment
                        ->getClientOriginalName(),

                    'path' => $path,

                    'mime_type' => $attachment
                        ->getMimeType(),

                    'size_bytes' => $attachment
                        ->getSize(),
                ]);
        }
    }

    public function deleteAttachment(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'journals.attachments'
            ),
            403
        );

        $attachment =
            JournalAttachment::query()
                ->where(
                    'journal_id',
                    $this->journalId
                )
                ->findOrFail($id);

        Storage::disk('public')
            ->delete(
                $attachment->path
            );

        $attachment->delete();

        session()->flash(
            'success',
            'Lampiran berhasil dihapus.'
        );
    }

    public function publish(): void
    {
        abort_unless(
            auth()->user()->can(
                'journals.publish'
            ),
            403
        );

        $journal =
            Journal::query()
                ->findOrFail(
                    $this->journalId
                );

        $journal->update([
            'status' => 'published',

            'published_at' => now(),

            'updated_by' => auth()->id(),
        ]);

        session()->flash(
            'success',
            'Jurnal berhasil dipublikasikan.'
        );
    }

    public function returnToDraft(): void
    {
        abort_unless(
            auth()->user()->can(
                'journals.publish'
            ),
            403
        );

        $journal =
            Journal::query()
                ->findOrFail(
                    $this->journalId
                );

        $journal->update([
            'status' => 'draft',

            'published_at' => null,

            'updated_by' => auth()->id(),
        ]);

        session()->flash(
            'success',
            'Jurnal dikembalikan menjadi draft.'
        );
    }

    protected function loadJournal(
        Journal $journal
    ): void {
        $this->journalId =
            $journal->id;

        $this->attendance_session_id =
            $journal
                ->attendance_session_id;

        $this->objective =
            $journal->objective ?? '';

        $this->material =
            $journal->material ?? '';

        $this->activity_description =
            $journal
                ->activity_description;

        $this->result =
            $journal->result ?? '';

        $this->evaluation =
            $journal->evaluation ?? '';

        $this->follow_up =
            $journal->follow_up ?? '';

        $this->notes =
            $journal->notes ?? '';
    }

    protected function nullableText(
        ?string $value
    ): ?string {
        $value = trim(
            $value ?? ''
        );

        return $value === ''
            ? null
            : $value;
    }

    protected function attendanceStats(): array
    {
        if (
            ! $this->attendance_session_id
        ) {
            return [
                'participants' => 0,
                'present' => 0,
                'late' => 0,
                'sick' => 0,
                'excused' => 0,
                'absent' => 0,
                'unrecorded' => 0,
            ];
        }

        $session =
            AttendanceSession::query()
                ->withCount('participants')
                ->where(
                    'activity_id',
                    $this->activityId
                )
                ->find(
                    $this->attendance_session_id
                );

        if (! $session) {
            return [
                'participants' => 0,
                'present' => 0,
                'late' => 0,
                'sick' => 0,
                'excused' => 0,
                'absent' => 0,
                'unrecorded' => 0,
            ];
        }

        $counts =
            Attendance::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->selectRaw(
                    'status, COUNT(*) as total'
                )
                ->groupBy('status')
                ->pluck(
                    'total',
                    'status'
                );

        $recorded =
            $counts->sum();

        return [
            'participants' => $session
                ->participants_count,

            'present' => (int)
                ($counts['present'] ?? 0),

            'late' => (int)
                ($counts['late'] ?? 0),

            'sick' => (int)
                ($counts['sick'] ?? 0),

            'excused' => (int)
                ($counts['excused'] ?? 0),

            'absent' => (int)
                ($counts['absent'] ?? 0),

            'unrecorded' => max(
                0,
                $session
                    ->participants_count
                -
                $recorded
            ),
        ];
    }

    public function render()
    {
        $activity =
            Activity::query()
                ->with([
                    'academicYear',
                    'semester',
                    'coaches',
                ])
                ->findOrFail(
                    $this->activityId
                );

        $attendanceSessions =
            AttendanceSession::query()
                ->where(
                    'activity_id',
                    $activity->id
                )
                ->orderBy('open_at')
                ->get();

        $journal =
            $this->journalId
                ? Journal::query()
                    ->with('attachments')
                    ->find(
                        $this->journalId
                    )
                : null;

        $attendanceStats =
            $this->attendanceStats();

        return view(
            'livewire.journals.manage',
            compact(
                'activity',
                'attendanceSessions',
                'journal',
                'attendanceStats'
            )
        );
    }
}
