<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

/**
 * CertificateVerificationController
 *
 * Iterasi 4 — Endpoint publik untuk validasi QR code sertifikat.
 *
 * Routes:
 *   GET /verify/{token}         → halaman verifikasi visual (web)
 *   GET /api/verify/{token}     → JSON response untuk QR scanner / integrasi
 */
class CertificateVerificationController extends Controller
{
    /**
     * Halaman verifikasi PUBLIK — tampilan web, NO download.
     * Sudah ada di CertificateController::verify(), controller ini hanya
     * menyediakan API endpoint tambahan.
     */

    /**
     * API endpoint — JSON untuk QR scanner / integrasi pihak ketiga.
     *
     * Response sukses (200):
     * {
     *   "valid": true,
     *   "certificate": { nama, perusahaan, nomor, event_name, event_date,
     *                    event_place, institution, issued_at, verification_url }
     * }
     *
     * Response gagal (404):
     * {
     *   "valid": false,
     *   "message": "Sertifikat tidak ditemukan."
     * }
     */
    public function apiVerify(string $token)
    {
        $certificate = Certificate::with('institution')
            ->where('verification_token', $token)
            ->first();

        if (!$certificate) {
            return response()->json([
                'valid'   => false,
                'message' => 'Sertifikat tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'valid'       => true,
            'certificate' => [
                'nama'             => $certificate->nama,
                'perusahaan'       => $certificate->perusahaan,
                'nomor'            => $certificate->nomor,
                'event_name'       => $certificate->event_name,
                'event_date'       => $certificate->event_date,
                'event_place'      => $certificate->event_place,
                'institution'      => $certificate->institution->name ?? '-',
                'issued_at'        => $certificate->issued_at->format('d M Y'),
                'verification_url' => $certificate->verificationUrl(),
            ],
        ]);
    }
}
