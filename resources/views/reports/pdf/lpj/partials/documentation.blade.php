<section class="page-break">
    @include('reports.pdf.lpj.partials.letterhead')

    <div class="document-title">
        DOKUMENTASI KEGIATAN EKSTRA / PENGEMBANGAN DIRI<br>
        EKSTRA KURIKULER PRAMUKA
    </div>

    <div class="documentation-session-heading">
        {{ strtoupper($routineSession['label']) }} - {{ strtoupper($reportMonth['label']) }}
        @if ($routineSession['startTime'] || $routineSession['endTime'])
            <br>
            <span class="session-time">
                {{ $routineSession['startTime'] ?: '--:--' }}
                -
                {{ $routineSession['endTime'] ?: '--:--' }} WITA
            </span>
        @endif
    </div>

    @foreach ($routineSession['documentation'] as $documentation)
        <div class="documentation-item">
            <div class="documentation-date">
                {{ $loop->iteration }}. {{ $documentation['activity']->start_at->locale('id')->translatedFormat('l, d F Y') }}
                @if ($documentation['activity']->title)
                    - {{ $documentation['activity']->title }}
                @endif
            </div>

            <table class="documentation-grid">
                @foreach ($documentation['attachments']->chunk(2) as $attachmentRow)
                    <tr>
                        @foreach ($attachmentRow as $attachment)
                            <td>
                                <img src="{{ $attachment->pdf_src }}" alt="Dokumentasi">
                                <div class="documentation-caption">{{ $attachment->original_name }}</div>
                            </td>
                        @endforeach
                        @if ($attachmentRow->count() === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</section>
