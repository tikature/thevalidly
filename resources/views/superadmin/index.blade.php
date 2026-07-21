@extends('layouts.app')

@section('title', 'Super Admin - Kelola Lembaga')

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
        <div class="stat-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1)"><i class="fa-solid fa-user-shield" style="color: rgb(255, 255, 255);"></i>️</div>
        <div>
            <div class="stat-val">{{ $superAdmins->count() }}</div>
            <div class="stat-lbl">Super Admin</div>
        </div>
    </div>
</div>

{{-- ══ BACKGROUND LIBRARY SISTEM ══ --}}
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span style="font-size:.8rem;font-weight:700;color:var(--navy-mid);letter-spacing:1px;text-transform:uppercase">
            <i class="bi bi-image me-1"></i>Background Library Sistem
            <span style="font-weight:400;font-size:.75rem;color:#9ca3af;letter-spacing:0;text-transform:none;margin-left:6px">
                ({{ $systemBackgrounds->count() }} background)
            </span>
        </span>
        <button class="btn-sm-navy" data-bs-toggle="modal" data-bs-target="#modalAddBackground">
            <i class="bi bi-plus-circle me-1"></i>Tambah
        </button>
    </div>

    <div class="inst-card" style="padding:12px">
        @if($systemBackgrounds->isEmpty())
            <p class="text-muted mb-0" style="font-size:.82rem">
                <i class="bi bi-info-circle me-1"></i>Belum ada background sistem. Background yang ditambahkan di sini akan tersedia untuk semua lembaga.
            </p>
        @else
            <div style="display:flex;flex-wrap:wrap;gap:10px">
                @foreach($systemBackgrounds as $bg)
                <div style="position:relative;width:110px;flex-shrink:0">
                    <img src="{{ asset('storage/' . $bg->path) }}"
                         alt="{{ $bg->name }}"
                         style="width:110px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;display:block">
                    <div style="font-size:.68rem;color:var(--navy);font-weight:600;margin-top:3px;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px">
                        {{ $bg->name }}
                    </div>
                    <button onclick="confirmDeleteBg({{ $bg->id }}, '{{ addslashes($bg->name) }}')"
                            style="position:absolute;top:4px;right:4px;background:rgba(220,53,69,.8);border:none;
                                   color:#fff;border-radius:50%;width:22px;height:22px;font-size:.65rem;
                                   display:flex;align-items:center;justify-content:center;cursor:pointer;line-height:1"
                            title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                @endforeach
            </div>
        @endif
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
                        <div class="admin-avatar"
                             style="background:var(--navy-mid)">
                            {{ strtoupper(substr($sa->name, 0, 2)) }}
                        </div>
                        <div>
                            <div style="font-weight:600;color:var(--navy)">
                                {{ $sa->name }}

                                {{-- Badge: Akun Utama --}}
                                @if($sa->is_primary)
                                    <span style="font-size:.66rem;background:#fef3c7;color:#92400e;
                                                 border-radius:5px;padding:2px 7px;font-weight:700;
                                                 margin-left:6px;border:1px solid #fde68a">
                                        Utama
                                    </span>
                                @endif

                                {{-- Badge: Anda --}}
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

                    {{-- Tombol aksi: hanya tampil jika bukan diri sendiri dan bukan akun utama --}}
                    @if($sa->id !== auth()->id() && !$sa->isPrimarySuperAdmin())
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
                <form method="POST" action="{{ route('superadmin.institutions.toggle', $inst) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="{{ $inst->is_active ? 'btn-sm-warn' : 'btn-sm-success' }}">
                        <i class="bi bi-{{ $inst->is_active ? 'pause-circle' : 'play-circle' }} me-1"></i>
                        {{ $inst->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
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

{{-- Modal Tambah Background Sistem --}}
<div class="modal fade" id="modalAddBackground" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-image me-2"></i>Tambah Background Sistem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('superadmin.backgrounds.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:0.82rem">
                        <i class="bi bi-info-circle me-1"></i>
                        Background yang diunggah akan tersedia sebagai pilihan default untuk semua lembaga. Format PNG/JPG, maksimal 2 MB.
                    </p>
                    <div class="mb-3">
                        <label class="form-label-sm">Nama Background</label>
                        <input type="text" name="name" class="form-control"
                               placeholder="Kosongkan untuk pakai nama file" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">File Gambar *</label>
                        <input type="file" name="file" class="form-control"
                               accept="image/png,image/jpeg" required
                               onchange="previewBgImage(this)">
                    </div>
                    <div id="bgPreviewWrap" style="display:none">
                        <label class="form-label-sm">Preview</label>
                        <img id="bgPreviewImg" src="" alt="preview"
                             style="width:100%;max-height:150px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4"
                            style="border-radius:9px;font-size:0.85rem;font-weight:600"
                            data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn px-4"
                            style="background:var(--navy);color:#fff;border:none;border-radius:9px;font-size:0.85rem;font-weight:700">
                        <i class="bi bi-upload me-1"></i>Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus Background --}}
<div class="modal fade" id="modalDeleteBackground" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
            <div class="modal-body p-4 text-center">
                <div style="width:56px;height:56px;background:#fff0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:1.5rem">
                    🗑️
                </div>
                <h5 class="fw-bold mb-2" style="color:var(--navy);font-size:1.05rem">Hapus Background</h5>
                <p class="text-muted mb-4" style="font-size:0.875rem;line-height:1.6">
                    Hapus background <strong id="deleteBgName"></strong> dari library sistem?
                    Background ini tidak akan tersedia untuk lembaga manapun.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4"
                            style="border-radius:9px;font-size:0.85rem;font-weight:600"
                            data-bs-dismiss="modal">Batal</button>
                    <form id="deleteBgForm" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn px-4"
                                style="background:#ef4444;color:#fff;border:none;border-radius:9px;font-size:0.85rem;font-weight:700">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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

function previewBgImage(input) {
    const wrap = document.getElementById('bgPreviewWrap');
    const img  = document.getElementById('bgPreviewImg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        wrap.style.display = 'none';
    }
}

function confirmDeleteBg(id, name) {
    document.getElementById('deleteBgName').textContent = name;
    document.getElementById('deleteBgForm').action = `/superadmin/backgrounds/${id}`;
    new bootstrap.Modal(document.getElementById('modalDeleteBackground')).show();
}
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