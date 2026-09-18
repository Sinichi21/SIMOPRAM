<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $snapshot['title'] }}</title>
    <style>
        @page { margin: 24px 28px 105px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 17px; margin: 5px 0; } h2 { font-size: 13px; margin: 10px 0; }
        .heading { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .metadata { margin: 10px 0; line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #777; padding: 5px; word-wrap: break-word; }
        th { background: #eee; } thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .number { text-align: right; } .rank { width: 7%; text-align: center; } .participant { width: 23%; }
        .page-break { page-break-before: always; }
        .signatures { margin-top: 22px; } .signatures td { border: 0; text-align: center; padding: 12px; }
        .signature-space { height: 58px; }
        .footer { position: fixed; bottom: -87px; left: 0; right: 0; height: 75px; border-top: 1px solid #aaa; padding-top: 5px; font-size: 8px; }
        .qr { float: right; width: 70px; height: 70px; } .muted { color: #555; }
    </style>
</head>
<body>
    <div class="footer">
        <img class="qr" src="{{ $qr }}" alt="QR validasi">
        <strong>SIMPRAM · Validasi arsip dokumen</strong><br>
        Nomor: NILAI-{{ $report->issued_at->format('Ymd') }}-{{ $report->id }}<br>
        Tanggal terbit: {{ $report->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('d F Y H:i') }} WITA<br>
        Kode validasi: {{ $report->code }}<br>
        QR memverifikasi identitas dan keutuhan arsip cetakan. {{ $report->with_signatures ? 'Tanda tangan juri dibubuhkan pada ruang yang tersedia.' : 'Dicetak tanpa ruang tanda tangan.' }}
    </div>
    @php($forms = $report->format === 'judges' ? $snapshot['judge_forms'] : [null])
    @foreach($forms as $form)
        <section @class(['page-break' => ! $loop->first])>
            <div class="heading">
                <div>{{ $snapshot['organizer'] }}</div>
                <h1>{{ $report->format === 'judges' ? 'REKAP PENILAIAN JURI' : 'REKAP LENGKAP HASIL PENILAIAN' }}</h1>
                <h2>{{ $snapshot['activity'] }}</h2><div>{{ $snapshot['title'] }}</div>
            </div>
            <div class="metadata">
                <strong>{{ $snapshot['is_final'] ? 'HASIL FINAL' : 'HASIL SEMENTARA' }}</strong><br>
                @if($form)
                    Juri {{ $form['number'] }} - {{ $form['name'] }} · {{ $form['finalized'] ? 'Penilaian sudah final' : 'Penilaian belum final; nilai tidak ditampilkan' }}<br>
                @else
                    @foreach($snapshot['judges'] as $judge)
                        Juri {{ $judge['number'] }} - {{ $judge['name'] }}{{ $judge['finalized'] ? '' : ' (belum final)' }}{{ $loop->last ? '' : ' | ' }}
                    @endforeach<br>
                @endif
                Nilai juri = jumlah (nilai kriteria ÷ nilai maksimal × bobot). Nilai akhir = rata-rata juri yang sudah final.<br>
                Draft dan juri yang dicabut tidak dihitung. Nilai sama mendapat peringkat sama (1, 1, 3).
            </div>
            @if($form)
                <table>
                    <thead><tr><th class="rank">No.</th><th class="participant">Peserta / regu</th>
                        @foreach($snapshot['criteria'] as $criterion)<th>{{ $criterion['name'] }}<br><span class="muted">Maks. {{ $criterion['max_score'] }} / Bobot {{ $criterion['weight'] }}%</span></th>@endforeach
                        <th>Total berbobot</th>
                    </tr></thead>
                    <tbody>@foreach($form['rows'] as $row)<tr>
                        <td class="rank">{{ $loop->iteration }}</td><td>{{ $row['name'] }}</td>
                        @foreach($snapshot['criteria'] as $criterion)<td class="number">{{ isset($row['scores'][$criterion['id']]) ? number_format($row['scores'][$criterion['id']], 2) : '—' }}</td>@endforeach
                        <td class="number">{{ $row['total'] === null ? '—' : number_format($row['total'], 2) }}</td>
                    </tr>@endforeach</tbody>
                </table>
            @else
                <table>
                    <thead><tr><th class="rank">Peringkat</th><th class="participant">Peserta / regu</th>
                        @foreach($snapshot['judges'] as $judge)<th>Juri {{ $judge['number'] }}</th>@endforeach
                        <th>Nilai rata-rata</th>
                    </tr></thead>
                    <tbody>@foreach($snapshot['rankings'] as $row)<tr>
                        <td class="rank">{{ $row['rank'] ?? '—' }}</td><td>{{ $row['name'] }}</td>
                        @foreach($snapshot['judges'] as $judge)<td class="number">{{ isset($row['judge_scores'][$judge['id']]) ? number_format($row['judge_scores'][$judge['id']], 2) : '—' }}</td>@endforeach
                        <td class="number">{{ $row['score'] === null ? '—' : number_format($row['score'], 2) }}</td>
                    </tr>@endforeach</tbody>
                </table>
            @endif
            @if($report->with_signatures)
                <table class="signatures">
                    @foreach(collect($form ? [$form] : $snapshot['judges'])->chunk(3) as $group)
                        <tr>@foreach($group as $signer)<td>
                            Juri {{ $signer['number'] }}<div class="signature-space"></div>
                            <strong>{{ $signer['name'] }}</strong>
                        </td>@endforeach</tr>
                    @endforeach
                </table>
            @endif
        </section>
    @endforeach
</body>
</html>
