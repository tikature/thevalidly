<?php

namespace Tests\Feature\Iterasi2;

use App\Models\BackgroundLibrary;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SKPL-VLDLY-006 — Manajemen Background Default
 * Iterasi 2 | US06
 *
 * Jumlah test method: 4 (sesuai jumlah AC)
 * Catatan: nama tabel di DB adalah 'background_library' (singular)
 */
class ManajemenBackgroundDefaultTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $adminLembaga;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->superAdmin = User::factory()->create([
            'role'       => 'super_admin',
            'is_active'  => true,
            'is_primary' => true,
        ]);

        $this->institution = Institution::factory()->create(['is_active' => true]);

        $this->adminLembaga = User::factory()->create([
            'role'           => 'admin',
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
    }

    /**
     * AC1: Super Admin dapat mengunggah background ke library default sistem
     * dalam format PNG atau JPG.
     */
    public function test_super_admin_dapat_mengunggah_background_ke_library_sistem(): void
    {
        $file = UploadedFile::fake()->image("background.png", 1920, 1080);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.backgrounds.store'), [
                'file' => $file,
                'name' => 'Background Biru',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('background_library', [
            'name'      => 'Background Biru',
            'is_system' => true,
        ]);
    }

    /**
     * AC2: Ketika file yang diunggah bukan PNG atau JPG, atau ukurannya
     * melebihi batas yang ditentukan, sistem menolak unggahan dengan pesan informatif.
     */
    public function test_unggahan_background_ditolak_jika_format_tidak_valid(): void
    {
        $file = UploadedFile::fake()->create('background.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.backgrounds.store'), [
                'file' => $file,
                'name' => 'Background PDF',
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('background_library', ['name' => 'Background PDF']);
    }

    /**
     * AC3: Background yang berhasil diunggah otomatis tersedia untuk dipilih
     * oleh seluruh Admin Lembaga melalui Background Library mereka.
     */
    public function test_background_sistem_tersedia_di_library_admin_lembaga(): void
    {
        $bg = BackgroundLibrary::create([
            'institution_id' => null,
            'name'           => 'Background Sistem',
            'path'           => 'backgrounds/system/test.jpg',
            'is_system'      => true,
        ]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('background.library.index'));

        $response->assertOk();
        $data = $response->json();
        $systemIds = collect($data['system'])->pluck('id');
        $this->assertTrue($systemIds->contains($bg->id));
    }

    /**
     * AC4: Ketika Super Admin menghapus background dari library default,
     * background tersebut tidak lagi tersedia di Background Library Admin Lembaga manapun.
     */
    public function test_background_sistem_dihapus_tidak_tersedia_di_library_lembaga(): void
    {
        $bg = BackgroundLibrary::create([
            'institution_id' => null,
            'name'           => 'Background Dihapus',
            'path'           => 'backgrounds/system/hapus.jpg',
            'is_system'      => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.backgrounds.destroy', $bg));

        $this->assertDatabaseMissing('background_library', ['id' => $bg->id]);

        $response = $this->actingAs($this->adminLembaga)
            ->get(route('background.library.index'));

        $data = $response->json();
        $systemIds = collect($data['system'])->pluck('id');
        $this->assertFalse($systemIds->contains($bg->id));
    }
}