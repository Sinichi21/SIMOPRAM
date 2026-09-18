<section class="page-break">
    @include('reports.pdf.lpj.partials.letterhead')

    <div class="document-title">
        DAFTAR HADIR PEMBINA EKSTRA / PENGEMBANGAN DIRI<br>
        EKSTRA KURIKULER PRAMUKA {{ strtoupper($school->name) }}<br>
        TAHUN PELAJARAN {{ $academicYear->name }}
    </div>
    <div class="month-label">BULAN : {{ strtoupper($reportMonth['label']) }}</div>

    <table class="attendance-table" style="margin-top: 4mm;">
        <thead>
            <tr>
                <th class="col-no" rowspan="2">NO</th>
                <th class="col-name" rowspan="2">NAMA</th>
                <th colspan="{{ max(1, $reportMonth['dates']->count()) }}">TANGGAL</th>
                <th rowspan="2">KET.</th>
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
            @forelse ($reportMonth['coachRows'] as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="col-name">{{ $row['coach']->name }}</td>
                    @foreach ($reportMonth['dates'] as $date)
                        @php($status = $row['statuses'][$date->format('Y-m-d')] ?? '-')
                        <td class="{{ $status === 'LIBUR' ? 'holiday' : 'status' }}">{{ $status === 'LIBUR' ? 'LIBUR' : (($manualCoachAttendance ?? false) ? '' : $status) }}</td>
                    @endforeach
                    <td>-</td>
                </tr>
            @empty
                <tr><td colspan="{{ 3 + $reportMonth['dates']->count() }}">Belum ada pembina yang ditugaskan pada kegiatan bulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($manualCoachAttendance ?? false)
        <div class="attendance-note">Kehadiran diisi/ditandatangani langsung oleh pembina.</div>
    @else
        <div class="attendance-note">* H pada daftar pembina saat ini berasal dari penugasan pembina pada kegiatan SIMPRAM.</div>
    @endif

    <div class="signature-block">
        <table class="signature-table">
            <tr><td></td><td>{{ $signingCity }}, {{ $reportMonth['end']->locale('id')->translatedFormat('d F Y') }}<br>{{ $principalPosition }}</td></tr>
            <tr><td></td><td class="signature-space"></td></tr>
            <tr><td></td><td><span class="signature-name">{{ $principalName }}</span><br>{{ $principalIdentifier }}</td></tr>
        </table>
    </div>
</section>
