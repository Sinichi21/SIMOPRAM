<?php

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
