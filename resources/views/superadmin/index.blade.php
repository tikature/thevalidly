@extends('layouts.app')

@section('title', 'Super Admin — Kelola Lembaga')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">
@endpush

@section('content')

{{-- ══ TOAST NOTIFIKASI ══ --}}
{{-- Muncul untuk success dan error validasi (email duplikat, dll) --}}
<div id="toastWrap" style="
    position:fixed; top:24px; right:24px; z-index:9999;
    display:flex; flex-direction:column; gap:10px; pointer-events:none;
"></div>


{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--navy)">
            <i class="bi bi-shield-check me-2" style="color:var(--gold)"></i>Panel Super Admin
        </h4>
        <small class="text-muted">Kelola seluruh akun lembaga di Validly</small>
    </div>
    <button class="btn-add-lembaga" data-bs-toggle="modal" data-bs-target="#modalAddLembaga">
        <i class="bi bi-plus-circle me-2"></i>Tambah Lembaga Baru
    </button>
</div>

{{-- STATISTIK --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon navy">🏛</div>
            <div>
                <div class="stat-val">{{ $institutions->count() }}</div>
                <div class="stat-lbl">Total Lembaga</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div>
                <div class="stat-val">{{ $institutions->where('is_active', true)->count() }}</div>
                <div class="stat-lbl">Lembaga Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon gold">👤</div>
            <div>
                <div class="stat-val">{{ $institutions->sum('users_count') }}</div>
                <div class="stat-lbl">Total Akun Admin</div>
            </div>
        </div>
    </div>
</div>

{{-- DAFTAR LEMBAGA --}}
@if($institutions->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">🏛</div>
        <p class="mb-1 fw-600 text-dark">Belum ada lembaga terdaftar</p>
        <p>Klik tombol "Tambah Lembaga Baru" untuk mulai menambahkan.</p>
    </div>
@else
    @foreach($institutions as $inst)
    <div class="inst-card">
        {{-- Header kartu --}}
        <div class="inst-header">
            <div class="inst-name">
                <div class="status-dot {{ $inst->is_active ? 'active' : 'inactive' }}"></div>
                {{ $inst->name }}
                <span class="{{ $inst->is_active ? 'badge-active' : 'badge-inactive' }}">
                    {{ $inst->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="d-flex gap-2">
                {{-- Toggle aktif/nonaktif --}}
                <form method="POST" action="{{ route('superadmin.institutions.toggle', $inst) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="{{ $inst->is_active ? 'btn-sm-warn' : 'btn-sm-success' }}">
                        <i class="bi bi-{{ $inst->is_active ? 'pause-circle' : 'play-circle' }} me-1"></i>
                        {{ $inst->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                {{-- Hapus lembaga --}}
                {{-- Tombol edit lembaga --}}
                <button class="btn btn-sm me-1"
                        style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:6px;font-size:.75rem;font-weight:600"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditLembaga"
                        data-id="{{ $inst->id }}"
                        data-name="{{ $inst->name }}"
                        data-email="{{ $inst->email }}"
                        data-phone="{{ $inst->phone ?? '' }}"
                        data-address="{{ $inst->address ?? '' }}"
                        title="Edit lembaga">
                    <i class="bi bi-pencil"></i>
                </button>
                {{-- Tombol hapus lembaga — trigger modal konfirmasi --}}
                <button type="button" class="btn-sm-danger"
                        onclick="confirmDelete(
                            '{{ route('superadmin.institutions.destroy', $inst) }}',
                            'Hapus Lembaga',
                            'Apakah Anda yakin ingin menghapus lembaga <strong>{{ addslashes($inst->name) }}</strong> beserta seluruh akun admin-nya? Tindakan ini tidak dapat dibatalkan.',
                            'Hapus Lembaga'
                        )">
                    <i class="bi bi-trash me-1"></i>Hapus
                </button>
            </div>
        </div>

        {{-- Body kartu --}}
        <div class="inst-body">
            <div class="inst-meta">
                <span><i class="bi bi-envelope"></i>{{ $inst->email }}</span>
                @if($inst->phone)
                    <span><i class="bi bi-telephone"></i>{{ $inst->phone }}</span>
                @endif
                @if($inst->address)
                    <span><i class="bi bi-geo-alt"></i>{{ mb_strimwidth($inst->address, 0, 40, '...') }}</span>
                @endif
                <span><i class="bi bi-people"></i>{{ $inst->users_count }} Admin</span>
                <span class="text-muted"><i class="bi bi-calendar"></i>{{ $inst->created_at->format('d M Y') }}</span>
            </div>

            {{-- Daftar Admin --}}
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span style="font-size:0.78rem; font-weight:700; color:var(--navy-mid); letter-spacing:1px; text-transform:uppercase;">
                    <i class="bi bi-person-badge me-1"></i>Akun Admin
                </span>
                <button class="btn-sm-navy" data-bs-toggle="modal"
                        data-bs-target="#modalAddAdmin"
                        data-inst-id="{{ $inst->id }}"
                        data-inst-name="{{ $inst->name }}">
                    <i class="bi bi-person-plus me-1"></i>Tambah Admin
                </button>
            </div>

            @if($inst->users->isEmpty())
                <p class="text-muted" style="font-size:0.8rem">
                    <i class="bi bi-info-circle me-1"></i>Belum ada akun admin.
                </p>
            @else
                <ul class="admin-list">
                    @foreach($inst->users as $admin)
                    <li class="admin-item">
                        <div class="admin-item-info">
                            <div class="admin-avatar">
                                {{ strtoupper(substr($admin->name, 0, 2)) }}
                            </div>
                            <div>
                                <div style="font-weight:600; color:var(--navy)">{{ $admin->name }}</div>
                                <div style="color:#9ca3af; font-size:0.75rem">{{ $admin->email }}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm"
                                    style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:5px;font-size:.72rem"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditAdmin"
                                    data-id="{{ $admin->id }}"
                                    data-name="{{ $admin->name }}"
                                    data-email="{{ $admin->email }}"
                                    title="Edit admin">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn-sm-danger"
                                    onclick="confirmDelete(
                                        '{{ route('superadmin.admins.destroy', $admin) }}',
                                        'Hapus Admin',
                                        'Apakah Anda yakin ingin menghapus akun admin <strong>{{ addslashes($admin->name) }}</strong>?',
                                        'Hapus Admin'
                                    )">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    @endforeach
@endif

{{-- ══════════ MODALS ══════════ --}}
@include('superadmin.modals.add-lembaga')
@include('superadmin.modals.add-admin')
@include('superadmin.modals.edit-lembaga')
@include('superadmin.modals.edit-admin')
@include('superadmin.modals.confirm-delete')

@endsection

@push('scripts')
<script src="{{ asset('js/superadmin.js') }}"></script>
<script>
// ── Tampilkan toast dari server saat halaman load
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
        showToast(@json(session('success')), 'success');
    @endif

    @if($errors->hasBag('addInstitution'))
        showToast(@json($errors->getBag('addInstitution')->first()), 'error');
    @endif
    @if($errors->hasBag('editInstitution'))
        showToast(@json($errors->getBag('editInstitution')->first()), 'error');
    @endif
    @if($errors->hasBag('addAdmin'))
        showToast(@json($errors->getBag('addAdmin')->first()), 'error');
    @endif
    @if($errors->hasBag('editAdmin'))
        showToast(@json($errors->getBag('editAdmin')->first()), 'error');
    @endif
});
</script>

<style>
@keyframes slideIn {
    from { opacity: 0; transform: translateX(20px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes slideOut {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(20px); }
}
</style>
@endpush
