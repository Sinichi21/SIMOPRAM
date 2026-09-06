<section class="page-break">
    @include('reports.pdf.lpj.partials.letterhead')

    <div class="document-title">
        LAPORAN PELAKSANAAN KEGIATAN EKSTRA / PENGEMBANGAN DIRI<br>
        EKSTRA KURIKULER PRAMUKA
    </div>
    <div class="month-label">BULAN : {{ strtoupper($reportMonth['label']) }}</div>

    <table class="report-table" style="margin-top: 4mm;">
        <thead>
            <tr>
                <th class="number">No.</th>
                <th class="date">Hari / Tanggal</th>
                <th>Materi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportMonth['dateRows'] as $row)
                <tr>
                    <td class="number">{{ $loop->iteration }}</td>
                    <td>{{ $row['date']->translatedFormat('l, d F Y') }}</td>
                    <td>
                        @if ($row['isHoliday'])
                            <strong>{{ $row['holidayLabel'] }}</strong>
                        @else
                            <ul>
                                @foreach ($row['materials'] as $material)
                                    <li>{{ $material }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="center">Belum ada jadwal pada bulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>Mengetahui/Menyetujui:<br>Kepala {{ $school->name }}</td>
            <td>{{ $signingCity }}, {{ $reportMonth['end']->translatedFormat('d F Y') }}<br>Pembina Ekstra / Pengembangan Diri</td>
        </tr>
        <tr><td class="signature-space"></td><td></td></tr>
        <tr>
            <td><span class="signature-name">{{ $principalName }}</span><br>NIP. {{ $principalNip }}</td>
            <td><span class="signature-name">{{ $coachName }}</span><br>{{ $coachIdentifier }}</td>
        </tr>
    </table>
</section>
