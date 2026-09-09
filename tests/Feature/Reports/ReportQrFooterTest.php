<?php

use App\Models\ReportVerification;

test('shared report verification footer renders qr and code', function (): void {
    $verification = new ReportVerification([
        'code' => str_repeat('a', 48),
    ]);

    $html = view('reports.pdf.partials.verification-footer', [
        'verification' => $verification,
        'verificationQrDataUri' => 'data:image/png;base64,TEST',
    ])->render();

    expect($html)
        ->toContain('Verifikasi Dokumen SIMPRAM')
        ->toContain(str_repeat('a', 48))
        ->toContain('position: fixed');
});
