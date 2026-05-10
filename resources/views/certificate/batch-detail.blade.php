@extends('layouts.app')

@section('title', 'Detail Batch — ' . $batch->displayTitle())

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <a href="{{ route('certificate.history.batch') }}" class="text-muted text-decoration-none" style="font-size:.82rem">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Riwayat Batch
        </a>
        <h4 class="mb-0 fw-bold mt-1" style="color:var(--navy)">
            <i class="bi bi-people me-2" style="color:var(--gold)"></i>{{ $batch->displayTitle() }}
        </h4>
        <small class="text-muted">{{ $batch->event_name }} · {{ $batch->total }} peserta</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $batch->batchUrl() }}" target="_blank" class="btn btn-sm"
           style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:8px;font-weight:600;padding:8px 16px">
            <i class="bi bi-box-arrow-up-right me-1"></i>Halaman Publik
        </a>
        <a href="{{ route('certificate.batch.zip', $batch->batch_token) }}" class="btn btn-sm"
           style="background:var(--navy);color:var(--gold-light);border:none;border-radius:8px;font-weight:600;padding:8px 16px">
            <i class="bi bi-file-earmark-zip me-1"></i>Download ZIP
        </a>
    </div>
</div>

{{-- Info batch --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body text-center py-3">
                <div style="font-size:1.8rem;font-weight:800;color:var(--navy)">{{ $batch->total }}</div>
                <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Total Peserta</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body text-center py-3">
                <div style="font-size:1.8rem;font-weight:800;color:#16a34a">{{ $batch->processed - $batch->failed }}</div>
                <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Berhasil</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body text-center py-3">
                <div style="font-size:1.8rem;font-weight:800;color:#ef4444">{{ $batch->failed }}</div>
                <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Gagal</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body text-center py-3">
                @php $statusColor = match($batch->status) { 'done' => '#16a34a', 'processing' => '#d97706', 'failed' => '#ef4444', default => '#9ca3af' }; $statusLabel = match($batch->status) { 'done' => 'Selesai', 'processing' => 'Diproses', 'failed' => 'Gagal', default => 'Pending' }; @endphp
                <div style="font-size:1.2rem;font-weight:800;color:{{ $statusColor }}">{{ $statusLabel }}</div>
                <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Status</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabel sertifikat --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.875rem">
            <thead style="background:var(--navy);color:var(--gold-light)">
                <tr>
                    <th class="px-4 py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase">Nama Peserta</th>
                    <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase">Nomor</th>
                    <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase">Terbit</th>
                    <th class="py-3" style="min-width:160px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $cert)
                <tr>
                    <td class="px-4 py-3">
                        <div class="fw-bold" style="color:var(--navy)">{{ $cert->nama }}</div>
                        @if($cert->perusahaan)
                            <div style="font-size:.75rem;color:#9ca3af"><i class="bi bi-building me-1"></i>{{ $cert->perusahaan }}</div>
                        @endif
                    </td>
                    <td class="py-3">
                        <span style="font-family:monospace;font-size:.8rem;background:#f0f4ff;color:var(--navy-mid);padding:3px 8px;border-radius:5px">{{ $cert->nomor }}</span>
                    </td>
                    <td class="py-3" style="color:#6b7280;font-size:.85rem">{{ $cert->issued_at->format('d M Y') }}</td>
                    <td class="py-3 pe-4">
                        <div class="d-flex gap-2">
                            <a href="{{ $cert->participantUrl() }}" target="_blank"
                               class="btn btn-sm" style="background:var(--navy);color:var(--gold-light);border:none;border-radius:6px;font-size:.75rem;font-weight:600" title="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada sertifikat dalam batch ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
@if($certificates->hasPages())
<div class="mt-4 d-flex justify-content-center">
    {{ $certificates->links() }}
</div>
@endif

@endsection
