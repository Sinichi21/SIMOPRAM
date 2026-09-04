<?php

namespace App\Http\Controllers;

use App\Services\ReportVerificationService;
use Illuminate\Contracts\View\View;

class ReportVerificationController extends Controller
{
    public function show(
        string $code,
        ReportVerificationService $service
    ): View {
        $verification =
            $service->findPublic(
                strtolower(
                    $code
                )
            );

        return view(
            'reports.verify',
            [
                'verification' => $verification,

                'status' => $verification
                    ->publicStatus(),
            ]
        );
    }
}
