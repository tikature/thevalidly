<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use Notifiable, HasFactory;

    protected $fillable = [
        'name', 'email', 'password', 'plain_password', 'role', 'institution_id', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
        // plain_password sengaja tidak dimasukkan $hidden
        // agar bisa dibaca Super Admin di panel
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password'  => 'hashed',
        // plain_password sengaja tidak di-cast 'hashed'
    ];

    // Helper: cek apakah user adalah super admin
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // Helper: cek apakah user adalah admin lembaga
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Relasi ke lembaga
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}