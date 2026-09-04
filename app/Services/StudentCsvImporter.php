<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ScoutLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentScoutLevel;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use SplFileObject;

class StudentCsvImporter
{
    /** @return array{imported: int, failed: int, errors: array<int, string>} */
    public function import(string $path): array
    {
        $schoolId = app(SchoolContext::class)->id();

        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($this->detectDelimiter($path));

        $headers = null;
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($file as $index => $values) {
            if ($values === false || $values === [null]) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(
                    fn ($header): string => $this->normalizeHeader((string) $header),
                    $values
                );

                $this->validateHeaders($headers);

                continue;
            }

            if ($index > 5000) {
                $errors[] = 'Impor dibatasi maksimal 5.000 baris.';

                break;
            }

            $row = array_combine(
                $headers,
                array_pad($values, count($headers), null)
            );

            if ($row === false || collect($row)->filter(fn ($value) => filled($value))->isEmpty()) {
                continue;
            }

            try {
                $this->importRow($row, $schoolId);
                $imported++;
            } catch (\Throwable $exception) {
                $failed++;
                $errors[] = 'Baris '.($index + 1).': '.$this->errorMessage($exception);
            }
        }

        return compact('imported', 'failed', 'errors');
    }

    /** @param array<string, mixed> $row */
    private function importRow(array $row, int $schoolId): void
    {
        $data = [
            'nis' => $this->nullable($row['nis'] ?? null),
            'nisn' => $this->nullable($row['nisn'] ?? null),
            'name' => $this->nullable($row['nama'] ?? null),
            'gender' => $this->normalizeGender($row['jenis_kelamin'] ?? null),
            'birth_place' => $this->nullable($row['tempat_lahir'] ?? null),
            'birth_date' => $this->nullable($row['tanggal_lahir'] ?? null),
            'phone' => $this->nullable($row['telepon'] ?? null),
            'parent_phone' => $this->nullable($row['telepon_orang_tua'] ?? null),
            'address' => $this->nullable($row['alamat'] ?? null),
            'joined_at' => $this->nullable($row['tanggal_masuk'] ?? null),
            'status' => Str::lower($this->nullable($row['status'] ?? null) ?? 'active'),
            'academic_year' => $this->nullable($row['tahun_ajaran'] ?? null),
            'classroom' => $this->nullable($row['kelas'] ?? null),
            'scout_level' => $this->nullable($row['golongan'] ?? null),
        ];

        Validator::make($data, [
            'nis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'nis')->where(
                    fn ($query) => $query->where('school_id', $schoolId)
                ),
            ],
            'nisn' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students', 'nisn')->where(
                    fn ($query) => $query->where('school_id', $schoolId)
                ),
            ],
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'joined_at' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['active', 'inactive', 'graduated', 'transferred'])],
            'academic_year' => ['required', 'string'],
            'classroom' => ['required', 'string'],
            'scout_level' => ['required', 'string'],
        ], [], [
            'name' => 'nama',
            'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'parent_phone' => 'telepon orang tua',
            'joined_at' => 'tanggal masuk',
            'academic_year' => 'tahun ajaran',
            'classroom' => 'kelas',
            'scout_level' => 'golongan',
        ])->validate();

        $academicYear = AcademicYear::query()
            ->where('name', $data['academic_year'])
            ->firstOrFail();
        $classroom = Classroom::query()
            ->where('name', $data['classroom'])
            ->firstOrFail();
        $scoutLevel = ScoutLevel::query()
            ->where(function ($query) use ($data): void {
                $query
                    ->where('name', $data['scout_level'])
                    ->orWhere('code', $data['scout_level']);
            })
            ->firstOrFail();

        DB::transaction(function () use ($data, $academicYear, $classroom, $scoutLevel): void {
            $student = Student::query()->create([
                'nis' => $data['nis'],
                'nisn' => $data['nisn'],
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'],
                'parent_phone' => $data['parent_phone'],
                'address' => $data['address'],
                'joined_at' => $data['joined_at'],
                'status' => $data['status'],
            ]);

            StudentEnrollment::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'classroom_id' => $classroom->id,
                'status' => $student->status === 'active' ? 'active' : 'inactive',
                'enrolled_at' => $student->joined_at?->format('Y-m-d') ?? $academicYear->start_date,
            ]);

            StudentScoutLevel::query()->create([
                'student_id' => $student->id,
                'scout_level_id' => $scoutLevel->id,
                'academic_year_id' => $academicYear->id,
                'started_at' => $academicYear->start_date,
                'is_active' => (bool) $academicYear->is_active,
                'ended_at' => $academicYear->is_active ? null : $academicYear->end_date,
            ]);
        });
    }

    /** @param array<int, string> $headers */
    private function validateHeaders(array $headers): void
    {
        $required = ['nis', 'nama', 'jenis_kelamin', 'tahun_ajaran', 'kelas', 'golongan'];
        $missing = array_diff($required, $headers);

        if ($missing !== []) {
            throw new \InvalidArgumentException(
                'Kolom CSV wajib belum tersedia: '.implode(', ', $missing).'.'
            );
        }
    }

    private function detectDelimiter(string $path): string
    {
        $firstLine = (string) fgets(fopen($path, 'rb'));

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->replace("\xEF\xBB\xBF", '')
            ->ascii()
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();
    }

    private function normalizeGender(mixed $value): ?string
    {
        return match (Str::lower(trim((string) $value))) {
            'l', 'laki-laki', 'laki laki', 'male' => 'L',
            'p', 'perempuan', 'female' => 'P',
            default => $this->nullable($value),
        };
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function errorMessage(\Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())->flatten()->implode(' ');
        }

        if ($exception instanceof ModelNotFoundException) {
            return 'Tahun ajaran, kelas, atau golongan tidak ditemukan.';
        }

        return $exception->getMessage();
    }
}
