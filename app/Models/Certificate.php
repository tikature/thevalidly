<?php

namespace App\Models;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id', 'batch_id', 'issued_by', 'nama', 'perusahaan', 'nomor',
        'event_name', 'date_start', 'date_end', 'event_place',
        'signer_name', 'signer_title', 'cert_desc',
        'verification_token', 'qr_code', 'issued_at',
    ];

    protected $casts = [
        'issued_at'  => 'datetime',
        'date_start' => 'date',
        'date_end'   => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Certificate $cert) {
            if (empty($cert->verification_token)) {
                $cert->verification_token = (string) Str::uuid();
            }
        });

        static::created(function (Certificate $cert) {
            if (empty($cert->qr_code)) {
                $cert->generateAndSaveQrCode();
            }
        });
    }

    // ── Relasi ──────────────────────────────────────────────────
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function batch()
    {
        return $this->belongsTo(CertificateBatch::class, 'batch_id');
    }

    // ── URL Helpers ──────────────────────────────────────────────
    public function verificationUrl(): string
    {
        return url('/verify/' . $this->verification_token);
    }

    public function participantUrl(): string
    {
        return url('/cert/' . $this->verification_token);
    }

    public function pdfUrl(): string
    {
        return route('certificate.pdf', $this->verification_token);
    }

    // ── QR Code ─────────────────────────────────────────────────

    /**
     * Generate QR code PNG (data:image/png;base64,...) via endroid/qr-code v5.
     * PNG di-embed langsung di PDF via <img src="data:image/png;base64,...">
     * — didukung penuh oleh DomPDF.
     *
     * Install: composer require endroid/qr-code
     */
    public function generateAndSaveQrCode(): void
    {
        $url = $this->verificationUrl();

        $result  = (new PngWriter)->write(QrCode::create($url));
        $dataUri = 'data:image/png;base64,' . base64_encode($result->getString());

        static::withoutEvents(function () use ($dataUri) {
            $this->update(['qr_code' => $dataUri]);
        });
    }

    /**
     * Ambil QR code data URI PNG. Jika belum ada, generate on-demand.
     */
    public function getQrCodeDataUri(): string
    {
        if (empty($this->qr_code)) {
            $this->generateAndSaveQrCode();
            $this->refresh();
        }

        return $this->qr_code;
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('nama', 'like', "%{$keyword}%")
              ->orWhere('nomor', 'like', "%{$keyword}%")
              ->orWhere('event_name', 'like', "%{$keyword}%")
              ->orWhere('perusahaan', 'like', "%{$keyword}%");
        });
    }
}
