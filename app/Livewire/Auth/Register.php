<?php

namespace App\Livewire\Auth;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    public string $role = '';

    public ?int $school_id = null;

    public string $student_identifier = '';

    public ?int $matched_student_id = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $nta = '';

    public string $coach_position = '';

    public string $admin_position = '';

    public function mount(): void
    {
        $this->school_id = School::query()->where('is_active', true)
            ->whereKey(request()->integer('school'))->value('id');
    }

    public function updatedRole(): void
    {
        $this->resetRoleSpecificFields();
        $this->resetValidation();
    }

    public function updatedSchoolId(): void
    {
        $this->matched_student_id = null;

        if ($this->role === 'student') {
            $this->name = '';
            $this->findStudent();
        }

        $this->resetValidation([
            'school_id',
            'student_identifier',
            'matched_student_id',
        ]);
    }

    public function updatedStudentIdentifier(): void
    {
        if ($this->role !== 'student') {
            return;
        }

        $this->findStudent();
    }

    public function chooseRole(string $role): void
    {
        if (! in_array($role, ['student', 'coach', 'school_admin'], true)) {
            return;
        }

        if ($this->role !== $role) {
            $this->role = $role;
            $this->updatedRole();
        }
    }

    public function findStudent(): void
    {
        $this->matched_student_id = null;

        if ($this->role !== 'student') {
            return;
        }

        if (! $this->school_id || trim($this->student_identifier) === '') {
            $this->name = '';

            return;
        }

        $identifier = trim($this->student_identifier);

        $student = Student::query()
            ->where('school_id', $this->school_id)
            ->where('status', 'active')
            ->whereNull('user_id')
            ->where(function ($query) use ($identifier): void {
                $query->where('nis', $identifier)
                    ->orWhere('nisn', $identifier);
            })
            ->first();

        if (! $student) {
            $this->name = '';
            $this->resetValidation('matched_student_id');

            return;
        }

        $this->matched_student_id = $student->id;
        $this->name = $student->name;
        $this->resetValidation([
            'student_identifier',
            'matched_student_id',
            'name',
        ]);
    }

    protected function rules(): array
    {
        $rules = [
            'role' => [
                'required',
                Rule::in(['student', 'coach', 'school_admin']),
            ],
            'school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],
        ];

        if ($this->role === 'student') {
            $rules['student_identifier'] = [
                'required',
                'string',
                'max:30',
            ];

            $rules['matched_student_id'] = [
                'required',
                'integer',
                Rule::exists('students', 'id')
                    ->where(fn ($query) => $query
                        ->where('school_id', $this->school_id)
                        ->where('status', 'active')
                        ->whereNull('user_id')),
            ];
        }

        if ($this->role === 'coach') {
            $rules['phone'] = [
                'required',
                'string',
                'max:30',
            ];
            $rules['nta'] = [
                'nullable',
                'string',
                'max:100',
            ];
            $rules['coach_position'] = [
                'required',
                'string',
                'max:100',
            ];
        }

        if ($this->role === 'school_admin') {
            $rules['phone'] = [
                'required',
                'string',
                'max:30',
            ];
            $rules['admin_position'] = [
                'required',
                'string',
                'max:100',
            ];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'role.required' => 'Pilih jenis akun terlebih dahulu.',
            'role.in' => 'Jenis akun yang dipilih tidak diizinkan.',
            'school_id.required' => 'Pilih sekolah terlebih dahulu.',
            'matched_student_id.required' => 'Data siswa tidak ditemukan. Pastikan sekolah dan NIS/NISN sudah benar.',
            'matched_student_id.exists' => 'Data siswa tidak tersedia atau sudah terhubung ke akun lain.',
            'student_identifier.required' => 'NIS atau NISN wajib diisi.',
            'coach_position.required' => 'Jabatan pembina wajib diisi.',
            'admin_position.required' => 'Jabatan di sekolah wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi untuk jenis akun ini.',
        ];
    }

    public function register(): void
    {
        $validated = $this->validate();

        if ($validated['role'] === 'student') {
            $student = Student::query()
                ->whereKey($validated['matched_student_id'])
                ->where('school_id', $validated['school_id'])
                ->where('status', 'active')
                ->whereNull('user_id')
                ->firstOrFail();

            // Nama akun siswa selalu berasal dari master siswa, bukan input bebas.
            $validated['name'] = $student->name;
        }

        $registrationData = match ($validated['role']) {
            'student' => [
                'student_id' => $validated['matched_student_id'],
                'student_identifier' => trim($validated['student_identifier']),
            ],
            'coach' => [
                'nta' => trim($validated['nta'] ?? ''),
                'position' => trim($validated['coach_position']),
            ],
            'school_admin' => [
                'position' => trim($validated['admin_position']),
            ],
        };

        $user = new User;
        $user->forceFill([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => trim($validated['phone'] ?? '') ?: null,
            'password' => Hash::make($validated['password']),
            'requested_school_id' => $validated['school_id'],
            'requested_role' => $validated['role'],
            'registration_data' => json_encode($registrationData, JSON_THROW_ON_ERROR),
            'approval_status' => 'pending',
            'is_active' => false,
            'activation_pending' => false,
        ]);
        $user->save();

        event(new Registered($user));

        session()->flash(
            'status',
            'Pendaftaran berhasil dikirim. Akun dapat digunakan setelah disetujui admin sekolah.'
        );

        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        $schools = School::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $matchedStudent = $this->matched_student_id
            ? Student::query()
                ->with([
                    'enrollments' => fn ($query) => $query
                        ->with('classroom')
                        ->latest('id'),
                ])
                ->find($this->matched_student_id)
            : null;

        return view('livewire.auth.register', [
            'schools' => $schools,
            'matchedStudent' => $matchedStudent,
        ]);
    }

    private function resetRoleSpecificFields(): void
    {
        $this->student_identifier = '';
        $this->matched_student_id = null;
        $this->nta = '';
        $this->coach_position = '';
        $this->admin_position = '';

        if ($this->role === 'student') {
            $this->name = '';
        }
    }
}
