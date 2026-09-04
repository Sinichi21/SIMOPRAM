<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Daftar Akun Mandiri" description="Akun dapat digunakan setelah disetujui admin sekolah" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <flux:input
                name="phone"
                label="Nomor Telepon"
                :value="old('phone')"
                type="text"
                autocomplete="tel"
            />

            <flux:select name="requested_school_id" label="Sekolah" required>
                <flux:select.option value="">Pilih sekolah</flux:select.option>
                @foreach ($schools as $school)
                    <flux:select.option
                        :value="$school->id"
                        :selected="old('requested_school_id', request('school')) == $school->id"
                    >
                        {{ $school->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select name="requested_role" label="Daftar Sebagai" required>
                <flux:select.option value="">Pilih tipe akun</flux:select.option>
                <flux:select.option value="student" :selected="old('requested_role') === 'student'">Siswa</flux:select.option>
                <flux:select.option value="coach" :selected="old('requested_role') === 'coach'">Pembina</flux:select.option>
                <flux:select.option value="school_admin" :selected="old('requested_role') === 'school_admin'">Admin Sekolah</flux:select.option>
            </flux:select>

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    Daftar dan Tunggu Persetujuan
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
