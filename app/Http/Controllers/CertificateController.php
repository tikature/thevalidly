<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    /**
     * Halaman generator sertifikat.
     */
    public function index()
    {
        $institution = auth()->user()->institution;
        return view('certificate.index', compact('institution'));
    }

    /**
     * Simpan sertifikat ke DB.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'participants'              => 'required|array|min:1',
            'participants.*.nama'       => 'required|string|max:255',
            'participants.*.perusahaan' => 'nullable|string|max:255',
            'participants.*.nomor'      => 'required|string|max:100',
            'event_name'                => 'required|string|max:255',
            'event_date'                => 'required|string|max:100',
            'event_place'               => 'nullable|string|max:255',
            'signer_name'               => 'nullable|string|max:255',
            'signer_title'              => 'nullable|string|max:255',
            'cert_desc'                 => 'nullable|string|max:200',
        ]);

        $user          = auth()->user();
        $institutionId = $user->institution_id;
        $certificates  = [];

        foreach ($validated['participants'] as $p) {
            $cert = Certificate::create([
                'institution_id' => $institutionId,
                'issued_by'      => $user->id,
                'nama'           => $p['nama'],
                'perusahaan'     => $p['perusahaan'] ?? null,
                'nomor'          => $p['nomor'],
                'event_name'     => $validated['event_name'],
                'event_date'     => $validated['event_date'],
                'event_place'    => $validated['event_place'] ?? null,
                'signer_name'    => $validated['signer_name'] ?? null,
                'signer_title'   => $validated['signer_title'] ?? null,
                'cert_desc'      => $validated['cert_desc'] ?? null,
            ]);

            $certificates[] = [
                'id'                 => $cert->id,
                'nama'               => $cert->nama,
                'nomor'              => $cert->nomor,
                'verification_token' => $cert->verification_token,
                'verification_url'   => $cert->verificationUrl(),
                'pdf_url'            => route('certificate.pdf', $cert->verification_token),
            ];
        }

        return response()->json([
            'count'        => count($certificates),
            'certificates' => $certificates,
        ]);
    }

    /**
     * Pre-generate PDF dan simpan ke storage agar download berikutnya instan.
     * Dipanggil dari JS di background setelah store() berhasil.
     */
    public function pregenerate(string $token)
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with('institution')
            ->firstOrFail();

        if (auth()->user()->institution_id !== $certificate->institution_id) {
            abort(403);
        }

        // Jika sudah ada file cache, tidak perlu generate ulang
        $cachePath = 'pdf_cache/' . $token . '.pdf';
        if (Storage::disk('local')->exists($cachePath)) {
            return response()->json(['success' => true, 'cached' => true]);
        }

        try {
            $institution = $certificate->institution;
            $pdf = $this->buildPdf($certificate, $institution);

            // Simpan ke storage/app/pdf_cache/
            Storage::disk('local')->put($cachePath, $pdf->output());

            return response()->json(['success' => true, 'cached' => false]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate PDF sertifikat via DomPDF.
     * Jika sudah ada cache dari pregenerate(), langsung serve dari file.
     */
    public function pdf(string $token)
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with('institution')
            ->firstOrFail();

        if (auth()->user()->institution_id !== $certificate->institution_id) {
            abort(403);
        }

        $filename = 'sertifikat_'
            . Str::slug($certificate->nama)
            . '_'
            . str_replace(['/', '\\'], '-', $certificate->nomor)
            . '.pdf';

        // Cek apakah sudah ada cache dari pregenerate()
        $cachePath = 'pdf_cache/' . $token . '.pdf';
        if (Storage::disk('local')->exists($cachePath)) {
            $pdfContent = Storage::disk('local')->get($cachePath);
            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        // Fallback: generate on-the-fly jika belum ada cache
        $institution = $certificate->institution;
        $pdf = $this->buildPdf($certificate, $institution);

        return $pdf->download($filename);
    }

    /**
     * Build DomPDF instance — dipakai oleh pdf() dan pregenerate().
     */
    private function buildPdf(Certificate $certificate, $institution)
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('certificate.pdf', [
            'certificate' => $certificate,
            'institution' => $institution,
            'logoPath'    => $this->resolveAssetPath($institution->logo_path),
            'ttdPath'     => $this->resolveAssetPath($institution->ttd_path),
            'capPath'     => $this->resolveAssetPath($institution->cap_path),
            'bgPath'      => $this->resolveAssetPath($institution->background_path),
        ])
        ->setPaper([0, 0, 841.89, 595.28])
        ->setOptions([
            'isHtml5ParserEnabled'    => true,
            'isRemoteEnabled'         => false,
            'defaultFont'             => 'DejaVu Serif',
            'dpi'                     => 96,
            'isFontSubsettingEnabled' => true,
            'isPhpEnabled'            => false,
            'chroot'                  => str_replace('\\', '/', realpath(base_path())),
        ]);
    }

    /**
     * Upload aset (logo, ttd, cap, background) per lembaga.
     */
    public function uploadAsset(Request $request)
    {
        $request->validate([
            'type' => 'required|in:logo,ttd,cap,background',
            'file' => 'required|max:2048|mimetypes:image/jpeg,image/png',
        ], [
            'file.mimetypes' => 'Format file tidak didukung. Gunakan PNG atau JPG.',
            'file.max'       => 'Ukuran file terlalu besar. Maksimal 2MB.',
        ]);

        $institution = auth()->user()->institution;
        $type        = $request->type;
        $column      = $type . '_path';

        if ($institution->$column) {
            Storage::disk('public')->delete($institution->$column);
        }

        $path = $request->file('file')->store(
            'institutions/' . $institution->id . '/' . $type,
            'public'
        );

        $institution->update([$column => $path]);

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Hapus aset per lembaga.
     */
    public function removeAsset(Request $request)
    {
        $request->validate([
            'type' => 'required|in:logo,ttd,cap,background',
        ]);

        $institution = auth()->user()->institution;
        $type        = $request->type;
        $column      = $type . '_path';

        if ($institution->$column) {
            Storage::disk('public')->delete($institution->$column);
            $institution->update([$column => null]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Ambil URL semua aset lembaga.
     */
    public function getAssets()
    {
        $institution = auth()->user()->institution;

        return response()->json([
            'logo'       => $institution->logoUrl(),
            'ttd'        => $institution->ttdUrl(),
            'cap'        => $institution->capUrl(),
            'background' => $institution->backgroundUrl(),
        ]);
    }

    /**
     * Riwayat sertifikat.
     */
    public function history(Request $request)
    {
        $institutionId = auth()->user()->institution_id;
        $sort          = $request->sort === 'asc' ? 'asc' : 'desc';

        $certificates = Certificate::forInstitution($institutionId)
            ->with('issuedBy')
            ->when($request->search, fn($q) => $q->search($request->search))
            ->orderBy('issued_at', $sort)
            ->paginate(20)
            ->withQueryString();

        return view('certificate.history', compact('certificates', 'sort'));
    }

    /**
     * Hapus sertifikat.
     */
    public function destroy(Certificate $certificate)
    {
        if ($certificate->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        // Hapus cache PDF jika ada
        $cachePath = 'pdf_cache/' . $certificate->verification_token . '.pdf';
        if (Storage::disk('local')->exists($cachePath)) {
            Storage::disk('local')->delete($cachePath);
        }

        $certificate->delete();
        return back()->with('success', 'Sertifikat berhasil dihapus.');
    }

    /**
     * Halaman verifikasi publik.
     */
    public function verify(string $token)
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with('institution')
            ->first();

        if (!$certificate) {
            return view('certificate.verify-invalid', ['token' => $token]);
        }

        return view('certificate.verify', compact('certificate'));
    }

    /**
     * Konvert relative storage path ke absolute path untuk DomPDF.
     */
    private function resolveAssetPath(?string $relativePath): string
    {
        if (!$relativePath) return '';
        $full = storage_path('app/public/' . $relativePath);
        return str_replace('\\', '/', $full);
    }
}