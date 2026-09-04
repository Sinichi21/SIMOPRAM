<?php

namespace App\Livewire\ScoutGroups;

use App\Models\ScoutGroup;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

    public string $male_number = '';

    public string $female_number = '';

    public string $kwarran = '';

    public string $kwarcab = '';

    public string $kwarda = '';

    public string $kamabigus_name = '';

    public string $head_coach_name = '';

    public string $secretariat_address = '';

    public string $inauguration_date = '';

    public string $description = '';

    public bool $is_active = true;

    public string $search = '';

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'male_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'female_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'kwarran' => [
                'nullable',
                'string',
                'max:150',
            ],

            'kwarcab' => [
                'nullable',
                'string',
                'max:150',
            ],

            'kwarda' => [
                'nullable',
                'string',
                'max:150',
            ],

            'kamabigus_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'head_coach_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'secretariat_address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'inauguration_date' => [
                'nullable',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can('gudep.manage'),
            403
        );

        $validated = $this->validate();

        $scoutGroup = $this->editingId
            ? ScoutGroup::findOrFail(
                $this->editingId
            )
            : new ScoutGroup;

        $scoutGroup->fill($validated);

        $scoutGroup->save();

        session()->flash(
            'success',
            $this->editingId
                ? 'Data Gugus Depan berhasil diperbarui.'
                : 'Gugus Depan berhasil ditambahkan.'
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can('gudep.manage'),
            403
        );

        $gudep = ScoutGroup::findOrFail($id);

        $this->editingId =
            $gudep->id;

        $this->name =
            $gudep->name;

        $this->male_number =
            $gudep->male_number ?? '';

        $this->female_number =
            $gudep->female_number ?? '';

        $this->kwarran =
            $gudep->kwarran ?? '';

        $this->kwarcab =
            $gudep->kwarcab ?? '';

        $this->kwarda =
            $gudep->kwarda ?? '';

        $this->kamabigus_name =
            $gudep->kamabigus_name ?? '';

        $this->head_coach_name =
            $gudep->head_coach_name ?? '';

        $this->secretariat_address =
            $gudep->secretariat_address ?? '';

        $this->inauguration_date =
            $gudep->inauguration_date
                ?->format('Y-m-d') ?? '';

        $this->description =
            $gudep->description ?? '';

        $this->is_active =
            $gudep->is_active;
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(
            auth()->user()->can('gudep.manage'),
            403
        );

        $gudep =
            ScoutGroup::findOrFail($id);

        $gudep->update([
            'is_active' => ! $gudep->is_active,
        ]);

        session()->flash(
            'success',
            'Status Gugus Depan berhasil diperbarui.'
        );
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'name',
            'male_number',
            'female_number',
            'kwarran',
            'kwarcab',
            'kwarda',
            'kamabigus_name',
            'head_coach_name',
            'secretariat_address',
            'inauguration_date',
            'description',
        ]);

        $this->is_active = true;

        $this->resetValidation();
    }

    public function render()
    {
        $scoutGroups =
            ScoutGroup::query()
                ->when(
                    $this->search,
                    fn ($query) => $query->where(
                        'name',
                        'like',
                        '%'.
                        $this->search.
                        '%'
                    )
                )
                ->latest()
                ->paginate(10);

        return view(
            'livewire.scout-groups.index',
            compact('scoutGroups')
        );
    }
}
