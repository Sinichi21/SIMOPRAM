<?php

use Illuminate\Support\Facades\Route;

test('HTTP errors show branded guidance without internal exception details', function (int $status, string $heading) {
    config(['app.debug' => false]);
    Route::get('/__test-error', function () use ($status): never {
        abort($status, 'Private internal exception detail');
    });

    $response = $this->get('/__test-error');

    $response->assertStatus($status)
        ->assertSee($heading)
        ->assertSee('SIMPRAM')
        ->assertSee('Kembali ke beranda')
        ->assertSee(route('home'))
        ->assertDontSee('Private internal exception detail');

    if (in_array($status, [401, 419], true)) {
        $response->assertSee('Masuk kembali')->assertSee(route('login'));
    } else {
        $response->assertSee('Hubungi pengelola');
    }
})->with([
    [401, 'Silakan masuk terlebih dahulu.'],
    [403, 'Akses belum tersedia.'],
    [404, 'Sepertinya salah arah.'],
    [405, 'Halaman belum dapat dibuka.'],
    [419, 'Sesi Anda telah berakhir.'],
    [429, 'Istirahat sejenak, yuk.'],
    [500, 'Ada kendala di sistem.'],
    [502, 'Koneksi sedang terkendala.'],
    [503, 'Kami akan segera kembali.'],
    [504, 'Koneksi sedang terkendala.'],
    [507, 'Layanan sedang terkendala.'],
]);

test('unknown URLs use the custom not found page', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertSee('Sepertinya salah arah.');
});

test('unexpected exceptions use the custom server error page when debug is disabled', function () {
    config(['app.debug' => false]);
    Route::get('/__test-server-error', function (): never {
        throw new RuntimeException('Private database connection details');
    });

    $this->get('/__test-server-error')
        ->assertInternalServerError()
        ->assertSee('Ada kendala di sistem.')
        ->assertDontSee('Private database connection details');
});
