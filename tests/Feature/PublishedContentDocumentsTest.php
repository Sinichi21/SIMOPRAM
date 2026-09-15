<?php

use App\Livewire\Admin\PublicContent;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Letter;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\User;
use App\Services\ContentMediaService;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function reusableContentDocument(School $school): ReportVerification
{
    app(SchoolContext::class)->set($school);
    $service = app(ReportVerificationService::class);
    $document = $service->issueDocument(
        schoolId: $school->id, documentType: 'letter', sourceType: Letter::class, sourceId: 999,
        snapshotChecksum: str_repeat('a', 64), title: 'Undangan Lomba',
        metadata: ['security_classification' => 'Biasa'],
    );
    $document = $service->archivePdf($document, '%PDF-1.4 original', 'undangan.pdf');
    app(SchoolContext::class)->clear();

    return $document;
}

test('agenda and announcements reference issued PDFs without copying the archive', function (string $kind) {
    Storage::fake('local');
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $this->actingAs($admin);
    $document = reusableContentDocument(School::factory()->create());
    $before = Storage::disk('local')->allFiles();
    $component = Livewire::test(PublicContent::class, ['kind' => $kind])
        ->set('title', 'Informasi Lomba')->set('body', 'Informasi umum kegiatan')
        ->set('status', 'published')->set('publishedDocumentIds', [$document->id]);
    if ($kind === 'activities') {
        $component->set('startsAt', now()->format('Y-m-d\\TH:i'))->set('endsAt', now()->addDay()->format('Y-m-d\\TH:i'));
    }
    $component->call('save')->assertHasNoErrors();
    $record = $kind === 'activities' ? Activity::withoutGlobalScope('school')->firstOrFail() : Announcement::withoutGlobalScope('school')->firstOrFail();
    expect($record->attachments[0]['document_id'])->toBe($document->id)
        ->and($record->attachments[0])->not->toHaveKey('path')
        ->and(Storage::disk('local')->allFiles())->toBe($before);
    $url = route('public.content.attachment', [$kind, $record->id, 0]);
    $this->get($url)->assertOk()->assertContent('%PDF-1.4 original');
    $this->get($url.'?download=1')->assertDownload('undangan.pdf');
    $document->update(['revoked_at' => now()]);
    $this->get($url)->assertNotFound();
    app(ContentMediaService::class)->save($record, removeIndexes: [0]);
    expect($record->fresh()->attachments)->toBe([]);
    Storage::disk('local')->assertExists($document->file_path);
})->with(['activities', 'announcements']);

test('restricted unapproved and foreign school archives cannot be attached', function (string $case) {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $school = School::factory()->create();
    $document = reusableContentDocument($school);
    $activity = Activity::factory()->create(['school_id' => $school->id]);
    match ($case) {
        'restricted' => $document->update(['metadata' => ['security_classification' => 'Rahasia']]),
        'pending' => $document->forceFill(['required_signatories' => [['user_id' => auth()->id()]]])->save(),
        'foreign' => $activity->forceFill(['school_id' => School::factory()->create()->id])->save(),
        'unauthorized' => $this->actingAs(User::factory()->create()),
    };
    expect(fn () => app(ContentMediaService::class)->save($activity, documentIds: [$document->id]))->toThrow(ValidationException::class);
    expect($activity->fresh()->attachments ?? [])->toBe([]);
})->with(['restricted', 'pending', 'foreign', 'unauthorized']);

test('public document references reject changed archive bytes and duplicate selections do not multiply attachments', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $document = reusableContentDocument(School::factory()->create());
    $activity = Activity::factory()->publicRegistration()->create();
    app(ContentMediaService::class)->save($activity, documentIds: [$document->id]);
    app(ContentMediaService::class)->save($activity, documentIds: [$document->id]);
    expect($activity->fresh()->attachments)->toHaveCount(1);
    Storage::disk('local')->put($document->file_path, '%PDF altered');
    $this->get(route('public.content.attachment', ['activities', $activity->id, 0]))->assertNotFound();
});
