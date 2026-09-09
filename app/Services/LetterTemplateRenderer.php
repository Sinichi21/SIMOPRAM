<?php

namespace App\Services;

use App\Models\LetterTemplate;

class LetterTemplateRenderer
{
    public function __construct(
        private readonly LetterTemplateHtmlSanitizer $sanitizer
    ) {}

    /** @return array<string, string> */
    public function systemCatalog(): array
    {
        return [
            'letter_number' => 'Nomor Surat',
            'letter_date' => 'Tanggal Surat',
            'published_date' => 'Tanggal Terbit',
            'subject' => 'Perihal / Judul',
            'recipient' => 'Tujuan Surat',
            'recipient_location' => 'Lokasi Tujuan',
            'attachment_label' => 'Keterangan Lampiran',
            'attachment_count' => 'Jumlah Lampiran',
            'city' => 'Kota Penerbitan',
            'gudep' => 'Kode Gugusdepan',
            'signatory_name' => 'Nama Penandatangan',
            'signatory_position' => 'Jabatan Penandatangan',
            'signatory_identity' => 'NTA / Identitas Penandatangan',
        ];
    }

    /** @return array<string, string> */
    public function contentCatalog(): array
    {
        return [
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
        ];
    }

    /** @return array<string, string> */
    public function catalog(): array
    {
        return $this->systemCatalog() + $this->contentCatalog();
    }

    /** @return array<int, string> */
    public function systemKeys(): array
    {
        return array_keys($this->systemCatalog());
    }

    public function label(string $placeholder): string
    {
        return $this->catalog()[$placeholder] ?? str($placeholder)->replace('_', ' ')->title()->toString();
    }

    public function render(LetterTemplate $template, array $data, bool $markMissing = false): string
    {
        $body = $this->sanitizer->sanitize($template->body_template);

        $rendered = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function (array $match) use ($data, $markMissing): string {
                $key = $match[1];
                $value = $data[$key] ?? null;

                if ($value !== null && trim((string) $value) !== '') {
                    return $this->sanitizer->escapePlaceholderValue($value);
                }

                if (in_array($key, $this->systemKeys(), true)) {
                    return $markMissing
                        ? '<span style="color:#777">['.htmlspecialchars($this->label($key), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').']</span>'
                        : '';
                }

                return $markMissing
                    ? '<span>[Belum diisi: '.htmlspecialchars($this->label($key), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').']</span>'
                    : $match[0];
            },
            $body
        ) ?? $body;

        return $this->sanitizer->sanitize($rendered);
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
        $systemKeys = $this->systemKeys();

        return array_values(array_filter(
            $this->placeholders($template->body_template),
            function (string $key) use ($template, $data, $systemKeys): bool {
                if ($key === 'recipient' && $template->requires_recipient) {
                    return trim((string) ($data[$key] ?? '')) === '';
                }

                if (in_array($key, $systemKeys, true)) {
                    return false;
                }

                return trim((string) ($data[$key] ?? '')) === '';
            }
        ));
    }

    public function containsAny(string $body, array $keys): bool
    {
        $used = $this->placeholders($body);

        return count(array_intersect($used, $keys)) > 0;
    }
}
