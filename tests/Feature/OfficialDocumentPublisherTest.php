<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\User;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OfficialDocumentPublisherTest extends TestCase
{
    use RefreshDatabase;

    private function readySchool(): School
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['system_role' => 'super_admin']);
        $this->actingAs($user);
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        Permission::findOrCreate('reports.export', 'web');

        return $school;
    }

    private function publish(int $schoolId, string $payload): ReportVerification
    {
        return app(OfficialDocumentPublisher::class)->publish(
            schoolId: $schoolId,
            sourceType: Letter::class,
            sourceId: 987,
            documentType: 'administration_log',
            snapshotChecksum: hash('sha256', $payload),
            filename: 'arsip-administrasi.pdf',
            renderPdf: static fn (ReportVerification $verification): string => '%PDF-1.4 test '.$verification->code,
            title: 'Buku Administrasi',
        );
    }

    public function test_new_document_has_an_immutable_archive_and_verification_code(): void
    {
        Storage::fake('local');
        $school = $this->readySchool();
        $issued = $this->publish($school->id, 'first');

        $this->assertSame(1, $issued->revision);
        $this->assertNotNull($issued->file_sha256);
        $this->assertSame(48, strlen($issued->code));
        $this->assertSame(hash('sha256', '%PDF-1.4 test '.$issued->code), $issued->file_sha256);
        Storage::disk('local')->assertExists($issued->file_path);
        $this->assertStringContainsString($issued->code, app(ReportVerificationService::class)->publicUrl($issued));
        $this->assertStringStartsWith('data:image/', app(ReportVerificationService::class)->qrDataUri($issued));
    }

    public function test_same_snapshot_reuses_document_and_changed_snapshot_creates_revision(): void
    {
        Storage::fake('local');
        $school = $this->readySchool();

        $first = $this->publish($school->id, 'first');
        $again = $this->publish($school->id, 'first');
        $second = $this->publish($school->id, 'second');

        $this->assertSame($first->id, $again->id);
        $this->assertNotSame($first->code, $second->code);
        $this->assertSame(2, $second->revision);
        $this->assertSame($first->id, $second->replaces_id);
        $this->assertSame($second->id, $first->fresh()->superseded_by_id);
        $this->assertSame('superseded', $first->fresh()->publicStatus());
        $this->assertSame('valid', $second->publicStatus());
        Storage::disk('local')->assertExists($first->file_path);
        $this->assertSame(2, ReportVerification::query()->count());
    }

    public function test_failed_renderer_leaves_original_document_unchanged(): void
    {
        Storage::fake('local');
        $school = $this->readySchool();
        $first = $this->publish($school->id, 'first');

        try {
            app(OfficialDocumentPublisher::class)->publish(
                schoolId: $school->id,
                sourceType: Letter::class,
                sourceId: 987,
                documentType: 'administration_log',
                snapshotChecksum: hash('sha256', 'second'),
                filename: 'failed.pdf',
                renderPdf: static fn (ReportVerification $verification): string => throw new RuntimeException('renderer failed'),
            );
            $this->fail('Renderer should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('renderer failed', $exception->getMessage());
        }

        $this->assertSame(1, ReportVerification::query()->count());
        $this->assertNull($first->fresh()->superseded_by_id);
        Storage::disk('local')->assertExists($first->file_path);
    }

    public function test_cross_school_publication_is_denied(): void
    {
        Storage::fake('local');
        $this->readySchool();
        $another = School::factory()->create();

        $this->expectException(HttpException::class);
        $this->publish($another->id, 'foreign');
    }

    public function test_invalid_pdf_cannot_be_published(): void
    {
        Storage::fake('local');
        $school = $this->readySchool();

        $this->expectException(ValidationException::class);
        app(OfficialDocumentPublisher::class)->publish(
            schoolId: $school->id,
            sourceType: Letter::class,
            sourceId: 987,
            documentType: 'administration_log',
            snapshotChecksum: hash('sha256', 'invalid'),
            filename: 'invalid.pdf',
            renderPdf: static fn (ReportVerification $verification): string => 'not a PDF',
        );
    }
}
