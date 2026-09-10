<?php

namespace App\Livewire\Reports\PublishedDocuments;

use App\Models\ReportVerification;
use App\Services\DocumentApprovalService;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function approve(int $documentId, DocumentApprovalService $service): void
    {
        $service->approve($documentId);
        session()->flash('status', 'Persetujuan dokumen berhasil dicatat.');
    }

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
        $this->reset(['search', 'status', 'documentType', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function startRevoke(int $verificationId): void
    {
        abort_unless(auth()->user()?->can('report_verifications.manage'), 403);
        $verification = $this->baseQuery()->findOrFail($verificationId);
        abort_if($verification->isRevoked(), 409, 'Dokumen ini sudah dicabut.');
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

    public function revoke(ReportVerificationService $service): void
    {
        abort_unless(auth()->user()?->can('report_verifications.manage'), 403);

        $this->validate([
            'revocationReason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        abort_unless($this->revokeId, 422, 'Dokumen yang akan dicabut belum dipilih.');

        $verification = $this->baseQuery()->findOrFail($this->revokeId);
        $service->revoke($verification, $this->revocationReason, auth()->id());
        $this->cancelRevoke();

        session()->flash(
            'status',
            'Dokumen berhasil dicabut. QR lama tetap dapat dipindai dan akan menampilkan status Dicabut.'
        );
    }

    protected function baseQuery(): Builder
    {
        abort_unless(auth()->user()?->can('report_verifications.view'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return ReportVerification::query()->where('school_id', $schoolId)
            ->when(auth()->user()->system_role === 'principal', fn (Builder $query): Builder => $query->whereJsonContains('required_signatory_ids', auth()->id()));
    }

    protected function applyStatusFilter(Builder $query): void
    {
        if ($this->status === 'pending') {
            $query->whereNull('revoked_at')->whereNull('approval_completed_at')
                ->whereJsonLength('required_signatories', '>', 0)
                ->whereDoesntHave('closure', fn ($closure) => $closure->where('status', 'reopened'));

            return;
        }

        if ($this->status === 'revoked') {
            $query->whereNotNull('revoked_at');

            return;
        }

        if ($this->status === 'superseded') {
            $query->whereNull('revoked_at')->whereHas(
                'closure',
                fn ($q) => $q->where('status', 'reopened')
            );

            return;
        }

        if ($this->status === 'valid') {
            $this->withoutPendingApprovals($query);
            $query->whereNull('revoked_at')->where(function ($q): void {
                $q->whereNull('semester_closure_id')
                    ->orWhereHas('closure', fn ($closure) => $closure->where('status', 'locked'));
            });
        }
    }

    protected function statistics(): array
    {
        $base = $this->baseQuery();
        $valid = clone $base;
        $this->withoutPendingApprovals($valid);

        return [
            'total' => (clone $base)->count(),
            'valid' => $valid
                ->whereNull('revoked_at')
                ->where(function ($q): void {
                    $q->whereNull('semester_closure_id')
                        ->orWhereHas('closure', fn ($closure) => $closure->where('status', 'locked'));
                })
                ->count(),
            'superseded' => (clone $base)
                ->whereNull('revoked_at')
                ->whereHas('closure', fn ($q) => $q->where('status', 'reopened'))
                ->count(),
            'revoked' => (clone $base)->whereNotNull('revoked_at')->count(),
            'verification_count' => (int) (clone $base)->sum('verification_count'),
        ];
    }

    protected function withoutPendingApprovals(Builder $query): void
    {
        $query->where(fn ($query) => $query->whereNull('required_signatories')
            ->orWhereJsonLength('required_signatories', 0)
            ->orWhereNotNull('approval_completed_at'));
    }

    public function render(): View
    {
        $query = $this->baseQuery()->with([
            'closure.academicYear',
            'closure.semester',
            'issuer',
            'revoker',
            'source',
        ]);

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('code', 'like', $term)
                    ->orWhere('snapshot_checksum', 'like', $term)
                    ->orWhere('document_number', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhereHas('issuer', fn ($issuer) => $issuer->where('name', 'like', $term));
            });
        }

        if ($this->documentType !== '') {
            $query->where('document_type', $this->documentType);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('issued_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('issued_at', '<=', $this->dateTo);
        }

        $this->applyStatusFilter($query);

        $documents = $query->orderByDesc('issued_at')->paginate(20);
        $documentTypes = $this->baseQuery()
            ->select('document_type')
            ->distinct()
            ->orderBy('document_type')
            ->pluck('document_type');

        return view('livewire.reports.published-documents.index', [
            'documents' => $documents,
            'statistics' => $this->statistics(),
            'documentTypes' => $documentTypes,
        ]);
    }
}
