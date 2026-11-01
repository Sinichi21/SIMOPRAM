<?php

use App\Models\Coach;
use App\Models\DocumentSignatoryProfile;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Services\DocumentSignatoryService;
use App\Support\SchoolContext;

test('any active school user can become document signatory', function (): void {
    $school = School::factory()->create();
    $user = User::factory()->create([
        'name' => 'Pejabat Contoh',
        'is_active' => true,
    ]);

    SchoolUserMembership::query()->create([
        'school_id' => $school->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);

    app(SchoolContext::class)->set($school);

    DocumentSignatoryProfile::query()->create([
        'school_id' => $school->id,
        'user_id' => $user->id,
        'position' => 'Ketua Panitia',
        'identifier_type' => 'NTA',
        'identifier_number' => '22.09.03.001',
        'is_active' => true,
    ]);

    $resolved = app(DocumentSignatoryService::class)->resolve($user->id, $school->id);

    expect($resolved['name'])->toBe('Pejabat Contoh')
        ->and($resolved['position'])->toBe('Ketua Panitia')
        ->and($resolved['identity'])->toBe('NTA. 22.09.03.001');
});

test('document signatory profile is school specific', function (): void {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    $user = User::factory()->create(['is_active' => true]);

    foreach ([$schoolA, $schoolB] as $school) {
        SchoolUserMembership::query()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
    }

    DocumentSignatoryProfile::query()->create([
        'school_id' => $schoolA->id,
        'user_id' => $user->id,
        'position' => 'Pembina Gugusdepan',
        'identifier_type' => 'NTA',
        'identifier_number' => 'A-001',
        'is_active' => true,
    ]);

    DocumentSignatoryProfile::query()->create([
        'school_id' => $schoolB->id,
        'user_id' => $user->id,
        'position' => 'Koordinator',
        'identifier_type' => 'NIP',
        'identifier_number' => 'B-002',
        'is_active' => true,
    ]);

    app(SchoolContext::class)->set($schoolA);
    $a = app(DocumentSignatoryService::class)->resolve($user->id, $schoolA->id);

    app(SchoolContext::class)->set($schoolB);
    $b = app(DocumentSignatoryService::class)->resolve($user->id, $schoolB->id);

    expect($a['position'])->toBe('Pembina Gugusdepan')
        ->and($a['identity'])->toBe('NTA. A-001')
        ->and($b['position'])->toBe('Koordinator')
        ->and($b['identity'])->toBe('NIP. B-002');
});

test('user identity prefers NTA then NIP and leaves missing identifiers blank', function (?string $coachNta, ?string $profileType, ?string $profileNumber, string $expectedType, string $expectedNumber, string $expectedIdentity): void {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create();
    SchoolUserMembership::query()->create(['school_id' => $school->id, 'user_id' => $user->id, 'is_active' => true]);
    if ($coachNta !== null) {
        Coach::query()->create(['user_id' => $user->id, 'name' => $user->name, 'nip' => $coachNta, 'is_active' => true]);
    }
    if ($profileType !== null) {
        DocumentSignatoryProfile::query()->create([
            'user_id' => $user->id, 'identifier_type' => $profileType,
            'identifier_number' => $profileNumber, 'is_active' => true,
        ]);
    }

    $resolved = app(DocumentSignatoryService::class)->resolve($user->id, $school->id);

    expect($resolved['identifier_type'])->toBe($expectedType);
    expect($resolved['identifier_number'])->toBe($expectedNumber);
    expect($resolved['identity'])->toBe($expectedIdentity);
})->with([
    'coach NTA before profile NIP' => ['00123', 'NIP', '00999', 'NTA', '00123', 'NTA. 00123'],
    'profile NTA' => [null, 'NTA', '00456', 'NTA', '00456', 'NTA. 00456'],
    'explicit NTA before legacy NTA' => ['00123', 'NTA', '00456', 'NTA', '00456', 'NTA. 00456'],
    'coach NTA only' => ['00123', null, null, 'NTA', '00123', 'NTA. 00123'],
    'NIP when coach NTA blank' => ['   ', 'NIP', '00999', 'NIP', '00999', 'NIP. 00999'],
    'NIP without coach' => [null, 'NIP', '00999', 'NIP', '00999', 'NIP. 00999'],
    'no identifiers' => [null, null, null, '', '', ''],
    'empty NTA and NIP' => ['   ', 'NIP', '   ', '', '', ''],
]);
