<section class="page-break">
    @include('reports.pdf.lpj.partials.letterhead')

    <div class="document-title">
        DAFTAR HADIR PESERTA/SISWA EKSTRA / PENGEMBANGAN DIRI<br>
        EKSTRA KURIKULER PRAMUKA
    </div>
    <div class="month-label">
        KELAS {{ strtoupper($classData['classroom']->name) }} - {{ strtoupper($reportMonth['label']) }}
    </div>

    <table class="attendance-table" style="margin-top: 4mm;">
        <thead>
            <tr>
                <th class="col-no" rowspan="2">No.</th>
                <th class="col-name" rowspan="2">Nama Siswa / Peserta</th>
                <th class="col-class" rowspan="2">Kelas</th>
                <th colspan="{{ max(1, $reportMonth['dates']->count()) }}">Tanggal dan Kehadiran</th>
            </tr>
            <tr>
                @forelse ($reportMonth['dates'] as $date)
                    <th>{{ $date->format('d/m/Y') }}</th>
                @empty
                    <th>-</th>
                @endforelse
            </tr>
        </thead>
        <tbody>
            @foreach ($studentChunk as $student)
                @php
                    $studentRowIndex = $loop->index;
                @endphp
                <tr>
                    <td>{{ ($chunkIndex * 24) + $loop->iteration }}</td>
                    <td class="col-name">{{ $student['name'] }}</td>
                    <td>{{ $student['className'] }}</td>
                    @foreach ($reportMonth['dates'] as $date)
                        @php
                            $dateKey = $date->format('Y-m-d');
                            $meta = $reportMonth['dateMeta'][$dateKey] ?? null;
                        @endphp
                        @if ($meta && $meta['isHoliday'])
                            @if ($studentRowIndex === 0)
                                <td class="holiday" rowspan="{{ $studentChunk->count() }}">
                                    {{ strtoupper($meta['holidayLabel'] ?: 'LIBUR') }}
                                </td>
                            @endif
                        @else
                            <td class="status">{{ $student['statuses'][$dateKey] ?? '-' }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="attendance-note">Keterangan: H = Hadir, I = Izin, S = Sakit, A = Alpa, - = belum/tidak tercatat.</div>

    @if ($isLastChunk)
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
    @endif
</section>
