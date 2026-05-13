{{-- ══════ MODAL: Tambah Super Admin ══════ --}}
<div class="modal fade" id="modalAddSuperAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-shield-plus me-2"></i>Tambah Super Admin
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.superadmins.store') }}">
                @csrf
                <div class="modal-body p-4">

                    @if($errors->addSuperAdmin->any())
                        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;border-radius:8px">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->addSuperAdmin->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label-sm">Nama *</label>
                        <input type="text" name="superadmin_name" class="form-control"
                               value="{{ old('superadmin_name') }}"
                               placeholder="Nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Email *</label>
                        <input type="email" name="superadmin_email" class="form-control"
                               value="{{ old('superadmin_email') }}"
                               placeholder="email@validly.app" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label-sm">Password *</label>
                        <div class="input-pw-wrap">
                            <input type="password" name="superadmin_password" id="pwAddSuperAdmin"
                                   class="form-control" placeholder="Min. 8 karakter"
                                   required minlength="8">
                            <button type="button" class="btn-eye"
                                    onclick="togglePw('pwAddSuperAdmin', this)" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-add-lembaga">
                        <i class="bi bi-shield-check me-2"></i>Simpan Super Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>