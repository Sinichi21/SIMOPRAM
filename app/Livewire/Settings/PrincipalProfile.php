<?php

namespace App\Livewire\Settings;

use App\Models\DocumentSignatoryProfile;
use App\Support\SchoolContext;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PrincipalProfile extends Component
{
    #[Locked]
    public int $schoolId = 0;

    public string $phone = '';

    public string $position = 'Kepala Sekolah';

    public string $identifierType = 'NIP';

    public string $identifierNumber = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->system_role === 'principal' && auth()->user()->is_active, 403);
        $this->schoolId = app(SchoolContext::class)->id() ?? 0;
        $this->phone = auth()->user()->phone ?? '';

        if ($this->schoolId === 0) {
            return;
        }

        $this->authorizeSchool();
        $profile = DocumentSignatoryProfile::query()
            ->where('school_id', $this->schoolId)->where('user_id', auth()->id())->first();
        $this->position = $profile?->position ?: 'Kepala Sekolah';
        $this->identifierType = $profile?->identifier_type ?: 'NIP';
        $this->identifierNumber = $profile?->identifier_number ?? '';
    }

    public function save(): void
    {
        $this->authorizeSchool();
        $this->phone = trim($this->phone);
        $this->position = trim($this->position);
        $this->identifierNumber = trim($this->identifierNumber);
        $this->validate([
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9() .-]+$/'],
            'position' => ['required', 'string', 'max:150'],
            'identifierType' => ['required', 'in:NIP,NTA'],
            'identifierNumber' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function (): void {
            auth()->user()->update(['phone' => $this->phone !== '' ? $this->phone : null]);
            DocumentSignatoryProfile::query()->updateOrCreate(
                ['school_id' => $this->schoolId, 'user_id' => auth()->id()],
                [
                    'position' => $this->position,
                    'identifier_type' => $this->identifierNumber !== '' ? $this->identifierType : null,
                    'identifier_number' => $this->identifierNumber !== '' ? $this->identifierNumber : null,
                ]
            );
        });

        Flux::toast(variant: 'success', text: 'Data diri Kepala Sekolah berhasil disimpan.');
    }

    protected function authorizeSchool(): void
    {
        $user = auth()->user();
        abort_unless($user?->system_role === 'principal' && $user->is_active, 403);
        abort_unless($this->schoolId && app(SchoolContext::class)->id() === $this->schoolId, 409,
            'Sekolah aktif berubah atau belum dipilih. Muat ulang halaman profil.');
        abort_unless($user->schoolMemberships()->where('school_id', $this->schoolId)
            ->where('is_active', true)->whereNull('left_at')->exists(), 403);
    }

    public function render(): View
    {
        return view('livewire.settings.principal-profile', [
            'schoolName' => app(SchoolContext::class)->school()?->name,
        ]);
    }
}
