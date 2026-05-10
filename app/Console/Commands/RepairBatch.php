<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CertificateBatch;
use App\Models\Certificate;
use Illuminate\Support\Facades\DB;

class RepairBatch extends Command
{
    protected $signature   = 'batch:repair {batch_id : ID batch yang stuck}';
    protected $description = 'Tandai batch selesai berdasarkan data yang sudah ada di DB';

    public function handle(): int
    {
        $batchId = $this->argument('batch_id');
        $batch   = CertificateBatch::find($batchId);

        if (!$batch) {
            $this->error("Batch ID {$batchId} tidak ditemukan.");
            return 1;
        }

        $actual = Certificate::where('batch_id', $batchId)->count();

        $this->info("Batch: {$batch->displayTitle()}");
        $this->info("Total di batch: {$batch->total}");
        $this->info("Tersimpan di DB: {$actual}");
        $this->info("Status: {$batch->status}");

        // Update counter sesuai data aktual
        DB::table('certificate_batches')->where('id', $batchId)->update([
            'processed'   => $actual,
            'total'       => $actual, // sesuaikan total dengan yang benar-benar ada
            'failed'      => 0,
            'status'      => 'done',
            'finished_at' => now(),
        ]);

        $this->info("✓ Batch diperbaiki: {$actual} sertifikat, status → done");
        return 0;
    }
}
