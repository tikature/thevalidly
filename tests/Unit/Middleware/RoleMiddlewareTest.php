<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit Test: RoleMiddleware
 *
 * Menguji logika pengecekan role dan status aktif user.
 * Jalankan: php artisan test --filter RoleMiddlewareTest
 */
class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private RoleMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoleMiddleware();
    }

    private function makeRequest(): Request
    {
        return Request::create('/test', 'GET');
    }

    private function next(): \Closure
    {
        return fn($req) => new Response('OK');
    }

    // ─── Guest (belum login) ───────────────────────────────────

    #[Test]
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $request  = $this->makeRequest();
        $response = $this->middleware->handle($request, $this->next(), 'admin');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    // ─── Role tidak sesuai ─────────────────────────────────────

    #[Test]
    public function admin_cannot_access_super_admin_route(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->adminOf($institution)->create();
        $this->actingAs($admin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($this->makeRequest(), $this->next(), 'super_admin');
    }

    #[Test]
    public function super_admin_cannot_access_admin_only_route(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($this->makeRequest(), $this->next(), 'admin');
    }

    // ─── Role sesuai ───────────────────────────────────────────

    #[Test]
    public function admin_can_access_admin_route(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->adminOf($institution)->create();
        $this->actingAs($admin);

        $response = $this->middleware->handle($this->makeRequest(), $this->next(), 'admin');
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function super_admin_can_access_super_admin_route(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $response = $this->middleware->handle($this->makeRequest(), $this->next(), 'super_admin');
        $this->assertEquals(200, $response->getStatusCode());
    }

    // ─── Multiple roles ────────────────────────────────────────

    #[Test]
    public function route_allowing_multiple_roles_accepts_both(): void
    {
        $institution = Institution::factory()->create();
        $admin       = User::factory()->adminOf($institution)->create();
        $superAdmin  = User::factory()->superAdmin()->create();

        $this->actingAs($admin);
        $res1 = $this->middleware->handle($this->makeRequest(), $this->next(), 'admin', 'super_admin');
        $this->assertEquals(200, $res1->getStatusCode());

        $this->actingAs($superAdmin);
        $res2 = $this->middleware->handle($this->makeRequest(), $this->next(), 'admin', 'super_admin');
        $this->assertEquals(200, $res2->getStatusCode());
    }

    // ─── Akun nonaktif ────────────────────────────────────────

    #[Test]
    public function inactive_user_is_logged_out_and_redirected(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->adminOf($institution)->inactive()->create();
        $this->actingAs($admin);

        $response = $this->middleware->handle($this->makeRequest(), $this->next(), 'admin');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertGuest(); // pastikan sudah logout
    }

    #[Test]
    public function inactive_user_gets_json_401_on_ajax_request(): void
    {
        $institution = Institution::factory()->create();
        $admin       = User::factory()->adminOf($institution)->inactive()->create();
        $this->actingAs($admin);

        // Simulasi request AJAX (Accept: application/json)
        $request = Request::create('/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $this->middleware->handle($request, $this->next(), 'admin');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertGuest();

        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('redirect', $body);
        $this->assertStringContainsString('login', $body['redirect']);
    }

    #[Test]
    public function inactive_user_json_response_contains_deactivation_message(): void
    {
        $institution = Institution::factory()->create();
        $admin       = User::factory()->adminOf($institution)->inactive()->create();
        $this->actingAs($admin);

        $request = Request::create('/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $this->middleware->handle($request, $this->next(), 'admin');
        $body     = json_decode($response->getContent(), true);

        $this->assertStringContainsString('dinonaktifkan', $body['message']);
    }

    #[Test]
    public function inactive_user_html_request_still_redirects(): void
    {
        $institution = Institution::factory()->create();
        $admin       = User::factory()->adminOf($institution)->inactive()->create();
        $this->actingAs($admin);

        // Request biasa (bukan AJAX) — tetap redirect 302
        $response = $this->middleware->handle($this->makeRequest(), $this->next(), 'admin');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }
}