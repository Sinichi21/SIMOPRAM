<div class="grid gap-4 sm:grid-cols-2">
    <label class="grid gap-2 text-sm font-medium">Sumber data
        <select :name="{{ $prefix }} + '[source]'" x-model="{{ $person }}.source" class="w-full rounded-lg border p-2">
            <option value="external">Input peserta luar SIMPRAM</option>
            @if (count($profiles[$role]))<option value="{{ $role }}">Gunakan data SIMPRAM</option>@endif
        </select>
    </label>
    <fieldset x-show="{{ $person }}.source !== 'external'" :disabled="{{ $person }}.source === 'external'" class="min-w-0">
        <label class="grid gap-2 text-sm font-medium">Pilih {{ $role === 'coach' ? 'pembina' : 'siswa' }} SIMPRAM
            <select :name="{{ $prefix }} + '[profile_id]'" x-model="{{ $person }}.profile_id" required class="w-full rounded-lg border p-2"><option value="">Pilih data terdaftar</option>
                @foreach ($profiles[$role] as $profile)<option value="{{ $profile['id'] }}">{{ $profile['name'] }} — {{ $profile['school'] }}</option>@endforeach
            </select>
        </label>
    </fieldset>
    <fieldset x-show="{{ $person }}.source === 'external'" :disabled="{{ $person }}.source !== 'external'" class="grid min-w-0 gap-4 sm:col-span-2 sm:grid-cols-2">
        <label class="grid gap-2 text-sm font-medium">Nama lengkap<input :name="{{ $prefix }} + '[name]'" x-model="{{ $person }}.name" required maxlength="150" class="w-full rounded-lg border p-2"></label>
        <label class="grid gap-2 text-sm font-medium">Sekolah / instansi asal<input :name="{{ $prefix }} + '[school_name]'" x-model="{{ $person }}.school_name" maxlength="200" class="w-full rounded-lg border p-2"></label>
    </fieldset>
    <label class="grid gap-2 text-sm font-medium">NTA / NIP (opsional)<input :name="{{ $prefix }} + '[identifier]'" x-model="{{ $person }}.identifier" maxlength="50" class="w-full rounded-lg border p-2"></label>
    <label class="grid gap-2 text-sm font-medium">Kirim akses melalui<select :name="{{ $prefix }} + '[channel]'" x-model="{{ $person }}.channel" class="w-full rounded-lg border p-2"><option value="email">Email</option><option value="whatsapp">WhatsApp</option></select></label>
    <fieldset class="min-w-0 sm:col-span-2"><label class="grid gap-2 text-sm font-medium">Email / nomor WhatsApp penerima akses<input :type="{{ $person }}.channel === 'email' ? 'email' : 'tel'" :name="{{ $prefix }} + '[destination]'" x-model="{{ $person }}.destination" :required="{{ $person }}.source === 'external'" maxlength="255" class="w-full rounded-lg border p-2"></label></fieldset>
    <p x-show="{{ $person }}.source !== 'external'" class="text-sm text-zinc-500 sm:col-span-2">Identitas dan sekolah diambil dari SIMPRAM. Kontak tersimpan tetap digunakan. Jika email atau nomor WhatsApp belum tersedia, isi kontak penerima akses di atas. Peserta tidak wajib memiliki akun.</p>
</div>
