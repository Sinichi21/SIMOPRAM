<table>
    <thead>

        {{-- IDENTITAS LAPORAN --}}

        <tr>
            <th colspan="{{ 7 + ($selectedConfig?->items?->count() ?? 0) }}">
                REKAP NILAI EKSTRAKURIKULER PRAMUKA
            </th>
        </tr>

        <tr>
            <td>Sekolah</td>
            <td colspan="{{ 6 + ($selectedConfig?->items?->count() ?? 0) }}">
                {{ $school->name }}
            </td>
        </tr>

        <tr>
            <td>NPSN</td>
            <td colspan="{{ 6 + ($selectedConfig?->items?->count() ?? 0) }}">
                {{ $school->npsn ?: '-' }}
            </td>
        </tr>

        <tr>
            <td>Tahun Ajaran</td>
            <td colspan="{{ 6 + ($selectedConfig?->items?->count() ?? 0) }}">
                {{ $academicYear?->name ?? '-' }}
            </td>
        </tr>

        <tr>
            <td>Semester</td>
            <td colspan="{{ 6 + ($selectedConfig?->items?->count() ?? 0) }}">
                {{ $semester?->name ?? 'Semua Semester' }}
            </td>
        </tr>

        <tr>
            <td>Kelas</td>
            <td colspan="{{ 6 + ($selectedConfig?->items?->count() ?? 0) }}">
                {{ $classroom?->name ?? 'Semua Kelas' }}
            </td>
        </tr>

        <tr>
            <td>Konfigurasi Nilai</td>
            <td colspan="{{ 6 + ($selectedConfig?->items?->count() ?? 0) }}">
                {{ $selectedConfig?->name ?? '-' }}
            </td>
        </tr>

        <tr></tr>

        {{-- HEADER TABLE --}}

        <tr>
            <th>No</th>

            <th>NIS</th>

            <th>Nama Siswa</th>

            <th>Kelas</th>

            @foreach (
                $selectedConfig->items
                    ->sortBy('sort_order')
                as $item
            )
                <th>
                    {{ $item->factor->name }}
                    ({{ number_format(
                        (float) $item->weight,
                        0
                    ) }}%)
                </th>
            @endforeach

            <th>Nilai Akhir</th>

            <th>Predikat</th>

            <th>Deskripsi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($students as $index => $student)

            @php
                $studentScores =
                    $scores->get(
                        $student->id,
                        collect()
                    );

                $finalGrade =
                    $finalGrades->get(
                        $student->id
                    );

                $enrollment =
                    $student
                        ->enrollments
                        ->firstWhere(
                            'academic_year_id',
                            $selectedConfig
                                ->academic_year_id
                        );
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

                @foreach (
                    $selectedConfig->items
                        ->sortBy('sort_order')
                    as $item
                )

                    @php
                        $score =
                            $studentScores
                                ->firstWhere(
                                    'assessment_factor_id',
                                    $item
                                        ->assessment_factor_id
                                );
                    @endphp

                    <td>
                        {{ $score
                            ? number_format(
                                (float) $score->score,
                                2
                            )
                            : '-'
                        }}
                    </td>

                @endforeach

                <td>
                    {{ $finalGrade
                        ? number_format(
                            (float) $finalGrade
                                ->final_score,
                            2
                        )
                        : '-'
                    }}
                </td>

                <td>
                    {{ $finalGrade
                        ?->letter_grade
                        ?? '-'
                    }}
                </td>

                <td>
                    {{ $finalGrade
                        ?->description
                        ?? '-'
                    }}
                </td>
            </tr>

        @empty

            <tr>
                <td
                    colspan="{{ 7 + $selectedConfig->items->count() }}"
                >
                    Belum ada data siswa.
                </td>
            </tr>

        @endforelse
    </tbody>
</table>