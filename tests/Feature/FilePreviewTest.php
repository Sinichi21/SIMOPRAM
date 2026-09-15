<?php

use App\Http\Middleware\PreviewFileResponse;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

test('public PDF attachments open inline and download only on explicit request', function () {
    Storage::fake('local');
    Storage::disk('local')->put('guide.pdf', '%PDF-1.4 guide');
    $activity = Activity::factory()->publicRegistration()->create(['attachments' => [
        ['path' => 'guide.pdf', 'name' => 'panduan.pdf', 'mime' => 'application/pdf'],
    ]]);
    $url = route('public.content.attachment', ['activities', $activity->id, 0]);
    $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');
    $this->get($url.'?download=1')->assertDownload('panduan.pdf');
    $this->get(route('public.activities.show', $activity))->assertSee('target="_blank"', false)->assertSee('Lihat panduan.pdf');
    $activity->update(['is_public' => false]);
    $this->get($url)->assertNotFound();
    $this->get($url.'?download=1')->assertNotFound();
});

test('unsupported files show a preview page without rendering untrusted active content', function () {
    Storage::fake('local');
    Storage::disk('local')->put('document.doc', '<script>alert(1)</script>');
    $activity = Activity::factory()->publicRegistration()->create(['attachments' => [
        ['path' => 'document.doc', 'name' => 'document.doc'],
    ]]);
    $url = route('public.content.attachment', ['activities', $activity->id, 0]);
    $this->get($url)->assertOk()->assertSee('Pratinjau berkas')->assertSee('Unduh berkas')->assertDontSee('<script>alert(1)</script>', false);
    $this->get($url.'?download=1')->assertDownload('document.doc');
});

test('Livewire file responses become preview events while other effects are preserved', function () {
    $request = Request::create('/livewire/update', 'POST', server: ['HTTP_X_LIVEWIRE' => '']);
    $file = ['name' => 'rekap.csv', 'content' => base64_encode("Nama,Status\nSiswa,Hadir"), 'contentType' => 'text/csv'];
    $response = app(PreviewFileResponse::class)->handle($request, fn () => response()->json(['components' => [
        ['snapshot' => '{}', 'effects' => ['download' => $file, 'html' => '<div>Rekap</div>']],
    ]]));
    $data = $response->getData(true);
    expect($data['components'][0]['effects'])->not->toHaveKey('download')
        ->and($data['components'][0]['effects']['html'])->toBe('<div>Rekap</div>')
        ->and($data['components'][0]['effects']['dispatches'][0])->toBe(['name' => 'file-preview', 'params' => $file + ['previewText' => null]]);
});

test('Word attachments preview escaped text while preserving the original download', function () {
    Storage::fake('local');
    $path = Storage::disk('local')->path('guide.docx');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Panduan lomba &lt;script&gt;alert(1)&lt;/script&gt;</w:t></w:r></w:p></w:body></w:document>');
    $zip->close();
    $activity = Activity::factory()->publicRegistration()->create(['attachments' => [['path' => 'guide.docx', 'name' => 'guide.docx']]]);
    $url = route('public.content.attachment', ['activities', $activity->id, 0]);
    $this->get($url)->assertOk()->assertSee('Panduan lomba')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->get($url.'?download=1')->assertDownload('guide.docx');
});

test('Excel preview reads the first worksheet without executing formulas', function (string $format) {
    Storage::fake('local');
    $filename = 'rekap.'.strtolower($format);
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setCellValue('A1', 'Nama peserta')->setCellValue('A2', 'Siswa Preview')->setCellValue('B2', '=1+2')->setCellValue('A201', 'Di luar batas');
    IOFactory::createWriter($spreadsheet, $format)->save(Storage::disk('local')->path($filename));
    $spreadsheet->disconnectWorksheets();
    $activity = Activity::factory()->publicRegistration()->create(['attachments' => [['path' => $filename, 'name' => $filename]]]);
    $this->get(route('public.content.attachment', ['activities', $activity->id, 0]))
        ->assertOk()->assertSee('Nama peserta')->assertSee('Siswa Preview')->assertSee('=1+2')->assertDontSee('Di luar batas');
})->with(['Xlsx', 'Xls']);
