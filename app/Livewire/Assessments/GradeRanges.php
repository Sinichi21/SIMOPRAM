<?php

namespace App\Livewire\Assessments;

use App\Models\GradeScaleConfig;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GradeRanges extends Component
{
    #[Locked]
    public int $schoolId;

    public array $ranges = [];

    #[Locked]
    public ?int $editingConfigId = null;

    public ?int $scoutLevelId = null;

    public string $configName = '';

    public function mount(): void
    {
        $this->schoolId = app(SchoolContext::class)->id() ?? 0;
        $this->authorizeSchool();
    }

    protected function defaultRanges(): array
    {
        return [
            ['letter_grade' => 'A', 'min_score' => 90, 'max_score' => 100, 'description' => 'Sangat baik'],
            ['letter_grade' => 'B', 'min_score' => 80, 'max_score' => 89.99, 'description' => 'Baik'],
            ['letter_grade' => 'C', 'min_score' => 70, 'max_score' => 79.99, 'description' => 'Cukup'],
            ['letter_grade' => 'D', 'min_score' => 0, 'max_score' => 69.99, 'description' => 'Perlu bimbingan'],
        ];
    }

    public function createConfig(): void
    {
        $this->authorizeSchool();
        $this->validate([
            'scoutLevelId' => ['required', 'integer', 'exists:scout_levels,id'],
            'configName' => ['required', 'string', 'max:150'],
        ]);
        $config = DB::transaction(function (): GradeScaleConfig {
            School::query()->lockForUpdate()->findOrFail($this->schoolId);
            $config = GradeScaleConfig::create(['name' => trim($this->configName), 'scout_level_id' => $this->scoutLevelId, 'is_active' => false]);
            foreach ($this->defaultRanges() as $index => $range) {
                $config->scales()->create([...$range, 'sort_order' => $index]);
            }

            return $config;
        });
        $this->reset('scoutLevelId', 'configName');
        session()->flash('grade-ranges', 'Konfigurasi dibuat. Klik Edit untuk menyesuaikan rentang dan deskripsi, kemudian aktifkan.');
    }

    public function editConfig(int $id): void
    {
        $this->authorizeSchool();
        $config = GradeScaleConfig::with('scales')->findOrFail($id);
        $this->editingConfigId = $config->id;
        $this->ranges = $config->scales->map(fn ($scale) => $scale->only(['letter_grade', 'min_score', 'max_score', 'description']))->all();
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingConfigId', 'ranges');
        $this->resetValidation();
    }

    public function toggleConfig(int $id): void
    {
        $this->authorizeSchool();
        DB::transaction(function () use ($id): void {
            School::query()->lockForUpdate()->findOrFail($this->schoolId);
            $config = GradeScaleConfig::findOrFail($id);
            if (! $config->is_active) {
                $this->validatedRanges($config->scales->map(fn ($scale) => $scale->only(['letter_grade', 'min_score', 'max_score', 'description']))->all());
                GradeScaleConfig::where('scout_level_id', $config->scout_level_id)->update(['is_active' => false]);
            }
            $config->update(['is_active' => ! $config->is_active]);
        });
        session()->flash('grade-ranges', 'Status konfigurasi diperbarui. Hitung ulang nilai untuk menerapkan perubahan.');
    }

    public function deleteConfig(int $id): void
    {
        $this->authorizeSchool();
        DB::transaction(function () use ($id): void {
            School::query()->lockForUpdate()->findOrFail($this->schoolId);
            GradeScaleConfig::findOrFail($id)->delete();
        });
        if ($this->editingConfigId === $id) {
            $this->cancelEdit();
        }
        session()->flash('grade-ranges', 'Konfigurasi dihapus. Nilai yang sudah tersimpan tidak diubah.');
    }

    protected function authorizeSchool(): void
    {
        abort_unless(auth()->user()?->can('assessments.manage'), 403);
        abort_unless($this->schoolId && $this->schoolId === app(SchoolContext::class)->id(), 409);
    }

    public function addRange(): void
    {
        $this->authorizeSchool();
        if (count($this->ranges) < 20) {
            $this->ranges[] = ['letter_grade' => '', 'min_score' => 0, 'max_score' => 0, 'description' => ''];
        }
    }

    public function removeRange(int $index): void
    {
        $this->authorizeSchool();
        unset($this->ranges[$index]);
        $this->ranges = array_values($this->ranges);
    }

    public function save(): void
    {
        $this->authorizeSchool();
        abort_unless($this->editingConfigId, 422, 'Pilih konfigurasi yang akan diedit.');
        GradeScaleConfig::findOrFail($this->editingConfigId);
        $ranges = $this->validatedRanges($this->ranges);
        DB::transaction(function () use ($ranges): void {
            School::query()->lockForUpdate()->findOrFail($this->schoolId);
            $config = GradeScaleConfig::findOrFail($this->editingConfigId);
            $config->scales()->delete();
            foreach ($ranges as $index => $range) {
                $config->scales()->create([...$range, 'sort_order' => $index]);
            }
        });
        session()->flash('grade-ranges', 'Rentang dan saran deskripsi disimpan. Hitung ulang nilai untuk menerapkan perubahan pada nilai yang sudah ada.');
    }

    protected function validatedRanges(array $input): Collection
    {
        $validated = Validator::make(['ranges' => $input], [
            'ranges' => ['required', 'array', 'min:1', 'max:20'],
            'ranges.*.letter_grade' => ['required', 'string', 'max:10', 'distinct'],
            'ranges.*.min_score' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'ranges.*.max_score' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'ranges.*.description' => ['required', 'string', 'max:2000'],
        ])->validate();
        $ranges = collect($validated['ranges'])->sortBy('min_score')->values();
        $next = 0;
        foreach ($ranges as $range) {
            $min = (int) round((float) $range['min_score'] * 100);
            $max = (int) round((float) $range['max_score'] * 100);
            if ($min !== $next || $max < $min) {
                throw ValidationException::withMessages(['ranges' => 'Rentang harus mencakup 0–100 tanpa tumpang tindih atau celah. Contoh: 0–79,99 lalu 80–100.']);
            }
            $next = $max + 1;
        }
        if ($next !== 10001) {
            throw ValidationException::withMessages(['ranges' => 'Rentang harus berakhir pada nilai 100.']);
        }

        return $ranges;
    }

    public function render(): View
    {
        $this->authorizeSchool();

        return view('livewire.assessments.grade-ranges', [
            'configs' => GradeScaleConfig::with('scoutLevel')->withCount('scales')->orderBy('scout_level_id')->latest('id')->get(),
            'scoutLevels' => ScoutLevel::orderBy('sort_order')->get(),
            'editingConfig' => $this->editingConfigId ? GradeScaleConfig::with('scoutLevel')->findOrFail($this->editingConfigId) : null,
        ]);
    }
}
