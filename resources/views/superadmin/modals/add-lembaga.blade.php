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
