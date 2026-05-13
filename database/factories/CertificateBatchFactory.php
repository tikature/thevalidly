<?php

namespace Database\Factories;

use App\Models\CertificateBatch;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CertificateBatchFactory extends Factory
{
    protected $model = CertificateBatch::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'event_name'     => $this->faker->sentence(4),
            'date_start'     => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'date_end'       => null,
            'batch_token'    => (string) Str::uuid(),
            'total'          => 0,
            'processed'      => 0,
            'failed'         => 0,
            'status'         => 'processing',
            'failed_entries' => null,
            'started_at'     => now(),
            'finished_at'    => null,
        ];
    }

    public function done(): static
    {
        return $this->state([
            'status'      => 'done',
            'finished_at' => now(),
        ]);
    }
}
