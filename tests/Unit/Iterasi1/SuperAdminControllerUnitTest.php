<?php

namespace Tests\Unit\Iterasi1;

use App\Http\Controllers\SuperAdminController;
use App\Models\BackgroundLibrary;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SuperAdminControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_destroy_institution_deletes_users_and_institution()
    {
        $controller = new SuperAdminController();

        $institutionMock = Mockery::mock(Institution::class)->makePartial();
        $institutionMock->name = 'Lembaga Test';

        $relationMock = Mockery::mock(HasMany::class);
        $relationMock->shouldReceive('delete')->once()->andReturn(true);

        $institutionMock->shouldReceive('users')->once()->andReturn($relationMock);
        $institutionMock->shouldReceive('delete')->once()->andReturn(true);

        $response = $controller->destroyInstitution($institutionMock);

        $this->assertTrue($response->isRedirection());
        $this->assertSame('Lembaga "Lembaga Test" berhasil dihapus.', session('success'));
    }

    public function test_store_super_admin_returns_validation_errors_when_data_invalid()
    {
        $controller = new SuperAdminController();
        $request = new Request([
            'superadmin_name' => '',
            'superadmin_email' => 'not-an-email',
            'superadmin_password' => 'short',
        ]);

        $response = $controller->storeSuperAdmin($request);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->get('errors')->getBag('addSuperAdmin')->has('superadmin_name'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_store_super_admin_creates_new_super_admin()
    {
        $controller = new SuperAdminController();
        $request = new Request([
            'superadmin_name' => 'Super Admin Baru',
            'superadmin_email' => 'superbaru@example.com',
            'superadmin_password' => 'password123',
        ]);

        $response = $controller->storeSuperAdmin($request);

        $user = User::where('email', 'superbaru@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('super_admin', $user->role);
        $this->assertTrue($response->isRedirect());
        $this->assertSame('Super Admin baru berhasil ditambahkan.', session('success'));
    }

    public function test_destroy_super_admin_blocks_self_deletion()
    {
        $controller = new SuperAdminController();
        $user = User::factory()->create([
            'name' => 'Saya',
            'email' => 'saya@example.com',
            'role' => 'super_admin',
            'is_primary' => false,
        ]);
        $this->actingAs($user);

        $response = $controller->destroySuperAdmin($user);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Tidak dapat menghapus akun Anda sendiri.', session('error'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_destroy_super_admin_blocks_primary_super_admin_deletion()
    {
        $controller = new SuperAdminController();
        $actor = User::factory()->create([
            'role' => 'super_admin',
            'is_primary' => false,
        ]);
        $primary = User::factory()->create([
            'name' => 'Primary',
            'email' => 'primary@example.com',
            'role' => 'super_admin',
            'is_primary' => true,
        ]);
        $this->actingAs($actor);

        $response = $controller->destroySuperAdmin($primary);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Akun Super Admin utama tidak dapat dihapus.', session('error'));
        $this->assertDatabaseHas('users', ['id' => $primary->id]);
    }

    public function test_destroy_super_admin_deletes_non_primary_super_admin()
    {
        $controller = new SuperAdminController();
        $actor = User::factory()->create([
            'role' => 'super_admin',
            'is_primary' => false,
        ]);
        $target = User::factory()->create([
            'name' => 'Target',
            'email' => 'target@example.com',
            'role' => 'super_admin',
            'is_primary' => false,
        ]);
        $this->actingAs($actor);

        $response = $controller->destroySuperAdmin($target);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Akun Super Admin "Target" berhasil dihapus.', session('success'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_store_institution_returns_validation_errors_when_data_invalid()
    {
        $controller = new SuperAdminController();
        $request = new Request([
            'institution_name' => '',
            'institution_email' => 'invalid',
            'institution_phone' => '',
            'institution_address' => '',
            'admin_name' => '',
            'admin_email' => 'bad-email',
            'admin_password' => 'short',
        ]);

        $response = $controller->storeInstitution($request);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->get('errors')->getBag('addInstitution')->has('institution_name'));
        $this->assertDatabaseCount('institutions', 0);
    }

    public function test_store_institution_creates_institution_and_admin()
    {
        $controller = new SuperAdminController();
        $request = new Request([
            'institution_name' => 'Lembaga Baru',
            'institution_email' => 'lembaga@example.com',
            'institution_phone' => '08123456789',
            'institution_address' => 'Jl. Baru',
            'admin_name' => 'Admin Baru',
            'admin_email' => 'adminbaru@example.com',
            'admin_password' => 'password123',
        ]);

        $response = $controller->storeInstitution($request);

        $institution = Institution::where('email', 'lembaga@example.com')->first();
        $admin = User::where('email', 'adminbaru@example.com')->first();

        $this->assertNotNull($institution);
        $this->assertNotNull($admin);
        $this->assertSame($institution->id, $admin->institution_id);
        $this->assertTrue($response->isRedirect());
        $this->assertSame('Lembaga "Lembaga Baru" berhasil ditambahkan.', session('success'));
    }

    public function test_update_institution_returns_validation_errors_when_data_invalid()
    {
        $controller = new SuperAdminController();
        $institution = Institution::factory()->create();
        $request = new Request([
            'institution_name' => '',
            'institution_email' => 'invalid',
            'institution_phone' => '',
            'institution_address' => '',
        ]);

        $response = $controller->updateInstitution($request, $institution);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->get('errors')->getBag('editInstitution')->has('institution_name'));
    }

    public function test_update_institution_updates_institution_details()
    {
        $controller = new SuperAdminController();
        $institution = Institution::factory()->create([
            'name' => 'Lama',
            'email' => 'lama@example.com',
            'phone' => '000',
            'address' => 'Alamat Lama',
        ]);
        $request = new Request([
            'institution_name' => 'Baru',
            'institution_email' => 'baru@example.com',
            'institution_phone' => '08111111111',
            'institution_address' => 'Alamat Baru',
        ]);

        $response = $controller->updateInstitution($request, $institution);

        $institution->refresh();

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Baru', $institution->name);
        $this->assertSame('baru@example.com', $institution->email);
        $this->assertSame('Lembaga "Baru" berhasil diperbarui.', session('success'));
    }

    public function test_store_admin_returns_validation_errors_when_data_invalid()
    {
        $controller = new SuperAdminController();
        $institution = Institution::factory()->create();
        $request = new Request([
            'admin_name' => '',
            'admin_email' => 'not-an-email',
            'admin_password' => 'short',
        ]);

        $response = $controller->storeAdmin($request, $institution);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->get('errors')->getBag('addAdmin')->has('admin_name'));
    }

    public function test_store_admin_creates_admin_for_institution()
    {
        $controller = new SuperAdminController();
        $institution = Institution::factory()->create();
        $request = new Request([
            'admin_name' => 'Admin Lembaga',
            'admin_email' => 'adminlembaga@example.com',
            'admin_password' => 'password123',
        ]);

        $response = $controller->storeAdmin($request, $institution);

        $admin = User::where('email', 'adminlembaga@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame($institution->id, $admin->institution_id);
        $this->assertSame('Admin baru berhasil ditambahkan.', session('success'));
        $this->assertTrue($response->isRedirect());
    }

    public function test_update_admin_returns_validation_errors_when_data_invalid()
    {
        $controller = new SuperAdminController();
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $request = new Request([
            'admin_name' => '',
            'admin_email' => 'bad-email',
            'admin_password' => 'short',
        ]);

        $response = $controller->updateAdmin($request, $user);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->getSession()->get('errors')->getBag('editAdmin')->has('admin_name'));
    }

    public function test_update_admin_updates_admin_details()
    {
        $controller = new SuperAdminController();
        $user = User::factory()->create(['name' => 'Admin Lama', 'email' => 'adminlama@example.com']);
        $request = new Request([
            'admin_name' => 'Admin Baru',
            'admin_email' => 'adminbaru@example.com',
            'admin_password' => 'password123',
        ]);

        $response = $controller->updateAdmin($request, $user);

        $user->refresh();

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Admin Baru', $user->name);
        $this->assertSame('adminbaru@example.com', $user->email);
        $this->assertSame('Admin "Admin Baru" berhasil diperbarui.', session('success'));
    }

    public function test_toggle_institution_updates_status_for_institution_and_users()
    {
        $controller = new SuperAdminController();
        $institution = Institution::factory()->create(['is_active' => false]);
        $user = User::factory()->create(['institution_id' => $institution->id, 'is_active' => false]);

        $response = $controller->toggleInstitution($institution);

        $institution->refresh();
        $user->refresh();

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($institution->is_active);
        $this->assertTrue($user->is_active);
        $this->assertSame('Lembaga "' . $institution->name . '" berhasil diaktifkan.', session('success'));
    }

    public function test_destroy_admin_deletes_admin_account()
    {
        $controller = new SuperAdminController();
        $user = User::factory()->create(['role' => 'admin']);

        $response = $controller->destroyAdmin($user);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Akun admin berhasil dihapus.', session('success'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_background_deletes_system_background()
    {
        Storage::fake('public');
        $background = BackgroundLibrary::create([
            'institution_id' => null,
            'name' => 'Background',
            'path' => 'backgrounds/system/background.jpg',
            'is_system' => true,
        ]);
        Storage::disk('public')->put($background->path, 'content');

        $controller = new SuperAdminController();
        $response = $controller->destroyBackground($background);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Background "Background" berhasil dihapus dari library sistem.', session('success'));
        $this->assertDatabaseMissing('background_library', ['id' => $background->id]);
    }

    public function test_destroy_background_rejects_non_system_background()
    {
        $background = BackgroundLibrary::create([
            'institution_id' => null,
            'name' => 'Background',
            'path' => 'backgrounds/system/background.jpg',
            'is_system' => false,
        ]);

        $controller = new SuperAdminController();
        $response = $controller->destroyBackground($background);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Hanya background sistem yang dapat dihapus melalui panel ini.', session('error'));
        $this->assertDatabaseHas('background_library', ['id' => $background->id]);
    }

    public function test_store_background_uses_uploaded_filename_when_name_missing()
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('hero.png', 100, 100);
        $request = new Request([
            'name' => null,
        ]);
        $request->files->set('file', $file);

        $controller = new SuperAdminController();
        $response = $controller->storeBackground($request);

        $background = BackgroundLibrary::where('name', 'hero')->first();

        $this->assertNotNull($background);
        $this->assertTrue($response->isRedirect());
        $this->assertSame('Background "hero" berhasil ditambahkan ke library sistem.', session('success'));
    }
    public function test_index_backgrounds_returns_correct_view_and_data()
{
    // 1. Persiapkan dummy data untuk SuperAdmin, Institution, dan System Background
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'is_primary' => true,
    ]);
    $this->actingAs($superAdmin);

    $institution = Institution::factory()->create();

    // Insert direct DB untuk background sistem (menghindari constraint NOT NULL 'name')
    DB::table('background_library')->insert([
        'name'           => 'System Background A',
        'path'           => 'backgrounds/sys_a.jpg',
        'is_system'      => true,
        'institution_id' => null,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    // 2. Eksekusi method controller secara langsung
    $controller = new SuperAdminController();
    $response = $controller->indexBackgrounds();

    // 3. Verifikasi response adalah instance dari View dan bernama 'superadmin.index'
    $this->assertInstanceOf(View::class, $response);
    $this->assertEquals('superadmin.index', $response->name());

    // 4. Verifikasi variabel yang di-pass ke view terdefinisi
    $this->assertArrayHasKey('institutions', $response->getData());
    $this->assertArrayHasKey('superAdmins', $response->getData());
    $this->assertArrayHasKey('systemBackgrounds', $response->getData());
}
}