<?php

namespace Tests\Unit\Iterasi2;

use Tests\TestCase;
use App\Http\Controllers\BackgroundLibraryController;
use App\Models\BackgroundLibrary;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BackgroundLibraryControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_and_destroy_with_authenticated_user()
    {
        Storage::fake('public');

        // 1. Buat Institution & User
        $institution = Institution::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $institution->id,
        ]);

        $this->actingAs($user);

        $controller = new BackgroundLibraryController();

        // 2. Testing Store Background
        $file = UploadedFile::fake()->image('bg.jpg');
        $request = Request::create('/backgrounds', 'POST', [
            'name' => 'Background Test',
        ], [], ['file' => $file]);

        $responseStore = $controller->store($request);
        $this->assertNotNull($responseStore);

        // 3. Testing Destroy Background (Sistem Background -> Harus 403 Forbidden)
        $systemBg = BackgroundLibrary::create([
            'name' => 'System Background',
            'path' => 'backgrounds/system.jpg',
            'is_system' => true,
            'institution_id' => null,
        ]);

        try {
            $controller->destroy($systemBg);
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    /**
     * Coverage Baris 122: Menguji abort(403) saat user mengakses background milik lembaga lain
     */
    public function test_user_cannot_access_background_from_another_institution()
    {
        $institutionA = Institution::factory()->create();
        $institutionB = Institution::factory()->create();

        $user = User::factory()->create([
            'institution_id' => $institutionA->id,
        ]);
        $this->actingAs($user);

        $backgroundId = DB::table('background_library')->insertGetId([
            'name'           => 'Bg Lembaga B',
            'path'           => 'backgrounds/lembaga_b.jpg',
            'is_system'      => false,
            'institution_id' => $institutionB->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $backgroundOther = BackgroundLibrary::findOrFail($backgroundId);

        $controller = new BackgroundLibraryController();

        try {
            $controller->select($backgroundOther);
            $this->fail('Expected HttpException for foreign institution background.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    /**
     * Coverage Baris 129: Menguji penetapan $url untuk background non-sistem milik lembaga sendiri
     */
    public function test_user_can_get_url_for_own_institution_background()
    {
        Storage::fake('public');

        $institution = Institution::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $institution->id,
        ]);
        $this->actingAs($user);

        $backgroundId = DB::table('background_library')->insertGetId([
            'name'           => 'Bg Lembaga A',
            'path'           => 'backgrounds/lembaga_a.jpg',
            'is_system'      => false,
            'institution_id' => $institution->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $backgroundOwn = BackgroundLibrary::findOrFail($backgroundId);

        $controller = new BackgroundLibraryController();
        $response = $controller->select($backgroundOwn);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertNotEmpty($response->getData(true)['url']);
        $this->assertSame($backgroundOwn->path, $institution->fresh()->background_path);
    }
}