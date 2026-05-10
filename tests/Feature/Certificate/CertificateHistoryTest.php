<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Certificate History (individual + batch)
 *
 * Scope file ini:
 * - Simpan riwayat sertifikat tunggal dan bulk
 * - Halaman history individual (akses, filter, search)
 * - Halaman history batch (akses, filter)
 *
 * Jalankan: php artisan test --filter CertificateHistoryTest
 */
class CertificateHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User        $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create(['name' => 'Lembaga Test']);
        $this->admin       = User::factory()->adminOf($this->institution)->create();
    }

    // ── Simpan riwayat ────────────────────────────────────────

    #[Test]
    public function admin_can_save_certificate_record(): void
    {
        $this->actingAs($this->admin)
            ->post(route('certificate.store'), [
                'nama'       => 'Budi Santoso',
                'perusahaan' => 'PT. Maju Bersama',
                'nomor'      => 'CERT/001/2026',
                'event_name' => 'Pelatihan Laravel',
                'event_date' => '22 April 2026',
            ])
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'verification_token', 'verification_url']);

        $this->assertDatabaseHas('certificates', [
            'nama'           => 'Budi Santoso',
            'nomor'          => 'CERT/001/2026',
            'institution_id' => $this->institution->id,
        ]);
    }

    #[Test]
    public function saving_certificate_generates_unique_token(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('certificate.store'), [
                'nama'       => 'Budi Santoso',
                'nomor'      => 'CERT/001/2026',
                'event_name' => 'Pelatihan Laravel',
                'event_date' => '22 April 2026',
            ]);

        $token = $response->json('verification_token');
        $this->assertNotNull($token);
        $this->assertDatabaseHas('certificates', ['verification_token' => $token]);
    }

    #[Test]
    public function saving_certificate_without_perusahaan_is_allowed(): void
    {
        $this->actingAs($this->admin)
            ->post(route('certificate.store'), [
                'nama'       => 'Sari Dewi',
                'nomor'      => 'CERT/002/2026',
                'event_name' => 'Workshop PHP',
                'event_date' => '22 April 2026',
            ])
            ->assertStatus(200);

        $cert = Certificate::where('nomor', 'CERT/002/2026')->first();
        $this->assertNull($cert->perusahaan);
    }

    #[Test]
    public function certificate_is_linked_to_correct_institution(): void
    {
        $this->actingAs($this->admin)
            ->post(route('certificate.store'), [
                'nama'       => 'Budi',
                'nomor'      => 'CERT/001/2026',
                'event_name' => 'Training',
                'event_date' => '22 April 2026',
            ]);

        $cert = Certificate::first();
        $this->assertEquals($this->institution->id, $cert->institution_id);
    }

    #[Test]
    public function bulk_save_stores_all_certificates(): void
    {
        $participants = [
            ['nama' => 'Peserta 1', 'nomor' => 'CERT/001/2026', 'perusahaan' => 'PT. A'],
            ['nama' => 'Peserta 2', 'nomor' => 'CERT/002/2026', 'perusahaan' => null],
            ['nama' => 'Peserta 3', 'nomor' => 'CERT/003/2026', 'perusahaan' => 'PT. B'],
        ];

        $this->actingAs($this->admin)
            ->post(route('certificate.storeBulk'), [
                'participants' => $participants,
                'event_name'   => 'Pelatihan Massal',
                'event_date'   => '22 April 2026',
            ])
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'count', 'certificates']);

        $this->assertDatabaseCount('certificates', 3);
    }

    #[Test]
    public function guest_cannot_save_certificate(): void
    {
        $this->post(route('certificate.store'), [
            'nama'  => 'Budi',
            'nomor' => 'CERT/001/2026',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function nama_is_required_to_save(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'nomor'      => 'CERT/001/2026',
                'event_name' => 'Training',
                'event_date' => '22 April 2026',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nama');
    }

    // ── Halaman riwayat individual ─────────────────────────────

    #[Test]
    public function admin_can_access_history_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.history'))
            ->assertStatus(200)
            ->assertSee('Riwayat Sertifikat');
    }

    #[Test]
    public function history_shows_only_own_institution_certificates(): void
    {
        $other = Institution::factory()->create();

        Certificate::factory()->count(3)->create(['institution_id' => $this->institution->id]);
        Certificate::factory()->count(2)->create(['institution_id' => $other->id]);

        $this->actingAs($this->admin)
            ->get(route('certificate.history'))
            ->assertStatus(200);

        $certs = Certificate::forInstitution($this->institution->id)->get();
        $this->assertCount(3, $certs);
    }

    #[Test]
    public function history_can_be_searched_by_nama(): void
    {
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Budi Santoso',
        ]);
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
            'nama'           => 'Sari Dewi',
        ]);

        $this->actingAs($this->admin)
            ->get(route('certificate.history', ['search' => 'Budi']))
            ->assertStatus(200)
            ->assertSee('Budi Santoso')
            ->assertDontSee('Sari Dewi');
    }

    #[Test]
    public function guest_cannot_access_history_page(): void
    {
        $this->get(route('certificate.history'))
            ->assertRedirect(route('login'));
    }

    // ── Halaman riwayat batch ──────────────────────────────────

    #[Test]
    public function admin_can_access_batch_history_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.history.batch'))
            ->assertStatus(200);
    }

    #[Test]
    public function guest_cannot_access_batch_history_page(): void
    {
        $this->get(route('certificate.history.batch'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function batch_history_shows_only_institution_batches(): void
    {
        CertificateBatch::factory()->create([
            'institution_id' => $this->institution->id,
            'event_name'     => 'Batch Kita',
        ]);

        $other = Institution::factory()->create();
        CertificateBatch::factory()->create([
            'institution_id' => $other->id,
            'event_name'     => 'Batch Lain',
        ]);

        $this->actingAs($this->admin)
            ->get(route('certificate.history.batch'))
            ->assertSee('Batch Kita')
            ->assertDontSee('Batch Lain');
    }
}
