<?php

namespace App\Http\Controllers;

use App\Models\BackgroundLibrary;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class SuperAdminController extends Controller
{
    public function index()
    {
        $institutions = Institution::with('users')
            ->withCount('users')
            ->latest()
            ->get();

        // Akun utama (is_primary) selalu di posisi pertama, sisanya diurutkan by created_at
        $superAdmins = User::where('role', 'super_admin')
            ->orderByDesc('is_primary')
            ->oldest()
            ->get();

        $systemBackgrounds = BackgroundLibrary::system()
            ->orderBy('name')
            ->get();

        return view('superadmin.index', compact('institutions', 'superAdmins', 'systemBackgrounds'));
    }

    // ─── Tambah Super Admin ────────────────────────────────────

    public function storeSuperAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'superadmin_name'     => 'required|string|max:255',
            'superadmin_email'    => 'required|email|unique:users,email',
            'superadmin_password' => 'required|min:8',
        ], [
            'superadmin_email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'superadmin_password.min' => 'Password minimal 8 karakter.',
        ], [
            'superadmin_name'     => 'nama',
            'superadmin_email'    => 'email',
            'superadmin_password' => 'password',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'addSuperAdmin')->withInput();
        }

        User::create([
            'name'           => $request->superadmin_name,
            'email'          => $request->superadmin_email,
            'password'       => Hash::make($request->superadmin_password),
            'plain_password' => $request->superadmin_password,
            'role'           => 'super_admin',
            'institution_id' => null,
            'is_primary'     => false, // Akun baru tidak pernah menjadi akun utama
        ]);

        return back()->with('success', 'Super Admin baru berhasil ditambahkan.');
    }

    // ─── Hapus Super Admin ─────────────────────────────────────

    public function destroySuperAdmin(User $user)
    {
        // Cegah super admin menghapus dirinya sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }

        // Cegah menghapus akun utama
        if ($user->isPrimarySuperAdmin()) {
            return back()->with('error', 'Akun Super Admin utama tidak dapat dihapus.');
        }

        $name = $user->name;
        $user->delete();
        return back()->with('success', "Akun Super Admin \"{$name}\" berhasil dihapus.");
    }

    // ─── Tambah Lembaga ────────────────────────────────────────

    public function storeInstitution(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'institution_name'    => 'required|string|max:255',
            'institution_email'   => 'required|email|unique:institutions,email',
            'institution_phone'   => 'required|string|max:20',
            'institution_address' => 'required|string',
            'admin_name'          => 'required|string|max:255',
            'admin_email'         => 'required|email|unique:users,email',
            'admin_password'      => 'required|min:8',
        ], [
            'institution_email.unique' => 'Email lembaga sudah digunakan oleh lembaga lain.',
            'admin_email.unique'       => 'Email admin sudah digunakan oleh akun lain.',
            'admin_password.min'       => 'Password minimal 8 karakter.',
        ], [
            'institution_name'    => 'nama lembaga',
            'institution_email'   => 'email lembaga',
            'institution_phone'   => 'nomor telepon',
            'institution_address' => 'alamat',
            'admin_name'          => 'nama admin',
            'admin_email'         => 'email admin',
            'admin_password'      => 'password',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'addInstitution')->withInput();
        }

        $institution = Institution::create([
            'name'    => $request->institution_name,
            'slug'    => Str::slug($request->institution_name) . '-' . Str::random(4),
            'email'   => $request->institution_email,
            'phone'   => $request->institution_phone,
            'address' => $request->institution_address,
        ]);

        User::create([
            'name'           => $request->admin_name,
            'email'          => $request->admin_email,
            'password'       => Hash::make($request->admin_password),
            'plain_password' => $request->admin_password,
            'role'           => 'admin',
            'institution_id' => $institution->id,
        ]);

        return back()->with('success', "Lembaga \"{$institution->name}\" berhasil ditambahkan.");
    }

    // ─── Edit Lembaga ──────────────────────────────────────────

    public function updateInstitution(Request $request, Institution $institution)
    {
        $validator = Validator::make($request->all(), [
            'institution_name'    => 'required|string|max:255',
            'institution_email'   => 'required|email|unique:institutions,email,' . $institution->id,
            'institution_phone'   => 'required|string|max:20',
            'institution_address' => 'required|string',
        ], [
            'institution_email.unique' => 'Email lembaga sudah digunakan oleh lembaga lain.',
        ], [
            'institution_name'    => 'nama lembaga',
            'institution_email'   => 'email lembaga',
            'institution_phone'   => 'nomor telepon',
            'institution_address' => 'alamat',
        ]);

        if ($validator->fails()) {
            session([
                'editInstId'      => $institution->id,
                'editInstName'    => $request->institution_name,
                'editInstEmail'   => $request->institution_email,
                'editInstPhone'   => $request->institution_phone,
                'editInstAddress' => $request->institution_address,
            ]);
            return back()->withErrors($validator, 'editInstitution')->withInput();
        }

        $institution->update([
            'name'    => $request->institution_name,
            'email'   => $request->institution_email,
            'phone'   => $request->institution_phone,
            'address' => $request->institution_address,
        ]);

        return back()->with('success', "Lembaga \"{$institution->name}\" berhasil diperbarui.");
    }

    // ─── Tambah Admin ──────────────────────────────────────────

    public function storeAdmin(Request $request, Institution $institution)
    {
        $validator = Validator::make($request->all(), [
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|unique:users,email',
            'admin_password' => 'required|min:8',
        ], [
            'admin_email.unique'  => 'Email ini sudah digunakan oleh akun lain.',
            'admin_password.min'  => 'Password minimal 8 karakter.',
        ], [
            'admin_name'     => 'nama admin',
            'admin_email'    => 'email admin',
            'admin_password' => 'password',
        ]);

        if ($validator->fails()) {
            session([
                'addAdminInstId'   => $institution->id,
                'addAdminInstName' => $institution->name,
            ]);
            return back()->withErrors($validator, 'addAdmin')->withInput();
        }

        User::create([
            'name'           => $request->admin_name,
            'email'          => $request->admin_email,
            'password'       => Hash::make($request->admin_password),
            'plain_password' => $request->admin_password,
            'role'           => 'admin',
            'institution_id' => $institution->id,
        ]);

        return back()->with('success', 'Admin baru berhasil ditambahkan.');
    }

    // ─── Edit Admin ────────────────────────────────────────────

    public function updateAdmin(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|unique:users,email,' . $user->id,
            'admin_password' => 'nullable|min:8',
        ], [
            'admin_email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'admin_password.min' => 'Password minimal 8 karakter.',
        ], [
            'admin_name'  => 'nama admin',
            'admin_email' => 'email admin',
        ]);

        if ($validator->fails()) {
            session([
                'editAdminId'    => $user->id,
                'editAdminName'  => $request->admin_name,
                'editAdminEmail' => $request->admin_email,
            ]);
            return back()->withErrors($validator, 'editAdmin')->withInput();
        }

        $data = [
            'name'  => $request->admin_name,
            'email' => $request->admin_email,
        ];

        if (!empty($request->admin_password)) {
            $data['password']       = Hash::make($request->admin_password);
            $data['plain_password'] = $request->admin_password;
        }

        $user->update($data);

        return back()->with('success', "Admin \"{$user->name}\" berhasil diperbarui.");
    }

    // ─── Toggle & Destroy Lembaga/Admin ───────────────────────

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

    public function destroyAdmin(User $user)
    {
        $user->delete();
        return back()->with('success', 'Akun admin berhasil dihapus.');
    }

    // ─── System Background Library ─────────────────────────────

    public function indexBackgrounds()
    {
        $systemBackgrounds = BackgroundLibrary::system()->orderBy('name')->get();
        return view('superadmin.index', [
            'institutions'      => Institution::with('users')->withCount('users')->latest()->get(),
            'superAdmins'       => User::where('role', 'super_admin')->orderByDesc('is_primary')->oldest()->get(),
            'systemBackgrounds' => $systemBackgrounds,
        ]);
    }

    public function storeBackground(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'name' => 'nullable|string|max:100',
        ]);

        $file = $request->file('file');
        $name = $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $path = $file->store('backgrounds/system', 'public');

        BackgroundLibrary::create([
            'institution_id' => null,
            'name'           => $name,
            'path'           => $path,
            'is_system'      => true,
        ]);

        return back()->with('success', "Background \"$name\" berhasil ditambahkan ke library sistem.");
    }

    public function destroyBackground(BackgroundLibrary $background)
    {
        if (!$background->is_system) {
            return back()->with('error', 'Hanya background sistem yang dapat dihapus melalui panel ini.');
        }

        Storage::disk('public')->delete($background->path);
        $name = $background->name;
        $background->delete();

        return back()->with('success', "Background \"$name\" berhasil dihapus dari library sistem.");
    }
}