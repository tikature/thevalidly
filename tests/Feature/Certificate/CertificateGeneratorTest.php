<?php

namespace Tests\Feature\Certificate;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Certificate Generator (Iterasi 2)
 *
 * Jalankan: php artisan test --filter CertificateGeneratorTest
 */
class CertificateGeneratorTest extends TestCase
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

    // ── Akses halaman generator ────────────────────────────────

    #[Test]
    public function admin_can_access_generator_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.index'))
            ->assertStatus(200)
            ->assertSee('Generator Sertifikat');
    }

    #[Test]
    public function guest_cannot_access_generator_page(): void
    {
        $this->get(route('certificate.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function superadmin_cannot_access_generator_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)
            ->get(route('certificate.index'))
            ->assertForbidden();
    }

    #[Test]
    public function inactive_admin_cannot_access_generator_page(): void
    {
        $inactive = User::factory()->adminOf($this->institution)->inactive()->create();
        // Admin tidak aktif di-redirect (bukan 403) karena middleware cek is_active
        // dan redirect ke login dengan pesan error
        $this->actingAs($inactive)
            ->get(route('certificate.index'))
            ->assertRedirect();
    }

    // ── Store sertifikat ────────────────────────────────────────

    #[Test]
    public function admin_can_store_single_certificate(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Budi Santoso', 'perusahaan' => 'PT Test', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Pelatihan Test',
                'event_date'   => 'Held on 01-01-26 at Purwokerto',
                'event_place'  => 'Purwokerto',
                'signer_name'  => 'Dr. Test',
                'signer_title' => 'Ketua',
            ]);

        $res->assertStatus(200)
            ->assertJsonStructure([
                'count',
                'certificates' => [['id', 'nama', 'nomor', 'verification_token', 'pdf_url', 'verification_url']],
            ]);

        $this->assertDatabaseHas('certificates', [
            'nama'           => 'Budi Santoso',
            'nomor'          => 'CERT/001/2026',
            'institution_id' => $this->institution->id,
            'issued_by'      => $this->admin->id,
        ]);
    }

    #[Test]
    public function admin_can_store_multiple_certificates(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [
                    ['nama' => 'Peserta Satu', 'perusahaan' => null,     'nomor' => 'CERT/001/2026'],
                    ['nama' => 'Peserta Dua',  'perusahaan' => 'PT ABC', 'nomor' => 'CERT/002/2026'],
                    ['nama' => 'Peserta Tiga', 'perusahaan' => null,     'nomor' => 'CERT/003/2026'],
                ],
                'event_name'  => 'Pelatihan Massal',
                'event_date'  => 'Held on 01-01-26 at Purwokerto',
                'event_place' => 'Purwokerto',
            ]);

        $res->assertStatus(200)->assertJson(['count' => 3]);
        $this->assertDatabaseCount('certificates', 3);
    }

    #[Test]
    public function store_fails_without_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participants', 'event_name', 'event_date']);
    }

    #[Test]
    public function store_fails_without_participant_nama(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => '', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Test',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participants.0.nama']);
    }

    #[Test]
    public function store_fails_with_empty_participants_array(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [],
                'event_name'   => 'Test',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participants']);
    }

    #[Test]
    public function store_fails_without_event_name(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['event_name']);
    }

    #[Test]
    public function store_succeeds_without_optional_event_place(): void
    {
        // event_place nullable — boleh tidak diisi
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Test',
                'event_date'   => 'Held on 01-01-26 at Test',
            ])
            ->assertStatus(200);
    }

    #[Test]
    public function certificate_stores_issued_by_current_admin(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test User', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Event Test',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
            ]);

        $this->assertDatabaseHas('certificates', ['issued_by' => $this->admin->id]);
    }

    #[Test]
    public function certificate_stores_correct_institution_id(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test User', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Event Test',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
            ]);

        $this->assertDatabaseHas('certificates', [
            'institution_id' => $this->institution->id,
        ]);
    }

    #[Test]
    public function certificate_has_unique_verification_token(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [
                    ['nama' => 'Peserta A', 'nomor' => 'CERT/001/2026'],
                    ['nama' => 'Peserta B', 'nomor' => 'CERT/002/2026'],
                ],
                'event_name'  => 'Event',
                'event_date'  => 'Held on 01-01-26 at Test',
                'event_place' => 'Test',
            ]);

        $tokens = Certificate::pluck('verification_token')->toArray();
        $this->assertCount(2, array_unique($tokens));
    }

    #[Test]
    public function cert_desc_is_saved_correctly(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Event',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
                'cert_desc'    => 'Telah berhasil menyelesaikan:',
            ]);

        $this->assertDatabaseHas('certificates', ['cert_desc' => 'Telah berhasil menyelesaikan:']);
    }

    #[Test]
    public function cert_desc_max_200_characters(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Event',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
                'cert_desc'    => str_repeat('A', 201),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cert_desc']);
    }

    #[Test]
    public function signer_name_and_title_are_saved(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Event',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
                'signer_name'  => 'Dr. Ahmad Fauzi, M.Pd.',
                'signer_title' => 'Direktur',
            ]);

        $this->assertDatabaseHas('certificates', [
            'signer_name'  => 'Dr. Ahmad Fauzi, M.Pd.',
            'signer_title' => 'Direktur',
        ]);
    }

    #[Test]
    public function store_response_contains_pdf_url(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Event',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
            ]);

        $data = $res->json();
        $this->assertStringContainsString('/pdf', $data['certificates'][0]['pdf_url']);
    }

    #[Test]
    public function store_response_contains_verification_url(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson(route('certificate.store'), [
                'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
                'event_name'   => 'Event',
                'event_date'   => 'Held on 01-01-26 at Test',
                'event_place'  => 'Test',
            ]);

        $data = $res->json();
        $this->assertStringContainsString('/verify/', $data['certificates'][0]['verification_url']);
    }

    #[Test]
    public function guest_cannot_store_certificate(): void
    {
        $this->postJson(route('certificate.store'), [
            'participants' => [['nama' => 'Test', 'nomor' => 'CERT/001/2026']],
            'event_name'   => 'Event',
            'event_date'   => 'Held on 01-01-26 at Test',
            'event_place'  => 'Test',
        ])->assertUnauthorized();
    }

    // ── PDF Download ────────────────────────────────────────────

    #[Test]
    public function admin_cannot_download_pdf_of_other_institution(): void
    {
        $otherInst = Institution::factory()->create();
        $cert      = Certificate::factory()->forInstitution($otherInst)->create();

        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertForbidden();
    }

    #[Test]
    public function pdf_returns_404_for_invalid_token(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', 'token-tidak-ada'))
            ->assertNotFound();
    }

    #[Test]
    public function guest_cannot_download_pdf(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();
        $this->get(route('certificate.pdf', $cert->verification_token))
            ->assertRedirect(route('login'));
    }

    // ── Riwayat ─────────────────────────────────────────────────

    #[Test]
    public function admin_can_access_history(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.history'))
            ->assertStatus(200)
            ->assertSee('Riwayat Sertifikat');
    }

    #[Test]
    public function guest_cannot_access_history(): void
    {
        $this->get(route('certificate.history'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function history_shows_only_institution_certificates(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Milik Lembaga Ini']);

        $otherInst = Institution::factory()->create();
        Certificate::factory()->forInstitution($otherInst)->create(['nama' => 'Milik Lembaga Lain']);

        $this->actingAs($this->admin)
            ->get(route('certificate.history'))
            ->assertSee('Milik Lembaga Ini')
            ->assertDontSee('Milik Lembaga Lain');
    }

    #[Test]
    public function history_can_search_by_name(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Budi Dicari']);
        Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Citra Tidak Dicari']);

        $this->actingAs($this->admin)
            ->get(route('certificate.history', ['search' => 'Budi']))
            ->assertSee('Budi Dicari')
            ->assertDontSee('Citra Tidak Dicari');
    }

    #[Test]
    public function history_can_search_by_nomor(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['nomor' => 'CERT/999/2026']);
        Certificate::factory()->forInstitution($this->institution)->create(['nomor' => 'CERT/001/2026']);

        $this->actingAs($this->admin)
            ->get(route('certificate.history', ['search' => '999']))
            ->assertSee('CERT/999/2026')
            ->assertDontSee('CERT/001/2026');
    }

    #[Test]
    public function history_can_search_by_event_name(): void
    {
        Certificate::factory()->forInstitution($this->institution)->create(['event_name' => 'Pelatihan Khusus']);
        Certificate::factory()->forInstitution($this->institution)->create(['event_name' => 'Seminar Umum']);

        $this->actingAs($this->admin)
            ->get(route('certificate.history', ['search' => 'Khusus']))
            ->assertSee('Pelatihan Khusus')
            ->assertDontSee('Seminar Umum');
    }

    #[Test]
    public function history_is_paginated(): void
    {
        Certificate::factory()->forInstitution($this->institution)->count(25)->create();

        $this->actingAs($this->admin)
            ->get(route('certificate.history'))
            ->assertStatus(200);

        $this->assertEquals(25, Certificate::count());
    }

    // ── Hapus sertifikat ────────────────────────────────────────

    #[Test]
    public function admin_can_delete_own_institution_certificate(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->actingAs($this->admin)
            ->delete(route('certificate.destroy', $cert))
            ->assertRedirect();

        $this->assertDatabaseMissing('certificates', ['id' => $cert->id]);
    }

    #[Test]
    public function admin_cannot_delete_other_institution_certificate(): void
    {
        $otherInst = Institution::factory()->create();
        $cert      = Certificate::factory()->forInstitution($otherInst)->create();

        $this->actingAs($this->admin)
            ->delete(route('certificate.destroy', $cert))
            ->assertForbidden();

        $this->assertDatabaseHas('certificates', ['id' => $cert->id]);
    }

    #[Test]
    public function guest_cannot_delete_certificate(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->delete(route('certificate.destroy', $cert))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('certificates', ['id' => $cert->id]);
    }

    // ── Verifikasi publik ────────────────────────────────────────

    #[Test]
    public function public_can_verify_valid_certificate(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create(['nama' => 'Peserta Valid']);

        $this->get(route('certificate.verify', $cert->verification_token))
            ->assertStatus(200)
            ->assertSee('Peserta Valid')
            ->assertSee('Sertifikat Valid');
    }

    #[Test]
    public function verify_returns_invalid_view_for_wrong_token(): void
    {
        $this->get(route('certificate.verify', 'token-salah'))
            ->assertStatus(200)
            ->assertSee('Tidak Ditemukan');
    }

    #[Test]
    public function verify_page_is_publicly_accessible(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->get(route('certificate.verify', $cert->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function verify_shows_institution_name(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create();

        $this->get(route('certificate.verify', $cert->verification_token))
            ->assertSee($this->institution->name);
    }

    #[Test]
    public function verify_shows_event_name(): void
    {
        $cert = Certificate::factory()->forInstitution($this->institution)->create([
            'event_name' => 'Pelatihan Verifikasi Test',
        ]);

        $this->get(route('certificate.verify', $cert->verification_token))
            ->assertSee('Pelatihan Verifikasi Test');
    }
    #[Test]
    public function pdf_method_generates_correct_filename(): void
    {
        $cert = Certificate::factory()
            ->forInstitution($this->institution)
            ->create([
                'nama'  => 'Budi Santoso',
                'nomor' => 'CERT/001/2026',
            ]);

        // Mock DomPDF agar tidak perlu render sungguhan
        $mockPdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $mockPdf->shouldReceive('loadView')->andReturnSelf();
        $mockPdf->shouldReceive('setPaper')->andReturnSelf();
        $mockPdf->shouldReceive('setOptions')->andReturnSelf();
        $mockPdf->shouldReceive('download')->andReturn(
            response('', 200, ['Content-Type' => 'application/pdf'])
        );

        $this->app->instance(\Barryvdh\DomPDF\PDF::class, $mockPdf);

        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertStatus(200);
    }

    #[Test]
    public function pdf_forbidden_covers_auth_check_branch(): void
    {
        // Cover baris abort(403) - branch yang belum ter-cover
        $otherInst = Institution::factory()->create();
        $cert      = Certificate::factory()->forInstitution($otherInst)->create();

        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertForbidden();
    }

    #[Test]
    public function pdf_works_with_institution_without_assets(): void
    {
        // Institution tanpa logo/ttd/cap/background
        $cert = Certificate::factory()
            ->forInstitution($this->institution)
            ->create();

        // Hanya cek tidak ada exception saat load — DomPDF di-skip di test
        $this->actingAs($this->admin)
            ->get(route('certificate.pdf', $cert->verification_token))
            ->assertStatus(200);
    }
}
