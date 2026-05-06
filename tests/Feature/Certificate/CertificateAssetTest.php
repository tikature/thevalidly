<?php

namespace Tests\Feature\Certificate;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Asset Upload/Remove (Iterasi 2)
 *
 * Jalankan: php artisan test --filter CertificateAssetTest
 */
class CertificateAssetTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User        $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->institution = Institution::factory()->create();
        $this->admin       = User::factory()->adminOf($this->institution)->create();
    }

    // ── Upload ──────────────────────────────────────────────────

    #[Test]
    public function admin_can_upload_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file])
            ->assertStatus(200)
            ->assertJsonStructure(['url']);

        $this->assertNotNull($this->institution->fresh()->logo_path);
        Storage::disk('public')->assertExists($this->institution->fresh()->logo_path);
    }

    #[Test]
    public function admin_can_upload_ttd(): void
    {
        $file = UploadedFile::fake()->image('ttd.png', 300, 100);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'ttd', 'file' => $file])
            ->assertStatus(200)
            ->assertJsonStructure(['url']);

        $this->assertNotNull($this->institution->fresh()->ttd_path);
    }

    #[Test]
    public function admin_can_upload_cap(): void
    {
        $file = UploadedFile::fake()->image('cap.png', 200, 200);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'cap', 'file' => $file])
            ->assertStatus(200)
            ->assertJsonStructure(['url']);

        $this->assertNotNull($this->institution->fresh()->cap_path);
    }

    #[Test]
    public function admin_can_upload_background(): void
    {
        $file = UploadedFile::fake()->image('bg.jpg', 1280, 720)->size(1000);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'background', 'file' => $file])
            ->assertStatus(200)
            ->assertJsonStructure(['url']);

        $this->assertNotNull($this->institution->fresh()->background_path);
    }

    #[Test]
    public function upload_returns_accessible_url(): void
    {
        $file = UploadedFile::fake()->image('logo.png');

        $res = $this->actingAs($this->admin)
            ->post(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file])
            ->assertStatus(200);

        $this->assertNotEmpty($res->json('url'));
    }

    // ── Replace ─────────────────────────────────────────────────

    #[Test]
    public function upload_replaces_old_asset(): void
    {
        $file1 = UploadedFile::fake()->image('logo1.png');
        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file1]);

        $oldPath = $this->institution->fresh()->logo_path;
        Storage::disk('public')->assertExists($oldPath);

        $file2 = UploadedFile::fake()->image('logo2.png');
        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file2]);

        $newPath = $this->institution->fresh()->logo_path;
        $this->assertNotEquals($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    // ── Validasi ────────────────────────────────────────────────

    #[Test]
    public function upload_fails_for_invalid_type(): void
    {
        $file = UploadedFile::fake()->image('test.png');

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'invalid', 'file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function upload_fails_for_file_too_large(): void
    {
        $file = UploadedFile::fake()->image('big.png')->size(3000); // 3MB > 2MB limit

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function upload_fails_without_file(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function upload_fails_without_type(): void
    {
        $file = UploadedFile::fake()->image('logo.png');

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function upload_rejects_webp_format(): void
    {
        $png     = UploadedFile::fake()->image('source.png');
        $content = file_get_contents($png->getPathname());
        $file    = UploadedFile::fake()->createWithContent('test.webp', $content);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file])
            ->assertStatus(422);
    }

    // ── Remove ──────────────────────────────────────────────────

    #[Test]
    public function admin_can_remove_asset(): void
    {
        $file = UploadedFile::fake()->image('logo.png');
        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file]);

        $path = $this->institution->fresh()->logo_path;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.remove'), ['type' => 'logo'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNull($this->institution->fresh()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    #[Test]
    public function remove_fails_for_invalid_type(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.remove'), ['type' => 'invalid'])
            ->assertStatus(422);
    }

    #[Test]
    public function remove_nonexistent_asset_returns_success(): void
    {
        // Tidak ada asset yang diupload, tapi remove tetap sukses
        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.remove'), ['type' => 'logo'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ── Get Assets ──────────────────────────────────────────────

    #[Test]
    public function admin_can_get_assets(): void
    {
        $this->actingAs($this->admin)
            ->get(route('certificate.asset.get'))
            ->assertStatus(200)
            ->assertJsonStructure(['logo', 'ttd', 'cap', 'background']);
    }

    #[Test]
    public function get_assets_returns_null_when_no_assets(): void
    {
        $res = $this->actingAs($this->admin)
            ->get(route('certificate.asset.get'))
            ->assertStatus(200);

        $this->assertNull($res->json('logo'));
        $this->assertNull($res->json('ttd'));
        $this->assertNull($res->json('cap'));
        $this->assertNull($res->json('background'));
    }

    #[Test]
    public function get_assets_returns_url_after_upload(): void
    {
        $file = UploadedFile::fake()->image('logo.png');
        $this->actingAs($this->admin)
            ->postJson(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file]);

        $res = $this->actingAs($this->admin)
            ->get(route('certificate.asset.get'))
            ->assertStatus(200);

        $this->assertNotNull($res->json('logo'));
    }

    // ── Auth guard ──────────────────────────────────────────────

    #[Test]
    public function guest_cannot_upload_asset(): void
    {
        $file = UploadedFile::fake()->image('logo.png');
        $this->post(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_remove_asset(): void
    {
        $this->post(route('certificate.asset.remove'), ['type' => 'logo'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_get_assets(): void
    {
        $this->get(route('certificate.asset.get'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function superadmin_cannot_upload_asset(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $file       = UploadedFile::fake()->image('logo.png');

        $this->actingAs($superAdmin)
            ->post(route('certificate.asset.upload'), ['type' => 'logo', 'file' => $file])
            ->assertForbidden();
    }
}
