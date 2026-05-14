<?php

namespace App\Http\Controllers;

use App\Models\BackgroundLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * BackgroundLibraryController
 *
 * Endpoint:
 *   GET  /backgrounds/library          → index()   : list system + lembaga
 *   POST /backgrounds/library          → store()   : upload background lembaga baru
 *   DELETE /backgrounds/library/{id}   → destroy() : hapus background lembaga
 *   POST /backgrounds/library/{id}/select → select() : set background aktif ke session
 */
class BackgroundLibraryController extends Controller
{
    const MAX_PER_INSTITUTION = 10;

    /**
     * List semua background: system (Validly) + milik lembaga.
     */
    public function index(): JsonResponse
    {
        $institutionId = auth()->user()->institution_id;

        $system   = BackgroundLibrary::system()
                        ->orderBy('name')
                        ->get()
                        ->map(fn ($bg) => $this->format($bg));

        $lembaga  = BackgroundLibrary::forInstitution($institutionId)
                        ->latest()
                        ->get()
                        ->map(fn ($bg) => $this->format($bg));

        return response()->json([
            'system'        => $system,
            'lembaga'       => $lembaga,
            'current_count' => $lembaga->count(),
            'max_count'     => self::MAX_PER_INSTITUTION,
        ]);
    }

    /**
     * Upload background baru milik lembaga ke library.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'name' => 'nullable|string|max:100',
        ]);

        $institution = auth()->user()->institution;

        $count = BackgroundLibrary::forInstitution($institution->id)->count();
        if ($count >= self::MAX_PER_INSTITUTION) {
            return response()->json([
                'message' => 'Batas maksimal ' . self::MAX_PER_INSTITUTION . ' background per lembaga telah tercapai. Hapus background lain terlebih dahulu.',
                'limit_reached' => true,
            ], 422);
        }

        $file = $request->file('file');
        $name = $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $path = $file->store('backgrounds/library/' . $institution->id, 'public');

        $bg = BackgroundLibrary::create([
            'institution_id' => $institution->id,
            'name'           => $name,
            'path'           => $path,
            'is_system'      => false,
        ]);

        return response()->json(array_merge($this->format($bg), [
            'current_count' => $count + 1,
            'max_count'     => self::MAX_PER_INSTITUTION,
        ]));
    }

    /**
     * Hapus background lembaga dari library.
     */
    public function destroy(BackgroundLibrary $background): JsonResponse
    {
        // Pastikan hanya background milik lembaga sendiri yang bisa dihapus
        if ($background->is_system || $background->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        Storage::disk('public')->delete($background->path);
        $background->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Pilih background dari library — set ke institution background_path aktif.
     */
    public function select(BackgroundLibrary $background): JsonResponse
    {
        $institution = auth()->user()->institution;

        // Boleh pilih system bg atau bg milik lembaga sendiri
        if (!$background->is_system && $background->institution_id !== $institution->id) {
            abort(403);
        }

        $institution->update(['background_path' => $background->path]);

        $url = $background->is_system
            ? asset('storage/' . $background->path)
            : Storage::disk('public')->url($background->path);

        return response()->json([
            'success' => true,
            'url'     => $url,
        ]);
    }

    private function format(BackgroundLibrary $bg): array
    {
        // Background system: disimpan di public/storage/backgrounds/system/
        // diakses via URL langsung /storage/backgrounds/system/...
        // Background lembaga: disimpan via Laravel Storage disk public
        $url = $bg->is_system
            ? asset('storage/' . $bg->path)
            : Storage::disk('public')->url($bg->path);

        return [
            'id'        => $bg->id,
            'name'      => $bg->name,
            'url'       => $url,
            'is_system' => $bg->is_system,
        ];
    }
}
