<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportViewExport implements FromView, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected string $viewName,
        protected array $viewData,
        protected string $sheetTitle,
    ) {}

    public function view(): View
    {
        return view(
            $this->viewName,
            $this->viewData
        );
    }

    public function title(): string
    {
        $title = preg_replace(
            '/[\\\\\/\?\*\[\]:]/',
            '',
            $this->sheetTitle
        );

        return mb_substr(
            $title ?: 'Laporan',
            0,
            31
        );
    }
}
