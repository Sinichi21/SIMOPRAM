<?php

use Illuminate\Support\Facades\Route;

test('public report verification route is registered', function (): void {
    expect(Route::has('reports.verify'))->toBeTrue();

    $url = route('reports.verify', [
        'code' => str_repeat('a', 48),
    ]);

    expect($url)->toContain('/verify/report/'.str_repeat('a', 48));
});
