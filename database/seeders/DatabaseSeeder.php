<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Jalankan dengan: php artisan db:seed
     * Atau sekaligus: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // Buat akun Super Admin default
        // GANTI email & password sebelum deploy ke production!
        User::firstOrCreate(
            ['email' => 'superadmin@validly.id'],
            [
                'name'     => 'Super Admin Validly',
                'password' => Hash::make('superadmin123'), // ganti password ini!
                'role'     => 'super_admin',
                'is_active' => true,
            ]
        );

        $this->command->info('Super Admin berhasil dibuat:');
        $this->command->info('  Email   : superadmin@validly.id');
        $this->command->info('  Password: superadmin123');
        $this->command->warn('  ⚠ Segera ganti password setelah login pertama!');
    }
}