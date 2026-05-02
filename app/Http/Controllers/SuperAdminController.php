<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        return view('superadmin.index', compact('institutions'));
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
            $data['password'] = Hash::make($request->admin_password);
        }

        $user->update($data);

        return back()->with('success', "Admin \"{$user->name}\" berhasil diperbarui.");
    }

    // ─── Logic Lainnya (Toggle & Destroy tetap sama) ──────────

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
}