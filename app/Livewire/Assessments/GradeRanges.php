<?php

namespace App\Livewire\Assessments;

use App\Models\GradeScaleConfig;
use App\Models\School;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GradeRanges extends Component
{
    #[Locked]
    public int $schoolId;

    public array $ranges = [];

    public function mount(): void
    {
        $this->schoolId = app(SchoolContext::class)->id() ?? 0;
        $this->authorizeSchool();
        $config = GradeScaleConfig::query()->where('is_active', true)->with('scales')->first();
        $this->ranges = $config?->scales->map(fn ($scale) => $scale->only(['letter_grade', 'min_score', 'max_score', 'description']))->all() ?? [
            ['letter_grade' => 'A', 'min_score' => 90, 'max_score' => 100, 'description' => 'Sangat baik'],
            ['letter_grade' => 'B', 'min_score' => 80, 'max_score' => 89.99, 'description' => 'Baik'],
            ['letter_grade' => 'C', 'min_score' => 70, 'max_score' => 79.99, 'description' => 'Cukup'],
            ['letter_grade' => 'D', 'min_score' => 0, 'max_score' => 69.99, 'description' => 'Perlu bimbingan'],
        ];
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
        $validated = $this->validate([
            'ranges' => ['required', 'array', 'min:1', 'max:20'],
            'ranges.*.letter_grade' => ['required', 'string', 'max:10', 'distinct'],
            'ranges.*.min_score' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'ranges.*.max_score' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'ranges.*.description' => ['required', 'string', 'max:2000'],
        ]);
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
        DB::transaction(function () use ($ranges): void {
            School::query()->lockForUpdate()->findOrFail($this->schoolId);
            $config = GradeScaleConfig::query()->where('is_active', true)->firstOrCreate([], ['name' => 'Predikat Nilai', 'is_active' => true]);
            $config->scales()->delete();
            foreach ($ranges as $index => $range) {
                $config->scales()->create([...$range, 'sort_order' => $index]);
            }
        });
        session()->flash('grade-ranges', 'Rentang dan saran deskripsi disimpan. Hitung ulang nilai untuk menerapkan perubahan pada nilai yang sudah ada.');
    }

    public function render(): View
    {
        $this->authorizeSchool();

        return view('livewire.assessments.grade-ranges');
    }
}
