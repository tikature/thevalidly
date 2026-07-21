<?php

namespace Tests\Unit\Iterasi3;

use Tests\TestCase;
use App\Console\Commands\BackfillQrCodes;
use App\Models\Certificate;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class BackfillQrCodesCommandUnitTest extends TestCase
{
    use RefreshDatabase;

    private function bindDummyIO($command, array $parameters = [])
    {
        $input = new ArrayInput($parameters, $command->getDefinition());
        $output = new OutputStyle($input, new NullOutput());

        $command->setInput($input);
        $command->setOutput($output);
        $command->setLaravel($this->app);
    }

    /**
     * Skenario 1: Early Return saat $total === 0 (Baris 24-27)
     */
    public function test_command_when_no_certificates_need_backfill()
    {
        Certificate::query()->delete();

        $command = new BackfillQrCodes();
        $this->bindDummyIO($command);

        $result = $command->handle();

        $this->assertEquals(BackfillQrCodes::SUCCESS, $result);
    }

    /**
     * Skenario 2: Menguji Alur Sukses di Dalam chunkById (Baris 28-41 Branch Success)
     */
    public function test_command_successful_chunk_execution()
    {
        Certificate::query()->delete();

        $cert = Certificate::factory()->create();

        // Paksa qr_code menjadi NULL langsung di tingkat DB agar melewati Model Event/Observer
        DB::table('certificates')->where('id', $cert->id)->update(['qr_code' => null]);

        $command = new BackfillQrCodes();
        $this->bindDummyIO($command, ['--chunk' => 10]);

        $result = $command->handle();

        $this->assertEquals(BackfillQrCodes::SUCCESS, $result);
    }

    /**
     * Skenario 3: Menguji Alur Gagal di Dalam handle() Asli (Baris 42-44 Branch Else/Failed & Baris 54 Return Failure)
     */
    public function test_command_failed_branch_execution()
    {
        Certificate::query()->delete();

        $cert = Certificate::factory()->create([
            'verification_token' => 'TOKEN-TEST-FAILED',
        ]);

        // Paksa qr_code menjadi NULL langsung di tingkat DB
        DB::table('certificates')->where('id', $cert->id)->update(['qr_code' => null]);

        // HANYA override processOne(), biarkan handle() menjalankan kode ASLI dari BackfillQrCodes
        $command = new class extends BackfillQrCodes {
            protected function processOne(Certificate $cert): array
            {
                return [false, 'Forced Failure to Cover Line 42-44'];
            }
        };

        $this->bindDummyIO($command, ['--chunk' => 10]);

        // Panggil handle() ASLI agar baris 42-44 ($failed++, newLine, warn) terhitung di Code Coverage
        $result = $command->handle();

        $this->assertEquals(BackfillQrCodes::FAILURE, $result);
    }

    /**
     * Skenario 4: Direct Test Method processOne (Try-Catch Coverage)
     */
    public function test_process_one_method_coverage()
    {
        $cert = Certificate::factory()->create();

        $command = new BackfillQrCodes();

        $reflection = new \ReflectionClass(BackfillQrCodes::class);
        $method = $reflection->getMethod('processOne');
        $method->setAccessible(true);

        // Test Success Try Block
        [$ok, $err] = $method->invoke($command, $cert);
        $this->assertTrue($ok);
        $this->assertEmpty($err);

        // Test Catch Exception Block
        $badCert = new class extends Certificate {
            public function generateAndSaveQrCode(): void
            {
                throw new \Exception('Force Exception for Catch Block');
            }
        };

        [$okCatch, $errCatch] = $method->invoke($command, $badCert);
        $this->assertFalse($okCatch);
        $this->assertEquals('Force Exception for Catch Block', $errCatch);
    }
}