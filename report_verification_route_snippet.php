<?php

// Tambahkan import ini di bagian atas routes/web.php:
// use App\Http\Controllers\ReportVerificationController;

// Tempatkan route berikut DI LUAR group auth / school / school.required.
Route::get(
    '/verify/report/{code}',
    [
        ReportVerificationController::class,
        'show',
    ]
)
    ->where('code', '[a-f0-9]{48}')
    ->middleware('throttle:60,1')
    ->name('reports.verify');
