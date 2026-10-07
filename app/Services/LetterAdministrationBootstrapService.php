<?php

namespace App\Services;

use App\Models\LetterField;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\School;
use App\Models\SchoolLetterSetting;
use App\Models\SchoolDocumentSetting;
use App\Models\ScoutAdministrationProfile;

class LetterAdministrationBootstrapService
{
    public function ensureForSchool(int $schoolId): void
    {
        $school = School::query()->with('scoutGroups')->findOrFail($schoolId);
        $group = $school->scoutGroups->first();

        $gudepCode = $this->combinedGudepCode($group?->male_number, $group?->female_number);

        SchoolLetterSetting::withoutGlobalScope('school')->firstOrCreate(
            ['school_id' => $schoolId],
            [
                'gudep_code' => $gudepCode,
                'number_format' => '{sequence}/{type}/{month_roman}.{year_short}/{gudep}-{field}',
                'agenda_format' => '{sequence_padded}/SM/{year}',
                'year_format' => 'short',
                'sequence_reset' => 'yearly',
                'letterhead_title' => 'GERAKAN PRAMUKA',
                'letterhead_subtitle' => $group?->name ?: $school->name,
                'letterhead_address' => $group?->secretariat_address ?: $school->address,
                'city' => $school->city,
                'default_signatory_name' => $group?->head_coach_name,
                'default_signatory_position' => 'Pembina Gugusdepan',
            ]
        );

        $legacy = SchoolLetterSetting::withoutGlobalScope('school')->where('school_id', $schoolId)->first();
        $documentSetting = SchoolDocumentSetting::withoutGlobalScope('school')->where('school_id', $schoolId)->first();

        $profiles = [
            'male' => 'Administrasi Gudep Putra',
            'female' => 'Administrasi Gudep Putri',
            'mabigus' => 'Administrasi Mabigus',
        ];

        foreach ($profiles as $type => $name) {
            ScoutAdministrationProfile::withoutGlobalScope('school')->firstOrCreate(
                ['school_id' => $schoolId, 'type' => $type],
                [
                    'name' => $name,
                    'number_format' => (string) $legacy->number_format,
                    'agenda_format' => (string) $legacy->agenda_format,
                    'default_signatory_user_id' => $documentSetting?->default_letter_signatory_user_id,
                    'letterhead_title' => $legacy->letterhead_title,
                    'letterhead_subtitle' => null,
                    'letterhead_address' => $legacy->letterhead_address,
                    'is_active' => true,
                ]
            );
        }

        $types = [
            ['01', 'Surat Permohonan', 'Permohonan izin, narasumber, peminjaman barang, dan permohonan sejenis.'],
            ['02', 'Surat Pemberitahuan', 'Pemberitahuan kegiatan, edaran, dan komunikasi rutin.'],
            ['03', 'SK / Piagam / Sertifikat', 'Surat keputusan, piagam, sertifikat, dan surat keterangan penghargaan.'],
            ['04', 'Surat Tugas / Surat Perintah', 'Surat tugas atau surat perintah untuk pelaksanaan kegiatan.'],
        ];
        foreach ($types as $i => [$code, $name, $description]) {
            LetterType::withoutGlobalScope('school')->firstOrCreate(
                ['school_id' => $schoolId, 'code' => $code],
                ['name' => $name, 'description' => $description, 'is_active' => true, 'sort_order' => $i + 1]
            );
        }

        $fields = [
            ['A', 'Pimpinan / Kebijakan', 'Pimpinan atau staf pimpinan yang menangani kebijakan pimpinan.'],
            ['B', 'Bidang Keuangan', 'Surat yang berkaitan dengan administrasi dan pengelolaan keuangan.'],
            ['C', 'Bidang Organisasi dan Kegiatan', 'Surat yang berkaitan dengan organisasi dan kegiatan kepramukaan.'],
        ];
        foreach ($fields as $i => [$code, $name, $description]) {
            LetterField::withoutGlobalScope('school')->firstOrCreate(
                ['school_id' => $schoolId, 'code' => $code],
                ['name' => $name, 'description' => $description, 'is_active' => true, 'sort_order' => $i + 1]
            );
        }

        $typeIds = LetterType::withoutGlobalScope('school')->where('school_id', $schoolId)->pluck('id', 'code');
        $templates = $this->defaultTemplates();
        foreach ($templates as $i => $template) {
            LetterTemplate::withoutGlobalScope('school')->firstOrCreate(
                ['school_id' => $schoolId, 'slug' => $template['slug']],
                [
                    'letter_type_id' => $typeIds[$template['type']] ?? null,
                    'name' => $template['name'],
                    'document_kind' => $template['document_kind'] ?? 'letter',
                    'title' => $template['title'],
                    'body_template' => $template['body'],
                    'default_field_code' => $template['field'],
                    'requires_recipient' => $template['requires_recipient'] ?? true,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }

    private function combinedGudepCode(?string $male, ?string $female): ?string
    {
        $male = trim((string) $male);
        $female = trim((string) $female);
        if ($male === '' && $female === '') {
            return null;
        }
        if ($male === '') {
            return $female;
        }
        if ($female === '') {
            return $male;
        }

        if (str_contains($male, '.') && str_contains($female, '.')) {
            [$prefixMale, $suffixMale] = explode('.', $male, 2);
            [$prefixFemale, $suffixFemale] = explode('.', $female, 2);
            if ($prefixMale === $prefixFemale) {
                return $prefixMale.'.'.$suffixMale.'-'.$suffixFemale;
            }
        }

        return $male.'-'.$female;
    }

    private function defaultTemplates(): array
    {
        return [
            ['slug' => 'surat-permohonan', 'name' => 'Surat Permohonan', 'type' => '01', 'field' => 'C', 'title' => 'PERMOHONAN', 'body' => "Yth. {{recipient}}\n\nSalam Pramuka,\n\nSehubungan dengan {{activity_name}}, kami memohon {{request_detail}}.\n\nHari/Tanggal : {{activity_date}}\nWaktu : {{activity_time}}\nTempat : {{activity_location}}\n\nDemikian permohonan ini disampaikan. Atas perhatian dan kerja samanya kami ucapkan terima kasih."],
            ['slug' => 'surat-pemberitahuan', 'name' => 'Surat Pemberitahuan', 'type' => '02', 'field' => 'C', 'title' => 'PEMBERITAHUAN', 'body' => "Yth. {{recipient}}\n\nSalam Pramuka,\n\nDengan hormat kami memberitahukan bahwa akan dilaksanakan {{activity_name}}.\n\nHari/Tanggal : {{activity_date}}\nWaktu : {{activity_time}}\nTempat : {{activity_location}}\n\n{{additional_information}}\n\nDemikian pemberitahuan ini disampaikan."],
            ['slug' => 'surat-tugas', 'name' => 'Surat Tugas', 'type' => '04', 'field' => 'A', 'title' => 'SURAT TUGAS', 'body' => "Pertimbangan:\n{{considerations}}\n\nDasar:\n{{legal_basis}}\n\nMENUGASKAN\n\nKepada:\n{{assignees}}\n\nUntuk:\n{{assignment_purpose}}\n\nSurat tugas ini dilaksanakan dengan penuh tanggung jawab."],
            ['slug' => 'surat-undangan', 'name' => 'Surat Undangan', 'type' => '02', 'field' => 'C', 'title' => 'UNDANGAN', 'body' => "Yth. {{recipient}}\nDi {{recipient_location}}\n\nSalam Pramuka,\n\nKami mengundang Kakak/Bapak/Ibu untuk menghadiri kegiatan berikut:\n\nHari/Tanggal : {{activity_date}}\nWaktu : {{activity_time}}\nTempat : {{activity_location}}\nAcara : {{activity_name}}\n\nDemikian undangan ini disampaikan. Atas kehadirannya kami ucapkan terima kasih."],
            ['slug' => 'surat-pengantar', 'name' => 'Surat Pengantar', 'type' => '02', 'field' => 'C', 'title' => 'SURAT PENGANTAR', 'body' => "Yth. {{recipient}}\n\nBersama surat ini kami sampaikan {{delivered_items}} untuk {{purpose}}.\n\nMohon dapat diterima dan dipergunakan sebagaimana mestinya."],
            ['slug' => 'surat-keterangan', 'name' => 'Surat Keterangan / Piagam', 'type' => '03', 'field' => 'A', 'title' => 'SURAT KETERANGAN', 'body' => "Yang bertanda tangan di bawah ini menerangkan bahwa:\n\nNama : {{person_name}}\nNTA/NISN : {{person_identity}}\nGugusdepan : {{gudep}}\n\n{{statement}}\n\nSurat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya."],
            ['slug' => 'lembar-tanda-terima', 'name' => 'Lembar Tanda Terima', 'type' => '02', 'field' => 'C', 'title' => 'LEMBAR TANDA TERIMA', 'body' => "Telah diterima dari : {{sender_name}}\nOleh : {{recipient}}\n\nUraian dokumen/barang:\n{{received_items}}\n\nTanggal diterima : {{received_date}}\n\nPenerima,\n{{receiver_name}}"],
            ['slug' => 'pengumuman', 'name' => 'Pengumuman', 'type' => '02', 'field' => 'C', 'title' => 'PENGUMUMAN', 'body' => "TENTANG\n{{subject}}\n\n{{announcement_body}}\n\nDemikian pengumuman ini disampaikan untuk menjadi perhatian."],
            ['slug' => 'nota-dinas', 'name' => 'Nota Dinas', 'type' => '02', 'field' => 'A', 'title' => 'NOTA DINAS', 'body' => "Kepada : {{recipient}}\nDari : {{sender_name}}\nPerihal : {{subject}}\n\n{{note_body}}"],
            ['slug' => 'laporan-kegiatan', 'name' => 'Laporan Kegiatan', 'type' => '03', 'field' => 'C', 'title' => 'LAPORAN KEGIATAN', 'document_kind' => 'report', 'requires_recipient' => false, 'body' => "A. PENDAHULUAN\n{{introduction}}\n\nB. KEGIATAN YANG DILAKSANAKAN\n{{activity_execution}}\n\nC. HASIL / EVALUASI\n{{evaluation}}\n\nD. PENUTUP\n{{closing}}"],
        ];
    }
}
