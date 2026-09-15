<?php

use App\Livewire\Admin\PublicContent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('id');
});

test('every framework validation message has an Indonesian translation', function () {
    $messages = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
    unset($messages['custom'], $messages['attributes'], $messages['values']);
    foreach (array_keys(Arr::dot($messages)) as $key) {
        expect(__('validation.'.$key))->toBeString()->not->toStartWith('validation.');
    }
});

test('validation identifies the field and explains how to correct the value', function (array $data, array $rules, string $field, string $message) {
    expect(Validator::make($data, $rules)->errors()->first($field))->toBe($message);
})->with([
    'required school' => [[], ['school_name' => ['required']], 'school_name', 'Sekolah wajib diisi.'],
    'email format' => [['email' => 'bukan-email'], ['email' => ['email']], 'email', 'Alamat email harus berupa alamat email yang valid, misalnya nama@contoh.com.'],
    'minimum group size' => [['members' => ['satu']], ['members' => ['array', 'min:4']], 'members', 'Anggota peserta minimal 4 item.'],
    'end date order' => [['startsAt' => '2026-09-15', 'endsAt' => '2026-09-14'], ['endsAt' => ['date', 'after:startsAt']], 'endsAt', 'Tanggal selesai harus setelah Tanggal mulai.'],
    'invalid option' => [['category' => 'unknown'], ['category' => ['in:individual,team']], 'category', 'Pilihan Kategori peserta tidak valid. Pilih kembali dari daftar yang tersedia.'],
    'range maximum' => [['teamMax' => 101], ['teamMax' => ['numeric', 'max:100']], 'teamMax', 'Jumlah maksimal anggota tim tidak boleh lebih dari 100.'],
    'nested participant' => [['members' => [['name' => '']]], ['members.*.name' => ['required']], 'members.0.name', 'Nama anggota ke-1 wajib diisi.'],
    'password confirmation' => [['password' => 'abc', 'password_confirmation' => 'def'], ['password' => ['confirmed']], 'password', 'Konfirmasi Kata sandi tidak sama. Masukkan kembali nilai yang sama.'],
    'conditional field' => [['period_type' => 'monthly'], ['month' => ['required_if:period_type,monthly']], 'month', 'Bulan wajib diisi ketika Jenis periode bernilai bulanan.'],
    'terms' => [['terms' => false], ['terms' => ['accepted']], 'terms', 'Syarat dan ketentuan wajib disetujui sebelum mengirim formulir.'],
    'duplicate email' => [['email' => 'used@example.com'], ['email' => ['in:available@example.com']], 'email', 'Pilihan Alamat email tidak valid. Pilih kembali dari daftar yang tersedia.'],
]);

test('upload errors explain size and accepted file types', function () {
    $validator = Validator::make(['files' => [UploadedFile::fake()->create('panduan.pdf', 6, 'application/pdf')]], ['files.*' => ['file', 'max:5']]);
    expect($validator->errors()->first('files.0'))->toBe('Berkas lampiran ke-1 maksimal berukuran 5 KB.');
    $validator = Validator::make(['attachment' => UploadedFile::fake()->create('script.txt', 1, 'text/plain')], ['attachment' => ['mimes:pdf,jpg']]);
    expect($validator->errors()->first('attachment'))->toBe('Jenis berkas Berkas lampiran tidak didukung. Gunakan berkas: pdf, jpg.');
});

test('duplicate master records and explicit form messages remain understandable', function () {
    $user = User::factory()->create();
    expect(Validator::make(['email' => $user->email], ['email' => ['unique:users,email']])->errors()->first('email'))
        ->toBe('Alamat email sudah digunakan. Gunakan data lain atau periksa data yang sudah terdaftar.');
    expect(Validator::make([], ['name' => ['required']], ['name.required' => 'Nama resmi wajib diisi sesuai identitas.'])->errors()->first('name'))
        ->toBe('Nama resmi wajib diisi sesuai identitas.');
});

test('HTTP and Livewire forms display translated errors without raw translation keys', function () {
    $this->post(route('login.store'), [])->assertSessionHasErrors(['email' => 'Alamat email wajib diisi.', 'password' => 'Kata sandi wajib diisi.']);
    $component = Livewire::actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->test(PublicContent::class, ['kind' => 'activities'])->call('save')->assertHasErrors('title');
    expect($component->errors()->first('title'))->toBe('Judul wajib diisi.');
    $component->assertSee('Judul wajib diisi.')->assertDontSee('validation.required');
});

test('authentication failures and reset messages resolve in Indonesian', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'incorrect'])
        ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai. Periksa kembali data masuk Anda.']);
    expect(__('passwords.token'))->toBe('Tautan pengaturan ulang kata sandi tidak valid atau sudah kedaluwarsa. Minta tautan baru.')
        ->and(__('auth.throttle', ['seconds' => 60]))->toContain('60 detik')->not->toContain(':seconds');
});
