<?php

namespace Tests\Feature\Console;

use App\Console\Commands\BackfillQrCodes;
use App\Models\Certificate;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Test: BackfillQrCodes Artisan Command — Iterasi 4
 *
 * Jalankan: php artisan test --filter BackfillQrCodesTest
 */
class BackfillQrCodesTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
    }

    #[Test]
    public function command_generates_qr_for_certificates_without_qr_code(): void
    {
        $certs = Certificate::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
        ]);

        Certificate::withoutEvents(function () {
            Certificate::query()->update(['qr_code' => null]);
        });

        $this->assertSame(3, Certificate::whereNull('qr_code')->count());

        $this->artisan('app:backfill-qr-codes')
            ->assertExitCode(0);

        $this->assertSame(0, Certificate::whereNull('qr_code')->count());

        foreach ($certs as $cert) {
            $this->assertStringStartsWith('data:image/png;base64,', $cert->fresh()->qr_code);
        }
    }

    #[Test]
    public function command_skips_certificates_that_already_have_qr_code(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $existingQr = $cert->fresh()->qr_code;
        $this->assertNotNull($existingQr);

        $this->artisan('app:backfill-qr-codes')
            ->assertExitCode(0);

        $this->assertSame($existingQr, $cert->fresh()->qr_code);
    }

    #[Test]
    public function command_outputs_message_when_nothing_to_backfill(): void
    {
        Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->artisan('app:backfill-qr-codes')
            ->expectsOutputToContain('Semua sertifikat sudah punya QR code')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_respects_chunk_option(): void
    {
        Certificate::factory()->count(5)->create([
            'institution_id' => $this->institution->id,
        ]);

        Certificate::withoutEvents(function () {
            Certificate::query()->update(['qr_code' => null]);
        });

        $this->artisan('app:backfill-qr-codes', ['--chunk' => 2])
            ->assertExitCode(0);

        $this->assertSame(0, Certificate::whereNull('qr_code')->count());
    }

    #[Test]
    public function command_works_with_empty_certificates_table(): void
    {
        $this->assertSame(0, Certificate::count());

        $this->artisan('app:backfill-qr-codes')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_reports_failure_and_returns_exit_1_when_processing_fails(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        Certificate::withoutEvents(function () use ($cert) {
            $cert->update(['qr_code' => null]);
        });

        // Bind subclass yang override processOne agar throw Exception
        $this->app->bind(BackfillQrCodes::class, fn() => new class extends BackfillQrCodes {
            protected function processOne(Certificate $cert): array
            {
                return [false, 'Simulated QR generation failure'];
            }
        });

        $this->artisan('app:backfill-qr-codes')
            ->expectsOutputToContain('Gagal')
            ->assertExitCode(1);
    }
    #[Test]
    public function process_one_catches_exception_and_returns_failure(): void
    {
        $cert = Certificate::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        Certificate::withoutEvents(fn() => $cert->update(['qr_code' => null]));

        // Panggil processOne langsung via Reflection dengan cert yang
        // qr_code-nya null dan APP_URL invalid supaya url() throw
        config(['app.url' => '']); // bukan throw, tapi cukup

        // Cara paling reliable: mock generateAndSaveQrCode via partial mock pada instance
        $certMock = \Mockery::mock($cert)->makePartial();
        $certMock->shouldReceive('generateAndSaveQrCode')
            ->once()
            ->andThrow(new \Exception('Simulated failure'));

        $command = new \App\Console\Commands\BackfillQrCodes;

        $method = new \ReflectionMethod($command, 'processOne');
        $method->setAccessible(true);

        [$ok, $error] = $method->invoke($command, $certMock);

        $this->assertFalse($ok);
        $this->assertSame('Simulated failure', $error);
    }
}
