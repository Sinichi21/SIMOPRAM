<?php

use App\Livewire\Assessments\GradeRanges;
use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\GradeScaleConfig;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
});

test('configuration creation requires a valid level and opens ranges only on edit', function () {
    $level = ScoutLevel::create(['code' => 'siaga', 'name' => 'Siaga']);
    $component = Livewire::test(GradeRanges::class)->assertDontSee('Nilai minimum');
    $component->call('createConfig')->assertHasErrors(['scoutLevelId', 'configName']);
    $component->set('scoutLevelId', 999999)->set('configName', 'Siaga baru')->call('createConfig')->assertHasErrors('scoutLevelId');
    $component->set('scoutLevelId', $level->id)->call('createConfig')->assertHasNoErrors()->assertSee('Siaga baru')->assertDontSee('Nilai minimum');
    $config = GradeScaleConfig::sole();
    expect($config->scout_level_id)->toBe($level->id)->and($config->is_active)->toBeFalse();
    $component->call('editConfig', $config->id)->assertSee('Nilai minimum')
        ->set('ranges.0.description', str_repeat('Latihan ', 50))->call('save')->assertHasNoErrors();
    expect($config->scales()->where('letter_grade', 'A')->first()->description)->toBe(str_repeat('Latihan ', 50));
});

test('activation replaces only the same level and supports deactivation and deletion', function () {
    $siaga = ScoutLevel::create(['code' => 'siaga', 'name' => 'Siaga']);
    $penggalang = ScoutLevel::create(['code' => 'penggalang', 'name' => 'Penggalang']);
    $first = GradeScaleConfig::create(['name' => 'Siaga lama', 'scout_level_id' => $siaga->id, 'is_active' => true]);
    $second = GradeScaleConfig::create(['name' => 'Siaga baru', 'scout_level_id' => $siaga->id, 'is_active' => false]);
    $second->scales()->create(['letter_grade' => 'A', 'min_score' => 0, 'max_score' => 100, 'description' => 'Siaga']);
    $other = GradeScaleConfig::create(['name' => 'Penggalang', 'scout_level_id' => $penggalang->id, 'is_active' => true]);

    $component = Livewire::test(GradeRanges::class)->call('toggleConfig', $second->id)->assertHasNoErrors();
    expect($first->fresh()->is_active)->toBeFalse()->and($second->fresh()->is_active)->toBeTrue()->and($other->fresh()->is_active)->toBeTrue();
    $component->call('toggleConfig', $second->id)->assertHasNoErrors();
    expect($second->fresh()->is_active)->toBeFalse();
    $component->call('editConfig', $second->id)->call('deleteConfig', $second->id)->assertSet('editingConfigId', null);
    $this->assertSoftDeleted($second);
});

test('configuration actions cannot access another tenant', function (string $action) {
    $foreign = new GradeScaleConfig(['name' => 'Rahasia', 'is_active' => true]);
    $foreign->school_id = School::factory()->create()->id;
    $foreign->save();

    expect(fn () => Livewire::test(GradeRanges::class)->call($action, $foreign->id))
        ->toThrow(ModelNotFoundException::class);
    $this->assertDatabaseHas('grade_scale_configs', ['id' => $foreign->id, 'is_active' => true, 'deleted_at' => null]);
})->with(['editConfig', 'toggleConfig', 'deleteConfig']);

test('save refuses an unselected configuration', function () {
    Livewire::test(GradeRanges::class)->call('save')->assertStatus(422);
    $this->assertDatabaseCount('grade_scale_configs', 0);
});

test('grades use the students level in the assessment year and preserve manual descriptions', function () {
    $schoolId = app(SchoolContext::class)->id();
    $year = AcademicYear::factory()->create(['school_id' => $schoolId]);
    $anotherYear = AcademicYear::factory()->create(['school_id' => $schoolId]);
    $assessment = AssessmentConfig::create(['name' => 'Penilaian', 'academic_year_id' => $year->id]);
    $siaga = ScoutLevel::create(['code' => 'siaga', 'name' => 'Siaga']);
    $penggalang = ScoutLevel::create(['code' => 'penggalang', 'name' => 'Penggalang']);
    $student = Student::factory()->create(['school_id' => $schoolId]);
    $student->scoutLevelHistories()->create(['scout_level_id' => $siaga->id, 'academic_year_id' => $year->id, 'is_active' => true, 'started_at' => now()]);
    $student->scoutLevelHistories()->create(['scout_level_id' => $penggalang->id, 'academic_year_id' => $anotherYear->id, 'is_active' => true, 'started_at' => now()]);
    foreach ([$siaga, $penggalang] as $level) {
        $config = GradeScaleConfig::create(['name' => $level->name, 'scout_level_id' => $level->id, 'is_active' => true]);
        $config->scales()->create(['letter_grade' => 'A', 'min_score' => 0, 'max_score' => 100, 'description' => 'Deskripsi '.$level->name]);
    }

    $service = app(AssessmentService::class);
    $grade = $service->calculateFinalGrade($assessment, $student);
    expect($grade->description)->toBe('Deskripsi Siaga');
    $grade->update(['manual_description' => 'Catatan khusus siswa']);
    expect($service->calculateFinalGrade($assessment, $student)->description)->toBe('Catatan khusus siswa');
});

test('missing or inactive level configuration never borrows from another level', function (bool $legacy, bool $inactive) {
    $schoolId = app(SchoolContext::class)->id();
    $year = AcademicYear::factory()->create(['school_id' => $schoolId]);
    $assessment = AssessmentConfig::create(['name' => 'Penilaian', 'academic_year_id' => $year->id]);
    $student = Student::factory()->create(['school_id' => $schoolId]);
    $level = ScoutLevel::create(['code' => 'siaga', 'name' => 'Siaga']);
    $config = GradeScaleConfig::create(['name' => 'Siaga', 'scout_level_id' => $level->id, 'is_active' => ! $inactive]);
    if ($inactive) {
        $student->scoutLevelHistories()->create(['scout_level_id' => $level->id, 'academic_year_id' => $year->id, 'is_active' => true, 'started_at' => now()]);
    }
    $config->scales()->create(['letter_grade' => 'A', 'min_score' => 0, 'max_score' => 100, 'description' => 'Khusus Siaga']);
    if ($legacy) {
        $general = GradeScaleConfig::create(['name' => 'Umum', 'is_active' => true]);
        $general->scales()->create(['letter_grade' => 'B', 'min_score' => 0, 'max_score' => 100, 'description' => 'Umum lama']);
    }

    expect(app(AssessmentService::class)->calculateFinalGrade($assessment, $student)->description)->toBe($legacy ? 'Umum lama' : null);
})->with([[true, false], [false, false], [true, true], [false, true]]);

test('empty configurations cannot be activated', function () {
    $config = GradeScaleConfig::create(['name' => 'Belum lengkap', 'is_active' => false]);

    Livewire::test(GradeRanges::class)->call('toggleConfig', $config->id)->assertHasErrors('ranges');
    expect($config->fresh()->is_active)->toBeFalse();
});

test('configuration mutations recheck management permissions', function (string $action) {
    $config = GradeScaleConfig::create(['name' => 'Dilindungi', 'is_active' => true]);
    $component = Livewire::test(GradeRanges::class);
    auth()->user()->update(['system_role' => 'student']);

    $component->call($action, $config->id)->assertForbidden();
    expect($config->fresh()->is_active)->toBeTrue()->and($config->fresh()->deleted_at)->toBeNull();
})->with(['editConfig', 'toggleConfig', 'deleteConfig']);

test('range edits and activation invalidate calculated grade signatures', function () {
    $year = AcademicYear::factory()->create(['school_id' => app(SchoolContext::class)->id()]);
    $assessment = AssessmentConfig::create(['name' => 'Penilaian', 'academic_year_id' => $year->id]);
    $level = ScoutLevel::create(['code' => 'siaga', 'name' => 'Siaga']);
    $config = GradeScaleConfig::create(['name' => 'Siaga', 'scout_level_id' => $level->id, 'is_active' => true]);
    $scale = $config->scales()->create(['letter_grade' => 'A', 'min_score' => 0, 'max_score' => 100, 'description' => 'Awal']);
    $service = app(AssessmentService::class);
    $signature = $service->configurationSignature($assessment);

    $scale->update(['description' => 'Deskripsi baru']);
    expect($service->configurationSignature($assessment))->not->toBe($signature);
    $signature = $service->configurationSignature($assessment);
    $config->update(['is_active' => false]);
    expect($service->configurationSignature($assessment))->not->toBe($signature);
});
