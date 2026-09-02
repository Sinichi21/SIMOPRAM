<table>
    <thead>

        <tr>
            <th colspan="12">
                REKAP ABSENSI EKSTRAKURIKULER PRAMUKA
            </th>
        </tr>

        <tr>
            <td>Sekolah</td>
            <td colspan="11">
                {{ $school->name }}
            </td>
        </tr>

        <tr>
            <td>NPSN</td>
            <td colspan="11">
                {{ $school->npsn ?: '-' }}
            </td>
        </tr>

        <tr>
            <td>Tahun Ajaran</td>
            <td colspan="11">
                {{ $academicYear?->name ?? '-' }}
            </td>
        </tr>

        <tr>
            <td>Semester</td>
            <td colspan="11">
                {{ $semester?->name ?? 'Semua Semester' }}
            </td>
        </tr>

        <tr>
            <td>Kelas</td>
            <td colspan="11">
                {{ $classroom?->name ?? 'Semua Kelas' }}
            </td>
        </tr>

        <tr>
            <td>Total Sesi</td>
            <td colspan="11">
                {{ $sessionCount }}
            </td>
        </tr>

        <tr></tr>

        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Pertemuan</th>
            <th>Hadir</th>
            <th>Terlambat</th>
            <th>Sakit</th>
            <th>Izin</th>
            <th>Alpa</th>
            <th>Belum Dicatat</th>
            <th>% Kehadiran</th>
        </tr>
    </thead>

    <tbody>

        @forelse ($rows as $index => $row)

            @php
                $student =
                    $row['student'];

                $enrollment =
                    $academicYear
                        ? $student
                            ->enrollments
                            ->firstWhere(
                                'academic_year_id',
                                $academicYear->id
                            )
                        : null;
            @endphp

            <tr>
                <td>
                    {{ $index + 1 }}
                </td>

                <td>
                    {{ $student->nis }}
                </td>

                <td>
                    {{ $student->name }}
                </td>

                <td>
                    {{ $enrollment?->classroom?->name ?? '-' }}
                </td>

                <td>
                    {{ $row['participants'] }}
                </td>

                <td>
                    {{ $row['present'] }}
                </td>

                <td>
                    {{ $row['late'] }}
                </td>

                <td>
                    {{ $row['sick'] }}
                </td>

                <td>
                    {{ $row['excused'] }}
                </td>

                <td>
                    {{ $row['absent'] }}
                </td>

                <td>
                    {{ $row['unrecorded'] }}
                </td>

                <td>
                    {{ number_format(
                        $row['presence_percentage'],
                        2
                    ) }}%
                </td>
            </tr>

        @empty

            <tr>
                <td colspan="12">
                    Belum ada data absensi.
                </td>
            </tr>

        @endforelse

    </tbody>
</table>