<?php

namespace App\\Livewire\\PeopleMerge;

use App\\Models\\Coach;
use App\\Models\\Person;
use App\\Models\\Student;
use App\\Models\\User;
use Illuminate\\Contracts\\View\\View;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;
use Illuminate\\Validation\\ValidationException;
use Livewire\\Component;
use Livewire\\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $profileType = 'coach';

    public string $search = '';

    /** @var array<int, int|string> */
    public array $selectedProfileIds = [];

    public ?int $targetPersonId = null;

    public string $newPersonName = '';

    public bool $confirmLink = false;

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
    }

    public function updatedProfileType(): void
    {
        $this->authorizeSuperAdmin();
        $this->reset(['search', 'selectedProfileIds', 'targetPersonId', 'newPersonName', 'confirmLink']);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->authorizeSuperAdmin();
        $this->resetPage();
    }

    public function updatedSelectedProfileIds(): void
    {
        $this->authorizeSuperAdmin();

        if ($this->targetPersonId || trim($this->newPersonName) !== '') {
            return;
        }

        $firstId = collect($this->selectedProfileIds)->first();

        if ($firstId === null) {
            return;
        }

        $profile = $this->profileQuery($this->profileType)
            ->whereNull('person_id')
            ->find((int) $firstId);

        if ($profile) {
            $this->newPersonName = (string) $profile->name;
        }
    }

    public function linkProfiles(): void
    {
        $this->authorizeSuperAdmin();

        $validated = $this->validate([
            'profileType' => ['required', 'string', 'in:coach,student,user'],
            'selectedProfileIds' => ['required', 'array', 'min:1'],
            'selectedProfileIds.*' => ['required', 'integer', 'distinct'],
            'targetPersonId' => ['nullable', 'integer', 'exists:people,id'],
            'newPersonName' => ['nullable', 'string', 'max:150'],
            'confirmLink' => ['accepted'],
        ]);

        $profileIds = collect($validated['selectedProfileIds'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($validated['targetPersonId'] === null) {
            if ($profileIds->count() < 2) {
                throw ValidationException::withMessages([
                    'selectedProfileIds' => 'Pilih minimal dua profil untuk membuat identitas baru dari hasil penggabungan.',
                ]);
            }

            if (trim((string) $validated['newPersonName']) === '') {
                throw ValidationException::withMessages([
                    'newPersonName' => 'Nama identitas wajib diisi ketika membuat identitas baru.',
                ]);
            }
        }

        $type = $validated['profileType'];

        $person = DB::transaction(function () use ($validated, $profileIds, $type): Person {
            $profiles = $this->profileQuery($type)
                ->whereNull('person_id')
                ->whereKey($profileIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($profiles->count() !== $profileIds->count()) {
                throw ValidationException::withMessages([
                    'selectedProfileIds' => 'Sebagian profil tidak ditemukan atau sudah tertaut ke identitas lain. Muat ulang halaman dan periksa pilihan.',
                ]);
            }

            $isNewPerson = $validated['targetPersonId'] === null;

            if ($isNewPerson) {
                /** @var Model $firstProfile */
                $firstProfile = $profiles->first();

                $person = Person::query()->create([
                    'name' => trim((string) $validated['newPersonName']),
                    'gender' => $firstProfile->getAttribute('gender'),
                    'birth_place' => $firstProfile->getAttribute('birth_place'),
                    'birth_date' => $firstProfile->getAttribute('birth_date'),
                ]);
            } else {
                $person = Person::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['targetPersonId']);
            }

            foreach ($profiles as $profile) {
                $profile->forceFill(['person_id' => $person->id]);
                $profile->save();
            }

            Log::info('people.profiles_linked', [
                'actor_user_id' => auth()->id(),
                'profile_type' => $type,
                'profile_ids' => $profileIds->all(),
                'person_id' => $person->id,
                'created_person' => $isNewPerson,
            ]);

            return $person;
        });

        $linkedCount = $profileIds->count();
        $this->reset(['selectedProfileIds', 'targetPersonId', 'newPersonName', 'confirmLink']);
        $this->resetPage();

        session()->flash(
            'status',
            "{$linkedCount} profil berhasil ditautkan ke identitas {$person->name}. Data profil tidak dihapus."
        );
    }

    public function render(): View
    {
        $this->authorizeSuperAdmin();

        $query = $this->profileQuery($this->profileType)
            ->whereNull('person_id')
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->where('name', 'like', $term);

                    if ($this->profileType === 'user') {
                        $nested->orWhere('email', 'like', $term);
                    } elseif ($this->profileType === 'student') {
                        $nested->orWhere('nis', 'like', $term)
                            ->orWhere('nisn', 'like', $term);
                    } else {
                        $nested->orWhere('nip', 'like', $term);
                    }
                });
            })
            ->orderBy('name');

        if ($this->profileType !== 'user') {
            $query->with('school');
        }

        $profiles = $query->paginate(15);
        $people = Person::query()
            ->withCount(['users', 'studentProfiles', 'coachProfiles'])
            ->orderBy('name')
            ->limit(100)
            ->get();

        return view('livewire.people-merge.index', [
            'profiles' => $profiles,
            'people' => $people,
        ]);
    }

    private function profileQuery(string $type): Builder
    {
        return match ($type) {
            'coach' => Coach::query(),
            'student' => Student::query(),
            'user' => User::query(),
            default => throw ValidationException::withMessages([
                'profileType' => 'Jenis profil tidak valid.',
            ]),
        };
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
