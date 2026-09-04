<?php

namespace App\Livewire\Reports\PublishedDocuments;

use App\Models\ReportVerification;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $documentType = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $revokeId = null;

    public string $revocationReason = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDocumentType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'status',
            'documentType',
            'dateFrom',
            'dateTo',
        ]);

        $this->resetPage();
    }

    public function startRevoke(
        int $verificationId
    ): void {
        abort_unless(
            auth()->user()?->can(
                'report_verifications.manage'
            ),
            403
        );

        $schoolId = app(SchoolContext::class)->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        $verification = ReportVerification::query()
            ->where('school_id', $schoolId)
            ->findOrFail($verificationId);

        abort_if(
            $verification->isRevoked(),
            409,
            'Dokumen ini sudah dicabut.'
        );

        $this->revokeId = $verification->id;
        $this->revocationReason = '';
        $this->resetValidation();
    }

    public function cancelRevoke(): void
    {
        $this->revokeId = null;
        $this->revocationReason = '';
        $this->resetValidation();
    }

    public function revoke(
        ReportVerificationService $service
    ): void {
        abort_unless(
            auth()->user()?->can(
                'report_verifications.manage'
            ),
            403
        );

        $this->validate([
            'revocationReason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        $schoolId = app(SchoolContext::class)->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        abort_unless(
            $this->revokeId,
            422,
            'Dokumen yang akan dicabut belum dipilih.'
        );

        $verification = ReportVerification::query()
            ->where('school_id', $schoolId)
            ->findOrFail($this->revokeId);

        $service->revoke(
            verification: $verification,
            reason: $this->revocationReason,
            revokedBy: auth()->id()
        );

        $this->cancelRevoke();

        session()->flash(
            'status',
            'Dokumen berhasil dicabut. QR lama tetap dapat dipindai, '
            .'tetapi halaman verifikasi akan menampilkan status Dicabut.'
        );
    }

    protected function baseQuery()
    {
        $schoolId = app(SchoolContext::class)->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        return ReportVerification::query()
            ->where('school_id', $schoolId);
    }

    protected function applyStatusFilter(
        $query
    ): void {
        if ($this->status === 'revoked') {
            $query->whereNotNull('revoked_at');

            return;
        }

        if ($this->status === 'superseded') {
            $query
                ->whereNull('revoked_at')
                ->whereHas(
                    'closure',
                    fn ($query) => $query->where(
                        'status',
                        'reopened'
                    )
                );

            return;
        }

        if ($this->status === 'valid') {
            $query
                ->whereNull('revoked_at')
                ->whereHas(
                    'closure',
                    fn ($query) => $query->where(
                        'status',
                        'locked'
                    )
                );
        }
    }

    protected function statistics(): array
    {
        $base = $this->baseQuery();

        return [
            'total' => (clone $base)->count(),

            'valid' => (clone $base)
                ->whereNull('revoked_at')
                ->whereHas(
                    'closure',
                    fn ($query) => $query->where(
                        'status',
                        'locked'
                    )
                )
                ->count(),

            'superseded' => (clone $base)
                ->whereNull('revoked_at')
                ->whereHas(
                    'closure',
                    fn ($query) => $query->where(
                        'status',
                        'reopened'
                    )
                )
                ->count(),

            'revoked' => (clone $base)
                ->whereNotNull('revoked_at')
                ->count(),

            'verification_count' => (int) (clone $base)
                ->sum('verification_count'),
        ];
    }

    public function render(): View
    {
        $query = $this->baseQuery()
            ->with([
                'closure.academicYear',
                'closure.semester',
                'issuer',
                'revoker',
            ]);

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';

            $query->where(
                function ($query) use ($term): void {
                    $query
                        ->where('code', 'like', $term)
                        ->orWhere(
                            'snapshot_checksum',
                            'like',
                            $term
                        )
                        ->orWhereHas(
                            'issuer',
                            fn ($query) => $query->where(
                                'name',
                                'like',
                                $term
                            )
                        );
                }
            );
        }

        if ($this->documentType !== '') {
            $query->where(
                'document_type',
                $this->documentType
            );
        }

        if ($this->dateFrom !== '') {
            $query->whereDate(
                'issued_at',
                '>=',
                $this->dateFrom
            );
        }

        if ($this->dateTo !== '') {
            $query->whereDate(
                'issued_at',
                '<=',
                $this->dateTo
            );
        }

        $this->applyStatusFilter($query);

        $documents = $query
            ->orderByDesc('issued_at')
            ->paginate(20);

        $documentTypes = $this->baseQuery()
            ->select('document_type')
            ->distinct()
            ->orderBy('document_type')
            ->pluck('document_type');

        return view(
            'livewire.reports.published-documents.index',
            [
                'documents' => $documents,
                'statistics' => $this->statistics(),
                'documentTypes' => $documentTypes,
            ]
        );
    }
}
