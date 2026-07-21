@extends('layouts.app')

@section('title', 'Detail Batch - ' . $batch->displayTitle())

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <a href="{{ route('certificate.history.batch') }}"
           class="btn btn-sm mb-2"
           style="background:#f0f4ff;color:var(--navy-mid);border:1.5px solid #dde4f0;border-radius:8px;font-weight:600;font-size:.8rem;padding:6px 14px">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Riwayat Batch
        </a>
        <h4 class="mb-0 fw-bold mt-1" style="color:var(--navy)">
            <i class="bi bi-people me-2" style="color:var(--gold)"></i>{{ $batch->displayTitle() }}
        </h4>
        <small class="text-muted">{{ $batch->event_name }} · {{ $batch->total }} peserta</small>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ $batch->batchUrl() }}" target="_blank" class="btn btn-sm"
           style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:8px;font-weight:600;padding:8px 16px">
            <i class="bi bi-box-arrow-up-right me-1"></i>Halaman Publik
        </a>
        <a href="{{ route('certificate.batch.zip', $batch->batch_token) }}" class="btn btn-sm"
           style="background:var(--navy);color:var(--gold-light);border:none;border-radius:8px;font-weight:600;padding:8px 16px">
            <i class="bi bi-file-earmark-zip me-1"></i>Download ZIP
        </a>
        <button type="button"
                onclick="confirmDeleteBatch('{{ route('certificate.batch.destroy', $batch->id) }}', '{{ addslashes($batch->displayTitle()) }}', {{ $batch->total }})"
                class="btn btn-sm"
                style="background:#fef2f2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:8px;font-weight:600;padding:8px 16px">
            <i class="bi bi-trash me-1"></i>Hapus Batch
        </button>
    </div>
</div>

{{-- Info batch --}}
<div class="batch-stat-grid mb-4" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
    <div class="card border-0 shadow-sm mb-0" style="border-radius:12px">
        <div class="card-body text-center py-3">
            <div style="font-size:1.8rem;font-weight:800;color:var(--navy)">{{ $batch->total }}</div>
            <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Total Peserta</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-0" style="border-radius:12px">
        <div class="card-body text-center py-3">
            <div style="font-size:1.8rem;font-weight:800;color:#16a34a">{{ $batch->processed - $batch->failed }}</div>
            <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Berhasil</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-0" style="border-radius:12px">
        <div class="card-body text-center py-3">
            <div style="font-size:1.8rem;font-weight:800;color:#ef4444">{{ $batch->failed }}</div>
            <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Gagal</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-0" style="border-radius:12px">
        <div class="card-body text-center py-4">
            @php
                $statusColor = match($batch->status) { 'done' => '#16a34a', 'processing' => '#d97706', 'failed' => '#ef4444', default => '#9ca3af' };
                $statusLabel = match($batch->status) { 'done' => 'Selesai', 'processing' => 'Diproses', 'failed' => 'Gagal', default => 'Pending' };
            @endphp
            <div style="font-size:1.2rem;font-weight:800;color:{{ $statusColor }}">{{ $statusLabel }}</div>
            <div style="font-size:.75rem;color:#9ca3af;font-weight:600">Status</div>
        </div>
    </div>
</div>

<style>
@media (min-width: 768px) {
    .batch-stat-grid {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}
</style>

{{-- Info detail batch --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-body px-4 py-3">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div style="font-size:.68rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9ca3af;margin-bottom:4px">
                    <i class="bi bi-calendar-event me-1"></i>Tanggal Acara
                </div>
                <div style="font-size:.875rem;font-weight:600;color:var(--navy)">
                    @if($batch->date_start)
                        {{ $batch->date_start->format('d M Y') }}
                        @if($batch->date_end && $batch->date_end->ne($batch->date_start))
                            – {{ $batch->date_end->format('d M Y') }}
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div style="font-size:.68rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9ca3af;margin-bottom:4px">
                    <i class="bi bi-geo-alt me-1"></i>Tempat Acara
                </div>
                <div style="font-size:.875rem;font-weight:600;color:var(--navy)">
                    {{ $batch->event_place ?: '—' }}
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div style="font-size:.68rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9ca3af;margin-bottom:4px">
                    <i class="bi bi-pen me-1"></i>Penandatangan
                </div>
                <div style="font-size:.875rem;font-weight:600;color:var(--navy)">
                    {{ $batch->signer_name ?: '—' }}
                </div>
                @if($batch->signer_title)
                    <div style="font-size:.75rem;color:#9ca3af">{{ $batch->signer_title }}</div>
                @endif
            </div>
            <div class="col-sm-6 col-lg-3">
                <div style="font-size:.68rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9ca3af;margin-bottom:4px">
                    <i class="bi bi-clock-history me-1"></i>Diterbitkan
                </div>
                <div style="font-size:.875rem;font-weight:600;color:var(--navy)">
                    {{ $batch->started_at?->format('d M Y') ?? '—' }}
                </div>
                @if($batch->issuedBy)
                    <div style="font-size:.75rem;color:#a5b4fc">
                        <i class="bi bi-person-check me-1"></i>{{ $batch->issuedBy->name }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
{{-- TAMBAHKAN SNIPPET INI tepat sebelum baris: --}}
{{-- <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden"> --}}

{{-- Search Bar --}}
<form method="GET" action="{{ route('certificate.batch.detail', $batch->id) }}" class="mb-3">
    <div class="input-group" style="max-width:400px">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Cari nama, nomor, instansi..."
            class="form-control"
            style="border-radius:8px 0 0 8px;border:1.5px solid #dde4f0;font-size:.85rem;color:var(--navy)"
        >
        <button
            type="submit"
            class="btn"
            style="background:var(--navy);color:var(--gold-light);border-radius:0 8px 8px 0;padding:0 16px;font-size:.85rem;font-weight:600"
        >
            <i class="bi bi-search"></i>
        </button>
        @if(request('search'))
            <a
                href="{{ route('certificate.batch.detail', $batch->id) }}"
                class="btn btn-sm ms-2 d-flex align-items-center"
                style="background:#f0f4ff;color:var(--navy-mid);border:1.5px solid #dde4f0;border-radius:8px;font-size:.8rem;font-weight:600;white-space:nowrap"
            >
                <i class="bi bi-x me-1"></i>Reset
            </a>
        @endif
    </div>
    @if(request('search'))
        <div class="mt-2" style="font-size:.78rem;color:#9ca3af">
            Hasil pencarian untuk "<strong>{{ request('search') }}</strong>"
            — {{ $certificates->total() }} sertifikat ditemukan
        </div>
    @endif
</form>
{{-- Tabel sertifikat --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.875rem">
            <thead style="background:var(--navy);color:var(--gold-light)">
                <tr>
                    <th class="px-4 py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase">Nama Peserta</th>
                    <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase">Nomor</th>
                    <th class="py-3" style="min-width:200px font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase">Aksi</th>
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
                    <td class="py-3 pe-4">
                        <div class="d-flex gap-2 align-items-center">
                            {{-- Download PDF --}}
                            <a href="{{ $cert->participantUrl() }}" target="_blank"
                               class="btn btn-sm d-inline-flex align-items-center gap-1"
                               style="background:var(--navy);color:var(--gold-light);border:none;border-radius:6px;font-size:.75rem;font-weight:600">
                                <i class="bi bi-file-earmark-pdf"></i>
                                <span>Download PDF</span>
                            </a>
                            {{-- Salin link --}}
                            <button type="button"
                                    class="btn btn-sm"
                                    style="background:#f3f4f6;color:#6b7280;border:none;border-radius:6px;font-size:.75rem"
                                    title="Salin link peserta"
                                    onclick="copyLink('{{ $cert->participantUrl() }}', this)">
                                <i class="bi bi-clipboard"></i>
                            </button>
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
    <nav>
        <ul class="pagination pagination-sm mb-0" style="gap:4px">
            @if($certificates->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link" style="border-radius:8px;border:1.5px solid #eef2f9;color:#ccc">
                        <i class="bi bi-chevron-left"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $certificates->previousPageUrl() }}"
                       style="border-radius:8px;border:1.5px solid #eef2f9;color:var(--navy-mid);font-weight:600">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            @endif

            @foreach($certificates->getUrlRange(max(1, $certificates->currentPage()-2), min($certificates->lastPage(), $certificates->currentPage()+2)) as $page => $url)
                @if($page == $certificates->currentPage())
                    <li class="page-item active">
                        <span class="page-link"
                              style="border-radius:8px;background:var(--navy);border-color:var(--navy);font-weight:700">
                            {{ $page }}
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $url }}"
                           style="border-radius:8px;border:1.5px solid #eef2f9;color:var(--navy-mid);font-weight:600">
                            {{ $page }}
                        </a>
                    </li>
                @endif
            @endforeach

            @if($certificates->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $certificates->nextPageUrl() }}"
                       style="border-radius:8px;border:1.5px solid #eef2f9;color:var(--navy-mid);font-weight:600">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link" style="border-radius:8px;border:1.5px solid #eef2f9;color:#ccc">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
</div>
<div class="text-muted text-center mt-3" style="font-size:.78rem">
    Menampilkan {{ $certificates->firstItem() }}–{{ $certificates->lastItem() }}
    dari {{ $certificates->total() }} sertifikat
</div>
@endif

{{-- Modal Konfirmasi Hapus Batch --}}
<div id="modalDeleteBatch" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,20,50,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;width:min(420px,92vw);padding:28px 24px;box-shadow:0 20px 60px rgba(0,0,0,.25);text-align:center">
        <div style="font-size:2rem;margin-bottom:10px">🗑️</div>
        <h6 style="font-weight:800;color:#0F1E3C;margin-bottom:6px">Hapus Batch?</h6>
        <p id="deleteBatchName" style="color:#6b7280;font-size:.85rem;margin-bottom:4px"></p>
        <p id="deleteBatchCount" style="color:#b91c1c;font-size:.82rem;font-weight:600;margin-bottom:20px"></p>
        <div class="d-flex gap-2 justify-content-center">
            <button onclick="closeDeleteBatchModal()"
                    style="background:#f0f4ff;border:1.5px solid #dde4f0;border-radius:8px;padding:9px 20px;font-size:.82rem;font-weight:700;color:#0F1E3C;cursor:pointer">
                Batal
            </button>
            <button id="btnDoDeleteBatch"
                    style="background:#ef4444;border:none;border-radius:8px;padding:9px 20px;font-size:.82rem;font-weight:700;color:#fff;cursor:pointer">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<form id="formDeleteBatch" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function copyLink(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'bi bi-check';
        btn.style.color = '#16a34a';
        setTimeout(() => { icon.className = 'bi bi-clipboard'; btn.style.color = '#6b7280'; }, 2000);
    });
}

let _deleteBatchUrl = null;

function confirmDeleteBatch(action, title, total) {
    _deleteBatchUrl = action;
    document.getElementById('deleteBatchName').textContent = '"' + title + '" akan dihapus permanen.';
    document.getElementById('deleteBatchCount').textContent = total + ' sertifikat di dalamnya ikut terhapus.';
    document.getElementById('modalDeleteBatch').style.display = 'flex';
}

function closeDeleteBatchModal() {
    _deleteBatchUrl = null;
    document.getElementById('modalDeleteBatch').style.display = 'none';
}

document.getElementById('btnDoDeleteBatch').addEventListener('click', function () {
    if (!_deleteBatchUrl) return;
    const form = document.getElementById('formDeleteBatch');
    form.action = _deleteBatchUrl;
    closeDeleteBatchModal();
    form.submit();
});

document.getElementById('modalDeleteBatch').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteBatchModal();
});
</script>

@endsection
