<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id', 'batch_id', 'issued_by', 'nama', 'perusahaan', 'nomor',
        'event_name', 'event_date', 'event_place',
        'signer_name', 'signer_title', 'cert_desc',
        'verification_token', 'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Certificate $cert) {
            if (empty($cert->verification_token)) {
                $cert->verification_token = (string) Str::uuid();
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
