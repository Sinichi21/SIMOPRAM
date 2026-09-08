<section class="page-break">
    @include('reports.pdf.lpj.partials.letterhead')

    <div class="document-title">
        DATA KEGIATAN EKSTRA / PENGEMBANGAN DIRI<br>
        TAHUN PELAJARAN {{ $academicYear->name }}
    </div>

    <table class="data-table">
        <tr><td>1.</td><td>Nama Pembina Ekstra</td><td>: {{ $coachName }}</td></tr>
        <tr><td>2.</td><td>Nomor HP</td><td>: {{ $responsibleCoach?->phone ?: '-' }}</td></tr>
        <tr><td>3.</td><td>Nama Ekstra/Pengemb. Diri</td><td>: Pramuka</td></tr>
        <tr><td>4.</td><td>Hari Pelaksanaan Ekstra</td><td>: {{ $schedule['dayName'] }}</td></tr>
        <tr>
            <td>5.</td><td>Jam Pelaksanaan Ekstra</td>
            <td>: {{ $schedule['startTime'] ?: '-' }}{{ $schedule['endTime'] ? ' - '.$schedule['endTime'].' WITA' : '' }}</td>
        </tr>
        <tr><td>6.</td><td>Tempat Pelaksanaan Ekstra</td><td>: {{ $schedule['location'] }}</td></tr>
    </table>

    <table class="signature-table">
        <tr>
            <td>Mengetahui/Menyetujui:<br>Kepala {{ $school->name }}</td>
            <td>{{ $signingCity }}, {{ $signingDate->locale('id')->translatedFormat('d F Y') }}<br>Pembina Ekstra / Pengembangan Diri</td>
        </tr>
        <tr><td class="signature-space"></td><td></td></tr>
        <tr>
            <td><span class="signature-name">{{ $principalName }}</span><br>NIP. {{ $principalNip }}</td>
            <td><span class="signature-name">{{ $coachName }}</span><br>{{ $coachIdentifier }}</td>
        </tr>
    </table>
</section>
