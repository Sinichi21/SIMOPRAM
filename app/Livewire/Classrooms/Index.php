<?php

namespace App\Livewire\Classrooms;

use App\Models\Classroom;
use App\Support\SchoolContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $grade = null;

    public string $description = '';

    public bool $is_active = true;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        $schoolId = app(SchoolContext::class)->id();

        return [
            'name' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'classrooms',
                    'name'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'school_id',
                            $schoolId
                        )
                    )
                    ->ignore($this->editingId),
            ],

            'grade' => [
                'nullable',
                'integer',
                'between:1,12',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can('classrooms.manage'),
            403
        );

        $validated = $this->validate();

        $classroom = $this->editingId
            ? Classroom::findOrFail(
                $this->editingId
            )
            : new Classroom;

        $classroom->fill($validated);

        $classroom->save();

        session()->flash(
            'success',
            $this->editingId
                ? 'Kelas berhasil diperbarui.'
                : 'Kelas berhasil ditambahkan.'
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can('classrooms.manage'),
            403
        );

        $classroom =
            Classroom::findOrFail($id);

        $this->editingId =
            $classroom->id;

        $this->name =
            $classroom->name;

        $this->grade =
            $classroom->grade;

        $this->description =
            $classroom->description ?? '';

        $this->is_active =
            $classroom->is_active;
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(
            auth()->user()->can('classrooms.manage'),
            403
        );

        $classroom =
            Classroom::findOrFail($id);

        $classroom->update([
            'is_active' => ! $classroom->is_active,
        ]);

        session()->flash(
            'success',
            $classroom->is_active
                ? 'Kelas berhasil diaktifkan.'
                : 'Kelas berhasil dinonaktifkan.'
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
            'grade',
            'description',
        ]);

        $this->is_active = true;

        $this->resetValidation();
    }

    public function render()
    {
        $classrooms =
            Classroom::query()
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
                ->orderBy('grade')
                ->orderBy('name')
                ->paginate(10);

        return view(
            'livewire.classrooms.index',
            compact('classrooms')
        );
    }
}
