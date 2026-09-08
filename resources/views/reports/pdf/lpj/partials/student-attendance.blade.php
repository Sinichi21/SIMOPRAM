<section class="page-break">
    @include('reports.pdf.lpj.partials.letterhead')

    <div class="document-title">
        DAFTAR HADIR PESERTA/SISWA EKSTRA / PENGEMBANGAN DIRI<br>
        EKSTRA KURIKULER PRAMUKA
    </div>

    <div class="attendance-session-heading">
        {{ strtoupper($routineSession['label']) }}
    </div>

    @if ($routineSession['startTime'] || $routineSession['endTime'])
        <div class="attendance-session-time">
            {{ $routineSession['startTime'] ?: '--:--' }}
            -
            {{ $routineSession['endTime'] ?: '--:--' }} WITA
        </div>
    @endif

    <div class="month-label">
        KELAS {{ strtoupper($classData['classroom']->name) }} - {{ strtoupper($reportMonth['label']) }}
    </div>

    <table class="attendance-table" style="margin-top: 4mm;">
        <thead>
            <tr>
                <th class="col-no" rowspan="2">No.</th>
                <th class="col-name" rowspan="2">Nama Siswa / Peserta</th>
                <th class="col-class" rowspan="2">Kelas</th>
                <th colspan="{{ max(1, $routineSession['dates']->count()) }}">Tanggal dan Kehadiran</th>
            </tr>
            <tr>
                @forelse ($routineSession['dates'] as $date)
                    <th class="date-column">{{ $date->format('d/m') }}</th>
                @empty
                    <th>-</th>
                @endforelse
            </tr>
        </thead>
        <tbody>
            @foreach ($classData['students'] as $student)
                @php
                    $studentRowIndex = $loop->index;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="col-name">{{ $student['name'] }}</td>
                    <td class="col-class">{{ $student['className'] }}</td>
                    @foreach ($routineSession['dates'] as $date)
                        @php
                            $dateKey = $date->format('Y-m-d');
                            $meta = $routineSession['dateMeta'][$dateKey] ?? null;
                        @endphp
                        @if ($meta && $meta['isHoliday'])
                            @if ($studentRowIndex === 0)
                                <td class="holiday date-column" rowspan="{{ $classData['students']->count() }}">
                                    {{ strtoupper($meta['holidayLabel'] ?: 'LIBUR') }}
                                </td>
                            @endif
                        @else
                            <td class="status date-column">{{ $student['statuses'][$dateKey] ?? '-' }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="attendance-note">Keterangan: H = Hadir, I = Izin, S = Sakit, A = Alpa, - = belum/tidak tercatat.</div>

    <div class="signature-block">
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
    </div>
</section>
