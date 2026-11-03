<h1 style="text-align:center;font-size:16pt;">PROFIL DAN ORGANISASI GUGUSDEPAN</h1>
<p style="text-align:center;">{{ $gudep->name }} — {{ $year?->name ?? 'Seluruh periode' }}</p>
<hr>
<table style="width:100%;border-collapse:collapse;">
    <tr><td width="35%">Nomor Gudep Putra</td><td>: {{ $gudep->male_number ?: '-' }}</td></tr>
    <tr><td>Nomor Gudep Putri</td><td>: {{ $gudep->female_number ?: '-' }}</td></tr>
    <tr><td>Kwartir Ranting</td><td>: {{ $gudep->kwarran ?: '-' }}</td></tr>
    <tr><td>Kwartir Cabang</td><td>: {{ $gudep->kwarcab ?: '-' }}</td></tr>
    <tr><td>Kwartir Daerah</td><td>: {{ $gudep->kwarda ?: '-' }}</td></tr>
    <tr><td>Kamabigus</td><td>: {{ $gudep->kamabigus_name ?: '-' }}</td></tr>
    <tr><td>Pembina Gudep</td><td>: {{ $gudep->head_coach_name ?: '-' }}</td></tr>
    <tr><td>Alamat Sekretariat</td><td>: {{ $gudep->secretariat_address ?: '-' }}</td></tr>
</table>
<h2 style="margin-top:20px;font-size:12pt;">Susunan Kepengurusan dan Majelis Pembimbing</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>Kelompok</th><th>Nama</th><th>Jabatan</th><th>Masa tugas</th><th>Nomor SK</th></tr></thead>
    <tbody>
        @forelse ($officers as $officer)
            <tr>
                <td>{{ ucfirst($officer->scope) }}</td>
                <td>{{ $officer->person_name }}</td>
                <td>{{ $officer->position }}</td>
                <td>{{ $officer->started_at?->format('d/m/Y') }} — {{ $officer->ended_at?->format('d/m/Y') ?? 'aktif' }}</td>
                <td>{{ $officer->decree_number ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Belum ada data kepengurusan.</td></tr>
        @endforelse
    </tbody>
</table>
<h2 style="margin-top:20px;font-size:12pt;">Satuan Golongan</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>Nama</th><th>Jenis</th><th>Golongan</th><th>Tahun Ajaran</th><th>Putra/Putri</th></tr></thead>
    <tbody>
        @forelse ($formations as $formation)
            <tr>
                <td>{{ $formation->name }}</td>
                <td>{{ ucfirst($formation->formation_type) }}</td>
                <td>{{ $formation->scoutLevel?->name ?? '-' }}</td>
                <td>{{ $formation->academicYear?->name ?? '-' }}</td>
                <td>{{ ucfirst($formation->gender) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Belum ada satuan golongan.</td></tr>
        @endforelse
    </tbody>
</table>
<h2 style="margin-top:20px;font-size:12pt;">Satuan Tertaut</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>Nama kelompok</th><th>Golongan</th><th>Satuan Golongan</th><th>Tahun ajaran</th></tr></thead>
    <tbody>
        @forelse ($units as $unit)
            <tr><td>{{ $unit->name }}</td><td>{{ $unit->scoutLevel?->name ?? '-' }}</td><td>{{ $unit->gudepFormation?->name ?? '-' }}</td><td>{{ $unit->academicYear?->name ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="4">Belum ada kelompok tertaut.</td></tr>
        @endforelse
    </tbody>
</table>
<p style="margin-top:15px;font-size:8pt;">Dokumen ini dihasilkan dari data administrasi SIMPRAM. Nomor SK yang tercatat tidak menggantikan berkas SK asli.</p>
