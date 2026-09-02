<?php

namespace App\Livewire\Coaches;

use App\Models\Coach;
use App\Support\SchoolContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $nip = '';

    public string $name = '';

    public string $gender = '';

    public string $phone = '';

    public string $position = '';

    public string $certificate_number = '';

    public bool $is_active = true;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function schoolId(): int
    {
        $schoolId = app(
            SchoolContext::class
        )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        return $schoolId;
    }

    protected function rules(): array
    {
        $schoolId = $this->schoolId();

        return [
            'nip' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'coaches',
                    'nip'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'school_id',
                                $schoolId
                            )
                    )
                    ->ignore(
                        $this->editingId
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'gender' => [
                'nullable',
                Rule::in([
                    'L',
                    'P',
                ]),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'position' => [
                'nullable',
                'string',
                'max:100',
            ],

            'certificate_number' => [
                'nullable',
                'string',
                'max:100',
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
                    ? 'coaches.update'
                    : 'coaches.create'
            ),
            403
        );

        $validated = $this->validate();

        /*
        |--------------------------------------------------------------------------
        | Normalisasi data kosong
        |--------------------------------------------------------------------------
        */

        $validated['nip'] =
            filled($validated['nip'])
                ? trim($validated['nip'])
                : null;

        $validated['gender'] =
            filled($validated['gender'])
                ? $validated['gender']
                : null;

        $validated['phone'] =
            filled($validated['phone'])
                ? trim($validated['phone'])
                : null;

        $validated['position'] =
            filled($validated['position'])
                ? trim($validated['position'])
                : null;

        $validated['certificate_number'] =
            filled(
                $validated[
                    'certificate_number'
                ]
            )
                ? trim(
                    $validated[
                        'certificate_number'
                    ]
                )
                : null;

        if ($this->editingId) {

            $coach = Coach::query()
                ->findOrFail(
                    $this->editingId
                );

            $coach->update(
                $validated
            );

            $message =
                'Data pembina berhasil diperbarui.';

        } else {

            Coach::query()
                ->create(
                    $validated
                );

            $message =
                'Pembina berhasil ditambahkan.';
        }

        $this->resetForm();

        session()->flash(
            'success',
            $message
        );
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can(
                'coaches.update'
            ),
            403
        );

        $coach = Coach::query()
            ->findOrFail($id);

        $this->editingId =
            $coach->id;

        $this->nip =
            $coach->nip ?? '';

        $this->name =
            $coach->name;

        $this->gender =
            $coach->gender ?? '';

        $this->phone =
            $coach->phone ?? '';

        $this->position =
            $coach->position ?? '';

        $this->certificate_number =
            $coach->certificate_number
            ?? '';

        $this->is_active =
            (bool) $coach->is_active;

        $this->resetValidation();
    }

    public function toggleStatus(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'coaches.toggle'
            ),
            403
        );

        $coach = Coach::query()
            ->findOrFail($id);

        $coach->update([
            'is_active' =>
                ! $coach->is_active,
        ]);

        session()->flash(
            'success',
            $coach->is_active
                ? 'Pembina berhasil diaktifkan.'
                : 'Pembina berhasil dinonaktifkan.'
        );
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId',
            'nip',
            'name',
            'gender',
            'phone',
            'position',
            'certificate_number',
        ]);

        $this->is_active = true;

        $this->resetValidation();
    }

    public function render()
    {
        $coaches = Coach::query()
            ->when(
                $this->search,
                function ($query): void {

                    $search =
                        '%' .
                        trim($this->search) .
                        '%';

                    $query->where(
                        function ($query) use (
                            $search
                        ): void {

                            $query
                                ->where(
                                    'name',
                                    'like',
                                    $search
                                )
                                ->orWhere(
                                    'nip',
                                    'like',
                                    $search
                                )
                                ->orWhere(
                                    'phone',
                                    'like',
                                    $search
                                )
                                ->orWhere(
                                    'position',
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
            'livewire.coaches.index',
            compact('coaches')
        );
    }
}