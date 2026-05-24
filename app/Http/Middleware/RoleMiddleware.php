<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Cek apakah user yang login memiliki role yang diizinkan.
     *
     * Penggunaan di route:
     *   ->middleware('role:super_admin')
     *   ->middleware('role:admin')
     *   ->middleware('role:super_admin,admin')  ← multiple role
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Cek apakah role user ada di daftar role yang diizinkan
        if (!in_array($user->role, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Cek apakah akun aktif
        if (!$user->is_active) {
            auth()->logout();

            // Untuk request AJAX/JSON → kembalikan 401 JSON bukan redirect HTML
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message'  => 'Akun Anda telah dinonaktifkan.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan.']);
        }

        return $next($request);
    }
}