<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CertificateBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'issued_by',
        'event_name',
        'title',
        'event_date',
        'event_place',
        'signer_name',
        'signer_title',
        'cert_desc',
        'batch_token',
        'total',
        'processed',
        'failed',
        'status',
        'failed_entries',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'failed_entries' => 'array',
        'started_at'     => 'datetime',
        'finished_at'    => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (CertificateBatch $batch) {
            if (empty($batch->batch_token)) {
                $batch->batch_token = (string) Str::uuid();
            }
        });
    }

    // ── Relasi ────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'batch_id');
    }

    // ── Helper ────────────────────────────────────────────────────

    /**
     * Generate judul batch otomatis.
     * Format: "Nama Acara - Batch N"
     */
    public static function generateTitle(string $eventName, int $institutionId): string
    {
        $truncated = mb_substr($eventName, 0, 200);

        $count = static::where('institution_id', $institutionId)
            ->where('event_name', $eventName)
            ->count();

        return $truncated . ' - Batch ' . ($count + 1);
    }

    public function displayTitle(): string
    {
        return $this->title ?: $this->event_name;
    }

    public function progressPercent(): int
    {
        if ($this->total === 0) return 0;
        return (int) round(($this->processed / $this->total) * 100);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function batchUrl(): string
    {
        return route('certificate.batch.show', $this->batch_token);
    }
}
