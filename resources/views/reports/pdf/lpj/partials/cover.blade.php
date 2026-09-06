<section class="cover page-break">
    @if (is_file($coverBorderPath))
        <img class="cover-border" src="{{ $coverBorderPath }}" alt="Border Pramuka">
    @endif

    <div class="cover-logos">
        @if ($schoolLogoPath)
            <img src="{{ $schoolLogoPath }}" alt="Logo Sekolah">
        @endif
        @if ($scoutGroupLogoPath)
            <img src="{{ $scoutGroupLogoPath }}" alt="Logo Pramuka">
        @endif
    </div>

    <div class="cover-title">
        LAPORAN PERTANGGUNG JAWABAN<br>
        PELAKSANAAN KEGIATAN EKSTRA / PENGEMBANGAN DIRI<br>
        EKSTRA KURIKULER PRAMUKA
    </div>

    <div class="cover-author">
        OLEH:<br>
        {{ strtoupper($coachName) }}
    </div>

    <div class="cover-school">
        {{ strtoupper($school->name) }}<br>
        @if ($documentSetting?->parent_agency)
            {{ strtoupper($documentSetting->parent_agency) }}<br>
        @endif
        {{ strtoupper($school->city ?: '') }}<br>
        {{ $periodEnd->format('Y') }}
    </div>
</section>
