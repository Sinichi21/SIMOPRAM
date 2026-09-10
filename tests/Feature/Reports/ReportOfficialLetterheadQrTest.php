<?php

use App\Models\ReportVerification;

test('grade pdf template uses official letterhead and shared verification footer', function (): void {
    $template = file_get_contents(resource_path('views/reports/pdf/grades.blade.php'));

    expect($template)
        ->toContain("@include('reports.pdf.lpj.partials.letterhead')")
        ->toContain("@include('reports.pdf.partials.verification-footer')")
        ->toContain('font-size: 14pt !important')
        ->not->toContain('<table class="verification-box">');
});

test('attendance pdf template uses official letterhead and shared verification footer', function (): void {
    $template = file_get_contents(resource_path('views/reports/pdf/attendance.blade.php'));

    expect($template)
        ->toContain("@include('reports.pdf.lpj.partials.letterhead')")
        ->toContain("@include('reports.pdf.partials.verification-footer')")
        ->toContain('font-size: 14pt !important');
});

test('verification footer can render a grade verification code', function (): void {
    $verification = new ReportVerification([
        'code' => str_repeat('b', 48),
    ]);

    $html = view('reports.pdf.partials.verification-footer', [
        'verification' => $verification,
        'verificationQrDataUri' => 'data:image/png;base64,TEST',
    ])->render();

    expect($html)
        ->toContain('Verifikasi Dokumen SIMPRAM')
        ->toContain(str_repeat('b', 48));
});
