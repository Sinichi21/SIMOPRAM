<?php

declare(strict_types=1);

use App\Models\InventoryLoanEvidence;
use App\Models\InventoryLoanEvent;
use App\Models\School;
use App\Support\SchoolContext;
use App\Services\Inventory\InventoryLoanEvidenceThumbnailService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('rejects modified evidence before embedding it into a document', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);

    $event = new InventoryLoanEvent();
    $event->id = 10;
    $photo = new InventoryLoanEvidence();
    $photo->id = 20;
    $photo->disk = 'local';
    $photo->path = 'inventory/loan-evidence/test.jpg';
    $photo->sha256 = hash('sha256', 'original');
    Storage::disk('local')->put($photo->path, 'tampered');
    $event->setRelation('evidence', collect([$photo]));

    expect(fn () => app(InventoryLoanEvidenceThumbnailService::class)->forEvents(collect([$event])))
        ->toThrow(ValidationException::class);
});
