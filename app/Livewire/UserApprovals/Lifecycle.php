<?php

namespace App\Livewire\UserApprovals;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\UserTransfer;
use App\Services\UserLifecycleService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Lifecycle extends Component
{
    #[Locked]
    public int $contextSchoolId = 0;

    public function mount(): void
    {
        $this->contextSchoolId = app(SchoolContext::class)->id() ?? 0;
        $this->authorizeContext();
    }

    protected function authorizeContext(): void
    {
        abort_unless(auth()->user()?->can('user_approvals.manage'), 403);
        abort_unless($this->contextSchoolId && app(SchoolContext::class)->id() === $this->contextSchoolId, 409,
            'Sekolah aktif berubah. Muat ulang halaman sebelum mengelola pengguna.');
    }

    public ?int $userId = null;

    public ?int $studentId = null;

    public ?int $studentTargetSchoolId = null;

    public string $studentTransferReason = '';

    public function requestStudent(UserLifecycleService $service): void
    {
        $this->authorizeContext();
        $this->validate([
            'studentId' => ['required', 'integer'],
            'studentTargetSchoolId' => ['required', 'integer'],
            'studentTransferReason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $service->requestStudentTransfer($this->studentId, $this->studentTargetSchoolId, $this->studentTransferReason);
        $this->reset('studentId', 'studentTargetSchoolId', 'studentTransferReason');
        session()->flash('lifecycle', 'Transfer siswa diajukan. Menunggu persetujuan sekolah tujuan.');
    }

    public string $action = 'inactive';

    public ?int $targetSchoolId = null;

    public string $reason = '';

    public ?int $yearId = null;

    public ?int $classroomId = null;

    public ?int $sourceSchoolId = null;

    public string $nisn = '';

    public ?int $incomingYearId = null;

    public ?int $incomingClassroomId = null;

    public string $incomingReason = '';

    public function requestIncoming(UserLifecycleService $service): void
    {
        $this->authorizeContext();
        $this->validate([
            'sourceSchoolId' => ['required', 'integer'], 'nisn' => ['required', 'string', 'max:30'],
            'incomingYearId' => ['required', 'integer'], 'incomingClassroomId' => ['required', 'integer'],
            'incomingReason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $service->requestIncomingStudentTransfer($this->sourceSchoolId, $this->nisn, $this->incomingYearId, $this->incomingClassroomId, $this->incomingReason);
        $this->reset('nisn', 'incomingReason');
        session()->flash('lifecycle', 'Usulan siswa masuk dikirim untuk persetujuan admin sekolah asal.');
    }

    public function apply(UserLifecycleService $service): void
    {
        $this->authorizeContext();
        $this->validate(['userId' => ['required', 'integer'], 'action' => ['required', 'in:inactive,retired,active,disable,enable,transfer']]);
        if ($this->action === 'transfer') {
            $this->validate(['targetSchoolId' => ['required', 'integer'], 'reason' => ['required', 'min:5', 'max:1000']]);
            $service->requestTransfer($this->userId, $this->targetSchoolId, $this->reason);
        } elseif (in_array($this->action, ['disable', 'enable'], true)) {
            $service->account($this->userId, $this->action === 'enable');
        } else {
            $service->membership($this->userId, $this->action === 'active', $this->action === 'retired' ? 'retired' : 'inactive');
        }
        $this->reset('userId', 'reason', 'targetSchoolId');
        session()->flash('lifecycle', $this->action === 'transfer'
            ? 'Pengajuan transfer menunggu penerimaan sekolah tujuan.'
            : 'Status pengguna berhasil diperbarui.');
        $this->dispatch('users-changed');
    }

    public function resolve(int $transferId, string $decision, UserLifecycleService $service): void
    {
        $this->authorizeContext();
        $service->resolveTransfer($transferId, $decision, $this->yearId, $this->classroomId);
        session()->flash('lifecycle', 'Status transfer berhasil diperbarui.');
        $this->dispatch('users-changed');
    }

    public function render(): View
    {
        $this->authorizeContext();
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);

        return view('livewire.user-approvals.lifecycle', [
            'schoolId' => $schoolId,
            'students' => Student::query()->where('status', '!=', 'transferred')->orderBy('name')->get(['id', 'name', 'nisn', 'nis']),
            'users' => User::query()->whereHas('schoolMemberships', fn ($query) => $query->where('school_id', $schoolId))
                ->whereIn('system_role', ['student', 'coach', 'school_admin', 'principal'])->where('id', '!=', auth()->id())
                ->orderBy('name')->get(['id', 'name', 'email']),
            'schools' => School::query()->where('is_active', true)->where('id', '!=', $schoolId)->orderBy('name')->get(['id', 'name']),
            'years' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('grade')->orderBy('name')->get(),
            'transfers' => UserTransfer::query()->where(fn ($query) => $query->where('from_school_id', $schoolId)->orWhere('to_school_id', $schoolId))
                ->with(['user', 'student', 'fromSchool', 'toSchool', 'targetYear', 'targetClassroom'])->orderByRaw("case when status = 'pending' then 0 else 1 end")
                ->latest()->limit(50)->get(),
        ]);
    }
}
