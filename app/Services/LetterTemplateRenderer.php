<?php

namespace App\Services;

use App\Models\LetterTemplate;

class LetterTemplateRenderer
{
    /**
     * Placeholder yang disediakan oleh Template Builder.
     * Key disimpan di body template dengan format {{ key }}.
     *
     * @return array<string, string>
     */
    public function catalog(): array
    {
        return [
            'recipient' => 'Penerima',
            'recipient_location' => 'Lokasi penerima',
            'subject' => 'Perihal',
            'letter_date' => 'Tanggal surat',
            'city' => 'Kota penerbitan',
            'gudep' => 'Kode gugusdepan',
            'activity_name' => 'Nama kegiatan',
            'activity_date' => 'Hari / tanggal kegiatan',
            'activity_time' => 'Waktu kegiatan',
            'activity_location' => 'Tempat kegiatan',
            'request_detail' => 'Detail permohonan',
            'additional_information' => 'Informasi tambahan',
            'considerations' => 'Pertimbangan',
            'legal_basis' => 'Dasar',
            'assignees' => 'Pihak yang ditugaskan',
            'assignment_purpose' => 'Tujuan penugasan',
            'purpose' => 'Tujuan / keperluan',
            'delivered_items' => 'Dokumen / barang yang disampaikan',
            'person_name' => 'Nama orang',
            'person_identity' => 'NTA / NISN / identitas',
            'statement' => 'Isi keterangan',
            'sender_name' => 'Nama pengirim',
            'received_items' => 'Dokumen / barang yang diterima',
            'received_date' => 'Tanggal diterima',
            'receiver_name' => 'Nama penerima barang/dokumen',
            'announcement_body' => 'Isi pengumuman',
            'note_body' => 'Isi nota dinas',
            'introduction' => 'Pendahuluan laporan',
            'activity_execution' => 'Pelaksanaan kegiatan',
            'evaluation' => 'Hasil / evaluasi',
            'closing' => 'Penutup laporan',
            'signatory_name' => 'Nama penandatangan',
            'signatory_position' => 'Jabatan penandatangan',
            'signatory_identity' => 'NTA / identitas penandatangan',
        ];
    }

    public function label(string $placeholder): string
    {
        return $this->catalog()[$placeholder] ?? str($placeholder)->replace('_', ' ')->title()->toString();
    }

    public function render(LetterTemplate $template, array $data, bool $markMissing = false): string
    {
        $body = $template->body_template;

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $match) use ($data, $markMissing): string {
            $key = $match[1];
            $value = $data[$key] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }

            return $markMissing ? '[Belum diisi: '.$this->label($key).']' : $match[0];
        }, $body) ?? $body;
    }

    /** @return array<int, string> */
    public function placeholders(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /** @return array<int, string> */
    public function missingPlaceholders(LetterTemplate $template, array $data): array
    {
        return array_values(array_filter(
            $this->placeholders($template->body_template),
            fn (string $key): bool => trim((string) ($data[$key] ?? '')) === ''
        ));
    }
}
