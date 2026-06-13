@extends('layouts.app')

@section('title', 'Riwayat Sertifikat')

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

{{-- Tab navigasi --}}
<div class="d-flex gap-2 mb-4">
    <a href="{{ route('certificate.history') }}"
       class="btn {{ !request()->routeIs('certificate.history.batch') ? 'active' : '' }}"
       style="border-radius:9px;font-weight:600;font-size:.85rem;padding:9px 20px;
              background:{{ !request()->routeIs('certificate.history.batch') ? 'var(--navy)' : '#f0f4ff' }};
              color:{{ !request()->routeIs('certificate.history.batch') ? 'var(--gold-light)' : 'var(--navy-mid)' }};
              border:none">
        <i class="bi bi-person me-1"></i>Individual
    </a>
    <a href="{{ route('certificate.history.batch') }}"
       class="btn {{ request()->routeIs('certificate.history.batch') ? 'active' : '' }}"
       style="border-radius:9px;font-weight:600;font-size:.85rem;padding:9px 20px;
              background:{{ request()->routeIs('certificate.history.batch') ? 'var(--navy)' : '#f0f4ff' }};
              color:{{ request()->routeIs('certificate.history.batch') ? 'var(--gold-light)' : 'var(--navy-mid)' }};
              border:none">
        <i class="bi bi-people me-1"></i>Batch / Massal
    </a>
</div>

{{-- Search --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('certificate.history') }}">
            <div class="d-flex gap-2 flex-wrap">
                <div class="input-group flex-grow-1">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#dde4f0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0"
                           style="border-color:#dde4f0"
                           placeholder="Cari nama, nomor, acara, atau perusahaan..."
                           value="{{ request('search') }}">
                    @if(request('search'))
                        <a href="{{ route('certificate.history', ['sort' => request('sort')]) }}"
                           class="btn btn-outline-secondary" style="border-color:#dde4f0">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                    <button type="submit" class="btn" style="background:var(--navy);color:var(--gold-light);border-radius:0 8px 8px 0;font-weight:600">
                        Cari
                    </button>
                </div>
                {{-- Pertahankan sort query saat search --}}
                <input type="hidden" name="sort" value="{{ $sort }}">
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Tabel riwayat --}}
@if($certificates->isEmpty())
    <div class="text-center py-5">
        <div style="font-size:3rem;margin-bottom:12px">📋</div>
        <p class="fw-bold text-dark mb-1">
            {{ request('search') ? 'Tidak ada hasil untuk "' . request('search') . '"' : 'Belum ada riwayat sertifikat' }}
        </p>
        <p class="text-muted" style="font-size:.875rem">
            {{ request('search') ? 'Coba kata kunci lain.' : 'Generate sertifikat pertama Anda sekarang.' }}
        </p>
        @if(!request('search'))
            <a href="{{ route('certificate.index') }}" class="btn mt-2"
               style="background:var(--navy);color:var(--gold-light);border:none;border-radius:8px;font-weight:600">
                <i class="bi bi-plus-circle me-1"></i>Generate Sekarang
            </a>
        @endif
    </div>
@else
    <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem">
                <thead style="background:var(--navy);color:var(--gold-light)">
                    <tr>
                        <th class="px-4 py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;min-width:160px">Nama Peserta</th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase">Nomor</th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;min-width:140px">
                            <a href="{{ route('certificate.history', array_merge(request()->query(), ['sort' => $sort === 'desc' ? 'asc' : 'desc', 'sort_by' => 'event'])) }}"
                               style="color:var(--gold-light);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                                Tanggal Acara
                                <i class="bi bi-arrow-{{ (request('sort_by') === 'event' && $sort === 'desc') ? 'down' : ((request('sort_by') === 'event' && $sort === 'asc') ? 'up' : 'down-up') }}" style="font-size:.72rem;opacity:.6"></i>
                            </a>
                        </th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;width:250px;max-width:250px">Nama Acara</th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;min-width:140px">
                            <a href="{{ route('certificate.history', array_merge(request()->query(), ['sort' => $sort === 'desc' ? 'asc' : 'desc', 'sort_by' => 'issued'])) }}"
                               style="color:var(--gold-light);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                                Tanggal Terbit
                                <i class="bi bi-arrow-{{ (!request('sort_by') || request('sort_by') === 'issued') && $sort === 'desc' ? 'down' : 'up' }}" style="font-size:.72rem;opacity:.6"></i>
                            </a>
                        </th>
                        <th class="py-3" style="font-weight:600;font-size:.72rem;letter-spacing:1px;text-transform:uppercase min-width:130px" >AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($certificates as $cert)
                    <tr>
                        {{-- Nama Peserta --}}
                        <td class="px-4 py-3">
                            <div class="fw-600" style="color:var(--navy);font-weight:600">{{ $cert->nama }}</div>
                            @if($cert->perusahaan)
                                <div style="font-size:.75rem;color:#9ca3af">
                                    <i class="bi bi-building me-1"></i>{{ $cert->perusahaan }}
                                </div>
                            @endif
                        </td>
                        {{-- Nomor --}}
                        <td class="py-3">
                            <span style="font-family:monospace;font-size:.8rem;background:#f0f4ff;color:var(--navy-mid);padding:3px 8px;border-radius:5px">
                                {{ $cert->nomor }}
                            </span>
                        </td>
                        {{-- Tanggal Acara --}}
                        <td class="py-3" style="color:#6b7280;white-space:nowrap;font-size:.85rem">
                            {{ $cert->date_start ? $cert->date_start->format('d M Y') : '-' }}{{ $cert->date_end && $cert->date_end->ne($cert->date_start) ? ' – '.$cert->date_end->format('d M Y') : '' }}
                        </td>
                        {{-- Nama Acara --}}
                        <td class="py-3" style="max-width:200px">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:250px" title="{{ $cert->event_name }}">
                                {{ $cert->event_name }}
                            </div>
                        </td>
                        {{-- Tanggal Terbit --}}
                        <td class="py-3" style="color:#6b7280;white-space:nowrap;font-size:.85rem">
                            {{ $cert->issued_at->format('d M Y') }}
                            @if($cert->issuedBy)
                                <div style="font-size:.72rem;color:#a5b4fc;margin-top:2px">
                                    <i class="bi bi-person-check me-1"></i>{{ $cert->issuedBy->name }}
                                </div>
                            @endif
                        </td>
                        <td class="py-3 pe-4">
                            <div class="d-flex gap-2 align-items-center">
                                {{-- Link Peserta (ada download) --}}
                                <a href="{{ $cert->participantUrl() }}" target="_blank"
                                   class="btn btn-sm"
                                   style="background:var(--navy);color:var(--gold-light);border:none;border-radius:6px;font-size:.75rem;font-weight:600"
                                   title="Halaman peserta (ada download)">
                                    <i class="bi bi-download"></i>
                                </a>
                                {{-- Salin link peserta --}}
                                <button type="button"
                                        class="btn btn-sm"
                                        style="background:#f3f4f6;color:#6b7280;border:none;border-radius:6px;font-size:.75rem"
                                        title="Salin link peserta"
                                        onclick="copyLink('{{ $cert->participantUrl() }}', this)">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                 {{-- Halaman Verifikasi Peserta --}}
                                <a href="{{ $cert->verificationUrl() }}" target="_blank"
                                   class="btn btn-sm"
                                   style="background:var(--navy-mid);color:var(--gold-light);border:none;border-radius:6px;font-size:.75rem;font-weight:600"
                                   title="Halaman Verifikasi Peserta">
                                    <i class="bi bi-check"></i>
                                </a>
                                {{-- Hapus / Info Batch --}}
                                @if($cert->batch_id)
                                    {{-- Bagian dari batch — tidak bisa dihapus individual --}}
                                    <a href="{{ route('certificate.batch.detail', $cert->batch_id) }}"
                                       class="btn btn-sm d-inline-flex align-items-center gap-1"
                                       style="background:#f0f4ff;color:var(--navy-mid);border:1.5px solid #dde4f0;border-radius:6px;font-size:.72rem;font-weight:600;white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis"
                                       title="Sertifikat ini bagian dari batch — kelola di halaman detail batch">
                                        <i class="bi bi-people-fill" style="flex-shrink:0"></i>
                                    </a>
                                @else
                                    {{-- Individual — bisa dihapus --}}
                                    <button type="button"
                                            class="btn btn-sm"
                                            style="background:#fef2f2;color:#b91c1c;border:none;border-radius:6px;font-size:.75rem"
                                            title="Hapus riwayat"
                                            onclick="confirmDeleteCert('{{ route('certificate.destroy', $cert) }}', '{{ addslashes($cert->nama) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($certificates->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            <nav>
                <ul class="pagination pagination-sm mb-0" style="gap:4px">
                    {{-- Previous --}}
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

                    {{-- Page numbers --}}
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

                    {{-- Next --}}
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
    @endif

    <div class="text-muted text-center mt-3" style="font-size:.78rem">
        Menampilkan {{ $certificates->firstItem() }}–{{ $certificates->lastItem() }}
        dari {{ $certificates->total() }} sertifikat
    </div>
@endif

{{-- ── Modal Konfirmasi Hapus ── --}}
<div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
            <div class="modal-body p-4 text-center">
                <div style="width:56px;height:56px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                    <i class="bi bi-trash3" style="font-size:1.5rem;color:#ef4444"></i>
                </div>
                <h5 class="fw-bold mb-2" style="color:var(--navy)">Hapus Sertifikat?</h5>
                <p class="text-muted mb-1" style="font-size:.875rem">Sertifikat atas nama</p>
                <p class="fw-bold mb-3" style="color:var(--navy-mid);font-size:.95rem" id="hapusNama">—</p>
                <p class="text-muted" style="font-size:.8rem">
                    Data sertifikat akan dihapus permanen dan link verifikasi tidak akan bisa diakses lagi.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex gap-2">
                <button type="button" class="btn flex-fill"
                        style="background:#f3f4f6;color:#374151;border:none;border-radius:9px;font-weight:600"
                        data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn flex-fill" id="btnHapusConfirm"
                        style="background:#ef4444;color:#fff;border:none;border-radius:9px;font-weight:600"
                        onclick="submitDelete()">
                    <span id="hapusSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk kirim DELETE request --}}
<form id="formHapus" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function copyLink(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'bi bi-check';
        btn.style.color = '#16a34a';
        setTimeout(() => {
            icon.className = 'bi bi-clipboard';
            btn.style.color = '#6b7280';
        }, 2000);
    });
}

function confirmDeleteCert(actionUrl, nama) {
    document.getElementById('hapusNama').textContent = nama;
    document.getElementById('formHapus').action = actionUrl;
    document.getElementById('hapusSpinner').classList.add('d-none');
    document.getElementById('btnHapusConfirm').disabled = false;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

function submitDelete() {
    const spinner = document.getElementById('hapusSpinner');
    const btn     = document.getElementById('btnHapusConfirm');
    spinner.classList.remove('d-none');
    btn.disabled = true;
    document.getElementById('formHapus').submit();
}
</script>

@endsection
