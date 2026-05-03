{{-- ══════ MODAL: Konfirmasi Hapus ══════ --}}
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
