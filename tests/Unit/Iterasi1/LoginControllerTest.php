<?php

namespace Tests\Unit\Iterasi1;

use Tests\TestCase;
use App\Models\User; // <-- Pastikan namespace model User ini ter-import
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Skenario 1: User BELUM login mengakses halaman login
     */
    public function test_guest_can_view_login_form()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Skenario 2 (Coverage Baris 13-16): User SUDAH login mengakses halaman login
     */
    public function test_authenticated_user_is_redirected_when_accessing_login_page()
    {
        // 1. Buat dummy user
        $user = User::factory()->create();

        // 2. Simulasi user sudah login (Auth::check() true) dan buka halaman login
        $response = $this->actingAs($user)->get('/login');

        // 3. Verifikasi response melakukan redirect (mengeksekusi return $this->redirectByRole())
        $response->assertRedirect();
    }
}