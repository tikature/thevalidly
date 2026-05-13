<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCertificateJob;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateBatchController extends Controller
{
    /**
     * Buat batch baru dan dispatch jobs ke queue.
     * Setiap job akan insert DB + generate PDF ke cache.
     */
    public function store(Request $request)
    {
        $request->validate([
            'participants'              => 'required|array|min:1|max:500',
            'participants.*.nama'       => 'required|string|max:255',
            'participants.*.perusahaan' => 'nullable|string|max:255',
            'participants.*.nomor'      => 'nullable|string|max:100',
            'event_name'                => 'required|string|max:255',
            'event_date'                => 'required|string|max:100',
            'event_place'               => 'nullable|string|max:255',
            'signer_name'               => 'nullable|string|max:255',
            'signer_title'              => 'nullable|string|max:255',
            'cert_desc'                 => 'nullable|string|max:200',
        ]);

        $institutionId = auth()->user()->institution_id;
        $participants  = $request->participants;

        $batch = CertificateBatch::create([
            'institution_id' => $institutionId,
            'issued_by'      => auth()->id(),
            'event_name'     => trim($request->event_name),
            'title'          => CertificateBatch::generateTitle(trim($request->event_name), $institutionId),
            'event_date'     => $request->event_date,
            'event_place'    => $request->event_place,
            'signer_name'    => $request->signer_name,
            'signer_title'   => $request->signer_title,
            'cert_desc'      => $request->cert_desc,
            'total'          => count($participants),
            'processed'      => 0,
            'failed'         => 0,
            'status'         => 'processing',
            'started_at'     => now(),
        ]);

        // Resolve asset paths sekali — dikirim ke semua jobs
        $institution = $batch->institution;
        $assetPaths  = [
            'logo' => $this->resolveAssetPath($institution->logo_path ?? ''),
            'ttd'  => $this->resolveAssetPath($institution->ttd_path ?? ''),
            'cap'  => $this->resolveAssetPath($institution->cap_path ?? ''),
            'bg'   => $this->resolveAssetPath($institution->background_path ?? ''),
        ];

        foreach ($participants as $index => $participant) {
            ProcessCertificateJob::dispatch($batch, $participant, $index, $assetPaths);
        }

        return response()->json([
            'batch_id'    => $batch->id,
            'batch_token' => $batch->batch_token,
            'total'       => $batch->total,
        ]);
    }

    /**
     * Progress polling — dipanggil tiap ~1.5 detik dari JS.
     * cached_pdf dihitung real-time dari disk — akurat sejak job pertama selesai.
     */
    public function progress(string $token)
    {
        $batch = CertificateBatch::where('batch_token', $token)
            ->where('institution_id', auth()->user()->institution_id)
            ->firstOrFail();

        // Hitung PDF yang sudah ada di cache — real-time, tidak perlu tunggu done
        $cachedCount = 0;
        $tokens      = $batch->certificates()->pluck('verification_token');
        $cacheDir    = storage_path('app/pdf_cache');
        foreach ($tokens as $vt) {
            $path = $cacheDir . DIRECTORY_SEPARATOR . $vt . '.pdf';
            if (file_exists($path) && filesize($path) > 0) {
                $cachedCount++;
            }
        }

        // Estimasi waktu sisa
        $eta = null;
        if ($batch->processed > 0 && $batch->status === 'processing') {
            $elapsed   = now()->diffInSeconds($batch->started_at);
            $rate      = $batch->processed / max($elapsed, 1);
            $remaining = $batch->total - $batch->processed;
            $eta       = $rate > 0 ? (int) ceil($remaining / $rate) : null;
        }

        // ZIP baru benar-benar siap kalau semua PDF sudah ada di cache
        $zipReady = $batch->isDone() && ($cachedCount >= ($batch->total - $batch->failed));

        // Generate zip filename sama persis seperti di downloadZip()
        $cleanEventName = $batch->event_name ?: ($batch->title ?: 'Sertifikat');
        $eventSlug      = \Illuminate\Support\Str::slug($cleanEventName, '_');
        $eventSlug      = mb_substr($eventSlug, 0, 40) ?: 'batch';
        $tanggal        = now()->format('Ymd');
        $batchNo        = 1;
        if (preg_match('/Batch\s+(\d+)/i', $batch->title ?? '', $m)) {
            $batchNo = $m[1];
        }
        $zipFilename = "Sertifikat_{$eventSlug}_{$tanggal}_Batch{$batchNo}.zip";

        return response()->json([
            'status'         => $batch->status,
            'total'          => $batch->total,
            'processed'      => $batch->processed,
            'failed'         => $batch->failed,
            'percent'        => $batch->progressPercent(),
            'failed_entries' => $batch->failed_entries ?? [],
            'cached_pdf'     => $cachedCount,
            'zip_ready'      => $zipReady,
            'eta_seconds'    => $eta,
            'batch_url'      => $batch->isDone() ? $batch->batchUrl() : null,
            'zip_filename'   => $zipFilename,
        ]);
    }

    /**
     * Halaman publik batch — list semua sertifikat + link download PDF.
     */
    public function show(string $batchToken)
    {
        $batch = CertificateBatch::with(['institution', 'certificates'])
            ->where('batch_token', $batchToken)
            ->firstOrFail();

        return view('certificate.batch', compact('batch'));
    }

    /**
     * Halaman detail batch di dashboard admin.
     */
    public function detail(int $batchId)
    {
        $batch = CertificateBatch::with(['institution'])
            ->where('id', $batchId)
            ->where('institution_id', auth()->user()->institution_id)
            ->firstOrFail();

        $certificates = $batch->certificates()
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('certificate.batch-detail', compact('batch', 'certificates'));
    }

    /**
     * Hapus seluruh batch beserta semua sertifikat.
     */
    public function destroyBatch(int $batchId)
    {
        $batch = CertificateBatch::where('id', $batchId)
            ->where('institution_id', auth()->user()->institution_id)
            ->firstOrFail();

        $title = $batch->displayTitle();

        foreach ($batch->certificates as $cert) {
            $cachePath = 'pdf_cache/' . $cert->verification_token . '.pdf';
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($cachePath)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($cachePath);
            }
        }

        $batch->certificates()->delete();
        $batch->delete();

        return back()->with('success', "Batch \"{$title}\" beserta semua sertifikatnya berhasil dihapus.");
    }

    /**
     * Ambil list sertifikat dalam batch — untuk preview setelah selesai.
     */
    public function certificates(string $token)
    {
        $batch = CertificateBatch::where('batch_token', $token)
            ->where('institution_id', auth()->user()->institution_id)
            ->firstOrFail();

        $certificates = $batch->certificates()
            ->select('id', 'nama', 'nomor', 'perusahaan', 'verification_token')
            ->get()
            ->map(fn($c) => [
                'nama'               => $c->nama,
                'nomor'              => $c->nomor,
                'perusahaan'         => $c->perusahaan ?? '',
                'verification_url'   => $c->verificationUrl(),
                'pdf_url'            => $c->pdfUrl(),
                'verification_token' => $c->verification_token,
            ]);

        return response()->json([
            'total'        => $certificates->count(),
            'certificates' => $certificates,
        ]);
    }

    /**
     * Download semua PDF dalam batch sebagai ZIP.
     * PDF diambil langsung dari cache yang sudah dibuat oleh job — tidak generate ulang.
     */

    protected $zipArchive;

    public function __construct(\ZipArchive $zip = null)
    {
        $this->zipArchive = $zip ?? new \ZipArchive();
    }

    public function downloadZip(string $token)
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $batch = CertificateBatch::where('batch_token', $token)
            ->where('institution_id', auth()->user()->institution_id)
            ->firstOrFail();

        $certificates = $batch->certificates()->get();

        if ($certificates->isEmpty()) {
            return response()->json(['error' => 'Tidak ada sertifikat dalam batch ini.'], 422);
        }

        // Nama file ZIP
        $cleanEventName = $batch->event_name ?: ($batch->title ?: 'Sertifikat');
        $eventSlug      = Str::slug($cleanEventName, '_');
        $eventSlug      = mb_substr($eventSlug, 0, 40) ?: 'batch';
        $tanggal     = now()->format('Ymd');
        $batchNo     = 1;
        if (preg_match('/Batch\s+(\d+)/i', $batch->title ?? '', $m)) {
            $batchNo = $m[1];
        }
        $zipFilename = "Sertifikat_{$eventSlug}_{$tanggal}_Batch{$batchNo}.zip";

        $tempDir  = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'batch_' . substr($token, 0, 8) . '_' . time() . '.zip';

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip    = $this->zipArchive;
        $opened = $zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($opened !== true) {
            return response()->json(['error' => 'Gagal membuat file ZIP di server.'], 500);
        }

        $cacheDir = storage_path('app' . DIRECTORY_SEPARATOR . 'pdf_cache');
        $added    = 0;

        foreach ($certificates as $cert) {
            $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $cert->verification_token . '.pdf';

            // PDF tidak ada di cache? Skip — jangan generate ulang di sini.
            // Kasus ini hanya terjadi kalau user download sebelum semua job selesai.
            if (!file_exists($cachePath) || filesize($cachePath) === 0) {
                \Illuminate\Support\Facades\Log::warning(
                    "ZIP skip — PDF tidak ada di cache: {$cert->nama} [{$cert->verification_token}]"
                );
                continue;
            }

            $safeNama  = Str::slug($cert->nama ?: 'peserta');
            $safeNomor = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $cert->nomor ?: 'cert');
            $zip->addFile($cachePath, $safeNama . '_' . $safeNomor . '.pdf');
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($tempPath);
            return response()->json([
                'error' => 'PDF belum siap. Tunggu hingga semua sertifikat selesai diproses, lalu coba lagi.'
            ], 422);
        }

        return response()->download($tempPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function resolveAssetPath(?string $relativePath): string
    {
        if (!$relativePath) return '';
        $full = storage_path('app/public/' . $relativePath);
        return str_replace('\\', '/', $full);
    }
}
