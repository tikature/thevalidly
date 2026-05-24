@extends('layouts.app')

@section('title', 'Super Admin — Kelola Lembaga')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">
@endpush

@section('content')

{{-- ══ TOAST NOTIFIKASI ══ --}}
<div id="toastWrap" style="
    position:fixed; top:24px; right:24px; z-index:9999;
    display:flex; flex-direction:column; gap:10px; pointer-events:none;
"></div>

{{-- ══ HEADER ══ --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--navy)">
            <i class="bi bi-shield-check me-2" style="color:var(--gold)"></i>Panel Super Admin
        </h4>
        <small class="text-muted">Kelola seluruh akun lembaga di Validly</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn-add-lembaga" data-bs-toggle="modal" data-bs-target="#modalAddSuperAdmin"
                style="background:#1a3260">
            <i class="bi bi-shield-plus me-2"></i>Tambah Super Admin
        </button>
        <button class="btn-add-lembaga" data-bs-toggle="modal" data-bs-target="#modalAddLembaga">
            <i class="bi bi-plus-circle me-2"></i>Tambah Lembaga Baru
        </button>
    </div>
</div>

{{-- ══ STATISTIK ══ --}}
<div class="stat-grid-sa" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-landmark-dome" style="color: rgb(255, 255, 255);"></i></div>
        <div>
            <div class="stat-val">{{ $institutions->count() }}</div>
            <div class="stat-lbl">Total Lembaga</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-square-check" style="color: rgb(255, 255, 255);"></i></div>
        <div>
            <div class="stat-val">{{ $institutions->where('is_active', true)->count() }}</div>
            <div class="stat-lbl">Lembaga Aktif</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-users" style="color: rgb(255, 255, 255);"></i></div>
        <div>
            <div class="stat-val">{{ $institutions->sum('users_count') }}</div>
            <div class="stat-lbl">Total Akun Admin</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1)"><i class="fa-solid fa-user-shield" style="color: rgb(255, 255, 255);"></i></div>
        <div>
            <div class="stat-val">{{ $superAdmins->count() }}</div>
            <div class="stat-lbl">Super Admin</div>
        </div>
    </div>
</div>

{{-- ══ DAFTAR SUPER ADMIN ══ --}}
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <span style="font-size:.8rem;font-weight:700;color:var(--navy-mid);letter-spacing:1px;text-transform:uppercase">
            <i class="bi bi-shield-lock me-1"></i>Akun Super Admin
        </span>
    </div>

    <div class="inst-card" style="padding:0;overflow:hidden">
        @if($superAdmins->isEmpty())
            <p class="text-muted p-4 mb-0" style="font-size:.85rem">
                <i class="bi bi-info-circle me-1"></i>Belum ada super admin terdaftar.
            </p>
        @else
            <ul class="admin-list mb-0" style="padding:12px">
                @foreach($superAdmins as $sa)
                <li class="admin-item">
                    <div class="admin-item-info">
                        <div class="admin-avatar" style="background:var(--navy-mid)">
                            {{ strtoupper(substr($sa->name, 0, 2)) }}
                        </div>
                        <div>
                            <div style="font-weight:600;color:var(--navy)">
                                {{ $sa->name }}
                                @if($sa->id === auth()->id())
                                    <span style="font-size:.68rem;background:#eef2ff;color:var(--navy-mid);
                                                 border-radius:5px;padding:2px 7px;font-weight:700;margin-left:6px">
                                        Anda
                                    </span>
                                @endif
                            </div>
                            <div style="color:#9ca3af;font-size:.75rem">{{ $sa->email }}</div>
                        </div>
                    </div>
                    @if($sa->id !== auth()->id())
                        <button type="button" class="btn-sm-danger"
                                onclick="confirmDelete(
                                    '{{ route('superadmin.superadmins.destroy', $sa) }}',
                                    'Hapus Super Admin',
                                    'Apakah Anda yakin ingin menghapus akun super admin <strong>{{ addslashes($sa->name) }}</strong>? Tindakan ini tidak dapat dibatalkan.',
                                    'Hapus Super Admin'
                                )">
                            <i class="bi bi-trash"></i>
                        </button>
                    @endif
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

{{-- ══ DAFTAR LEMBAGA ══ --}}
<div class="d-flex align-items-center mb-3">
    <span style="font-size:.8rem;font-weight:700;color:var(--navy-mid);letter-spacing:1px;text-transform:uppercase">
        <i class="bi bi-building me-1"></i>Daftar Lembaga
    </span>
</div>

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
                {{-- Edit lembaga --}}
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
                {{-- Hapus lembaga --}}
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
                <span style="font-size:.78rem;font-weight:700;color:var(--navy-mid);letter-spacing:1px;text-transform:uppercase">
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
                <p class="text-muted" style="font-size:.8rem">
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
                                <div style="font-weight:600;color:var(--navy)">{{ $admin->name }}</div>
                                <div style="color:#9ca3af;font-size:.75rem">{{ $admin->email }}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            {{-- Edit admin — kirim plain_password ke modal --}}
                            <button class="btn btn-sm"
                                    style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:5px;font-size:.72rem"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditAdmin"
                                    data-id="{{ $admin->id }}"
                                    data-name="{{ $admin->name }}"
                                    data-email="{{ $admin->email }}"
                                    data-plain-password="{{ $admin->plain_password ?? '' }}"
                                    title="Edit admin">
                                <i class="bi bi-pencil"></i>
                            </button>
                            {{-- Hapus admin --}}
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

{{-- ══ MODALS ══ --}}
@include('superadmin.modals.add-lembaga')
@include('superadmin.modals.add-admin')
@include('superadmin.modals.add-superadmin')
@include('superadmin.modals.edit-lembaga')
@include('superadmin.modals.edit-admin')
@include('superadmin.modals.confirm-delete')

@endsection

@push('scripts')
<script src="{{ asset('js/superadmin.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
        showToast(@json(session('success')), 'success');
    @endif
    @if(session('error'))
        showToast(@json(session('error')), 'error');
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
    @if($errors->hasBag('addSuperAdmin'))
        showToast(@json($errors->getBag('addSuperAdmin')->first()), 'error');
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