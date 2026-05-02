<?php

namespace Tests\Feature\Profile;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
        $this->admin       = User::factory()->adminOf($this->institution)->create([
            'name'  => 'Admin Test',
            'email' => 'admin@test.com',
        ]);
    }

    #[Test]
    public function admin_can_access_profile_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('profile.edit'))
            ->assertStatus(200)
            ->assertSee('Edit Profil')
            ->assertSee('Admin Test');
    }

    #[Test]
    public function guest_cannot_access_profile_page(): void
    {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function superadmin_cannot_access_profile_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)
            ->get(route('profile.edit'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_update_name_and_email(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('profile.update'), [
                'name'  => 'Nama Baru',
                'email' => 'baru@test.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'    => $this->admin->id,
            'name'  => 'Nama Baru',
            'email' => 'baru@test.com',
        ]);
    }

    #[Test]
    public function admin_can_update_password(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('profile.update'), [
                'name'                  => $this->admin->name,
                'email'                 => $this->admin->email,
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('newpassword123', $this->admin->fresh()->password));
    }

    #[Test]
    public function admin_can_update_without_changing_password(): void
    {
        $oldHash = $this->admin->password;

        $this->actingAs($this->admin)
            ->patch(route('profile.update'), [
                'name'  => 'Nama Baru',
                'email' => $this->admin->email,
            ])
            ->assertRedirect();

        $this->assertEquals($oldHash, $this->admin->fresh()->password);
    }

    #[Test]
    public function admin_cannot_use_duplicate_email(): void
    {
        User::factory()->adminOf($this->institution)->create(['email' => 'sudahada@test.com']);

        $this->actingAs($this->admin)
            ->patch(route('profile.update'), [
                'name'  => 'Admin Test',
                'email' => 'sudahada@test.com',
            ])
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function admin_can_keep_same_email(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('profile.update'), [
                'name'  => 'Nama Diubah',
                'email' => 'admin@test.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['name' => 'Nama Diubah']);
    }

    #[Test]
    public function password_confirmation_must_match(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('profile.update'), [
                'name'                  => $this->admin->name,
                'email'                 => $this->admin->email,
                'password'              => 'newpassword123',
                'password_confirmation' => 'tidakcocok999',
            ])
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('profile.update'), [
                'name'  => '',
                'email' => $this->admin->email,
            ])
            ->assertSessionHasErrors('name');
    }
}
