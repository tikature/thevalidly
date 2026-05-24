@extends('layouts.app')

@section('title', 'Riwayat Batch')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--navy)">
            <i class="bi bi-clock-history me-2" style="color:var(--gold)"></i>Riwayat Sertifikat
        </h4>
        <small class="text-muted">{{ auth()->user()->institution->name ?? '' }}</small>
    </div>
    <a href="{{ route('certificate.index') }}" class="btn btn-sm"
       style="background:var(--navy);color:var(--gold-light);border:none;border-radius:8px;font-weight:600;padding:8px 18px">
        <i class="bi bi-plus-circle me-1"></i>Generate Baru
    </a>
</div>

{{-- Flash message via JS notif (bukan alert HTML agar tidak double dengan notif lain) --}}
@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof showNotif === 'function') {
        showNotif('Berhasil', '{{ addslashes(session('success')) }}', 'success');
    }
});
</script>
@endif

{{-- Tab navigasi --}}
<div class="d-flex gap-2 mb-4">
    <a href="{{ route('certificate.history') }}"
       class="btn"
       style="border-radius:9px;font-weight:600;font-size:.85rem;padding:9px 20px;background:#f0f4ff;color:var(--navy-mid);border:none">
        <i class="bi bi-person me-1"></i>Individual
    </a>
    <a href="{{ route('certificate.history.batch') }}"
       class="btn"
       style="border-radius:9px;font-weight:600;font-size:.85rem;padding:9px 20px;background:var(--navy);color:var(--gold-light);border:none">
        <i class="bi bi-people me-1"></i>Batch / Massal
    </a>
</div>

{{-- Search --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('certificate.history.batch') }}">
            <div class="d-flex gap-2 flex-wrap">
                <div class="input-group flex-grow-1">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#dde4f0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0"
                           style="border-color:#dde4f0"
                           placeholder="Cari judul batch atau nama acara..."
                           value="{{ request('search') }}">
                    @if(request('search'))
                        <a href="{{ route('certificate.history.batch', ['sort' => request('sort')]) }}"
                           class="btn btn-outline-secondary" style="border-color:#dde4f0">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                    <button type="submit" class="btn"
                            style="background:var(--navy);color:var(--gold-light);border-radius:0 8px 8px 0;font-weight:600">
                        Cari
                    </button>
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Tabel batch --}}
@if($batches->isEmpty())
    <div class="text-center py-5">
        <div style="font-size:3rem;margin-bottom:12px">📦</div>
        <p class="fw-bold text-dark mb-1">
            {{ request('search') ? 'Tidak ada hasil untuk "' . request('search') . '"' : 'Belum ada batch sertifikat' }}
        </p>
        <p class="text-muted" style="font-size:.875rem">
            {{ request('search') ? 'Coba kata kunci lain.' : 'Generate sertifikat massal dari tab Upload Excel/CSV.' }}
        </p>
    </div>
@else
    <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem">
                <thead style="background:var(--navy);color:var(--gold-light)">
                    <tr>
                        <th class="px-4 py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;min-width:200px">
                            Judul Batch
                        </th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;min-width:140px">
                            <a href="{{ route('certificate.history.batch', array_merge(request()->query(), ['sort' => $sort === 'desc' ? 'asc' : 'desc', 'sort_by' => 'event'])) }}"
                               style="color:var(--gold-light);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                                Tanggal Acara
                                <i class="bi bi-arrow-{{ request('sort_by') === 'event' && $sort === 'asc' ? 'up' : 'down' }}" style="font-size:.72rem;opacity:.6"></i>
                            </a>
                        </th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;text-align:center">Total</th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;text-align:center;color:#4ade80">Berhasil</th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;text-align:center;color:#f87171">Gagal</th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;min-width:140px">
                            <a href="{{ route('certificate.history.batch', array_merge(request()->query(), ['sort' => $sort === 'desc' ? 'asc' : 'desc', 'sort_by' => 'issued'])) }}"
                               style="color:var(--gold-light);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                                Tanggal Terbit
                                <i class="bi bi-arrow-{{ (!request('sort_by') || request('sort_by') === 'issued') && $sort === 'asc' ? 'up' : 'down' }}" style="font-size:.72rem;opacity:.6"></i>
                            </a>
                        </th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;min-width:180px">Status</th>
                        <th class="py-3" style="min-width:160px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $batch)
                    <tr>
                        {{-- Judul Batch --}}
                        <td class="px-4 py-3">
                            <div class="fw-bold" style="color:var(--navy)">
                                {{ $batch->displayTitle() }}
                            </div>
                            <div style="font-size:.75rem;color:#9ca3af">
                                {{ $batch->event_name }}
                            </div>
                        </td>
                        {{-- Tanggal Acara --}}
                        <td class="py-3" style="color:#6b7280;white-space:nowrap;font-size:.85rem">
                            {{ $batch->date_start ? $batch->date_start->format('d M Y') : '-' }}{{ $batch->date_end && $batch->date_end->ne($batch->date_start) ? ' – '.$batch->date_end->format('d M Y') : '' }}
                        </td>
                        {{-- Total --}}
                        <td class="py-3" style="text-align:center;font-weight:700;color:var(--navy)">
                            {{ $batch->total }}
                        </td>
                        {{-- Berhasil --}}
                        <td class="py-3" style="text-align:center;font-weight:700;color:#16a34a">
                            {{ $batch->processed - $batch->failed }}
                        </td>
                        {{-- Gagal --}}
                        <td class="py-3" style="text-align:center;font-weight:700;color:{{ $batch->failed > 0 ? '#ef4444' : '#9ca3af' }}">
                            {{ $batch->failed }}
                        </td>
                        {{-- Tanggal Terbit --}}
                        <td class="py-3" style="color:#6b7280;white-space:nowrap;font-size:.85rem">
                            {{ $batch->started_at?->format('d M Y') ?? '-' }}
                            @if($batch->issuedBy)
                                <div style="font-size:.72rem;color:#a5b4fc;margin-top:2px">
                                    <i class="bi bi-person-check me-1"></i>{{ $batch->issuedBy->name }}
                                </div>
                            @endif
                        </td>
                        {{-- Status --}}
                        <td class="py-3">
                            @php
                                $statusColor = match($batch->status) {
                                    'done'       => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'label' => 'Selesai'],
                                    'processing' => ['bg' => '#fffbeb', 'color' => '#d97706', 'label' => 'Diproses...'],
                                    'failed'     => ['bg' => '#fef2f2', 'color' => '#ef4444', 'label' => 'Gagal'],
                                    default      => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Pending'],
                                };
                            @endphp
                            <span style="background:{{ $statusColor['bg'] }};color:{{ $statusColor['color'] }};font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px">
                                {{ $statusColor['label'] }}
                            </span>
                            @if($batch->failed > 0 && $batch->failed_entries)
                                <div style="font-size:.68rem;color:#ef4444;margin-top:3px">
                                    <i class="bi bi-exclamation-triangle me-1"></i>{{ $batch->failed }} gagal
                                </div>
                            @endif
                        </td>
                        {{-- Aksi --}}
                        <td class="py-3 pe-4">
                            <div class="d-flex gap-2 align-items-center">
                                {{-- Detail (individual dalam batch) --}}
                                <a href="{{ route('certificate.batch.detail', $batch->id) }}"
                                   class="btn btn-sm"
                                   style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:6px;font-size:.75rem;font-weight:600;white-space:nowrap"
                                   title="Lihat detail per peserta">
                                    <i class="bi bi-list-ul me-1"></i>Detail
                                </a>
                                {{-- Halaman batch publik --}}
                                <a href="{{ $batch->batchUrl() }}" target="_blank"
                                   class="btn btn-sm"
                                   style="background:var(--navy);color:var(--gold-light);border:none;border-radius:6px;font-size:.75rem"
                                   title="Halaman batch publik (peserta)">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                {{-- Hapus batch --}}
                                <button type="button"
                                        class="btn btn-sm"
                                        style="background:#fef2f2;color:#b91c1c;border:none;border-radius:6px;font-size:.75rem"
                                        title="Hapus batch"
                                        onclick="confirmDeleteBatch('{{ route('certificate.batch.destroy', $batch->id) }}', '{{ addslashes($batch->displayTitle()) }}', {{ $batch->total }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($batches->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            <nav>
                <ul class="pagination pagination-sm mb-0" style="gap:4px">
                    @if($batches->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link" style="border-radius:8px;border:1.5px solid #eef2f9;color:#ccc">
                                <i class="bi bi-chevron-left"></i>
                            </span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $batches->previousPageUrl() }}"
                               style="border-radius:8px;border:1.5px solid #eef2f9;color:var(--navy-mid);font-weight:600">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    @endif

                    @foreach($batches->getUrlRange(max(1, $batches->currentPage()-2), min($batches->lastPage(), $batches->currentPage()+2)) as $page => $url)
                        @if($page == $batches->currentPage())
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

                    @if($batches->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $batches->nextPageUrl() }}"
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
            Menampilkan {{ $batches->firstItem() }}–{{ $batches->lastItem() }}
            dari {{ $batches->total() }} batch
        </div>
    @endif
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

{{-- Form hapus batch tersembunyi --}}
<form id="formDeleteBatch" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
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
