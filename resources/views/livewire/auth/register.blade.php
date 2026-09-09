<div class="mx-auto w-full max-w-2xl space-y-6">
    <div class="space-y-2 text-center">
        <flux:heading size="xl">Daftar Akun SIMPRAM</flux:heading>
        <flux:text>
            Pilih jenis akun. Form akan menyesuaikan dengan peran yang Anda daftarkan.
        </flux:text>
    </div>

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="space-y-6">
        {{-- STEP 1: ROLE --}}
        <section class="space-y-3">
            <div>
                <div class="text-sm font-medium text-zinc-900 dark:text-white">
                    1. Daftar Sebagai
                </div>
                <div class="mt-1 text-sm text-zinc-500">
                    Pilih jenis akun yang sesuai dengan peran Anda.
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <button
                    type="button"
                    wire:click="chooseRole('student')"
                    class="rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-zinc-400
                        {{ $role === 'student'
                            ? 'border-zinc-900 bg-zinc-100 ring-1 ring-zinc-900 dark:border-white dark:bg-zinc-800 dark:ring-white'
                            : 'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500' }}"
                >
                    <div class="font-semibold text-zinc-900 dark:text-white">Siswa</div>
                    <div class="mt-1 text-xs leading-5 text-zinc-500">
                        Hubungkan akun dengan data siswa yang sudah terdaftar.
                    </div>
                </button>

                <button
                    type="button"
                    wire:click="chooseRole('coach')"
                    class="rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-zinc-400
                        {{ $role === 'coach'
                            ? 'border-zinc-900 bg-zinc-100 ring-1 ring-zinc-900 dark:border-white dark:bg-zinc-800 dark:ring-white'
                            : 'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500' }}"
                >
                    <div class="font-semibold text-zinc-900 dark:text-white">Pembina</div>
                    <div class="mt-1 text-xs leading-5 text-zinc-500">
                        Untuk pembina atau pelatih kegiatan Pramuka.
                    </div>
                </button>

                <button
                    type="button"
                    wire:click="chooseRole('school_admin')"
                    class="rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-zinc-400
                        {{ $role === 'school_admin'
                            ? 'border-zinc-900 bg-zinc-100 ring-1 ring-zinc-900 dark:border-white dark:bg-zinc-800 dark:ring-white'
                            : 'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500' }}"
                >
                    <div class="font-semibold text-zinc-900 dark:text-white">Admin Sekolah</div>
                    <div class="mt-1 text-xs leading-5 text-zinc-500">
                        Untuk pengelola SIMPRAM pada tingkat sekolah.
                    </div>
                </button>
            </div>

            @error('role')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </section>

        @if ($role !== '')
            {{-- STEP 2: SCHOOL --}}
            <section class="space-y-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                <div>
                    <div class="text-sm font-medium text-zinc-900 dark:text-white">
                        2. Sekolah
                    </div>
                    <div class="mt-1 text-sm text-zinc-500">
                        Pilih sekolah tempat akun akan digunakan.
                    </div>
                </div>

                <flux:select wire:model.live="school_id" label="Sekolah" required>
                    <option value="">Pilih sekolah</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </flux:select>
            </section>

            @if ($school_id)
                {{-- STEP 3: ROLE SPECIFIC PROFILE --}}
                <section class="space-y-4 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                    @if ($role === 'student')
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                3. Verifikasi Data Siswa
                            </div>
                            <div class="mt-1 text-sm text-zinc-500">
                                Masukkan NIS atau NISN yang sudah tercatat pada data siswa sekolah.
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                            <flux:input
                                wire:model="student_identifier"
                                label="NIS / NISN"
                                placeholder="Contoh: 2101 atau 3149600001"
                                autocomplete="off"
                                required
                            />

                            <flux:button
                                type="button"
                                wire:click="findStudent"
                                wire:loading.attr="disabled"
                                wire:target="findStudent"
                                class="sm:mb-[1px]"
                            >
                                <span wire:loading.remove wire:target="findStudent">Cari Data</span>
                                <span wire:loading wire:target="findStudent">Mencari...</span>
                            </flux:button>
                        </div>

                        @error('matched_student_id')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @if ($matchedStudent)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                            Data siswa ditemukan
                                        </div>
                                        <div class="mt-1 font-semibold text-zinc-900 dark:text-white">
                                            {{ $matchedStudent->name }}
                                        </div>
                                    </div>
                                    <div class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                        Terverifikasi
                                    </div>
                                </div>

                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-zinc-500">NIS</dt>
                                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $matchedStudent->nis ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">NISN</dt>
                                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $matchedStudent->nisn ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">Kelas</dt>
                                        <dd class="font-medium text-zinc-900 dark:text-white">
                                            {{ $matchedStudent->enrollments->first()?->classroom?->name ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">Status</dt>
                                        <dd class="font-medium text-zinc-900 dark:text-white">Aktif</dd>
                                    </div>
                                </dl>
                            </div>
                        @elseif ($student_identifier !== '')
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                                Data belum ditemukan. Periksa NIS/NISN atau hubungi admin sekolah jika data siswa belum diimpor.
                            </div>
                        @endif
                    @elseif ($role === 'coach')
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                3. Data Pembina
                            </div>
                            <div class="mt-1 text-sm text-zinc-500">
                                Lengkapi identitas pembina yang akan digunakan pada administrasi SIMPRAM.
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model="name"
                                label="Nama Lengkap"
                                placeholder="Nama lengkap beserta gelar jika ada"
                                required
                                autocomplete="name"
                            />
                            <flux:input
                                wire:model="phone"
                                label="Nomor Telepon"
                                placeholder="Contoh: 081234567890"
                                required
                                autocomplete="tel"
                            />
                            <flux:input
                                wire:model="nta"
                                label="NTA"
                                placeholder="Opsional jika belum memiliki"
                            />
                            <flux:input
                                wire:model="coach_position"
                                label="Jabatan Pembina"
                                placeholder="Contoh: Pembina Penggalang"
                                required
                            />
                        </div>
                    @elseif ($role === 'school_admin')
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                3. Data Admin Sekolah
                            </div>
                            <div class="mt-1 text-sm text-zinc-500">
                                Pendaftaran admin memerlukan verifikasi sebelum hak akses diberikan.
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model="name"
                                label="Nama Lengkap"
                                placeholder="Nama lengkap"
                                required
                                autocomplete="name"
                            />
                            <flux:input
                                wire:model="phone"
                                label="Nomor Telepon"
                                placeholder="Contoh: 081234567890"
                                required
                                autocomplete="tel"
                            />
                        </div>

                        <flux:input
                            wire:model="admin_position"
                            label="Jabatan di Sekolah"
                            placeholder="Contoh: Operator Sekolah / Koordinator Ekstra"
                            required
                        />

                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                            Role Admin Sekolah tidak diberikan saat registrasi. Hak akses baru diberikan setelah pendaftaran disetujui sesuai kewenangan sistem.
                        </div>
                    @endif
                </section>

                @php
                    $canShowAccountSection = $role !== 'student' || $matchedStudent;
                @endphp

                @if ($canShowAccountSection)
                    {{-- STEP 4: ACCOUNT --}}
                    <section class="space-y-4 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $role === 'student' ? '4' : '4' }}. Data Akun
                            </div>
                            <div class="mt-1 text-sm text-zinc-500">
                                Email dan kata sandi ini akan digunakan untuk masuk ke SIMPRAM setelah akun disetujui.
                            </div>
                        </div>

                        @if ($role === 'student')
                            <flux:input
                                :value="$matchedStudent?->name"
                                label="Nama Lengkap"
                                type="text"
                                readonly
                                disabled
                            />

                            <flux:input
                                wire:model="phone"
                                label="Nomor Telepon"
                                type="text"
                                placeholder="Opsional"
                                autocomplete="tel"
                            />
                        @endif

                        <flux:input
                            wire:model="email"
                            label="Alamat Email"
                            type="email"
                            placeholder="nama@email.com"
                            required
                            autocomplete="email"
                        />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model="password"
                                label="Kata Sandi"
                                type="password"
                                required
                                autocomplete="new-password"
                                viewable
                            />

                            <flux:input
                                wire:model="password_confirmation"
                                label="Konfirmasi Kata Sandi"
                                type="password"
                                required
                                autocomplete="new-password"
                                viewable
                            />
                        </div>
                    </section>

                    <div class="border-t border-zinc-200 pt-5 dark:border-zinc-800">
                        <flux:button
                            variant="primary"
                            type="submit"
                            class="w-full"
                            wire:loading.attr="disabled"
                            wire:target="register"
                        >
                            <span wire:loading.remove wire:target="register">
                                Daftar dan Tunggu Persetujuan
                            </span>
                            <span wire:loading wire:target="register">
                                Mengirim pendaftaran...
                            </span>
                        </flux:button>
                    </div>
                @endif
            @endif
        @endif
    </form>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        Sudah memiliki akun?
        <flux:link :href="route('login')" wire:navigate>Masuk</flux:link>
    </div>
</div>
