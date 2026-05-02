<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function index()
    {
        $institutions = Institution::with('users')
            ->withCount('users')
            ->latest()
            ->get();

        return view('superadmin.index', compact('institutions'));
    }

    public function storeInstitution(Request $request)
    {
        $validated = $request->validate([
            'institution_name'    => 'required|string|max:255',
            'institution_email'   => 'required|email|unique:institutions,email',
            'institution_phone'   => 'required|string|max:20',
            'institution_address' => 'required|string',
            'admin_name'          => 'required|string|max:255',
            'admin_email'         => 'required|email|unique:users,email',
            'admin_password'      => 'required|min:8',
        ]);

        $institution = Institution::create([
            'name'    => $validated['institution_name'],
            'slug'    => Str::slug($validated['institution_name']) . '-' . Str::random(4),
            'email'   => $validated['institution_email'],
            'phone'   => $validated['institution_phone'] ?? null,
            'address' => $validated['institution_address'] ?? null,
        ]);

        User::create([
            'name'           => $validated['admin_name'],
            'email'          => $validated['admin_email'],
            'password'       => Hash::make($validated['admin_password']),
            'role'           => 'admin',
            'institution_id' => $institution->id,
        ]);

        return back()->with('success', "Lembaga \"{$institution->name}\" berhasil ditambahkan.");
    }

    /**
     * Update data lembaga.
     */
    public function updateInstitution(Request $request, Institution $institution)
    {
        $validated = $request->validate([
            'institution_name'    => 'required|string|max:255',
            'institution_email'   => 'required|email|unique:institutions,email,' . $institution->id,
            'institution_phone'   => 'required|string|max:20',
            'institution_address' => 'required|string',
        ]);

        $institution->update([
            'name'    => $validated['institution_name'],
            'email'   => $validated['institution_email'],
            'phone'   => $validated['institution_phone'] ?? null,
            'address' => $validated['institution_address'] ?? null,
        ]);

        return back()->with('success', "Lembaga \"{$institution->name}\" berhasil diperbarui.");
    }

    public function toggleInstitution(Institution $institution)
    {
        $institution->update(['is_active' => !$institution->is_active]);
        $institution->users()->update(['is_active' => $institution->is_active]);

        $status = $institution->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Lembaga \"{$institution->name}\" berhasil {$status}.");
    }

    public function destroyInstitution(Institution $institution)
    {
        $name = $institution->name;
        $institution->users()->delete();
        $institution->delete();
        return back()->with('success', "Lembaga \"{$name}\" berhasil dihapus.");
    }

    public function storeAdmin(Request $request, Institution $institution)
    {
        $validated = $request->validate([
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|unique:users,email',
            'admin_password' => 'required|min:8',
        ]);

        User::create([
            'name'           => $validated['admin_name'],
            'email'          => $validated['admin_email'],
            'password'       => Hash::make($validated['admin_password']),
            'role'           => 'admin',
            'institution_id' => $institution->id,
        ]);

        return back()->with('success', 'Admin baru berhasil ditambahkan.');
    }

    /**
     * Update data admin.
     */
    public function updateAdmin(Request $request, User $user)
    {
        $validated = $request->validate([
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|unique:users,email,' . $user->id,
            'admin_password' => 'nullable|min:8',
        ]);

        $data = [
            'name'  => $validated['admin_name'],
            'email' => $validated['admin_email'],
        ];

        if (!empty($validated['admin_password'])) {
            $data['password'] = Hash::make($validated['admin_password']);
        }

        $user->update($data);

        return back()->with('success', "Admin \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroyAdmin(User $user)
    {
        $user->delete();
        return back()->with('success', 'Akun admin berhasil dihapus.');
    }
}
