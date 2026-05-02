<?php

namespace Tests\Feature\Auth;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature Test: Login & Authentication — Iterasi 1
 *
 * Jalankan: php artisan test --filter LoginTest
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    // ─── Halaman login ─────────────────────────────────────────

    #[Test]
    public function login_page_is_accessible_by_guest(): void
    {
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('Masuk ke Dasbor');
    }

    #[Test]
    public function login_page_shows_reset_password_email_info(): void
    {
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('mail@oemahwebsite.com');
    }

    #[Test]
    public function login_page_has_no_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertDontSee('Lupa Password?')
            ->assertDontSee('password.request');
    }

    #[Test]
    public function logged_in_admin_is_redirected_from_login_to_certificate(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->adminOf($institution)->create();

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirect(route('certificate.index'));
    }

    #[Test]
    public function logged_in_superadmin_is_redirected_from_login_to_superadmin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('login'))
            ->assertRedirect(route('superadmin.index'));
    }

    // ─── Login sukses ──────────────────────────────────────────

    #[Test]
    public function admin_can_login_and_is_redirected_to_certificate(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->adminOf($institution)->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post(route('login.post'), [
            'email'    => 'admin@test.com',
            'password' => 'password123',
        ])
        ->assertRedirect(route('certificate.index'));

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function super_admin_can_login_and_is_redirected_to_superadmin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'email'    => 'superadmin@test.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post(route('login.post'), [
            'email'    => 'superadmin@test.com',
            'password' => 'password123',
        ])
        ->assertRedirect(route('superadmin.index'));

        $this->assertAuthenticatedAs($superAdmin);
    }

    // ─── Validasi field ────────────────────────────────────────

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $institution = Institution::factory()->create();
        User::factory()->adminOf($institution)->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('correct_password'),
        ]);

        $this->post(route('login.post'), [
            'email'    => 'admin@test.com',
            'password' => 'wrong_password',
        ])
        ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function login_fails_with_nonexistent_email(): void
    {
        $this->post(route('login.post'), [
            'email'    => 'notexist@test.com',
            'password' => 'somepassword',
        ])
        ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function login_requires_email_field(): void
    {
        $this->post(route('login.post'), ['password' => 'password123'])
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function login_requires_valid_email_format(): void
    {
        $this->post(route('login.post'), [
            'email'    => 'bukan-email',
            'password' => 'password123',
        ])
        ->assertSessionHasErrors('email');
    }

    #[Test]
    public function login_requires_password_field(): void
    {
        $this->post(route('login.post'), ['email' => 'admin@test.com'])
            ->assertSessionHasErrors('password');
    }

    // ─── Akun nonaktif ─────────────────────────────────────────

    #[Test]
    public function inactive_user_cannot_login(): void
    {
        $institution = Institution::factory()->create();
        User::factory()->adminOf($institution)->inactive()->create([
            'email'    => 'inactive@test.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post(route('login.post'), [
            'email'    => 'inactive@test.com',
            'password' => 'password123',
        ])
        ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ─── Forgot/Reset password route tidak ada ─────────────────

    #[Test]
    public function forgot_password_route_does_not_exist(): void
    {
        $this->get('/forgot-password')->assertNotFound();
    }

    #[Test]
    public function reset_password_route_does_not_exist(): void
    {
        $this->get('/reset-password/sometoken')->assertNotFound();
    }

    // ─── Logout ────────────────────────────────────────────────

    #[Test]
    public function logged_in_user_can_logout(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->adminOf($institution)->create();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }

    #[Test]
    public function logout_requires_post_method(): void
    {
        $this->get('/logout')->assertMethodNotAllowed();
    }

    // ─── Remember me ───────────────────────────────────────────

    #[Test]
    public function user_can_login_with_remember_me(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->adminOf($institution)->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post(route('login.post'), [
            'email'    => 'admin@test.com',
            'password' => 'password123',
            'remember' => '1',
        ])
        ->assertRedirect(route('certificate.index'));

        $this->assertAuthenticatedAs($admin);
    }
}
