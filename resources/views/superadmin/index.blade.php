@extends('layouts.app')

@section('title', 'Super Admin — Kelola Lembaga')

@push('styles')
<style>
    :root { --navy: #0F1E3C; --navy-mid: #1a3260; --gold: #C9A84C; --gold-light: #E8D48B; }

    .stat-card {
        background: #fff;
        border: 1px solid #dde4f0;
        border-radius: 14px;
        padding: 22px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 8px rgba(15,30,60,0.06);
    }
    .stat-icon {
        width: 52px; height: 52px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .stat-icon.navy { background: linear-gradient(135deg, var(--navy), var(--navy-mid)); }
    .stat-icon.gold  { background: linear-gradient(135deg, #b8872a, var(--gold)); }
    .stat-icon.green { background: linear-gradient(135deg, #16a34a, #0d6b32); }
    .stat-val { font-size: 1.8rem; font-weight: 700; color: var(--navy); line-height: 1; }
    .stat-lbl { font-size: 0.78rem; color: #6b7280; margin-top: 3px; }

    .inst-card {
        background: #fff;
        border: 1px solid #dde4f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15,30,60,0.06);
        margin-bottom: 16px;
        transition: box-shadow .2s;
    }
    .inst-card:hover { box-shadow: 0 6px 24px rgba(15,30,60,0.1); }
    .inst-header {
        background: var(--navy);
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .inst-name {
        font-weight: 700;
        color: #fff;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .inst-name .status-dot {
        width: 8px; height: 8px; border-radius: 50%;
    }
    .inst-name .status-dot.active  { background: #4ade80; }
    .inst-name .status-dot.inactive { background: #f87171; }

    .inst-body { padding: 16px 20px; }
    .inst-meta { font-size: 0.8rem; color: #6b7280; margin-bottom: 12px; }
    .inst-meta span { margin-right: 16px; }
    .inst-meta i { margin-right: 4px; }

    /* Admin list in card */
    .admin-list { list-style: none; padding: 0; margin: 0; }
    .admin-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 12px;
        background: #f9fbff;
        border: 1px solid #eef2f9;
        border-radius: 8px;
        margin-bottom: 6px;
        font-size: 0.82rem;
    }
    .admin-item-info { display: flex; align-items: center; gap: 10px; }
    .admin-avatar {
        width: 30px; height: 30px;
        background: linear-gradient(135deg, var(--navy), var(--navy-mid));
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: var(--gold-light); font-size: 0.72rem; font-weight: 700;
        flex-shrink: 0;
    }

    /* Buttons kecil */
    .btn-sm-navy {
        background: var(--navy); color: var(--gold-light);
        border: none; border-radius: 6px; padding: 4px 10px;
        font-size: 0.72rem; font-weight: 600; cursor: pointer;
        transition: all .2s; white-space: nowrap;
    }
    .btn-sm-navy:hover { background: var(--navy-mid); }
    .btn-sm-danger {
        background: #fef2f2; color: #b91c1c;
        border: 1px solid #fecaca; border-radius: 6px; padding: 4px 10px;
        font-size: 0.72rem; font-weight: 600; cursor: pointer;
        transition: all .2s; white-space: nowrap;
    }
    .btn-sm-danger:hover { background: #fee2e2; }
    .btn-sm-warn {
        background: #fffbeb; color: #b45309;
        border: 1px solid #fde68a; border-radius: 6px; padding: 4px 10px;
        font-size: 0.72rem; font-weight: 600; cursor: pointer;
        transition: all .2s;
    }
    .btn-sm-warn:hover { background: #fef3c7; }
    .btn-sm-success {
        background: #f0fdf4; color: #16a34a;
        border: 1px solid #bbf7d0; border-radius: 6px; padding: 4px 10px;
        font-size: 0.72rem; font-weight: 600; cursor: pointer;
        transition: all .2s;
    }
    .btn-sm-success:hover { background: #dcfce7; }

    .btn-add-lembaga {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
        color: var(--gold-light);
        border: none; border-radius: 10px;
        padding: 10px 24px;
        font-weight: 700; font-size: 0.85rem;
        letter-spacing: 1px; cursor: pointer; transition: all .2s;
        text-decoration: none; display: inline-block;
    }
    .btn-add-lembaga:hover { opacity: 0.9; color: var(--gold-light); }

    /* Modal form */
    .modal-header { background: var(--navy); color: #fff; border-bottom: 1px solid var(--navy-mid); }
    .modal-title { color: var(--gold-light); }
    .modal-header .btn-close { filter: invert(1); }
    .form-label-sm { font-size: 0.72rem; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #6b7280; }

    .empty-state {
        text-align: center; padding: 48px 24px;
        color: #9ca3af; font-size: 0.9rem;
    }
    .empty-state .empty-icon { font-size: 3rem; margin-bottom: 12px; }

    .badge-active   { background: #dcfce7; color: #16a34a; font-size: 0.68rem; padding: 3px 8px; border-radius: 10px; font-weight: 700; }
    .badge-inactive { background: #fee2e2; color: #b91c1c; font-size: 0.68rem; padding: 3px 8px; border-radius: 10px; font-weight: 700; }

    /* Toggle password visibility */
    .input-pw-wrap { position: relative; }
    .input-pw-wrap .form-control { padding-right: 40px; }
    .btn-eye {
        position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        background: none; border: none; color: #9ca3af; cursor: pointer;
        padding: 0; font-size: 1rem; line-height: 1; transition: color .2s;
    }
    .btn-eye:hover { color: var(--navy); }
</style>
@endpush

@section('content')

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
                    <span><i class="bi bi-geo-alt"></i>{{ Str::limit($inst->address, 40) }}</span>
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

{{-- ══════════ MODAL: Tambah Lembaga ══════════ --}}
<div class="modal fade" id="modalAddLembaga" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-building-add me-2"></i>Tambah Lembaga Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.institutions.store') }}">
                @csrf
                <div class="modal-body p-4">
                    {{-- Error duplikasi email --}}
                    @if($errors->any())
                    <div class="alert border-0 mb-3" style="background:#fef2f2;color:#b91c1c;font-size:0.82rem;border-radius:8px;">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <p class="text-muted mb-3" style="font-size:0.82rem">
                                <i class="bi bi-info-circle me-1"></i>
                                Isi data lembaga dan akun admin pertama. Kolom bertanda <strong>*</strong> wajib diisi.
                            </p>
                        </div>

                        {{-- Info Lembaga --}}
                        <div class="col-12">
                            <h6 class="fw-bold mb-3" style="color:var(--navy); font-size:0.82rem; letter-spacing:1px; text-transform:uppercase; border-bottom:1px solid #eee; padding-bottom:8px;">
                                🏛 Informasi Lembaga
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Nama Lembaga *</label>
                            <input type="text" name="institution_name" class="form-control"
                                   placeholder="Contoh: Lembaga Pelatihan ABC"
                                   value="{{ old('institution_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Email Lembaga *</label>
                            <input type="email" name="institution_email" class="form-control"
                                   placeholder="info@lembaga.com"
                                   value="{{ old('institution_email') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Nomor Telepon *</label>
                            <input type="text" name="institution_phone" class="form-control"
                                   placeholder="08xx-xxxx-xxxx"
                                   value="{{ old('institution_phone') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Alamat *</label>
                            <input type="text" name="institution_address" class="form-control"
                                   placeholder="Kota, Provinsi"
                                   value="{{ old('institution_address') }}" required>
                        </div>

                        {{-- Akun Admin --}}
                        <div class="col-12 mt-2">
                            <h6 class="fw-bold mb-3" style="color:var(--navy); font-size:0.82rem; letter-spacing:1px; text-transform:uppercase; border-bottom:1px solid #eee; padding-bottom:8px;">
                                👤 Akun Admin Lembaga
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Nama Admin *</label>
                            <input type="text" name="admin_name" class="form-control"
                                   placeholder="Nama lengkap admin"
                                   value="{{ old('admin_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Email Admin *</label>
                            <input type="email" name="admin_email" class="form-control"
                                   placeholder="admin@lembaga.com"
                                   value="{{ old('admin_email') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Password *</label>
                            <div class="input-pw-wrap">
                                <input type="password" name="admin_password" id="pwAddInst" class="form-control"
                                       placeholder="Min. 8 karakter" required minlength="8">
                                <button type="button" class="btn-eye" onclick="togglePw('pwAddInst', this)" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="alert border-0 mb-0" style="background:#fffbeb; color:#92400e; font-size:0.8rem; border-radius:8px;">
                                <i class="bi bi-lightbulb me-2"></i>
                                Admin ini akan langsung bisa login dan menggunakan generator sertifikat untuk lembaga tersebut.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-add-lembaga">
                        <i class="bi bi-check-circle me-2"></i>Simpan Lembaga
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════ MODAL: Tambah Admin ke Lembaga ══════════ --}}
<div class="modal fade" id="modalAddAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>Tambah Admin — <span id="modalInstName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formAddAdmin">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label-sm">Nama Admin *</label>
                        <input type="text" name="admin_name" class="form-control"
                               placeholder="Nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Email Admin *</label>
                        <input type="email" name="admin_email" class="form-control"
                               placeholder="email@lembaga.com" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label-sm">Password *</label>
                        <div class="input-pw-wrap">
                            <input type="password" name="admin_password" id="pwAddAdmin" class="form-control"
                                   placeholder="Min. 8 karakter" required minlength="8">
                            <button type="button" class="btn-eye" onclick="togglePw('pwAddAdmin', this)" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-add-lembaga">
                        <i class="bi bi-person-check me-2"></i>Simpan Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════ MODAL KONFIRMASI HAPUS ══════ --}}
{{-- Modal Edit Lembaga --}}
<div class="modal fade" id="modalEditLembaga" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Lembaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEditLembaga" action="">
                @csrf @method('PATCH')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-600" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280">Nama Lembaga</label>
                            <input type="text" name="institution_name" id="editInstName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280">Email</label>
                            <input type="email" name="institution_email" id="editInstEmail" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280">Telepon</label>
                            <input type="text" name="institution_phone" id="editInstPhone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-600" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280">Alamat</label>
                            <textarea name="institution_address" id="editInstAddress" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm fw-700"
                            style="background:var(--navy);color:var(--gold-light);border:none;border-radius:7px;padding:7px 20px">
                        <i class="bi bi-check-circle me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Admin --}}
<div class="modal fade" id="modalEditAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Edit Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEditAdmin" action="">
                @csrf @method('PATCH')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280">Nama</label>
                        <input type="text" name="admin_name" id="editAdminName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280">Email</label>
                        <input type="email" name="admin_email" id="editAdminEmail" class="form-control" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-600" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280">Password Baru</label>
                        <div class="input-pw-wrap">
                            <input type="password" name="admin_password" id="pwEditAdmin" class="form-control" placeholder="Kosongkan jika tidak diubah">
                            <button type="button" class="btn-eye" onclick="togglePw('pwEditAdmin', this)" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password.</small>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm fw-700"
                            style="background:var(--navy);color:var(--gold-light);border:none;border-radius:7px;padding:7px 20px">
                        <i class="bi bi-check-circle me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
            <div class="modal-body p-4 text-center">
                {{-- Icon --}}
                <div style="width:56px;height:56px;background:#fff0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:1.5rem;">
                    🗑️
                </div>
                <h5 class="fw-bold mb-2" id="confirmModalTitle" style="color:var(--navy);font-size:1.05rem"></h5>
                <p class="text-muted mb-4" id="confirmModalBody" style="font-size:0.875rem;line-height:1.6"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4"
                            style="border-radius:9px;font-size:0.85rem;font-weight:600"
                            data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" id="confirmModalBtn"
                            class="btn px-4"
                            style="background:#ef4444;color:#fff;border:none;border-radius:9px;font-size:0.85rem;font-weight:700;">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk submit DELETE --}}
<form id="formConfirmDelete" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
// ── Toggle password visibility
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// ── Modal Tambah Admin
// Modal edit lembaga
document.getElementById('modalEditLembaga').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editInstName').value    = btn.dataset.name || '';
    document.getElementById('editInstEmail').value   = btn.dataset.email || '';
    document.getElementById('editInstPhone').value   = btn.dataset.phone || '';
    document.getElementById('editInstAddress').value = btn.dataset.address || '';
    const id = btn.dataset.id;
    document.getElementById('formEditLembaga').action = `/superadmin/institutions/${id}`;
});

// Modal edit admin
document.getElementById('modalEditAdmin').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editAdminName').value  = btn.dataset.name || '';
    document.getElementById('editAdminEmail').value = btn.dataset.email || '';
    const id = btn.dataset.id;
    document.getElementById('formEditAdmin').action = `/superadmin/admins/${id}`;
});

document.getElementById('modalAddAdmin').addEventListener('show.bs.modal', function(e) {
    const btn      = e.relatedTarget;
    const instId   = btn.getAttribute('data-inst-id');
    const instName = btn.getAttribute('data-inst-name');
    document.getElementById('modalInstName').textContent = instName;
    const baseUrl = "{{ url('superadmin/institutions') }}";
    document.getElementById('formAddAdmin').action = `${baseUrl}/${instId}/admins`;
});

// ── Modal Konfirmasi Hapus
function confirmDelete(actionUrl, title, bodyHtml, btnLabel) {
    const modal    = document.getElementById('modalConfirmDelete');
    const form     = document.getElementById('formConfirmDelete');
    const titleEl  = document.getElementById('confirmModalTitle');
    const bodyEl   = document.getElementById('confirmModalBody');
    const btnEl    = document.getElementById('confirmModalBtn');

    titleEl.textContent = title;
    bodyEl.innerHTML    = bodyHtml;
    btnEl.textContent   = btnLabel || 'Hapus';

    // Set action URL ke form tersembunyi
    form.action = actionUrl;

    // Tombol konfirmasi → submit form
    btnEl.onclick = () => {
        btnEl.disabled   = true;
        btnEl.innerHTML  = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';
        form.submit();
    };

    // Tampilkan modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}
</script>
@endpush
