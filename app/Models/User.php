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
        'name', 'email', 'password', 'plain_password', 'role', 'institution_id', 'is_active', 'is_primary',
    ];

    protected $hidden = [
        'password', 'remember_token',
        // plain_password sengaja tidak dimasukkan $hidden
        // agar bisa dibaca Super Admin di panel
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_primary' => 'boolean',
        'password'   => 'hashed',
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

    // Helper: cek apakah user adalah super admin utama (akun pertama/primer)
    public function isPrimarySuperAdmin(): bool
    {
        return $this->role === 'super_admin' && $this->is_primary === true;
    }

    // Relasi ke lembaga
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}