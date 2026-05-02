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
        'name', 'email', 'password', 'role', 'institution_id', 'is_active'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
        'password'  => 'hashed',
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