<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . Str::random(4),
            'email'     => $this->faker->unique()->companyEmail(),
            'phone'     => $this->faker->phoneNumber(),
            'address'   => $this->faker->address(),
            'logo_path' => null,
            'is_active' => true,
        ];
    }

    // State: lembaga nonaktif
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
