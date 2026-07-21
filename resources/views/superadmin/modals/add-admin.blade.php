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
                               placeholder="Masukkan nama admin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Email Admin *</label>
                        <input type="email" name="admin_email" class="form-control"
                               placeholder="Masukkan email admin" required>
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
