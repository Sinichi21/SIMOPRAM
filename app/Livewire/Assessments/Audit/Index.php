<?php

namespace App\Livewire\Assessments\Audit;

use App\Models\AssessmentAuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $module = '';

    public string $action = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedModule(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
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
            'module',
            'action',
            'dateFrom',
            'dateTo',
        ]);

        $this->resetPage();
    }

    public function render()
    {
        $logs =
            AssessmentAuditLog::query()
                ->with(
                    'user'
                )
                ->when(
                    trim(
                        $this->search
                    ) !== '',
                    function ($query): void {
                        $search =
                            '%'
                            .trim(
                                $this->search
                            )
                            .'%';

                        $query->where(
                            function ($query) use (
                                $search
                            ): void {
                                $query
                                    ->where(
                                        'description',
                                        'like',
                                        $search
                                    )
                                    ->orWhere(
                                        'action',
                                        'like',
                                        $search
                                    )
                                    ->orWhereHas(
                                        'user',
                                        fn ($query) => $query->where(
                                            'name',
                                            'like',
                                            $search
                                        )
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $this->module !== '',
                    fn ($query) => $query->where(
                        'module',
                        $this->module
                    )
                )
                ->when(
                    $this->action !== '',
                    fn ($query) => $query->where(
                        'action',
                        $this->action
                    )
                )
                ->when(
                    $this->dateFrom !== '',
                    fn ($query) => $query->whereDate(
                        'created_at',
                        '>=',
                        $this->dateFrom
                    )
                )
                ->when(
                    $this->dateTo !== '',
                    fn ($query) => $query->whereDate(
                        'created_at',
                        '<=',
                        $this->dateTo
                    )
                )
                ->orderByDesc(
                    'created_at'
                )
                ->orderByDesc(
                    'id'
                )
                ->paginate(
                    25
                );

        $modules =
            AssessmentAuditLog::query()
                ->whereNotNull(
                    'module'
                )
                ->distinct()
                ->orderBy(
                    'module'
                )
                ->pluck(
                    'module'
                );

        $actions =
            AssessmentAuditLog::query()
                ->distinct()
                ->orderBy(
                    'action'
                )
                ->pluck(
                    'action'
                );

        return view(
            'livewire.assessments.audit.index',
            [
                'logs' => $logs,

                'modules' => $modules,

                'actions' => $actions,
            ]
        );
    }
}
