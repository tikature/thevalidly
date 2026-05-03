// ══ TOAST SYSTEM ══════════════════════════════════════════════════════
function showToast(message, type = "error") {
    const wrap = document.getElementById("toastWrap");
    const toast = document.createElement("div");
    const isErr = type === "error";

    toast.style.cssText = `
        background: ${isErr ? "#fff0f0" : "#f0fdf4"};
        border: 1px solid ${isErr ? "#fca5a5" : "#86efac"};
        border-left: 4px solid ${isErr ? "#ef4444" : "#22c55e"};
        color: ${isErr ? "#b91c1c" : "#15803d"};
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 0.85rem;
        font-weight: 500;
        min-width: 280px;
        max-width: 380px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        pointer-events: all;
        animation: slideIn .25s ease;
    `;

    toast.innerHTML = `
        <i class="bi bi-${isErr ? "exclamation-circle-fill" : "check-circle-fill"}" style="font-size:1.1rem;flex-shrink:0"></i>
        <span style="flex:1">${message}</span>
        <button onclick="closeToast(this.parentElement)" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1rem;padding:0;line-height:1;opacity:.6" title="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    `;

    wrap.appendChild(toast);

    // Auto close 5 detik
    setTimeout(() => closeToast(toast), 5000);
}

function closeToast(el) {
    if (!el || !el.parentElement) return;
    el.style.animation = "slideOut .2s ease forwards";
    setTimeout(() => el.remove(), 200);
}

// ── Toggle password visibility
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.className = "bi bi-eye-slash";
    } else {
        input.type = "password";
        icon.className = "bi bi-eye";
    }
}

// ── Modal edit lembaga — isi form saat buka
document
    .getElementById("modalEditLembaga")
    .addEventListener("show.bs.modal", function (e) {
        const btn = e.relatedTarget;
        if (!btn) return;
        document.getElementById("editInstName").value = btn.dataset.name || "";
        document.getElementById("editInstEmail").value =
            btn.dataset.email || "";
        document.getElementById("editInstPhone").value =
            btn.dataset.phone || "";
        document.getElementById("editInstAddress").value =
            btn.dataset.address || "";
        document.getElementById("formEditLembaga").action =
            `/superadmin/institutions/${btn.dataset.id}`;
    });

// ── Modal edit admin — isi form saat buka
document
    .getElementById("modalEditAdmin")
    .addEventListener("show.bs.modal", function (e) {
        const btn = e.relatedTarget;
        if (!btn) return;
        document.getElementById("editAdminName").value = btn.dataset.name || "";
        document.getElementById("editAdminEmail").value =
            btn.dataset.email || "";
        document.getElementById("formEditAdmin").action =
            `/superadmin/admins/${btn.dataset.id}`;
    });

// ── Modal tambah admin — isi action saat buka
document
    .getElementById("modalAddAdmin")
    .addEventListener("show.bs.modal", function (e) {
        const btn = e.relatedTarget;
        if (!btn) return;
        document.getElementById("modalInstName").textContent =
            btn.getAttribute("data-inst-name");
        const baseUrl = "{{ url('superadmin/institutions') }}";
        document.getElementById("formAddAdmin").action =
            `${baseUrl}/${btn.getAttribute("data-inst-id")}/admins`;
    });

// ── Modal Konfirmasi Hapus
function confirmDelete(actionUrl, title, bodyHtml, btnLabel) {
    const modal = document.getElementById("modalConfirmDelete");
    const form = document.getElementById("formConfirmDelete");
    const titleEl = document.getElementById("confirmModalTitle");
    const bodyEl = document.getElementById("confirmModalBody");
    const btnEl = document.getElementById("confirmModalBtn");

    titleEl.textContent = title;
    bodyEl.innerHTML = bodyHtml;
    btnEl.textContent = btnLabel || "Hapus";
    form.action = actionUrl;

    btnEl.onclick = () => {
        btnEl.disabled = true;
        btnEl.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';
        form.submit();
    };

    new bootstrap.Modal(modal).show();
}
