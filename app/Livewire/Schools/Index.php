<?php

namespace App\Livewire\Schools;

use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $npsn = '';

    public string $name = '';

    public string $level = '';

    public string $address = '';

    public string $village = '';

    public string $district = '';

    public string $city = '';

    public string $province = '';

    public string $postal_code = '';

    public string $phone = '';

    public string $email = '';

    public string $website = '';

    public string $timezone = 'Asia/Makassar';

    public bool $is_active = true;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'npsn' => [
                'required',
                'string',
                'max:20',
                Rule::unique('schools', 'npsn')
                    ->ignore($this->editingId),
            ],

            'name' => [
                'required',
                'string',
                'max:200',
            ],

            'level' => [
                'required',
                Rule::in([
                    'SD',
                    'SMP',
                    'SMA',
                    'SMK',
                    'MI',
                    'MTs',
                    'MA',
                    'Lainnya',
                ]),
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'village' => [
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'province' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:10',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'timezone' => [
                'required',
                'string',
                'max:50',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can(
                $this->editingId
                    ? 'schools.update'
                    : 'schools.create'
            ),
            403
        );

        $validated = $this->validate();

        if ($this->editingId) {

            $school = School::query()
                ->findOrFail($this->editingId);

        } else {

            $school = new School();

        }

        /*
        |--------------------------------------------------------------------------
        | Generate Slug
        |--------------------------------------------------------------------------
        */

        $school->slug = $this->makeUniqueSlug(
            $validated['name'],
            $this->editingId
        );

        $school->fill($validated);

        $school->save();

        session()->flash(
            'success',
            $this->editingId
                ? 'Data sekolah berhasil diperbarui.'
                : 'Sekolah berhasil ditambahkan.'
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can('schools.update'),
            403
        );

        $school = School::query()
            ->findOrFail($id);

        $this->editingId = $school->id;

        $this->npsn = $school->npsn;

        $this->name = $school->name;

        $this->level = $school->level ?? '';

        $this->address = $school->address ?? '';

        $this->village = $school->village ?? '';

        $this->district = $school->district ?? '';

        $this->city = $school->city ?? '';

        $this->province = $school->province ?? '';

        $this->postal_code =
            $school->postal_code ?? '';

        $this->phone = $school->phone ?? '';

        $this->email = $school->email ?? '';

        $this->website = $school->website ?? '';

        $this->timezone =
            $school->timezone ?: 'Asia/Makassar';

        $this->is_active =
            $school->is_active;
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(
            auth()->user()->can('schools.toggle'),
            403
        );

        $school = School::query()
            ->findOrFail($id);

        $newStatus = ! $school->is_active;

        $school->update([
            'is_active' => $newStatus,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Jika sekolah aktif yang sedang dikelola dinonaktifkan
        |--------------------------------------------------------------------------
        */

        if (
            ! $newStatus &&
            (int) session('active_school_id') ===
            (int) $school->id
        ) {
            session()->forget(
                'active_school_id'
            );

            setPermissionsTeamId(null);

            auth()->user()?->unsetRelation('roles');
            auth()->user()?->unsetRelation('permissions');
        }

        session()->flash(
            'success',
            $newStatus
                ? 'Sekolah berhasil diaktifkan.'
                : 'Sekolah berhasil dinonaktifkan.'
        );
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    protected function makeUniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'sekolah';
        }

        $slug = $baseSlug;

        $counter = 2;

        while (
            School::query()
                ->where('slug', $slug)
                ->when(
                    $ignoreId,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $ignoreId
                        )
                )
                ->exists()
        ) {
            $slug =
                $baseSlug . '-' . $counter;

            $counter++;
        }

        return $slug;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'npsn',
            'name',
            'level',
            'address',
            'village',
            'district',
            'city',
            'province',
            'postal_code',
            'phone',
            'email',
            'website',
        ]);

        $this->timezone =
            'Asia/Makassar';

        $this->is_active = true;

        $this->resetValidation();
    }

    public function render()
    {
        $schools = School::query()
            ->when(
                $this->search,
                function ($query) {

                    $search =
                        '%' .
                        $this->search .
                        '%';

                    $query->where(
                        function ($query) use ($search) {

                            $query
                                ->where(
                                    'name',
                                    'like',
                                    $search
                                )
                                ->orWhere(
                                    'npsn',
                                    'like',
                                    $search
                                )
                                ->orWhere(
                                    'city',
                                    'like',
                                    $search
                                );

                        }
                    );
                }
            )
            ->orderBy('name')
            ->paginate(10);

        return view(
            'livewire.schools.index',
            compact('schools')
        );
    }
}