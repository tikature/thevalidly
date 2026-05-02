<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'           => $this->faker->name(),
            'email'          => $this->faker->unique()->safeEmail(),
            'password'       => Hash::make('password'),  // password default untuk test
            'role'           => 'admin',
            'institution_id' => null,
            'is_active'      => true,
        ];
    }

    // State: super admin
    public function superAdmin(): static
    {
        return $this->state([
            'role'           => 'super_admin',
            'institution_id' => null,
        ]);
    }

    // State: admin lembaga (butuh institution)
    public function adminOf(Institution $institution): static
    {
        return $this->state([
            'role'           => 'admin',
            'institution_id' => $institution->id,
        ]);
    }

    // State: akun nonaktif
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
