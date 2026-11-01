<?php

declare(strict_types=1);

use App\Models\InventoryLoan;
use App\Models\InventoryLoanEvent;
use App\Models\InventoryLoanEvidence;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores a private transaction photo and serves it only within its school', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);

    $loan = InventoryLoan::query()->create([
        'loan_number' => 'EVIDENCE-001',
        'borrower_type' => 'manual_external',
        'borrower_name' => 'Peminjam',
        'requested_at' => now(),
        'status' => 'handed_over',
    ]);
    $event = InventoryLoanEvent::query()->create([
        'loan_id' => $loan->id,
        'event_type' => 'handover',
        'occurred_at' => now(),
        'recorded_at' => now(),
        'recorded_by' => auth()->id(),
        'recording_method' => 'online',
    ]);

    $this->post(route('inventory.loans.evidence.store', $event->id), [
        'photo' => UploadedFile::fake()->image('kondisi.jpg'),
    ])->assertRedirect();

    // Five photos are allowed for one event; the sixth must be rejected.
    for ($index = 2; $index <= 5; $index++) {
        $this->post(route('inventory.loans.evidence.store', $event->id), [
            'photo' => UploadedFile::fake()->image('kondisi-'.$index.'.jpg'),
        ])->assertRedirect();
    }
    $this->post(route('inventory.loans.evidence.store', $event->id), [
        'photo' => UploadedFile::fake()->image('kondisi-keenam.jpg'),
    ])->assertSessionHasErrors('photo');
    expect(InventoryLoanEvidence::query()->where('loan_event_id', $event->id)->count())->toBe(5);

    $record = InventoryLoanEvidence::query()->firstOrFail();
    expect($record->school_id)->toBe($school->id);
    Storage::disk('local')->assertExists($record->path);
    $response = $this->get(route('inventory.loans.evidence.show', $record->id))->assertOk();
    $cacheDirectives = array_map('trim', explode(',', $response->headers->get('Cache-Control', '')));
    expect($cacheDirectives)->toContain('private', 'no-store');

    Storage::disk('local')->put($record->path, 'tampered');
    $this->get(route('inventory.loans.evidence.show', $record->id))->assertStatus(409);

    $otherSchool = School::factory()->create();
    app(SchoolContext::class)->set($otherSchool);
    $this->withSession(['active_school_id' => $otherSchool->id])
        ->get(route('inventory.loans.evidence.show', $record->id))->assertNotFound();
});
