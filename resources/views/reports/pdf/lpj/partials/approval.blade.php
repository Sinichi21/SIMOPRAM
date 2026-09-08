<section class="page-break">
    @include('reports.pdf.lpj.partials.letterhead')

    <div class="approval-title">
        <h1>LEMBAR PENGESAHAN</h1>
    </div>

    <table class="approval-meta">
        <tr><td>Nama Ekstrakurikuler</td><td>: Pramuka</td></tr>
        <tr><td>Nama Pembina</td><td>: {{ $coachName }}</td></tr>
        <tr><td>NTA</td><td>: {{ $responsibleCoach?->nip ?: '-' }}</td></tr>
        <tr><td>Hari dan waktu Pelaksanaan</td><td>: {{ $schedule['dayName'] }}, {{ $schedule['startTime'] ?: '-' }}{{ $schedule['endTime'] ? ' - '.$schedule['endTime'].' WITA' : '' }}</td></tr>
        <tr><td>Tempat Pelaksanaan</td><td>: {{ $schedule['location'] }}</td></tr>
        <tr><td>Tahun Ajaran</td><td>: {{ $academicYear->name }}</td></tr>
        <tr><td>Semester</td><td>: {{ $semester->name }}</td></tr>
        <tr><td>Tempat</td><td>: {{ $signingCity }}</td></tr>
        <tr><td>Tanggal</td><td>: {{ $signingDate->locale('id')->translatedFormat('d F Y') }}</td></tr>
    </table>

    <table class="approval-signatures">
        <tr>
            <td>Pembina Ekstra/Pengembangan Diri</td>
            <td>Koordinator Ekstra/Pengembangan Diri</td>
        </tr>
        <tr><td class="signature-space"></td><td></td></tr>
        <tr>
            <td><span class="signature-name">{{ $coachName }}</span><br>{{ $coachIdentifier }}</td>
            <td>
                <span class="signature-name">{{ $documentSetting?->coordinator_name ?: '........................' }}</span><br>
                @if ($documentSetting?->coordinator_nip) NIP. {{ $documentSetting->coordinator_nip }} @endif
            </td>
        </tr>
    </table>

    <div class="approval-principal">
        Mengetahui/Menyetujui:<br>
        Kepala {{ $school->name }}
        <div class="signature-space"></div>
        <span class="signature-name">{{ $principalName }}</span><br>
        NIP. {{ $principalNip }}
    </div>
</section>
