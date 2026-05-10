<?php

namespace Tests\Unit\Controllers;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: CertificateBatchController
 *
 * Jalankan: php artisan test --filter CertificateBatchControllerTest
 */
class CertificateBatchControllerTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Pastikan direktori cache fisik ada — dipakai progress() dan downloadZip()
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $this->institution = Institution::factory()->create();
        $this->user        = User::factory()->adminOf($this->institution)->create();
    }

    protected function tearDown(): void
    {
        // Hapus semua file PDF cache fisik agar tidak bocor antar-test
        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    // ══════════════════════════════════════════════
    // Helper
    // ══════════════════════════════════════════════

    private function makeBatchWithCerts(int $count = 3, array $batchOverrides = []): CertificateBatch
    {
        $batch = CertificateBatch::factory()->create(array_merge([
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->user->id,
            'total'          => $count,
            'processed'      => $count,
            'status'         => 'done',
            'event_name'     => 'Pelatihan Test',
        ], $batchOverrides));

        Certificate::factory()->count($count)->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        return $batch->fresh(['certificates']);
    }

    /**
     * Tulis PDF langsung ke storage_path('app/pdf_cache') — path fisik sungguhan.
     *
     * Kenapa tidak Storage::fake('local'):
     *  - progress()     → file_exists(storage_path(...))   ← butuh file fisik
     *  - downloadZip()  → ZipArchive + storage_path(...)   ← butuh file fisik
     *  - destroyBatch() → Storage::disk('local')->delete() ← root default = storage_path('app')
     *
     * Storage::disk('local') default root = storage_path('app'), jadi path relatif
     * 'pdf_cache/{token}.pdf' resolves ke storage_path('app/pdf_cache/{token}.pdf') — sama
     * persis dengan path fisik yang kita tulis. Dengan demikian destroyBatch() juga
     * berhasil menghapus file yang sama tanpa perlu Storage::fake().
     */
    private function putPdfInCache(Certificate $cert): void
    {
        $path = storage_path('app') . DIRECTORY_SEPARATOR
              . 'pdf_cache' . DIRECTORY_SEPARATOR
              . $cert->verification_token . '.pdf';

        file_put_contents($path, '%PDF-1.4 fake-content');
    }

    // ══════════════════════════════════════════════
    // store() — POST /dashboard/certificates/batch
    // ══════════════════════════════════════════════

    #[Test]
    public function store_requires_authentication(): void
    {
        $this->postJson(route('certificate.batch.store'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function store_fails_validation_when_participants_empty(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [],
                'event_name'   => 'Test',
                'event_date'   => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participants']);
    }

    #[Test]
    public function store_fails_validation_when_event_name_missing(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Budi', 'perusahaan' => null, 'nomor' => null]],
                'event_date'   => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event_name']);
    }

    #[Test]
    public function store_fails_validation_when_participant_nama_missing(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['perusahaan' => 'PT Test']],
                'event_name'   => 'Test Event',
                'event_date'   => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participants.0.nama']);
    }

    #[Test]
    public function store_creates_batch_and_dispatches_jobs(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [
                    ['nama' => 'Budi Santoso', 'perusahaan' => 'PT A', 'nomor' => null],
                    ['nama' => 'Citra Dewi',   'perusahaan' => null,   'nomor' => null],
                ],
                'event_name'  => 'Workshop Unit Test',
                'event_date'  => '12 Mei 2026',
                'event_place' => 'Jakarta',
                'signer_name' => 'Dr. Test',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['batch_id', 'batch_token', 'total']);

        $this->assertDatabaseHas('certificate_batches', [
            'institution_id' => $this->institution->id,
            'event_name'     => 'Workshop Unit Test',
            'status'         => 'processing',
            'total'          => 2,
        ]);

        Queue::assertPushed(\App\Jobs\ProcessCertificateJob::class, 2);
    }

    #[Test]
    public function store_returns_correct_total_in_response(): void
    {
        Queue::fake();

        $participants = [];
        for ($i = 0; $i < 5; $i++) {
            $participants[] = ['nama' => "Peserta {$i}", 'perusahaan' => null, 'nomor' => null];
        }

        $this->actingAs($this->user)
            ->postJson(route('certificate.batch.store'), [
                'participants' => $participants,
                'event_name'   => 'Acara Besar',
                'event_date'   => '2026-06-01',
            ])
            ->assertOk()
            ->assertJson(['total' => 5]);
    }

    // ══════════════════════════════════════════════
    // progress() — GET /dashboard/certificates/batch/{token}/progress
    // ══════════════════════════════════════════════

    #[Test]
    public function progress_requires_authentication(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        $this->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertUnauthorized();
    }

    #[Test]
    public function progress_returns_404_for_unknown_token(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', 'token-tidak-ada'))
            ->assertNotFound();
    }

    #[Test]
    public function progress_returns_404_when_batch_belongs_to_other_institution(): void
    {
        $otherInst  = Institution::factory()->create();
        $otherBatch = CertificateBatch::factory()->create(['institution_id' => $otherInst->id]);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', $otherBatch->batch_token))
            ->assertNotFound();
    }

    #[Test]
    public function progress_returns_correct_structure(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'total'          => 3,
            'processed'      => 1,
            'failed'         => 0,
            'status'         => 'processing',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertOk()
            ->assertJsonStructure([
                'status', 'total', 'processed', 'failed',
                'percent', 'failed_entries', 'cached_pdf',
                'zip_ready', 'eta_seconds', 'batch_url',
            ]);
    }

    #[Test]
    public function progress_counts_cached_pdfs_correctly(): void
    {
        $batch = $this->makeBatchWithCerts(3, ['status' => 'done']);

        $certs = $batch->certificates;
        $this->putPdfInCache($certs[0]);
        $this->putPdfInCache($certs[1]);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertOk()
            ->assertJson(['cached_pdf' => 2]);
    }

    #[Test]
    public function progress_zip_ready_true_when_all_pdfs_cached_and_done(): void
    {
        $batch = $this->makeBatchWithCerts(2, ['status' => 'done', 'failed' => 0]);

        foreach ($batch->certificates as $cert) {
            $this->putPdfInCache($cert);
        }

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertOk()
            ->assertJson(['zip_ready' => true]);
    }

    #[Test]
    public function progress_zip_ready_false_when_not_all_pdfs_cached(): void
    {
        $batch = $this->makeBatchWithCerts(3, ['status' => 'done']);

        $this->putPdfInCache($batch->certificates->first());

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertOk()
            ->assertJson(['zip_ready' => false]);
    }

    #[Test]
    public function progress_batch_url_is_null_when_not_done(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'status'         => 'processing',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertOk()
            ->assertJson(['batch_url' => null]);
    }

    #[Test]
    public function progress_batch_url_is_present_when_done(): void
    {
        $batch = CertificateBatch::factory()->done()->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('certificate.batch.progress', $batch->batch_token))
            ->assertOk();

        $this->assertNotNull($response->json('batch_url'));
        $this->assertStringContainsString($batch->batch_token, $response->json('batch_url'));
    }

    // ══════════════════════════════════════════════
    // show() — GET /batch/{batch_token}  (publik)
    // ══════════════════════════════════════════════

    #[Test]
    public function show_is_accessible_without_authentication(): void
    {
        $batch = CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->get(route('certificate.batch.show', $batch->batch_token))
            ->assertOk();
    }

    #[Test]
    public function show_returns_404_for_unknown_token(): void
    {
        $this->get(route('certificate.batch.show', 'token-palsu'))
            ->assertNotFound();
    }

    // ══════════════════════════════════════════════
    // detail() — GET /dashboard/history/batch/{batchId}/detail
    // ══════════════════════════════════════════════

    #[Test]
    public function detail_requires_authentication(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        $this->get(route('certificate.batch.detail', $batch->id))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function detail_returns_404_for_other_institution_batch(): void
    {
        $otherInst  = Institution::factory()->create();
        $otherBatch = CertificateBatch::factory()->create(['institution_id' => $otherInst->id]);

        $this->actingAs($this->user)
            ->get(route('certificate.batch.detail', $otherBatch->id))
            ->assertNotFound();
    }

    #[Test]
    public function detail_renders_view_with_batch_and_certificates(): void
    {
        $batch = $this->makeBatchWithCerts(2);

        $this->actingAs($this->user)
            ->get(route('certificate.batch.detail', $batch->id))
            ->assertOk()
            ->assertViewIs('certificate.batch-detail')
            ->assertViewHas('batch')
            ->assertViewHas('certificates');
    }

    // ══════════════════════════════════════════════
    // destroyBatch() — DELETE /dashboard/history/batch/{batchId}
    // ══════════════════════════════════════════════

    #[Test]
    public function destroy_batch_requires_authentication(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        $this->delete(route('certificate.batch.destroy', $batch->id))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function destroy_batch_returns_404_for_other_institution(): void
    {
        $otherInst  = Institution::factory()->create();
        $otherBatch = CertificateBatch::factory()->create(['institution_id' => $otherInst->id]);

        $this->actingAs($this->user)
            ->delete(route('certificate.batch.destroy', $otherBatch->id))
            ->assertNotFound();
    }

    #[Test]
    public function destroy_batch_deletes_batch_and_all_certificates(): void
    {
        $batch   = $this->makeBatchWithCerts(3);
        $certIds = $batch->certificates->pluck('id')->toArray();

        $this->actingAs($this->user)
            ->delete(route('certificate.batch.destroy', $batch->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('certificate_batches', ['id' => $batch->id]);
        foreach ($certIds as $certId) {
            $this->assertDatabaseMissing('certificates', ['id' => $certId]);
        }
    }

    #[Test]
    public function destroy_batch_also_deletes_pdf_cache_files(): void
    {
        // Fake disk 'local' khusus test ini agar Storage::disk('local')->delete()
        // bisa diverifikasi tanpa bergantung pada path fisik yang berbeda tiap OS.
        Storage::fake('local');

        $batch  = $this->makeBatchWithCerts(2);
        $tokens = $batch->certificates->pluck('verification_token')->toArray();

        // Isi fake disk agar exists() di controller return true
        foreach ($tokens as $token) {
            Storage::disk('local')->put('pdf_cache/' . $token . '.pdf', '%PDF-fake');
        }

        foreach ($tokens as $token) {
            Storage::disk('local')->assertExists('pdf_cache/' . $token . '.pdf');
        }

        $this->actingAs($this->user)
            ->delete(route('certificate.batch.destroy', $batch->id))
            ->assertRedirect();

        // Controller memanggil Storage::disk('local')->delete() — file harus hilang
        foreach ($tokens as $token) {
            Storage::disk('local')->assertMissing('pdf_cache/' . $token . '.pdf');
        }
    }

    #[Test]
    public function destroy_batch_succeeds_when_no_pdf_cache_exists(): void
    {
        $batch = $this->makeBatchWithCerts(2);

        $this->actingAs($this->user)
            ->delete(route('certificate.batch.destroy', $batch->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('certificate_batches', ['id' => $batch->id]);
    }

    // ══════════════════════════════════════════════
    // certificates() — GET /dashboard/certificates/batch/{token}/certs
    // ══════════════════════════════════════════════

    #[Test]
    public function certificates_requires_authentication(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        $this->getJson(route('certificate.batch.certs', $batch->batch_token))
            ->assertUnauthorized();
    }

    #[Test]
    public function certificates_returns_404_for_other_institution_batch(): void
    {
        $otherInst  = Institution::factory()->create();
        $otherBatch = CertificateBatch::factory()->create(['institution_id' => $otherInst->id]);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.certs', $otherBatch->batch_token))
            ->assertNotFound();
    }

    #[Test]
    public function certificates_returns_list_with_correct_count(): void
    {
        $batch = $this->makeBatchWithCerts(4);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.certs', $batch->batch_token))
            ->assertOk()
            ->assertJson(['total' => 4])
            ->assertJsonStructure([
                'total',
                'certificates' => [
                    '*' => ['nama', 'nomor', 'perusahaan', 'verification_url', 'pdf_url', 'verification_token'],
                ],
            ]);
    }

    #[Test]
    public function certificates_verification_url_contains_token(): void
    {
        $batch = $this->makeBatchWithCerts(1);
        $cert  = $batch->certificates->first();

        $response = $this->actingAs($this->user)
            ->getJson(route('certificate.batch.certs', $batch->batch_token))
            ->assertOk();

        $certData = $response->json('certificates.0');
        $this->assertStringContainsString($cert->verification_token, $certData['verification_url']);
    }

    // ══════════════════════════════════════════════
    // downloadZip() — GET /dashboard/certificates/batch/{token}/zip
    // ══════════════════════════════════════════════

    #[Test]
    public function download_zip_requires_authentication(): void
    {
        $batch = CertificateBatch::factory()->create(['institution_id' => $this->institution->id]);

        $this->get(route('certificate.batch.zip', $batch->batch_token))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function download_zip_returns_422_when_no_certificates(): void
    {
        $batch = CertificateBatch::factory()->done()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.zip', $batch->batch_token))
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Tidak ada sertifikat dalam batch ini.']);
    }

    #[Test]
    public function download_zip_returns_422_when_no_pdf_in_cache(): void
    {
        $batch = $this->makeBatchWithCerts(2, ['status' => 'done']);

        $this->actingAs($this->user)
            ->getJson(route('certificate.batch.zip', $batch->batch_token))
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'PDF belum siap. Tunggu hingga semua sertifikat selesai diproses, lalu coba lagi.']);
    }

    #[Test]
    public function download_zip_returns_zip_file_when_pdfs_cached(): void
    {
        $batch = $this->makeBatchWithCerts(2, ['status' => 'done', 'event_name' => 'Test Event']);

        foreach ($batch->certificates as $cert) {
            $this->putPdfInCache($cert);
        }

        $response = $this->actingAs($this->user)
            ->get(route('certificate.batch.zip', $batch->batch_token));

        $response->assertOk();
        $this->assertStringContainsString('application/zip', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function download_zip_filename_contains_event_name_slug(): void
    {
        $batch = $this->makeBatchWithCerts(1, [
            'status'     => 'done',
            'event_name' => 'Workshop Laravel',
        ]);

        foreach ($batch->certificates as $cert) {
            $this->putPdfInCache($cert);
        }

        $response = $this->actingAs($this->user)
            ->get(route('certificate.batch.zip', $batch->batch_token));

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('workshop_laravel', strtolower($disposition));
    }
    #[Test]
    public function download_zip_returns_500_if_zip_cannot_be_opened(): void
    {
        $batch = $this->makeBatchWithCerts(1, ['status' => 'done']);
        $this->putPdfInCache($batch->certificates->first());

        // 1. Buat Mock ZipArchive biasa
        $mockZip = \Mockery::mock(\ZipArchive::class);
        $mockZip->shouldReceive('open')->andReturn(false);

        // 2. Suntikkan ke controller
        $controller = new \App\Http\Controllers\CertificateBatchController($mockZip);

        // 3. PENTING: Simulasi login agar auth()->user() tidak null
        $this->actingAs($this->user); 

        // 4. Panggil method secara langsung
        $response = $controller->downloadZip($batch->batch_token);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Gagal membuat file ZIP', $response->getContent());
    }

    // ══════════════════════════════════════════════
    // downloadZip() — temp dir belum ada → mkdir() dipanggil
    // ══════════════════════════════════════════════

    #[Test]
    public function download_zip_creates_temp_dir_when_not_exists(): void
    {
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (is_dir($tempDir)) {
            foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tempDir);
        }

        $this->assertDirectoryDoesNotExist($tempDir);

        $batch = CertificateBatch::factory()->done()->create([
            'institution_id' => $this->institution->id,
            'event_name'     => 'Test Event',
        ]);
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'batch_id'       => $batch->id,
        ]);

        $this->putPdfInCache($cert);

        $this->actingAs($this->user)
            ->get(route('certificate.batch.zip', $batch->batch_token))
            ->assertOk();

        $this->assertDirectoryExists($tempDir);
    }

    // ══════════════════════════════════════════════
    // resolveAssetPath() — private method
    // ══════════════════════════════════════════════

    #[Test]
    public function resolve_asset_path_returns_full_path_without_backslash(): void
    {
        $institution = Institution::factory()->create([
            'logo_path'       => 'institutions/1/logo/logo.png',
            'ttd_path'        => 'institutions/1/ttd/ttd.png',
            'cap_path'        => 'institutions/1/cap/cap.png',
            'background_path' => null,
        ]);
        $admin = User::factory()->adminOf($institution)->create();

        \Illuminate\Support\Facades\Queue::fake();

        $this->actingAs($admin)
            ->postJson(route('certificate.batch.store'), [
                'participants' => [['nama' => 'Test User', 'perusahaan' => null, 'nomor' => null]],
                'event_name'   => 'Test Event',
                'event_date'   => '2026-01-01',
            ])
            ->assertOk();

        $controller = new \App\Http\Controllers\CertificateBatchController();
        $method = new \ReflectionMethod($controller, 'resolveAssetPath');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'institutions/1/logo/logo.png');
        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringContainsString('institutions/1/logo/logo.png', $result);
    }

    #[Test]
    public function resolve_asset_path_returns_empty_for_null(): void
    {
        $controller = new \App\Http\Controllers\CertificateBatchController();
        $method = new \ReflectionMethod($controller, 'resolveAssetPath');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($controller, null));
        $this->assertEquals('', $method->invoke($controller, ''));
    }
}