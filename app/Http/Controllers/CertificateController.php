<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index()
    {
        $institution = auth()->user()->institution;
        return view('certificate.index', compact('institution'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'         => 'required|string|max:255',
            'perusahaan'   => 'nullable|string|max:255',
            'nomor'        => 'required|string|max:100',
            'cert_desc'    => 'nullable|string|max:200',
            'event_name'   => 'required|string|max:255',
            'date_start'   => 'required|date',
            'date_end'     => 'nullable|date|after_or_equal:date_start',
            'event_place'  => 'nullable|string|max:255',
            'signer_name'  => 'nullable|string|max:255',
            'signer_title' => 'nullable|string|max:255',
        ]);

        $cert = Certificate::create([
            ...$validated,
            'institution_id' => auth()->user()->institution_id,
            'issued_by'      => auth()->id(),
        ]);
        // QR code di-generate otomatis via model event `created`

        // Pre-cache PDF agar download lebih cepat
        $this->cachePdf($cert);

        return response()->json([
            'success'            => true,
            'verification_token' => $cert->verification_token,
            'verification_url'   => $cert->verificationUrl(),
            'pdf_url'            => $cert->pdfUrl(),
        ]);
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'participants'              => 'required|array|min:1',
            'participants.*.nama'       => 'required|string|max:255',
            'participants.*.nomor'      => 'nullable|string|max:100',
            'participants.*.perusahaan' => 'nullable|string|max:255',
            'event_name'                => 'required|string|max:255',
            'date_start'                => 'required|date',
            'date_end'                  => 'nullable|date|after_or_equal:date_start',
            'event_place'               => 'nullable|string|max:255',
            'signer_name'               => 'nullable|string|max:255',
            'signer_title'              => 'nullable|string|max:255',
            'cert_desc'                 => 'nullable|string|max:200',
        ]);

        $institutionId = auth()->user()->institution_id;
        $certificates  = [];

        foreach ($request->participants as $p) {
            $cert = Certificate::create([
                'institution_id' => $institutionId,
                'issued_by'      => auth()->id(),
                'nama'           => $p['nama'],
                'perusahaan'     => $p['perusahaan'] ?? null,
                'nomor'          => $p['nomor'],
                'cert_desc'      => $request->cert_desc,
                'event_name'     => $request->event_name,
                'date_start'     => $request->date_start,
                'date_end'       => $request->date_end ?: null,
                'event_place'    => $request->event_place,
                'signer_name'    => $request->signer_name,
                'signer_title'   => $request->signer_title,
            ]);
            // QR code di-generate otomatis via model event `created`

            // Pre-cache PDF agar download lebih cepat
            $this->cachePdf($cert);

            $certificates[] = [
                'nama'               => $cert->nama,
                'nomor'              => $cert->nomor,
                'verification_url'   => $cert->verificationUrl(),
                'verification_token' => $cert->verification_token,
                'pdf_url'            => $cert->pdfUrl(),
            ];
        }

        return response()->json([
            'success'      => true,
            'count'        => count($certificates),
            'certificates' => $certificates,
        ]);
    }

    public function pregenerate(string $token)
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with('institution')
            ->firstOrFail();

        if (auth()->user()->institution_id !== $certificate->institution_id) {
            abort(403);
        }

        $cachePath = 'pdf_cache/' . $token . '.pdf';
        if (Storage::disk('local')->exists($cachePath)) {
            return response()->json(['success' => true, 'cached' => true]);
        }

        try {
            $institution = $certificate->institution;
            $pdf = $this->buildPdf($certificate, $institution);
            Storage::disk('local')->put($cachePath, $pdf->output());
            return response()->json(['success' => true, 'cached' => false]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Pregenerate PDF gagal: ' . $e->getMessage(), [
                'token' => $token,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Gagal membuat PDF. Silakan coba lagi atau hubungi administrator.',
            ], 500);
        }
    }

    public function pdf(string $token)
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with('institution')
            ->firstOrFail();

        if (auth()->check() && auth()->user()->institution_id !== $certificate->institution_id) {
            abort(403);
        }

        $filename = 'sertifikat_'
            . Str::slug($certificate->nama)
            . '_'
            . str_replace(['/', '\\'], '-', $certificate->nomor)
            . '.pdf';

        $cachePath = 'pdf_cache/' . $token . '.pdf';
        if (Storage::disk('local')->exists($cachePath)) {
            $pdfContent = Storage::disk('local')->get($cachePath);
            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $institution = $certificate->institution;
        $pdf = $this->buildPdf($certificate, $institution);
        return $pdf->download($filename);
    }

    /**
     * Cache PDF ke storage/app/pdf_cache/ agar download lebih cepat.
     * Gagal diam-diam (hanya log) — tidak mengganggu response ke user.
     */
    private function cachePdf(Certificate $certificate): void
    {
        try {
            $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            $cachePath   = $cacheDir . DIRECTORY_SEPARATOR . $certificate->verification_token . '.pdf';
            $institution = $certificate->institution;

            // View warmup — hindari race condition rename .tmp di Windows
            view('certificate.pdf', [
                'certificate' => $certificate,
                'institution' => $institution,
                'logoPath'    => $this->resolveAssetPath($institution->logo_path),
                'ttdPath'     => $this->resolveAssetPath($institution->ttd_path),
                'capPath'     => $this->resolveAssetPath($institution->cap_path),
                'bgPath'      => $this->resolveAssetPath($institution->background_path),
            ])->render();

            $pdf = $this->buildPdf($certificate, $institution);
            file_put_contents($cachePath, $pdf->output());
            unset($pdf);
            gc_collect_cycles();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                'cachePdf gagal [' . $certificate->verification_token . ']: ' . $e->getMessage()
            );
        }
    }

    private function buildPdf(Certificate $certificate, $institution)
    {
        // Pastikan QR code sudah ada sebelum render PDF
        if (empty($certificate->qr_code)) {
            $certificate->generateAndSaveQrCode();
            $certificate->refresh();
        }

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

    public function history(Request $request)
    {
        $institutionId = auth()->user()->institution_id;
        $sort          = $request->sort === 'asc' ? 'asc' : 'desc';
        $sortBy        = $request->sort_by === 'event' ? 'date_start' : 'issued_at';

        $certificates = Certificate::forInstitution($institutionId)
            ->with(['issuedBy', 'batch'])
            ->when($request->search, fn($q) => $q->search($request->search))
            ->orderBy($sortBy, $sort)
            ->paginate(20)
            ->withQueryString();

        return view('certificate.history', compact('certificates', 'sort'));
    }

    public function historyBatch(Request $request)
    {
        $institutionId = auth()->user()->institution_id;
        $sort          = $request->sort === 'asc' ? 'asc' : 'desc';
        $sortBy        = $request->sort_by === 'event' ? 'date_start' : 'started_at';

        $batches = CertificateBatch::where('institution_id', $institutionId)
            ->with('issuedBy')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('title', 'like', "%{$request->search}%")
                       ->orWhere('event_name', 'like', "%{$request->search}%");
                });
            })
            ->orderBy($sortBy, $sort)
            ->paginate(20)
            ->withQueryString();

        return view('certificate.history-batch', compact('batches', 'sort'));
    }

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

    public function participant(string $token)
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with('institution')
            ->first();

        if (! $certificate) {
            return response()->view('certificate.participant-invalid', [], 200);
        }

        return view('certificate.participant', compact('certificate'));
    }

    public function destroy(Certificate $certificate)
    {
        if ($certificate->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $cachePath = 'pdf_cache/' . $certificate->verification_token . '.pdf';
        if (Storage::disk('local')->exists($cachePath)) {
            Storage::disk('local')->delete($cachePath);
        }

        $certificate->delete();
        return back()->with('success', 'Sertifikat berhasil dihapus.');
    }

    private function resolveAssetPath(?string $relativePath): string
    {
        if (!$relativePath) return '';
        $full = storage_path('app/public/' . $relativePath);
        return str_replace('\\', '/', $full);
    }
}
