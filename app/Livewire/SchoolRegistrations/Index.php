<?php

namespace App\Livewire\SchoolRegistrations;

use App\Models\School;
use App\Models\SchoolRegistrationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'pending';

    #[Locked]
    public ?int $selectedId = null;

    public bool $showDetail = false;

    public string $rejectionReason = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function show(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        SchoolRegistrationRequest::query()->findOrFail($id);
        $this->resetValidation();
        $this->rejectionReason = '';
        $this->selectedId = $id;
        $this->showDetail = true;
    }

    public function approve(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        try {
            DB::transaction(function (): void {
                $registration = $this->pendingRegistration();

                if (School::withTrashed()->where('npsn', $registration->npsn)->exists()) {
                    throw ValidationException::withMessages(['review' => 'NPSN sudah digunakan oleh sekolah lain. Periksa Data Sekolah.']);
                }

                $baseSlug = Str::slug($registration->school_name) ?: 'sekolah';
                $slug = $baseSlug;
                $counter = 2;

                while (School::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }

                $school = School::query()->create([
                    'name' => $registration->school_name,
                    'npsn' => $registration->npsn,
                    'slug' => $slug,
                    'level' => $registration->level,
                    'city' => $registration->city,
                    'timezone' => 'Asia/Makassar',
                    'is_active' => true,
                    'registration_open' => true,
                ]);

                $registration->forceFill([
                    'status' => 'approved',
                    'school_id' => $school->id,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ])->save();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['review' => 'Data sekolah berubah saat diproses. Periksa NPSN dan coba kembali.']);
        }

        $this->showDetail = false;
        session()->flash('success', 'Permohonan disetujui. Sekolah aktif tersedia di Data Sekolah. Lanjutkan penyiapan akun admin sekolah melalui alur pengelolaan akun.');
    }

    public function reject(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->rejectionReason = trim($this->rejectionReason);
        $this->validate(['rejectionReason' => ['required', 'string', 'max:1000']], [
            'rejectionReason.required' => 'Alasan penolakan wajib diisi.',
            'rejectionReason.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);

        DB::transaction(function (): void {
            $this->pendingRegistration()->forceFill([
                'status' => 'rejected',
                'rejection_reason' => $this->rejectionReason,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ])->save();
        });

        $this->showDetail = false;
        session()->flash('success', 'Permohonan ditolak. Alasan penolakan tersimpan dalam riwayat pengajuan.');
    }

    private function pendingRegistration(): SchoolRegistrationRequest
    {
        $registration = SchoolRegistrationRequest::query()->lockForUpdate()->findOrFail($this->selectedId);

        if ($registration->status !== 'pending') {
            throw ValidationException::withMessages(['review' => 'Permohonan ini sudah diproses. Muat ulang daftar untuk melihat status terbaru.']);
        }

        return $registration;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $registrations = SchoolRegistrationRequest::query()
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('school_name', 'like', '%'.$this->search.'%')
                        ->orWhere('npsn', 'like', '%'.$this->search.'%')
                        ->orWhere('city', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('id')->paginate(10);

        $selected = $this->showDetail && $this->selectedId
            ? SchoolRegistrationRequest::query()->with('reviewer')->findOrFail($this->selectedId)
            : null;

        return view('livewire.school-registrations.index', compact('registrations', 'selected'));
    }
}
