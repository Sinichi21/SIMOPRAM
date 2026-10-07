<?php

use App\Services\LpjReportService;

test('LPJ PDF embeds a bounded preview instead of the original photograph', function () {
    if (! extension_loaded('gd')) {
        test()->markTestSkipped('GD extension is required to generate PDF previews.');
    }

    $file = tempnam(sys_get_temp_dir(), 'simopram-lpj-preview-');
    $image = imagecreatetruecolor(2200, 1400);

    try {
        for ($y = 0; $y < 1400; $y += 20) {
            $color = imagecolorallocate($image, $y % 255, ($y * 3) % 255, ($y * 7) % 255);
            imagefilledrectangle($image, 0, $y, 2199, min(1399, $y + 19), $color);
        }

        imagejpeg($image, $file, 95);
        $originalHash = hash_file('sha256', $file);

        $method = new ReflectionMethod(LpjReportService::class, 'imageDataUri');
        $dataUri = $method->invoke(app(LpjReportService::class), $file, 'image/jpeg');

        expect($dataUri)->toStartWith('data:image/jpeg;base64,');

        $encoded = substr($dataUri, strlen('data:image/jpeg;base64,'));
        $preview = base64_decode($encoded, true);
        $dimensions = getimagesizefromstring($preview);

        expect(max($dimensions[0], $dimensions[1]))->toBeLessThanOrEqual(1100)
            ->and(strlen($preview))->toBeLessThanOrEqual(300 * 1024)
            ->and(hash_file('sha256', $file))->toBe($originalHash);
    } finally {
        imagedestroy($image);
        @unlink($file);
    }
});
