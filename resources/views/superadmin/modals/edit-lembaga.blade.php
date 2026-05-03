{{-- ══════ MODAL: Edit Lembaga ══════ --}}
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
