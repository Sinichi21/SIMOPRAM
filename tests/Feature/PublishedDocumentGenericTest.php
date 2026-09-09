<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\ReportVerification;
use App\Models\School;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishedDocumentGenericTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_document_can_be_issued_without_semester_closure(): void
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);

        $document = app(ReportVerificationService::class)->issueDocument(
            schoolId: $school->id,
            documentType: 'letter',
            sourceType: Letter::class,
            sourceId: 999,
            snapshotChecksum: str_repeat('a', 64),
            documentNumber: '49/02/IX.26/04.007-008-C',
            title: 'Pemberitahuan Kegiatan',
            metadata: ['security_classification' => 'Biasa']
        );

        $this->assertNull($document->semester_closure_id);
        $this->assertSame('letter', $document->document_type);
        $this->assertSame('49/02/IX.26/04.007-008-C', $document->document_number);
        $this->assertSame('valid', $document->publicStatus());
        $this->assertSame('Pemberitahuan Kegiatan', $document->publicTitle());
    }

    public function test_restricted_document_hides_title_on_public_verification(): void
    {
        $document = new ReportVerification([
            'document_type' => 'letter',
            'title' => 'Isi Rahasia',
            'metadata' => ['security_classification' => 'Rahasia'],
        ]);

        $this->assertSame('Dokumen berklasifikasi Rahasia', $document->publicTitle());
    }
}
