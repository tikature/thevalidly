@extends('layouts.app')

@section('title', 'Generator Sertifikat')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<style>
    :root { --navy:#0F1E3C; --navy-mid:#1a3260; --gold:#C9A84C; --gold-light:#E8D48B; }

    .gen-panel { background:#fff; border:1px solid #dde4f0; border-radius:14px; padding:24px 28px; margin-bottom:20px; box-shadow:0 2px 8px rgba(15,30,60,.05); }
    .gen-panel-title { font-size:.8rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--navy-mid); border-bottom:2px solid var(--gold); padding-bottom:10px; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
    .form-label-sm { font-size:.72rem; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:#6b7280; margin-bottom:5px; display:block; }
    .form-control,.form-select { border:1.5px solid #dde4f0; border-radius:8px; font-size:.875rem; }
    .form-control:focus,.form-select:focus { border-color:var(--navy-mid); box-shadow:0 0 0 3px rgba(26,50,96,.08); }

    /* Upload Box */
    .img-upload-box { border:2px dashed #dde4f0; border-radius:10px; padding:16px 10px; text-align:center; cursor:pointer; transition:all .2s; background:#f9fbff; position:relative; width:100%; height:110px; display:flex; flex-direction:column; align-items:center; justify-content:center; overflow:hidden; }
    .img-upload-box:hover { border-color:var(--navy-mid); background:#f0f4ff; }
    .img-upload-box input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .img-upload-box .upload-icon { font-size:1.6rem; margin-bottom:4px; }
    .img-upload-box p { font-size:.72rem; color:#9ca3af; line-height:1.5; margin:0; }
    .img-upload-box p strong { color:var(--navy-mid); display:block; }
    .img-preview-container { width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
    .img-preview-container img { max-width:90%; max-height:90%; object-fit:contain; }
    .upload-badge { position:absolute; top:6px; left:6px; background:#dcfce7; color:#16a34a; font-size:.6rem; padding:2px 6px; border-radius:10px; letter-spacing:1px; font-weight:700; }
    .btn-remove-img { position:absolute; top:4px; right:4px; width:20px; height:20px; border-radius:50%; background:#ef4444; color:#fff; border:none; font-size:13px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; z-index:10; font-weight:700; }
    .btn-remove-img:hover { background:#b91c1c; }

    /* Numbering */
    .num-preview { background:linear-gradient(135deg,var(--navy),var(--navy-mid)); color:var(--gold-light); font-family:'Playfair Display',serif; font-size:1.1rem; text-align:center; padding:12px; border-radius:8px; letter-spacing:3px; margin-top:12px; }
    .btn-add-seg { background:var(--navy); color:var(--gold-light); border:none; border-radius:7px; padding:5px 12px; font-size:.75rem; font-weight:700; cursor:pointer; letter-spacing:1px; }
    .seg-row { display:flex; align-items:center; gap:6px; background:#f9fbff; border:1.5px solid #dde4f0; border-radius:8px; padding:6px 8px; margin-bottom:6px; transition:all .2s; }
    .seg-row[draggable="true"] { cursor:grab; }
    .seg-row[draggable="true"]:active { cursor:grabbing; }
    .seg-row.dragging { opacity:.5; background:#e8f0ff; border-color:#a5b4fc; }
    .seg-row.drag-over { background:#e0e7ff; border-color:#6366f1; }
    .seg-drag-handle { color:#9ca3af; font-size:.9rem; cursor:grab; flex-shrink:0; user-select:none; padding:2px 4px; }
    .seg-row:active .seg-drag-handle { cursor:grabbing; }
    .seg-type { border:1.5px solid #dde4f0; border-radius:6px; font-size:.75rem; padding:4px 6px; background:#fff; color:var(--navy); font-weight:600; width:110px; flex-shrink:0; }
    .seg-value { border:1.5px solid #dde4f0; border-radius:6px; font-size:.8rem; padding:4px 8px; background:#fff; flex:1; min-width:0; }
    .seg-value:disabled { background:#f0f4ff; color:#9ca3af; }
    .seg-delete { background:none; border:none; color:#f87171; font-size:1rem; cursor:pointer; padding:0 2px; flex-shrink:0; opacity:.7; }
    .btn-sep { background:#f0f4ff; border:1.5px solid #dde4f0; border-radius:6px; color:var(--navy); font-size:.85rem; font-weight:700; width:36px; height:32px; cursor:pointer; }
    .btn-sep.active { background:var(--navy); color:var(--gold-light); border-color:var(--navy); }

    /* Peserta */
    .peserta-card { background:#f9fbff; border:1.5px solid #dde4f0; border-radius:10px; padding:14px 16px; margin-bottom:10px; position:relative; }
    .btn-add-peserta { background:#f0f4ff; border:1.5px dashed #a5b4fc; border-radius:8px; color:var(--navy-mid); font-size:.78rem; font-weight:700; padding:7px 14px; cursor:pointer; }
    .btn-add-peserta:hover { background:var(--navy); border-color:var(--navy); color:var(--gold-light); }
    .btn-del-peserta { background:none; border:none; color:#f87171; font-size:1rem; cursor:pointer; padding:2px 4px; border-radius:5px; opacity:.7; }
    .btn-del-peserta:hover { opacity:1; background:#fee2e2; }
    .btn-sm-danger-outline { background:#fff0f0; border:1.5px solid #fca5a5; color:#b91c1c; border-radius:8px; padding:6px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }

    /* Generate button */
    .btn-generate { background:linear-gradient(135deg,var(--navy),var(--navy-mid)); color:#fff; border:none; border-radius:10px; padding:12px 24px; font-weight:700; font-size:.85rem; letter-spacing:2px; text-transform:uppercase; width:100%; transition:all .2s; margin-top:8px; }
    .btn-generate:hover { opacity:.9; transform:translateY(-1px); color:var(--gold-light); }
    .btn-generate:disabled { opacity:.35; cursor:not-allowed; transform:none; }

    /* Results */
    #results { margin-top:32px; }
    .result-header { font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--navy); text-align:center; margin-bottom:20px; }
    .cert-card { background:#fff; border:1px solid #dde4f0; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(15,30,60,.06); }
    .cert-card-name { font-size:.85rem; color:var(--navy-mid); font-weight:600; margin:8px 0 6px; text-align:center; }
    .btn-dl { background:var(--navy); color:var(--gold-light); border:none; border-radius:7px; padding:9px; font-size:.78rem; font-weight:700; width:100%; cursor:pointer; transition:all .2s; text-decoration:none; display:block; text-align:center; }
    .btn-dl:hover { background:var(--navy-mid); color:var(--gold-light); }
    .btn-dl-secondary { background:#f0f4ff; color:var(--navy-mid); border:1.5px solid #dde4f0; border-radius:7px; padding:8px; font-size:.78rem; font-weight:600; width:100%; cursor:pointer; transition:all .2s; text-decoration:none; display:block; text-align:center; margin-top:6px; }
    .btn-dl-secondary:hover { background:var(--navy-soft); color:var(--navy); }

    /* Loading state tombol download */
    .btn-dl-loading { opacity:.6; cursor:not-allowed; pointer-events:none; }
    .btn-dl .spinner { display:inline-block; width:12px; height:12px; border:2px solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:50%; animation:spin .6s linear infinite; margin-right:5px; vertical-align:middle; }
    @keyframes spin { to { transform:rotate(360deg); } }
    .pdf-status { font-size:.65rem; text-align:center; margin-top:5px; height:14px; color:#9ca3af; }
    .pdf-status.ready { color:#16a34a; }
    .pdf-status.loading { color:var(--navy-mid); }

    /* ── Notif / Toast ── */
    .notif-wrap { position:fixed; top:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:10px; max-width:340px; pointer-events:none; }
    .notif { background:#fff; border-radius:12px; padding:14px 18px; box-shadow:0 8px 28px rgba(0,0,0,.13); font-size:.85rem; display:flex; align-items:flex-start; gap:10px; animation:notifIn .25s ease; border-left:4px solid #ef4444; pointer-events:all; }
    .notif.warn  { border-left-color:#f59e0b; }
    .notif.success { border-left-color:#16a34a; }
    .notif.info  { border-left-color:var(--navy-mid); }
    .notif-icon  { font-size:1rem; flex-shrink:0; margin-top:1px; }
    .notif-body  { flex:1; }
    .notif-title { font-weight:700; color:var(--navy); margin-bottom:3px; font-size:.85rem; }
    .notif-msg   { color:#6b7280; font-size:.78rem; line-height:1.55; }
    .notif-msg ul { margin:4px 0 0 14px; padding:0; }
    .notif-msg li { margin-bottom:2px; }
    .notif-close { background:none; border:none; color:#9ca3af; font-size:1.1rem; cursor:pointer; padding:0; line-height:1; flex-shrink:0; }
    .notif-close:hover { color:#374151; }
    @keyframes notifIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
    @keyframes notifOut { from { opacity:1; } to { opacity:0; transform:translateX(20px); } }
</style>
@endpush

@section('content')

<div class="notif-wrap" id="notifWrap"></div>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--navy)">
            <i class="bi bi-award me-2" style="color:var(--gold)"></i>Generator Sertifikat
        </h4>
        <small class="text-muted">{{ $institution->name ?? '' }}</small>
    </div>
    <a href="{{ route('certificate.history') }}" class="btn btn-sm" style="background:var(--navy);color:var(--gold-light);border:none;border-radius:8px;font-size:.8rem;font-weight:600;padding:8px 16px">
        <i class="bi bi-clock-history me-1"></i>Riwayat
    </a>
</div>

<div class="row g-4">

{{-- ════ KIRI: PENGATURAN ════ --}}
<div class="col-lg-5">

    {{-- Panel Pengaturan --}}
    <div class="gen-panel">
        <div class="gen-panel-title">⚙ Pengaturan Sertifikat</div>

        <div class="mb-3">
            <label class="form-label-sm">Nama Acara / Kegiatan <span style="color:#ef4444">*</span></label>
            <input type="text" class="form-control" id="eventName" placeholder="Nama Acara / Kegiatan">
        </div>
        <div class="mb-3">
            <label class="form-label-sm">Tempat Pelaksanaan <span style="color:#ef4444">*</span></label>
            <input type="text" class="form-control" id="eventPlace" placeholder="Tempat Pelaksanaan">
        </div>
        <div class="mb-3">
            <label class="form-label-sm d-flex align-items-center justify-content-between">
                <span>Tanggal Pelaksanaan <span style="color:#ef4444">*</span></span>
                <div class="form-check form-switch mb-0 ms-2">
                    <input class="form-check-input" type="checkbox" id="multiDayToggle" onchange="toggleMultiDay()">
                    <label class="form-check-label" for="multiDayToggle" style="font-size:.68rem;color:#9ca3af;letter-spacing:0;text-transform:none;font-weight:400">Beberapa hari</label>
                </div>
            </label>
            <div id="singleDayInput">
                <input type="date" class="form-control" id="dateStart" onchange="updateDatePreview()">
            </div>
            <div id="multiDayInput" class="d-none">
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control" id="dateFrom" onchange="updateDatePreview()">
                        <div class="form-text" style="font-size:.68rem">Dari</div>
                    </div>
                    <div class="col-6">
                        <input type="date" class="form-control" id="dateTo" onchange="updateDatePreview()">
                        <div class="form-text" style="font-size:.68rem">Sampai</div>
                    </div>
                </div>
            </div>
            <div id="datePreview" class="mt-2 px-2 py-1 rounded" style="font-size:.8rem;color:var(--navy-mid);background:#f0f4ff;border:1px solid #dde4f0;display:none"></div>
        </div>
        <div class="mb-3">
            <label class="form-label-sm d-flex align-items-center justify-content-between">
                <span>Deskripsi Kegiatan</span>
                <span id="descCount" style="font-size:.68rem;color:#9ca3af;font-weight:400;letter-spacing:0;text-transform:none">0/200</span>
            </label>
            <input type="text" class="form-control" id="certDesc" maxlength="200"
                   value="Has Successfully Completed a Training Course on:"
                   oninput="updateDescCount(this.value)">
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label-sm">Nama Penandatangan <span style="color:#ef4444">*</span></label>
                <input type="text" class="form-control" id="signerName" placeholder="Nama Lengkap Penandatangan">
            </div>
            <div class="col-6">
                <label class="form-label-sm">Jabatan Penandatangan <span style="color:#ef4444">*</span></label>
                <input type="text" class="form-control" id="signerTitle" placeholder="Jabatan Penandatangan">
            </div>
        </div>
    </div>

    {{-- Panel TTD, Cap, Logo --}}
    <div class="gen-panel">
        <div class="gen-panel-title">🖊 Tanda Tangan, Cap & Logo</div>
        <div class="row g-2 mb-3">
            <div class="col-4">
                <label class="form-label-sm">Tanda Tangan</label>
                <div class="img-upload-box" style="position:relative">
                    <input type="file" accept="image/png,image/jpeg" onchange="uploadAsset('ttd', this)">
                    <div id="ttdPreview"><div class="upload-icon">✍</div><p><strong>Upload TTD</strong>PNG transparan</p></div>
                    <span class="upload-badge d-none" id="ttdBadge">✓ OK</span>
                    <button type="button" class="btn-remove-img d-none" id="ttdRemove" onclick="removeAsset('ttd')">&times;</button>
                </div>
            </div>
            <div class="col-4">
                <label class="form-label-sm">Cap / Stempel</label>
                <div class="img-upload-box" style="position:relative">
                    <input type="file" accept="image/png,image/jpeg" onchange="uploadAsset('cap', this)">
                    <div id="capPreview"><div class="upload-icon">🔴</div><p><strong>Upload Cap</strong>PNG transparan</p></div>
                    <span class="upload-badge d-none" id="capBadge">✓ OK</span>
                    <button type="button" class="btn-remove-img d-none" id="capRemove" onclick="removeAsset('cap')">&times;</button>
                </div>
            </div>
            <div class="col-4">
                <label class="form-label-sm">Logo Institusi</label>
                <div class="img-upload-box" style="position:relative">
                    <input type="file" accept="image/png,image/jpeg" onchange="uploadAsset('logo', this)">
                    <div id="logoPreview"><div class="upload-icon">🏛</div><p><strong>Upload Logo</strong>Tampil di atas</p></div>
                    <span class="upload-badge d-none" id="logoBadge">✓ OK</span>
                    <button type="button" class="btn-remove-img d-none" id="logoRemove" onclick="removeAsset('logo')">&times;</button>
                </div>
            </div>
        </div>

        {{-- Background --}}
        <div class="mt-2 mb-1">
            <label class="form-label-sm">Background Sertifikat <span style="text-transform:none;letter-spacing:0;font-size:.68rem;color:#9ca3af;font-weight:400"> — opsional</span></label>
            <div class="d-flex align-items-center gap-3">
                <div class="img-upload-box" style="min-height:70px;flex:1;padding:10px">
                    <input type="file" accept=".jpg,.jpeg,.png" onchange="uploadAsset('background', this)" id="bgFileInput">
                    <div id="bgPreview"><div class="upload-icon" style="font-size:1.2rem">🖼</div><p><strong>Upload Background</strong>JPG / PNG, maks. 2MB</p></div>
                    <span class="upload-badge d-none" id="bgBadge">✓ OK</span>
                </div>
                <button type="button" onclick="removeAsset('background')" id="btnRemoveBg" class="d-none btn-sm-danger-outline">
                    <i class="bi bi-x-circle me-1"></i>Hapus BG
                </button>
            </div>
        </div>

        <div class="row g-2 p-2 mt-2" style="background:#f9fbff;border-radius:8px;border:1px solid #eee">
            <div class="col-12">
                <label class="form-label-sm">Ukuran TTD/Cap</label>
                <select class="form-select form-select-sm" id="ttdSize">
                    <option value="sm">Kecil</option>
                    <option value="md" selected>Sedang</option>
                    <option value="lg">Besar</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Auto Numbering --}}
    <div class="gen-panel">
        <div class="gen-panel-title">🔢 Auto Numbering</div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label-sm mb-0">Segmen Nomor</label>
            <button type="button" onclick="addSegment()" class="btn-add-seg"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
        </div>
        <div id="segmentList"></div>
        <div class="mt-4 mb-3 p-2" style="background:#f0f4ff;border-radius:8px;border:1px solid #dde4f0;font-size:.72rem;color:#6b7280;line-height:1.6">
            <i class="bi bi-info-circle me-1" style="color:var(--navy-mid)"></i><strong>Info:</strong> Urutan segmen = urutan di sertifikat. Drag ≡ untuk ubah urutan.
        </div>
        <div class="mt-3 mb-2">
            <label class="form-label-sm">Pemisah Antar Segmen</label>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn-sep active" onclick="setSep('/', this)">/</button>
                <button type="button" class="btn-sep" onclick="setSep('-', this)">-</button>
                <button type="button" class="btn-sep" onclick="setSep('.', this)">.</button>
                <button type="button" class="btn-sep" onclick="setSep('_', this)">_</button>
                <input type="text" id="customSep" maxlength="3" placeholder="..." class="form-control form-control-sm" style="width:60px" oninput="setSepCustom(this.value)">
            </div>
        </div>
        <div class="num-preview" id="numPreview">CERT/001/2026</div>
    </div>

</div>

{{-- ════ KANAN: DATA PESERTA ════ --}}
<div class="col-lg-7">
    <div class="gen-panel">
        <div class="gen-panel-title">👤 Data Peserta</div>

        <div id="pesertaList"></div>

        <div class="d-flex align-items-center gap-2 mb-3">
            <button type="button" class="btn-add-peserta" onclick="addPeserta()">
                <i class="bi bi-person-plus me-1"></i>Tambah Peserta
            </button>
            <span class="text-muted" id="pesertaCount" style="font-size:.75rem"></span>
        </div>

        <button class="btn-generate" id="btnGenerate" onclick="generate()">
            ✦ Generate Sertifikat
        </button>
    </div>

    {{-- Hasil --}}
    <div id="results" class="d-none">
        <div class="result-header">✦ Hasil Sertifikat</div>
        <div class="row g-3" id="certGrid"></div>
    </div>
</div>
</div>

@endsection

@push('scripts')
<script>
// ════ NOTIF SYSTEM ════
function showNotif(title, messages, type, duration) {
    type     = type     || 'error';
    duration = duration !== undefined ? duration : 5000;
    const wrap  = document.getElementById('notifWrap');
    const icons = { error:'⚠', warn:'⚠', success:'✓', info:'ℹ' };
    const notif = document.createElement('div');
    notif.className = 'notif ' + (type === 'error' ? '' : type);

    let msgHtml = '';
    if (Array.isArray(messages) && messages.length) {
        msgHtml = '<ul>' + messages.map(function(m){ return '<li>' + m + '</li>'; }).join('') + '</ul>';
    } else if (typeof messages === 'string' && messages) {
        msgHtml = '<div>' + messages + '</div>';
    }

    notif.innerHTML =
        '<div class="notif-icon">' + (icons[type] || '⚠') + '</div>' +
        '<div class="notif-body">' +
            '<div class="notif-title">' + title + '</div>' +
            '<div class="notif-msg">' + msgHtml + '</div>' +
        '</div>' +
        '<button class="notif-close" onclick="this.closest(\'.notif\').remove()">✕</button>';
    wrap.appendChild(notif);
    if (duration > 0) {
        setTimeout(function(){ notif.remove(); }, duration);
    }
    return notif;
}

// ════ STATE ════
const institutionName = '{{ addslashes($institution->name ?? '') }}';
let pesertaList = [{ nama: '', perusahaan: '', nomor: '' }];
let segments    = [{ type: 'teks', value: 'CERT' }, { type: 'nomor', value: '001' }, { type: 'tahun', value: '{{ date("Y") }}' }];
let separator   = '/';
let generatedCerts = [];

// ════ ASSET UPLOAD / REMOVE ════
async function uploadAsset(type, input) {
    const file = input.files[0];
    if (!file) return;

    const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowed.includes(file.type)) {
        showNotif('Format tidak didukung', 'Gunakan PNG atau JPG — bukan WebP, GIF, atau format lain.', 'warn');
        input.value = '';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        showNotif('File terlalu besar', 'Maksimal 2MB per file.', 'warn');
        input.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('type', type);
    formData.append('file', file);

    try {
        const res = await fetch('{{ route("certificate.asset.upload") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData,
        });
        
        const data = await res.json();
        
        // Jika response gagal, tampilkan error
        if (!res.ok) {
            console.error('Upload error:', data);
            showNotif('Upload gagal', data.message || 'Kesalahan server', 'error');
            input.value = '';
            return;
        }
        
        // Jika ada URL, tampilkan preview
        if (data.url) {
            showAssetPreview(type, data.url);
        } else {
            console.error('No URL in response:', data);
            showNotif('Upload gagal', 'Berhasil dikirim tapi preview tidak tersedia.', 'warn');
            input.value = '';
        }
    } catch (e) {
        console.error('Upload error:', e);
        showNotif('Upload gagal', e.message, 'error');
        input.value = '';
    }
}

async function removeAsset(type) {
    try {
        await fetch('{{ route("certificate.asset.remove") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ type }),
        });
    } catch (e) {}
    clearAssetPreview(type);
}

function showAssetPreview(type, url) {
    const key = type === 'background' ? 'bg' : type;
    const prev = document.getElementById(key + 'Preview');
    if (prev) {
        // Buat container untuk preview gambar
        const container = document.createElement('div');
        container.className = 'img-preview-container';
        
        // Buat elemen img dengan proper loading
        const img = document.createElement('img');
        img.src = url;
        
        // Handle image load error
        img.onerror = function() {
            console.error('Gambar gagal dimuat:', url);
            container.innerHTML = `<div style="color:#ef4444;font-size:.75rem;padding:8px;text-align:center">Gambar gagal dimuat</div>`;
        };
        
        // Handle image load success
        img.onload = function() {
            console.log('Gambar berhasil dimuat:', type, url);
        };
        
        // Clear previous content and add image
        container.appendChild(img);
        prev.innerHTML = '';
        prev.appendChild(container);
    }
    document.getElementById(key + 'Badge')?.classList.remove('d-none');
    if (type === 'background') {
        document.getElementById('btnRemoveBg')?.classList.remove('d-none');
    } else {
        document.getElementById(type + 'Remove')?.classList.remove('d-none');
    }
}

function clearAssetPreview(type) {
    const key     = type === 'background' ? 'bg' : type;
    const icons   = { ttd: '✍', cap: '🔴', logo: '🏛', bg: '🖼' };
    const labels  = { ttd: 'Upload TTD', cap: 'Upload Cap', logo: 'Upload Logo', bg: 'Upload Background' };
    const subs    = { ttd: 'PNG transparan', cap: 'PNG transparan', logo: 'Tampil di atas', bg: 'JPG / PNG, maks. 2MB' };
    const prev = document.getElementById(key + 'Preview');
    if (prev) {
        prev.innerHTML = `
            <div class="upload-icon">${icons[key]}</div>
            <p><strong>${labels[key]}</strong><br>${subs[key]}</p>
        `;
    }
    document.getElementById(key + 'Badge')?.classList.add('d-none');
    if (type === 'background') {
        document.getElementById('btnRemoveBg')?.classList.add('d-none');
        if (document.getElementById('bgFileInput')) document.getElementById('bgFileInput').value = '';
    } else {
        document.getElementById(type + 'Remove')?.classList.add('d-none');
    }
    // Reset file input
    const fileInputs = document.querySelectorAll(`input[type=file][onchange*="'${type}'"]`);
    fileInputs.forEach(input => input.value = '');
}

// Load aset dari server saat halaman dibuka
async function loadAssets() {
    try {
        const res  = await fetch('{{ route("certificate.asset.get") }}');
        const data = await res.json();
        if (res.ok) {
            ['logo', 'ttd', 'cap', 'background'].forEach(type => {
                if (data[type]) {
                    console.log('Loading asset:', type, data[type]);
                    showAssetPreview(type, data[type]);
                }
            });
        }
    } catch (e) {
        console.error('Load assets error:', e);
    }
}
loadAssets();

// ════ NUMBERING ════
const SEG_TYPES = {
    teks:  { label: 'Teks / Kode', auto: false, placeholder: 'Contoh: CERT' },
    nomor: { label: 'Nomor Urut',  auto: false, placeholder: 'Mulai dari: 001' },
    tahun: { label: 'Tahun',       auto: false, placeholder: 'Contoh: 2026' },
    bulan: { label: 'Bulan',       auto: true,  placeholder: 'Otomatis' },
};

function renderSegments() {
    const list = document.getElementById('segmentList');
    list.innerHTML = '';
    segments.forEach((seg, i) => {
        const row = document.createElement('div');
        row.className = 'seg-row';
        row.draggable = true;
        row.dataset.index = i;
        row.innerHTML = `
            <div class="seg-drag-handle" title="Drag untuk ubah urutan">≡</div>
            <select class="seg-type" onchange="changeSegType(${i}, this.value)">
                ${Object.entries(SEG_TYPES).map(([k,v]) => `<option value="${k}" ${seg.type===k?'selected':''}>${v.label}</option>`).join('')}
            </select>
            <input class="seg-value" type="text" value="${seg.value}"
                   placeholder="${SEG_TYPES[seg.type]?.placeholder || ''}"
                   ${seg.type === 'bulan' ? 'disabled' : ''}
                   oninput="segments[${i}].value=this.value;updatePreview()">
            <button class="seg-delete" onclick="deleteSegment(${i})">✕</button>`;
        
        // Drag event listeners
        row.addEventListener('dragstart', handleDragStart);
        row.addEventListener('dragend', handleDragEnd);
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('drop', handleDrop);
        row.addEventListener('dragenter', handleDragEnter);
        row.addEventListener('dragleave', handleDragLeave);
        
        list.appendChild(row);
    });
    updatePreview();
}

let draggedItem = null;

function handleDragStart(e) {
    draggedItem = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    document.querySelectorAll('.seg-row').forEach(row => row.classList.remove('drag-over'));
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    if (this !== draggedItem) this.classList.add('drag-over');
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    if (this !== draggedItem) {
        const fromIndex = parseInt(draggedItem.dataset.index);
        const toIndex = parseInt(this.dataset.index);
        
        // Swap segments
        const temp = segments[fromIndex];
        segments[fromIndex] = segments[toIndex];
        segments[toIndex] = temp;
        
        renderSegments();
    }
}

function addSegment() { segments.push({ type: 'teks', value: '' }); renderSegments(); }
function deleteSegment(i) { if (segments.length <= 1) { alert('Minimal 1 segmen!'); return; } segments.splice(i, 1); renderSegments(); }
function changeSegType(i, t) {
    segments[i].type  = t;
    segments[i].value = t === 'tahun' ? '{{ date("Y") }}' : t === 'bulan' ? '' : '';
    renderSegments();
}
function setSep(sep, btn) {
    separator = sep;
    document.querySelectorAll('.btn-sep').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('customSep').value = '';
    updatePreview();
}
function setSepCustom(val) { if (val) { separator = val; document.querySelectorAll('.btn-sep').forEach(b => b.classList.remove('active')); } updatePreview(); }
function genNomor(idx) {
    return segments.map(seg => {
        if (seg.type === 'nomor') {
            const start = parseInt(seg.value) || 1;
            const pad   = seg.value.length || 3;
            return String(start + idx).padStart(pad, '0');
        }
        if (seg.type === 'bulan') return String(new Date().getMonth() + 1).padStart(2, '0');
        return seg.value || '';
    }).filter(v => v !== '').join(separator);
}
function updatePreview() { document.getElementById('numPreview').textContent = genNomor(0) || '—'; }
renderSegments();

// ════ PESERTA ════
function renderPeserta() {
    const container = document.getElementById('pesertaList');
    container.innerHTML = '';
    pesertaList.forEach((p, i) => {
        const card = document.createElement('div');
        card.className = 'peserta-card';
        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span style="font-size:.72rem;font-weight:700;color:var(--navy-mid);text-transform:uppercase;letter-spacing:1px">Peserta ${i+1}</span>
                ${pesertaList.length > 1 ? `<button class="btn-del-peserta" onclick="deletePeserta(${i})">✕</button>` : ''}
            </div>
            <div class="mb-2">
                <label class="form-label-sm">Nama Peserta <span style="color:#ef4444">*</span></label>
                <input type="text" class="form-control form-control-sm" id="nama_${i}" value="${p.nama}" placeholder="Nama Lengkap Peserta" oninput="pesertaList[${i}].nama=this.value">
            </div>
            <div class="mb-2">
                <label class="form-label-sm">Perusahaan / Instansi <span style="font-size:.68rem;color:#9ca3af;font-weight:400;letter-spacing:0;text-transform:none"> — opsional</span></label>
                <input type="text" class="form-control form-control-sm" value="${p.perusahaan}" placeholder="Nama Perusahaan / Instansi" oninput="pesertaList[${i}].perusahaan=this.value">
            </div>
            <div>
                <label class="form-label-sm">Nomor Sertifikat <span style="font-size:.68rem;color:#9ca3af;font-weight:400;letter-spacing:0;text-transform:none"> — kosongkan untuk auto</span></label>
                <input type="text" class="form-control form-control-sm" value="${p.nomor}" placeholder="Kosongkan = auto-number" oninput="pesertaList[${i}].nomor=this.value">
            </div>`;
        container.appendChild(card);
    });
    document.getElementById('pesertaCount').textContent = pesertaList.length + ' peserta';
}

function addPeserta() { pesertaList.push({ nama: '', perusahaan: '', nomor: '' }); renderPeserta(); }
function deletePeserta(i) { pesertaList.splice(i, 1); renderPeserta(); }
renderPeserta();

// ════ TANGGAL ════
function fmtDate(dateStr) {
    if (!dateStr) return '';
    const d  = new Date(dateStr + 'T00:00:00');
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yy = String(d.getFullYear()).slice(2);
    return dd + '-' + mm + '-' + yy;
}
function getDateLine() {
    const place   = (document.getElementById('eventPlace').value || '').trim();
    const isMulti = document.getElementById('multiDayToggle').checked;
    if (isMulti) {
        const f = fmtDate(document.getElementById('dateFrom').value);
        const t = fmtDate(document.getElementById('dateTo').value);
        const ds = (f && t) ? f + ' until ' + t : (f || t);
        return 'Held on ' + ds + (place ? ' at ' + place : '');
    }
    const f = fmtDate(document.getElementById('dateStart').value);
    return 'Held on ' + f + (place ? ' at ' + place : '');
}
function toggleMultiDay() {
    const isMulti = document.getElementById('multiDayToggle').checked;
    document.getElementById('singleDayInput').classList.toggle('d-none', isMulti);
    document.getElementById('multiDayInput').classList.toggle('d-none', !isMulti);
    updateDatePreview();
}
function updateDatePreview() {
    const text = getDateLine();
    const prev = document.getElementById('datePreview');
    if (text && text !== 'Held on ') { prev.textContent = '📅 ' + text; prev.style.display = 'block'; }
    else prev.style.display = 'none';
}
document.getElementById('eventPlace').addEventListener('input', updateDatePreview);

// ════ DESC COUNTER ════
function updateDescCount(val) { document.getElementById('descCount').textContent = val.length + '/200'; }
updateDescCount(document.getElementById('certDesc').value);

// ════ TITLE CASE CONVERTER ════
function toTitleCase(str) {
    if (!str) return '';
    return str.trim().replace(/\b\w/g, char => char.toUpperCase()).replace(/\S*/g, word => {
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    });
}

// ════ GENERATE ════
async function generate() {
    // Sync nilai
    pesertaList.forEach((p, i) => {
        p.nama = (document.getElementById('nama_' + i)?.value || '').trim();
    });

    // Validasi — kumpulkan semua error dulu
    const eventName   = document.getElementById('eventName').value.trim();
    const eventPlace  = document.getElementById('eventPlace').value.trim();
    const signerName  = document.getElementById('signerName').value.trim();
    const signerTitle = document.getElementById('signerTitle').value.trim();
    const isMultiDay  = document.getElementById('multiDayToggle').checked;
    const dateStart   = document.getElementById('dateStart').value;
    const dateFrom    = document.getElementById('dateFrom')?.value;
    const dateTo      = document.getElementById('dateTo')?.value;

    const errors = [];
    if (!eventName)  errors.push('Nama acara wajib diisi');
    if (!eventPlace) errors.push('Tempat pelaksanaan wajib diisi');
    if (!isMultiDay && !dateStart) errors.push('Tanggal pelaksanaan wajib diisi');
    if (isMultiDay && (!dateFrom || !dateTo)) errors.push('Tanggal dari dan sampai wajib diisi');
    if (!signerName)  errors.push('Nama penandatangan wajib diisi');
    if (!signerTitle) errors.push('Jabatan penandatangan wajib diisi');

    // Cek peserta
    pesertaList.forEach(function(p, i) {
        if (!p.nama) errors.push('Nama peserta #' + (i+1) + ' wajib diisi');
    });

    // Cek segmen nomor kosong
    segments.forEach(function(seg, i) {
        if (seg.type === 'teks' && !seg.value.trim())
            errors.push('Segmen "Teks/Kode" #' + (i+1) + ' kosong — hapus jika tidak digunakan');
    });

    if (errors.length) {
        showNotif('Form belum lengkap', errors, 'error');
        return;
    }

    let autoIdx = 0;
    const participants = pesertaList.map(p => {
        return { nama: toTitleCase(p.nama), perusahaan:p.perusahaan, nomor: p.nomor || genNomor(autoIdx++) };
    });

    const btn = document.getElementById('btnGenerate');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await fetch('{{ route("certificate.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                participants,
                event_name:   eventName,
                event_date:   getDateLine(),
                event_place:  eventPlace,
                signer_name:  toTitleCase(document.getElementById('signerName').value),
                signer_title: toTitleCase(document.getElementById('signerTitle').value),
                cert_desc:    document.getElementById('certDesc').value,
            }),
        });

        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Gagal menyimpan');

        generatedCerts = data.certificates;
        renderResults(data.certificates);

    } catch (e) {
        showNotif('Gagal menyimpan', e.message, 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '✦ Generate Sertifikat';
    }
}

// ════ DOWNLOAD PDF dengan loading indicator ════
async function downloadPdf(btn, pdfUrl, nama) {
    btn.disabled = true;
    btn.classList.add('btn-dl-loading');
    btn.innerHTML = '<span class="spinner"></span>Menyiapkan PDF...';
    try {
        const res = await fetch(pdfUrl);
        if (!res.ok) throw new Error('Gagal mengambil PDF');
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'sertifikat_' + nama.toLowerCase().replace(/\s+/g, '_') + '.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (e) {
        showNotif('Download gagal', e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.classList.remove('btn-dl-loading');
        btn.innerHTML = '<i class="bi bi-file-earmark-pdf me-1"></i>Download PDF';
    }
}

// ════ PRE-GENERATE PDF di background ════
async function pregeneratePdf(token, statusEl) {
    statusEl.className = 'pdf-status loading';
    statusEl.textContent = '⏳ Menyiapkan PDF...';
    try {
        const res  = await fetch(`/certificate/pregenerate/${token}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (res.ok && data.success) {
            statusEl.className = 'pdf-status ready';
            statusEl.textContent = '✓ PDF siap diunduh';
        } else {
            statusEl.className = 'pdf-status';
            statusEl.textContent = '';
        }
    } catch (e) {
        statusEl.className = 'pdf-status';
        statusEl.textContent = '';
    }
}

function renderResults(certs) {
    const grid = document.getElementById('certGrid');
    grid.innerHTML = '';
    document.getElementById('results').classList.remove('d-none');
    document.getElementById('results').scrollIntoView({ behavior: 'smooth' });

    certs.forEach(cert => {
        const col      = document.createElement('div');
        col.className  = 'col-md-6';
        const statusId = 'status_' + cert.id;
        col.innerHTML  = `
            <div class="cert-card">
                <div class="cert-card-name">${cert.nama}</div>
                <div style="font-size:.75rem;color:#9ca3af;text-align:center;margin-bottom:10px">${cert.nomor}</div>
                <button class="btn-dl" onclick="downloadPdf(this, '${cert.pdf_url}', '${cert.nama}')">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                </button>
                <div class="pdf-status" id="${statusId}"></div>
            </div>`;
        grid.appendChild(col);

        // Pre-generate di background
        const statusEl = col.querySelector('#' + statusId);
        pregeneratePdf(cert.verification_token, statusEl);
    });
}
</script>
@endpush