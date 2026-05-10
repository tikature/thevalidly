<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Certificate;
use Illuminate\Support\Facades\DB;

class CleanBatchDuplicates extends Command
{
    protected $signature   = 'batch:clean-duplicates {batch_id? : ID batch yang mau dibersihkan, kosongkan untuk semua}';
    protected $description = 'Hapus sertifikat duplikat dalam batch (simpan yang pertama)';

    public function handle(): int
    {
        $batchId = $this->argument('batch_id');

        $query = DB::table('certificates')
            ->whereNotNull('batch_id')
            ->select('batch_id', 'nama', 'perusahaan', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('batch_id', 'nama', 'perusahaan')
            ->having('cnt', '>', 1);

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info('✓ Tidak ada duplikat ditemukan.');
            return 0;
        }

        $this->info("Ditemukan {$duplicates->count()} grup duplikat.");
        $totalDeleted = 0;

        foreach ($duplicates as $dup) {
            // Hapus semua kecuali yang paling awal (MIN id)
            $deleted = DB::table('certificates')
                ->where('batch_id', $dup->batch_id)
                ->where('nama', $dup->nama)
                ->where('perusahaan', $dup->perusahaan)
                ->where('id', '!=', $dup->keep_id)
                ->delete();

            $totalDeleted += $deleted;
            $this->line("  Batch {$dup->batch_id} | {$dup->nama}: hapus {$deleted} duplikat");
        }

        // Update hitungan processed di setiap batch yang terpengaruh
        $affectedBatches = $duplicates->pluck('batch_id')->unique();
        foreach ($affectedBatches as $bid) {
            $actual = DB::table('certificates')->where('batch_id', $bid)->count();
            DB::table('certificate_batches')->where('id', $bid)->update([
                'processed' => $actual,
                'total'     => $actual,
                'failed'    => 0,
            ]);
        }

        $this->info("✓ Total dihapus: {$totalDeleted} sertifikat duplikat.");
        $this->info("✓ Counter batch sudah disesuaikan.");
        return 0;
    }
}
