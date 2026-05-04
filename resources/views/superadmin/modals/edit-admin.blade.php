{{-- ══════ MODAL: Edit Admin ══════ --}}
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
