<?php

namespace Tests\Feature\Certificate;

use App\Models\BackgroundLibrary;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: Background Library
 *
 * Jalankan: php artisan test --filter BackgroundLibraryTest
 */
class BackgroundLibraryTest extends TestCase
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

    // ══════════════════════════════════════════════════════════
    // index() — list background library
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_list_background_library(): void
    {
        // Buat 2 system bg + 1 lembaga bg
        BackgroundLibrary::create(['name' => 'Classic', 'path' => 'backgrounds/system/classic.jpg', 'is_system' => true]);
        BackgroundLibrary::create(['name' => 'Modern',  'path' => 'backgrounds/system/modern.jpg',  'is_system' => true]);
        BackgroundLibrary::create(['institution_id' => $this->institution->id, 'name' => 'My BG', 'path' => 'backgrounds/library/1/mybg.jpg', 'is_system' => false]);

        $res = $this->actingAs($this->admin)
            ->getJson(route('background.library.index'))
            ->assertOk()
            ->assertJsonStructure([
                'system'  => [['id', 'name', 'url', 'is_system']],
                'lembaga' => [['id', 'name', 'url', 'is_system']],
            ]);

        $this->assertCount(2, $res->json('system'));
        $this->assertCount(1, $res->json('lembaga'));
    }

    #[Test]
    public function index_returns_empty_arrays_when_no_backgrounds(): void
    {
        $res = $this->actingAs($this->admin)
            ->getJson(route('background.library.index'))
            ->assertOk();

        $this->assertCount(0, $res->json('system'));
        $this->assertCount(0, $res->json('lembaga'));
    }

    #[Test]
    public function lembaga_only_sees_own_institution_backgrounds(): void
    {
        $other = Institution::factory()->create();
        BackgroundLibrary::create(['institution_id' => $other->id, 'name' => 'Other', 'path' => 'x.jpg', 'is_system' => false]);
        BackgroundLibrary::create(['institution_id' => $this->institution->id, 'name' => 'Mine', 'path' => 'y.jpg', 'is_system' => false]);

        $res = $this->actingAs($this->admin)
            ->getJson(route('background.library.index'))
            ->assertOk();

        $this->assertCount(1, $res->json('lembaga'));
        $this->assertEquals('Mine', $res->json('lembaga.0.name'));
    }

    #[Test]
    public function system_backgrounds_visible_to_all_institutions(): void
    {
        BackgroundLibrary::create(['name' => 'System BG', 'path' => 'sys.jpg', 'is_system' => true]);

        $otherInst  = Institution::factory()->create();
        $otherAdmin = User::factory()->adminOf($otherInst)->create();

        $res = $this->actingAs($otherAdmin)
            ->getJson(route('background.library.index'))
            ->assertOk();

        $this->assertCount(1, $res->json('system'));
    }

    // ══════════════════════════════════════════════════════════
    // store() — upload background ke library
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_upload_background_to_library(): void
    {
        $file = UploadedFile::fake()->image('mybg.jpg', 1280, 720)->size(500);

        $res = $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), [
                'file' => $file,
                'name' => 'Background Merah',
            ])
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'url', 'is_system']);

        $this->assertEquals('Background Merah', $res->json('name'));
        $this->assertFalse($res->json('is_system'));

        $bg = BackgroundLibrary::find($res->json('id'));
        $this->assertEquals($this->institution->id, $bg->institution_id);
        Storage::disk('public')->assertExists($bg->path);
    }

    #[Test]
    public function upload_uses_filename_as_name_when_name_not_provided(): void
    {
        $file = UploadedFile::fake()->image('my-custom-bg.jpg')->size(100);

        $res = $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertOk();

        $this->assertEquals('my-custom-bg', $res->json('name'));
    }

    #[Test]
    public function upload_to_library_fails_without_file(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['name' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function upload_to_library_fails_when_file_too_large(): void
    {
        $file = UploadedFile::fake()->image('big.jpg')->size(3000);

        $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function upload_to_library_fails_for_non_image(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function uploaded_background_is_not_system(): void
    {
        $file = UploadedFile::fake()->image('bg.jpg')->size(100);

        $res = $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertOk();

        $this->assertFalse($res->json('is_system'));
    }

    // ══════════════════════════════════════════════════════════
    // destroy() — hapus background dari library
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_delete_own_library_background(): void
    {
        $file = UploadedFile::fake()->image('bg.jpg')->size(100);
        Storage::disk('public')->put('backgrounds/library/1/bg.jpg', 'fake');

        $bg = BackgroundLibrary::create([
            'institution_id' => $this->institution->id,
            'name'           => 'Test BG',
            'path'           => 'backgrounds/library/1/bg.jpg',
            'is_system'      => false,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('background.library.destroy', $bg))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(BackgroundLibrary::find($bg->id));
        Storage::disk('public')->assertMissing($bg->path);
    }

    #[Test]
    public function admin_cannot_delete_system_background(): void
    {
        $bg = BackgroundLibrary::create(['name' => 'System', 'path' => 'sys.jpg', 'is_system' => true]);

        $this->actingAs($this->admin)
            ->deleteJson(route('background.library.destroy', $bg))
            ->assertForbidden();
    }

    #[Test]
    public function admin_cannot_delete_other_institution_background(): void
    {
        $other    = Institution::factory()->create();
        $otherBg  = BackgroundLibrary::create([
            'institution_id' => $other->id,
            'name'           => 'Other BG',
            'path'           => 'other.jpg',
            'is_system'      => false,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('background.library.destroy', $otherBg))
            ->assertForbidden();
    }

    // ══════════════════════════════════════════════════════════
    // select() — pilih background dari library jadi background aktif
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_select_system_background(): void
    {
        Storage::disk('public')->put('backgrounds/system/classic.jpg', 'fake');
        $bg = BackgroundLibrary::create(['name' => 'Classic', 'path' => 'backgrounds/system/classic.jpg', 'is_system' => true]);

        $res = $this->actingAs($this->admin)
            ->postJson(route('background.library.select', $bg))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'url']);

        $this->assertEquals('backgrounds/system/classic.jpg', $this->institution->fresh()->background_path);
    }

    #[Test]
    public function admin_can_select_own_library_background(): void
    {
        Storage::disk('public')->put('backgrounds/library/1/mybg.jpg', 'fake');
        $bg = BackgroundLibrary::create([
            'institution_id' => $this->institution->id,
            'name'           => 'My BG',
            'path'           => 'backgrounds/library/1/mybg.jpg',
            'is_system'      => false,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('background.library.select', $bg))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals('backgrounds/library/1/mybg.jpg', $this->institution->fresh()->background_path);
    }

    #[Test]
    public function admin_cannot_select_other_institution_background(): void
    {
        $other   = Institution::factory()->create();
        $otherBg = BackgroundLibrary::create([
            'institution_id' => $other->id,
            'name'           => 'Other BG',
            'path'           => 'other.jpg',
            'is_system'      => false,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('background.library.select', $otherBg))
            ->assertForbidden();
    }

    #[Test]
    public function select_updates_institution_background_path(): void
    {
        Storage::disk('public')->put('backgrounds/system/modern.jpg', 'fake');
        $bg = BackgroundLibrary::create(['name' => 'Modern', 'path' => 'backgrounds/system/modern.jpg', 'is_system' => true]);

        $this->assertNull($this->institution->fresh()->background_path);

        $this->actingAs($this->admin)
            ->postJson(route('background.library.select', $bg))
            ->assertOk();

        $this->assertEquals('backgrounds/system/modern.jpg', $this->institution->fresh()->background_path);
    }

    // ══════════════════════════════════════════════════════════
    // Limit 10 per lembaga
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function upload_fails_when_institution_has_10_backgrounds(): void
    {
        // Buat tepat 10 background
        for ($i = 1; $i <= 10; $i++) {
            BackgroundLibrary::create([
                'institution_id' => $this->institution->id,
                'name'           => "BG $i",
                'path'           => "backgrounds/library/{$this->institution->id}/bg{$i}.jpg",
                'is_system'      => false,
            ]);
        }

        $file = UploadedFile::fake()->image('new.jpg')->size(100);

        $res = $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertUnprocessable();

        $this->assertTrue($res->json('limit_reached'));
        $this->assertStringContainsString('10', $res->json('message'));
    }

    #[Test]
    public function upload_succeeds_when_institution_has_9_backgrounds(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            BackgroundLibrary::create([
                'institution_id' => $this->institution->id,
                'name'           => "BG $i",
                'path'           => "backgrounds/library/{$this->institution->id}/bg{$i}.jpg",
                'is_system'      => false,
            ]);
        }

        $file = UploadedFile::fake()->image('new.jpg')->size(100);

        $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertOk();
    }

    #[Test]
    public function limit_is_per_institution_not_global(): void
    {
        // Lembaga lain punya 10 background — tidak mempengaruhi lembaga ini
        $other = Institution::factory()->create();
        for ($i = 1; $i <= 10; $i++) {
            BackgroundLibrary::create([
                'institution_id' => $other->id,
                'name'           => "Other BG $i",
                'path'           => "backgrounds/library/{$other->id}/bg{$i}.jpg",
                'is_system'      => false,
            ]);
        }

        $file = UploadedFile::fake()->image('mine.jpg')->size(100);

        $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertOk();
    }

    #[Test]
    public function store_returns_current_and_max_count(): void
    {
        $file = UploadedFile::fake()->image('bg.jpg')->size(100);

        $res = $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertOk();

        $this->assertEquals(1, $res->json('current_count'));
        $this->assertEquals(10, $res->json('max_count'));
    }

    #[Test]
    public function index_returns_current_and_max_count(): void
    {
        BackgroundLibrary::create([
            'institution_id' => $this->institution->id,
            'name'           => 'My BG',
            'path'           => 'backgrounds/library/1/bg.jpg',
            'is_system'      => false,
        ]);

        $res = $this->actingAs($this->admin)
            ->getJson(route('background.library.index'))
            ->assertOk()
            ->assertJsonStructure(['system', 'lembaga', 'current_count', 'max_count']);

        $this->assertEquals(1, $res->json('current_count'));
        $this->assertEquals(10, $res->json('max_count'));
    }

    #[Test]
    public function after_delete_can_upload_again_when_was_at_limit(): void
    {
        $bgs = [];
        for ($i = 1; $i <= 10; $i++) {
            $bgs[] = BackgroundLibrary::create([
                'institution_id' => $this->institution->id,
                'name'           => "BG $i",
                'path'           => "backgrounds/library/{$this->institution->id}/bg{$i}.jpg",
                'is_system'      => false,
            ]);
        }

        // Hapus satu
        Storage::disk('public')->put($bgs[0]->path, 'fake');
        $this->actingAs($this->admin)
            ->deleteJson(route('background.library.destroy', $bgs[0]))
            ->assertOk();

        // Sekarang bisa upload lagi
        $file = UploadedFile::fake()->image('new.jpg')->size(100);
        $this->actingAs($this->admin)
            ->postJson(route('background.library.store'), ['file' => $file])
            ->assertOk();
    }

    // ══════════════════════════════════════════════════════════
    // System background URL
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function system_background_url_uses_asset_path(): void
    {
        $bg = BackgroundLibrary::create([
            'name'      => 'Elegant Gold',
            'path'      => 'backgrounds/system/elegant-gold.jpg',
            'is_system' => true,
        ]);

        $res = $this->actingAs($this->admin)
            ->getJson(route('background.library.index'))
            ->assertOk();

        $url = $res->json('system.0.url');
        $this->assertStringContainsString('backgrounds/system/elegant-gold.jpg', $url);
    }

    #[Test]
    public function select_system_background_updates_institution_path(): void
    {
        Storage::disk('public')->put('backgrounds/system/classic.jpg', 'fake');
        $bg = BackgroundLibrary::create([
            'name'      => 'Classic',
            'path'      => 'backgrounds/system/classic.jpg',
            'is_system' => true,
        ]);

        $res = $this->actingAs($this->admin)
            ->postJson(route('background.library.select', $bg))
            ->assertOk();

        $this->assertEquals('backgrounds/system/classic.jpg', $this->institution->fresh()->background_path);
        $this->assertStringContainsString('backgrounds/system/classic.jpg', $res->json('url'));
    }

    // ══════════════════════════════════════════════════════════
    // Auth guard
    // ══════════════════════════════════════════════════════════

    #[Test]
    public function guest_cannot_access_library(): void
    {
        $this->getJson(route('background.library.index'))->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_upload_to_library(): void
    {
        $file = UploadedFile::fake()->image('bg.jpg');
        $this->postJson(route('background.library.store'), ['file' => $file])->assertUnauthorized();
    }

    #[Test]
    public function superadmin_cannot_access_library(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->getJson(route('background.library.index'))
            ->assertForbidden();
    }
}
