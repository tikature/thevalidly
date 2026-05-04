<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'logo_path', 'ttd_path', 'cap_path', 'background_path', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Satu lembaga punya banyak user (admin)
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}