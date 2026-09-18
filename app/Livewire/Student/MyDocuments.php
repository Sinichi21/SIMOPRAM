<?php

namespace App\Livewire\Student;

use App\Services\StudentDocumentAccessService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class MyDocuments extends Component
{
    use WithPagination;

    public string $search = '';

    public string $documentType = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDocumentType(): void
    {
        $this->resetPage();
    }

    public function render(
        StudentDocumentAccessService $access
    ): View {
        $user = auth()->user();

        abort_unless(
            $user?->hasRole('student'),
            403
        );

        $student = $access->student(
            $user
        );

        if (! $student) {
            return view(
                'livewire.student.my-documents',
                [
                    'student' => null,
                    'documents' => null,
                    'documentTypes' => collect(),
                    'statistics' => [
                        'total' => 0,
                        'valid' => 0,
                        'revoked' => 0,
                    ],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Base query
        |--------------------------------------------------------------------------
        */

        $base = $access->queryFor(
            $user
        );

        /*
        |--------------------------------------------------------------------------
        | Statistik
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'total' => (clone $base)->count(),

            'valid' => (clone $base)
                ->whereNull(
                    'revoked_at'
                )
                ->count(),

            'revoked' => (clone $base)
                ->whereNotNull(
                    'revoked_at'
                )
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Jenis dokumen
        |--------------------------------------------------------------------------
        */

        $documentTypes =
            (clone $base)
                ->select(
                    'document_type'
                )
                ->distinct()
                ->orderBy(
                    'document_type'
                )
                ->pluck(
                    'document_type'
                );

        /*
        |--------------------------------------------------------------------------
        | Filter
        |--------------------------------------------------------------------------
        */

        $query = $access
            ->queryFor($user)
            ->with([
                'closure.academicYear',
                'closure.semester',
                'issuer',
            ]);

        if (
            trim(
                $this->search
            ) !== ''
        ) {
            $term =
                '%'
                .trim(
                    $this->search
                )
                .'%';

            $query->where(
                function ($query) use (
                    $term
                ): void {
                    $query
                        ->where(
                            'title',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'document_number',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'file_name',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'code',
                            'like',
                            $term
                        );
                }
            );
        }

        if (
            $this->documentType !== ''
        ) {
            $query->where(
                'document_type',
                $this->documentType
            );
        }

        $documents = $query
            ->orderByDesc(
                'issued_at'
            )
            ->paginate(12);

        return view(
            'livewire.student.my-documents',
            [
                'student' => $student,

                'documents' => $documents,

                'documentTypes' => $documentTypes,

                'statistics' => $statistics,
            ]
        );
    }
}
