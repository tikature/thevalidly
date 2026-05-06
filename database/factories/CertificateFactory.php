<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'institution_id'     => Institution::factory(),
            'issued_by'          => null,
            'nama'               => $this->faker->name(),
            'perusahaan'         => $this->faker->company(),
            'nomor'              => 'CERT/' . str_pad($this->faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT) . '/' . date('Y'),
            'event_name'         => 'Pelatihan ' . $this->faker->word(),
            'event_date'         => 'Held on ' . $this->faker->date('d-m-y') . ' at Purwokerto',
            'event_place'        => $this->faker->city(),
            'signer_name'        => 'Dr. ' . $this->faker->name(),
            'signer_title'       => 'Ketua Panitia',
            'cert_desc'          => 'Has Successfully Completed a Training Course on:',
            'verification_token' => (string) Str::uuid(),
            'issued_at'          => now(),
        ];
    }

    public function forInstitution(Institution $institution): static
    {
        return $this->state(['institution_id' => $institution->id]);
    }

    public function issuedBy(User $user): static
    {
        return $this->state(['issued_by' => $user->id]);
    }
}
