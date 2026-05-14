<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BackgroundLibrary extends Model
{
    use HasFactory;

    protected $table = 'background_library';

    protected $fillable = [
        'institution_id',
        'name',
        'path',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('is_system', false)
                     ->where('institution_id', $institutionId);
    }
}
