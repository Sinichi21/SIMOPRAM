<?php

declare(strict_types=1);

use App\Models\InventoryLoanEvent;
use App\Models\InventoryLoanEvidence;
use App\Models\School;
use App\Services\Inventory\InventoryLoanEvidenceThumbnailService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('rejects modified evidence before embedding it into a document', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);

    $event = new InventoryLoanEvent;
    $event->id = 10;
    $photo = new InventoryLoanEvidence;
    $photo->id = 20;
    $photo->disk = 'local';
    $photo->path = 'inventory/loan-evidence/test.jpg';
    $photo->sha256 = hash('sha256', 'original');
    Storage::disk('local')->put($photo->path, 'tampered');
    $event->setRelation('evidence', collect([$photo]));

    expect(fn () => app(InventoryLoanEvidenceThumbnailService::class)->forEvents(collect([$event])))
        ->toThrow(ValidationException::class);
});

it('creates a bounded JPEG thumbnail from a verified private photo', function () {
    if (! extension_loaded('gd')) {
        test()->markTestSkipped('GD is required to verify generated thumbnails.');
    }

    Storage::fake('local');
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);

    $image = imagecreatetruecolor(900, 600);
    try {
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 80, 140));
        ob_start();
        imagejpeg($image, null, 85);
        $bytes = ob_get_clean();
    } finally {
        imagedestroy($image);
    }

    expect($bytes)->toBeString();
    $path = 'inventory/loan-evidence/thumbnail-test.jpg';
    Storage::disk('local')->put($path, $bytes);

    $photo = new InventoryLoanEvidence;
    $photo->id = 21;
    $photo->disk = 'local';
    $photo->path = $path;
    $photo->sha256 = hash('sha256', $bytes);

    $event = new InventoryLoanEvent;
    $event->id = 11;
    $event->setRelation('evidence', collect([$photo]));

    $thumbnails = app(InventoryLoanEvidenceThumbnailService::class)->forEvents(collect([$event]));
    expect($thumbnails)->toHaveKey(21);
    expect($thumbnails[21])->toStartWith('data:image/jpeg;base64,');

    $thumbnailBytes = base64_decode(substr($thumbnails[21], strlen('data:image/jpeg;base64,')), true);
    expect($thumbnailBytes)->toBeString();
    $dimensions = getimagesizefromstring($thumbnailBytes);
    expect($dimensions)->not->toBeFalse();
    expect($dimensions[0])->toBeLessThanOrEqual(360);
    expect($dimensions[1])->toBeLessThanOrEqual(240);
    expect(strlen($thumbnailBytes))->toBeLessThan(strlen($bytes));
});
