<?php

namespace Tests\Feature\Certificate;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: Halaman Certificate — Iterasi 1
 *
 * Iterasi 1 hanya memastikan route & akses halaman certificate bekerja.
 * Fitur pembuatan sertifikat akan diuji penuh pada Iterasi 2.
 *
 * Jalankan: php artisan test --filter CertificatePageTest
 */
class CertificatePageTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
        $this->admin = User::factory()->adminOf($this->institution)->create();
    }

    #[Test]
    public function admin_can_access_certificate_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.index'))
            ->assertStatus(200)
            ->assertSee('Generator Sertifikat');
    }

    #[Test]
    public function guest_cannot_access_certificate_page(): void
    {
        $this->get(route('certificate.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function superadmin_cannot_access_certificate_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('certificate.index'))
            ->assertForbidden();
    }

    #[Test]
    public function inactive_admin_is_logged_out_when_accessing_certificate(): void
    {
        $inactiveAdmin = User::factory()->adminOf($this->institution)->inactive()->create();

        $this->actingAs($inactiveAdmin)
            ->get(route('certificate.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
