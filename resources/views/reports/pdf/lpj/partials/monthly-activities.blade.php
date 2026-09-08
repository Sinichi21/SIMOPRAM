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
                    <td>{{ $row['date']->locale('id')->translatedFormat('l, d F Y') }}</td>
                    <td>
                        @if ($row['sessions']->isEmpty())
                            <strong>{{ $row['holidayLabel'] ?: 'LIBUR / TIDAK ADA KEGIATAN' }}</strong>
                        @else
                            @foreach ($row['sessions'] as $session)
                                <div class="report-session">
                                    <div class="session-heading">
                                        {{ strtoupper($session['label']) }}
                                    </div>

                                    @if ($session['startTime'] || $session['endTime'])
                                        <div class="session-time">
                                            {{ $session['startTime'] ?: '--:--' }}
                                            -
                                            {{ $session['endTime'] ?: '--:--' }} WITA
                                        </div>
                                    @endif

                                    @if ($session['isCancelled'])
                                        <div style="margin-top: 1mm;">
                                            <strong>{{ $session['holidayLabel'] }}</strong>
                                        </div>
                                    @elseif ($session['materials']->isNotEmpty())
                                        <ul>
                                            @foreach ($session['materials'] as $material)
                                                <li>{{ $material }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
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
            <td>{{ $signingCity }}, {{ $reportMonth['end']->locale('id')->translatedFormat('d F Y') }}<br>Pembina Ekstra / Pengembangan Diri</td>
        </tr>
        <tr><td class="signature-space"></td><td></td></tr>
        <tr>
            <td><span class="signature-name">{{ $principalName }}</span><br>NIP. {{ $principalNip }}</td>
            <td><span class="signature-name">{{ $coachName }}</span><br>{{ $coachIdentifier }}</td>
        </tr>
    </table>
</section>
