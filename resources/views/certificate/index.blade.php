@extends('layouts.app')

@section('title', 'Generator Sertifikat')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
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
    .seg-row { display:flex; align-items:center; gap:6px; background:#f9fbff; border:1.5px solid #dde4f0; border-radius:8px; padding:6px 8px; margin-bottom:6px; cursor:grab; }
    .seg-drag-handle { color:#9ca3af; font-size:.9rem; cursor:grab; flex-shrink:0; user-select:none; padding:2px 4px; }
    .seg-type { border:1.5px solid #dde4f0; border-radius:6px; font-size:.75rem; padding:4px 6px; background:#fff; color:var(--navy); font-weight:600; width:110px; flex-shrink:0; }
    .seg-value { border:1.5px solid #dde4f0; border-radius:6px; font-size:.8rem; padding:4px 8px; background:#fff; flex:1; min-width:0; }
    .seg-value:disabled { background:#f0f4ff; color:#9ca3af; }
    .seg-delete { background:none; border:none; color:#f87171; font-size:1rem; cursor:pointer; padding:0 2px; flex-shrink:0; opacity:.7; }
    .btn-sep { background:#f0f4ff; border:1.5px solid #dde4f0; border-radius:6px; color:var(--navy); font-size:.85rem; font-weight:700; width:36px; height:32px; cursor:pointer; }
    .btn-sep.active { background:var(--navy); color:var(--gold-light); border-color:var(--navy); }

    /* Tabs */
    .gen-tabs { border-bottom:2px solid #eef2f9; margin-bottom:16px; gap:4px; display:flex; list-style:none; padding:0; margin-top:0; }
    .gen-tabs .nav-link { border:none; background:none; font-size:.82rem; font-weight:600; color:#9ca3af; padding:8px 16px; border-radius:8px 8px 0 0; cursor:pointer; transition:all .2s; border-bottom:2px solid transparent; margin-bottom:-2px; }
    .gen-tabs .nav-link.active { color:var(--navy-mid); border-bottom-color:var(--gold); background:#f9fbff; }

    /* File drop */
    .file-drop-zone { border:2px dashed #dde4f0; border-radius:12px; padding:28px 16px; text-align:center; cursor:pointer; transition:all .2s; background:#f9fbff; position:relative; }
    .file-drop-zone:hover { border-color:var(--navy-mid); background:#f0f4ff; }
    .file-drop-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }

    /* CSV Preview */
    .csv-preview { border:1px solid #dde4f0; border-radius:10px; overflow:hidden; margin-bottom:12px; font-size:.8rem; }
    .csv-preview table { margin:0; }
    .csv-preview thead { background:var(--navy); color:var(--gold-light); }

    /* Peserta */
    .peserta-card { background:#f9fbff; border:1.5px solid #dde4f0; border-radius:10px; padding:14px 16px; margin-bottom:10px; position:relative; }
    .btn-add-peserta { background:#f0f4ff; border:1.5px dashed #a5b4fc; border-radius:8px; color:var(--navy-mid); font-size:.78rem; font-weight:700; padding:7px 14px; cursor:pointer; }
    .btn-add-peserta:hover:not(:disabled) { background:var(--navy); border-color:var(--navy); color:var(--gold-light); }
    .btn-add-peserta:disabled { opacity:.5; cursor:not-allowed; }
    .btn-del-peserta { background:none; border:none; color:#f87171; font-size:1rem; cursor:pointer; padding:2px 4px; border-radius:5px; opacity:.7; position:absolute; top:14px; right:14px; }
    .btn-del-peserta:hover { opacity:1; background:#fee2e2; }
    .btn-sm-danger-outline { background:#fff0f0; border:1.5px solid #fca5a5; color:#b91c1c; border-radius:8px; padding:6px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }

    /* Generate */
    .btn-generate { background:linear-gradient(135deg,var(--navy),var(--navy-mid)); color:#fff; border:none; border-radius:10px; padding:12px 24px; font-weight:700; font-size:.85rem; letter-spacing:2px; text-transform:uppercase; width:100%; transition:all .2s; margin-top:8px; }
    .btn-generate:hover:not(:disabled) { opacity:.9; transform:translateY(-1px); color:var(--gold-light); }
    .btn-generate:disabled { opacity:.35; cursor:not-allowed; transform:none; }

    /* Results */
    #results { margin-top:32px; }
    .result-header { font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--navy); text-align:center; margin-bottom:20px; }
    .cert-card { background:#fff; border:1px solid #dde4f0; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(15,30,60,.06); }
    .cert-card-name { font-size:.95rem; color:var(--navy-mid); font-weight:700; margin:0 0 4px; }
    .cert-card-nomor { font-size:.75rem; color:#9ca3af; margin-bottom:12px; font-family:monospace; background:#f0f4ff; padding:2px 8px; border-radius:4px; display:inline-block; }
    .btn-dl { background:var(--navy); color:var(--gold-light); border:none; border-radius:7px; padding:9px; font-size:.78rem; font-weight:700; width:100%; cursor:pointer; transition:all .2s; text-decoration:none; display:block; text-align:center; }
    .btn-dl:hover { background:var(--navy-mid); color:var(--gold-light); }
    .pdf-status { font-size:.65rem; text-align:center; margin-top:5px; height:14px; color:#9ca3af; }
    .pdf-status.ready { color:#16a34a; }
    .pdf-status.loading { color:var(--navy-mid); }
    .btn-download-all { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; border:none; border-radius:9px; padding:10px 20px; font-weight:700; font-size:.82rem; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all .2s; }
    .btn-download-all:hover { opacity:.9; }

    /* Performa */
    .perf-badge { display:inline-flex; align-items:center; gap:5px; background:#f0f4ff; border:1px solid #dde4f0; border-radius:8px; padding:4px 10px; font-size:.72rem; color:var(--navy-mid); font-weight:600; }

    /* Notif */
    .notif-wrap { position:fixed; top:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:10px; max-width:340px; pointer-events:none; }
    .notif { background:#fff; border-radius:12px; padding:14px 18px; box-shadow:0 8px 28px rgba(0,0,0,.13); font-size:.85rem; display:flex; align-items:flex-start; gap:10px; animation:notifIn .25s ease; border-left:4px solid #ef4444; pointer-events:all; }
    .notif.warn { border-left-color:#f59e0b; }
    .notif.success { border-left-color:#16a34a; }
    .notif.info { border-left-color:var(--navy-mid); }
    .notif-icon { font-size:1rem; flex-shrink:0; margin-top:1px; }
    .notif-body { flex:1; }
    .notif-title { font-weight:700; color:var(--navy); margin-bottom:3px; font-size:.85rem; }
    .notif-msg { color:#6b7280; font-size:.78rem; line-height:1.55; }
    .notif-msg ul { margin:4px 0 0 14px; padding:0; }
    .notif-msg li { margin-bottom:2px; }
    .notif-close { background:none; border:none; color:#9ca3af; font-size:1.1rem; cursor:pointer; padding:0; line-height:1; flex-shrink:0; }
    @keyframes notifIn { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
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

    {{-- Pengaturan --}}
    <div class="gen-panel">
        <div class="gen-panel-title">⚙ Pengaturan Sertifikat</div>
        <div class="mb-3">
            <label class="form-label-sm">Nama Acara / Kegiatan <span style="color:#ef4444">*</span></label>
            <input type="text" class="form-control" id="eventName" placeholder="Nama Acara / Kegiatan">
        </div>
        <div class="mb-3">
            <label class="form-label-sm">Tempat Pelaksanaan</label>
            <input type="text" class="form-control" id="eventPlace" placeholder="Kota / Tempat">
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
                <input type="text" class="form-control" id="signerName" placeholder="Nama Lengkap">
            </div>
            <div class="col-6">
                <label class="form-label-sm">Jabatan <span style="color:#ef4444">*</span></label>
                <input type="text" class="form-control" id="signerTitle" placeholder="Jabatan">
            </div>
        </div>
    </div>

    {{-- Aset --}}
    <div class="gen-panel">
        <div class="gen-panel-title">🖊 Tanda Tangan, Cap & Logo</div>
        <div class="row g-2 mb-3">
            <div class="col-4">
                <label class="form-label-sm">Tanda Tangan</label>
                <div class="img-upload-box" style="position:relative">
                    <input type="file" accept="image/png,image/jpeg" onchange="uploadAsset('ttd', this)">
                    <div id="ttdPreview"><div class="upload-icon">✍</div><p><strong>Upload TTD</strong>PNG transparan</p></div>
                    <span class="upload-badge d-none" id="ttdBadge">✓</span>
                    <button type="button" class="btn-remove-img d-none" id="ttdRemove" onclick="removeAsset('ttd')">&times;</button>
                </div>
            </div>
            <div class="col-4">
                <label class="form-label-sm">Cap / Stempel</label>
                <div class="img-upload-box" style="position:relative">
                    <input type="file" accept="image/png,image/jpeg" onchange="uploadAsset('cap', this)">
                    <div id="capPreview"><div class="upload-icon">🔴</div><p><strong>Upload Cap</strong>PNG transparan</p></div>
                    <span class="upload-badge d-none" id="capBadge">✓</span>
                    <button type="button" class="btn-remove-img d-none" id="capRemove" onclick="removeAsset('cap')">&times;</button>
                </div>
            </div>
            <div class="col-4">
                <label class="form-label-sm">Logo Institusi</label>
                <div class="img-upload-box" style="position:relative">
                    <input type="file" accept="image/png,image/jpeg" onchange="uploadAsset('logo', this)">
                    <div id="logoPreview"><div class="upload-icon">🏛</div><p><strong>Upload Logo</strong>Tampil di atas</p></div>
                    <span class="upload-badge d-none" id="logoBadge">✓</span>
                    <button type="button" class="btn-remove-img d-none" id="logoRemove" onclick="removeAsset('logo')">&times;</button>
                </div>
            </div>
        </div>
        <div>
            <label class="form-label-sm">Background <span style="text-transform:none;letter-spacing:0;font-size:.68rem;color:#9ca3af;font-weight:400"> — opsional</span></label>
            <div class="d-flex align-items-center gap-3">
                <div class="img-upload-box" style="min-height:70px;flex:1;padding:10px">
                    <input type="file" accept=".jpg,.jpeg,.png" onchange="uploadAsset('background', this)" id="bgFileInput">
                    <div id="bgPreview"><div class="upload-icon" style="font-size:1.2rem">🖼</div><p><strong>Upload Background</strong>JPG / PNG, maks. 2MB</p></div>
                    <span class="upload-badge d-none" id="bgBadge">✓</span>
                </div>
                <button type="button" onclick="removeAsset('background')" id="btnRemoveBg" class="d-none btn-sm-danger-outline">
                    <i class="bi bi-x-circle me-1"></i>Hapus
                </button>
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

{{-- ════ KANAN: PESERTA ════ --}}
<div class="col-lg-7">
    <div class="gen-panel">
        <div class="gen-panel-title">👤 Data Peserta</div>

        <ul class="nav gen-tabs">
            <li class="nav-item"><button class="nav-link active" onclick="switchTab('manual', this)">✏ Input Manual</button></li>
            <li class="nav-item"><button class="nav-link" onclick="switchTab('upload', this)">📂 Upload Excel/CSV</button></li>
        </ul>

        {{-- TAB MANUAL --}}
        <div id="tabManual">
            <div id="pesertaList"></div>
            <div class="d-flex align-items-center gap-2 mb-3">
                <button type="button" class="btn-add-peserta" id="btnAddPeserta" onclick="addPeserta()">
                    <i class="bi bi-person-plus me-1"></i>Tambah Peserta
                </button>
                <span class="text-muted" id="pesertaCount" style="font-size:.75rem"></span>
            </div>
            <div id="infoMaxPeserta" class="d-none mb-3 px-3 py-2 rounded" style="background:#fffbeb;border:1px solid #fde68a;font-size:.8rem;color:#92400e">
                <i class="bi bi-info-circle me-1"></i>Untuk lebih dari 4 peserta, gunakan
                <button onclick="switchTab('upload', document.querySelectorAll('.gen-tabs .nav-link')[1])" style="background:none;border:none;color:#b45309;font-weight:700;cursor:pointer;padding:0;text-decoration:underline">Upload Excel/CSV</button>.
            </div>
            <button class="btn-generate" id="btnGenerate" onclick="generateManual()">✦ Generate Sertifikat</button>
        </div>

        {{-- TAB UPLOAD --}}
        <div id="tabUpload" class="d-none">
            <div class="file-drop-zone mb-3">
                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" onchange="handleFile(event)">
                <div style="font-size:2rem;margin-bottom:8px">📁</div>
                <p class="mb-1"><strong>Klik atau drag file di sini</strong></p>
                <p class="text-muted" style="font-size:.8rem">Format: Excel (.xlsx/.xls) atau CSV</p>
                <p class="mt-2" style="font-size:.72rem;color:#aaa">
                    Kolom wajib: <strong style="color:var(--navy-mid)">nama</strong> &middot;
                    Opsional: <strong style="color:var(--navy-mid)">perusahaan</strong>, <strong style="color:var(--navy-mid)">nomor</strong>
                </p>
            </div>
            <div id="perfEstimate" class="mb-3 d-none">
                <span class="perf-badge"><i class="bi bi-speedometer2 me-1"></i><span id="perfText"></span></span>
            </div>
            <a href="https://1drv.ms/x/c/26cb04c16980a1e6/IQBkTqw-eSeWS4gi8znVJZfFAYse5klT7kxBuZzv0qgpxyc?e=I9y04P" target="_blank" style="font-size:0.72rem;color:var(--navy-mid);font-weight:600;text-decoration:none;display:inline-block;margin-top:4px" onclick="event.stopPropagation()">
                <i class="bi bi-file-earmark-excel me-1"></i>Unduh Contoh Format Excel
            </a>
            <div id="previewTable"></div>
            <p class="text-muted" style="font-size:.78rem" id="fileInfo"></p>
            <button class="btn-generate" id="btnGenAll" disabled onclick="generateAll()">✦ Generate Semua Sertifikat</button>
        </div>
    </div>

    {{-- HASIL --}}
    <div id="results" class="d-none">
        <div class="result-header">✦ Hasil Sertifikat</div>
        <div class="d-flex gap-2 flex-wrap mb-3 align-items-center" id="resultTopActions">
            <button class="btn-download-all" id="btnDownloadZip" style="display:none" onclick="downloadZipFromServer()">
                <i class="bi bi-file-earmark-zip me-1"></i>Download ZIP (PDF Semua)
            </button>
        </div>
        {{-- Info jumlah sertifikat --}}
            <div id="batchSummaryInfo" style="display:none; background:#fffbeb; border:1px solid #fcd34d; border-radius:10px; font-size:.82rem; color:#92400e; padding:10px 14px; margin-bottom:12px">
                <i class="bi bi-info-circle me-1"></i>
                <span id="batchSummaryText"></span>
            </div>
        <div class="row g-3" id="certGrid"></div>
    </div>
</div>
</div>

{{-- Modal Progress Batch --}}
<div class="modal fade" id="modalBatchProgress" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
            <div class="modal-body p-4">
                {{-- Header --}}
                <div class="text-center mb-4">
                    <div style="font-size:2rem;margin-bottom:8px" id="modalIcon">⚙️</div>
                    <h5 class="fw-bold mb-1" style="color:var(--navy)" id="modalTitle">Memproses Sertifikat...</h5>
                    <p class="text-muted mb-0" style="font-size:.82rem" id="modalSubtitle">Harap tunggu, jangan tutup halaman ini.</p>
                </div>

                {{-- Progress bar --}}
                <div class="mb-3">
                    <div class="progress" style="height:12px;border-radius:8px;background:#eef2f9">
                        <div class="progress-bar" id="batchProgressBar"
                             style="background:linear-gradient(90deg,var(--navy),var(--gold));border-radius:8px;width:0%;transition:width .4s ease">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size:.78rem;color:#6b7280">
                        <span id="batchProgressText">Memulai...</span>
                        <span id="batchProgressPct" style="font-weight:800;color:var(--navy);font-size:.9rem">0%</span>
                    </div>
                </div>

                {{-- Stats grid --}}
                <div class="row g-2 mb-3">
                    <div class="col-3 text-center p-2" style="background:#f8fafc;border-radius:8px">
                        <div style="font-size:1.3rem;font-weight:800;color:var(--navy)" id="statTotal">0</div>
                        <div style="font-size:.65rem;color:#9ca3af;font-weight:600">TOTAL</div>
                    </div>
                    <div class="col-3 text-center p-2" style="background:#f0fdf4;border-radius:8px">
                        <div style="font-size:1.3rem;font-weight:800;color:#16a34a" id="statDone">0</div>
                        <div style="font-size:.65rem;color:#9ca3af;font-weight:600">BERHASIL</div>
                    </div>
                    <div class="col-3 text-center p-2" style="background:#fef2f2;border-radius:8px">
                        <div style="font-size:1.3rem;font-weight:800;color:#ef4444" id="statFailed">0</div>
                        <div style="font-size:.65rem;color:#9ca3af;font-weight:600">GAGAL</div>
                    </div>
                    <div class="col-3 text-center p-2" style="background:#f0f4ff;border-radius:8px">
                        <div style="font-size:1.3rem;font-weight:800;color:var(--navy-mid)" id="statCached">0</div>
                        <div style="font-size:.65rem;color:#9ca3af;font-weight:600">PDF DI CACHE</div>
                    </div>
                </div>

                {{-- Info estimasi --}}
                <div id="etaInfo" class="mb-3 px-3 py-2 rounded d-none" style="background:#f9fbff;border:1px solid #dde4f0;font-size:.8rem;color:var(--navy-mid)">
                    <div class="d-flex justify-content-between">
                        <span>⏱ Estimasi selesai:</span>
                        <strong id="etaText">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span>🚀 Kecepatan:</span>
                        <strong id="speedText">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span>⏰ Sudah berjalan:</span>
                        <strong id="elapsedText">—</strong>
                    </div>
                </div>

                {{-- Tip performa --}}
                <div id="perfTip" class="mb-3 px-3 py-2 rounded d-none" style="background:#fffbeb;border:1px solid #fde68a;font-size:.75rem;color:#92400e">
                    <i class="bi bi-lightbulb me-1"></i><span id="perfTipText"></span>
                </div>

                {{-- Failed entries --}}
                <div id="failedList" class="d-none mb-3">
                    <div style="font-size:.75rem;font-weight:700;color:#ef4444;margin-bottom:6px">
                        <i class="bi bi-exclamation-triangle me-1"></i>Peserta yang gagal:
                    </div>
                    <div id="failedEntries" style="font-size:.78rem;color:#374151;max-height:100px;overflow-y:auto;background:#fef2f2;border-radius:8px;padding:8px 12px"></div>
                </div>

                {{-- Done actions --}}
                <div id="batchDoneActions" class="d-none">
                    <hr class="my-3">
                    <div class="text-center mb-3">
                        <div style="font-size:1.5rem">✅</div>
                        <p class="fw-bold mb-0" style="color:var(--navy);font-size:.9rem" id="doneMessage">Batch selesai diproses!</p>
                        <p class="text-muted mb-0" style="font-size:.78rem" id="doneSummary"></p>
                    </div>
                    <button class="btn w-100" data-bs-dismiss="modal"
                            style="background:var(--navy);color:var(--gold-light);border:none;border-radius:9px;font-size:.85rem;font-weight:600;padding:10px">
                        Tutup & Lihat Hasil
                    </button>
                </div>

                {{-- Stuck actions --}}
                <div id="batchStuckActions" class="d-none">
                    <hr class="my-3">
                    <p class="text-center text-muted mb-3" style="font-size:.8rem">
                        Proses berhenti. Jalankan ulang worker lalu klik tombol di bawah.
                    </p>
                    <button class="btn w-100 mb-2" onclick="forceCheckBatch()"
                            style="background:#f59e0b;color:#fff;border:none;border-radius:9px;font-size:.85rem;font-weight:600;padding:10px">
                        🔄 Cek Ulang Progress
                    </button>
                    <button class="btn w-100" data-bs-dismiss="modal"
                            style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:9px;font-size:.85rem;font-weight:600;padding:10px">
                        Tutup (lihat hasil sebagian)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ════ NOTIF ════
function showNotif(title, messages, type, duration) {
    type = type || 'error'; duration = duration !== undefined ? duration : 5000;
    const wrap = document.getElementById('notifWrap');
    const icons = { error:'⚠', warn:'⚠', success:'✓', info:'ℹ' };
    const notif = document.createElement('div');
    notif.className = 'notif ' + (type === 'error' ? '' : type);
    let msgHtml = Array.isArray(messages) && messages.length
        ? '<ul>' + messages.map(m => '<li>' + m + '</li>').join('') + '</ul>'
        : (typeof messages === 'string' ? '<div>' + messages + '</div>' : '');
    notif.innerHTML = '<div class="notif-icon">' + (icons[type]||'⚠') + '</div><div class="notif-body"><div class="notif-title">' + title + '</div><div class="notif-msg">' + msgHtml + '</div></div><button class="notif-close" onclick="this.closest(\'.notif\').remove()">✕</button>';
    wrap.appendChild(notif);
    if (duration > 0) setTimeout(() => notif.remove(), duration);
}

// ════ STATE ════
let pesertaList = [{ nama:'', perusahaan:'', nomor:'' }];
let parsedData = [];
let segments = [{ type:'teks', value:'CERT' }, { type:'nomor', value:'001' }, { type:'tahun', value:'{{ date("Y") }}' }];
let separator = '/';
let draggedItem = null;
const MAX_PESERTA = 4;

// ════ ASSET UPLOAD ════
async function uploadAsset(type, input) {
    const file = input.files[0]; if (!file) return;
    if (!['image/jpeg','image/jpg','image/png'].includes(file.type)) { showNotif('Format tidak didukung', 'Gunakan PNG atau JPG.', 'warn'); input.value=''; return; }
    if (file.size > 2*1024*1024) { showNotif('File terlalu besar', 'Maksimal 2MB.', 'warn'); input.value=''; return; }
    const formData = new FormData(); formData.append('type', type); formData.append('file', file);
    try {
        const res = await fetch('{{ route("certificate.asset.upload") }}', { method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body:formData });
        const data = await res.json();
        if (!res.ok) { showNotif('Upload gagal', data.message||'Error', 'error'); input.value=''; return; }
        if (data.url) showAssetPreview(type, data.url);
    } catch(e) { showNotif('Upload gagal', e.message, 'error'); input.value=''; }
}

async function removeAsset(type) {
    await fetch('{{ route("certificate.asset.remove") }}', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify({type}) }).catch(()=>{});
    clearAssetPreview(type);
}

function showAssetPreview(type, url) {
    const key = type==='background' ? 'bg' : type;
    const prev = document.getElementById(key+'Preview');
    if (prev) { const c = document.createElement('div'); c.className='img-preview-container'; const img=document.createElement('img'); img.src=url; c.appendChild(img); prev.innerHTML=''; prev.appendChild(c); }
    document.getElementById(key+'Badge')?.classList.remove('d-none');
    if (type==='background') document.getElementById('btnRemoveBg')?.classList.remove('d-none');
    else document.getElementById(type+'Remove')?.classList.remove('d-none');
}

function clearAssetPreview(type) {
    const key = type==='background'?'bg':type;
    const icons={ttd:'✍',cap:'🔴',logo:'🏛',bg:'🖼'}, labels={ttd:'Upload TTD',cap:'Upload Cap',logo:'Upload Logo',bg:'Upload Background'}, subs={ttd:'PNG transparan',cap:'PNG transparan',logo:'Tampil di atas',bg:'JPG / PNG, maks. 2MB'};
    const prev = document.getElementById(key+'Preview');
    if (prev) prev.innerHTML = `<div class="upload-icon">${icons[key]}</div><p><strong>${labels[key]}</strong>${subs[key]}</p>`;
    document.getElementById(key+'Badge')?.classList.add('d-none');
    if (type==='background') { document.getElementById('btnRemoveBg')?.classList.add('d-none'); document.getElementById('bgFileInput').value=''; }
    else document.getElementById(type+'Remove')?.classList.add('d-none');
}

async function loadAssets() {
    try {
        const res = await fetch('{{ route("certificate.asset.get") }}');
        const data = await res.json();
        ['logo','ttd','cap','background'].forEach(t => { if(data[t]) showAssetPreview(t, data[t]); });
    } catch(e) {}
}
loadAssets();

// ════ NUMBERING ════
const SEG_TYPES = { teks:{label:'Teks / Kode',placeholder:'Contoh: CERT'}, nomor:{label:'Nomor Urut',placeholder:'Mulai dari: 001'}, tahun:{label:'Tahun',placeholder:'Contoh: 2026'}, bulan:{label:'Bulan',placeholder:'Otomatis'} };

function renderSegments() {
    const list = document.getElementById('segmentList'); list.innerHTML = '';
    segments.forEach((seg, i) => {
        const row = document.createElement('div'); row.className='seg-row'; row.draggable=true; row.dataset.index=i;
        row.innerHTML = `<div class="seg-drag-handle">≡</div>
            <select class="seg-type" onchange="changeSegType(${i},this.value)">${Object.entries(SEG_TYPES).map(([k,v])=>`<option value="${k}" ${seg.type===k?'selected':''}>${v.label}</option>`).join('')}</select>
            <input class="seg-value" type="text" value="${seg.type==='bulan'?getMonth():seg.value}" placeholder="${SEG_TYPES[seg.type]?.placeholder||''}" ${seg.type==='bulan'?'disabled':''} oninput="segments[${i}].value=this.value;updatePreview()">
            <button class="seg-delete" onclick="deleteSegment(${i})">✕</button>`;
        row.addEventListener('dragstart', ()=>{ draggedItem=row; setTimeout(()=>row.style.opacity='.5',0); });
        row.addEventListener('dragend', ()=>{ row.style.opacity=''; document.querySelectorAll('.seg-row').forEach(r=>r.style.background=''); });
        row.addEventListener('dragover', e=>{ e.preventDefault(); });
        row.addEventListener('drop', e=>{ e.preventDefault(); if(draggedItem&&draggedItem!==row){ const fi=parseInt(draggedItem.dataset.index),ti=parseInt(row.dataset.index); const tmp=segments[fi]; segments[fi]=segments[ti]; segments[ti]=tmp; renderSegments(); } });
        list.appendChild(row);
    });
    updatePreview();
}

function getMonth() { return String(new Date().getMonth()+1).padStart(2,'0'); }
function addSegment() { segments.push({type:'teks',value:''}); renderSegments(); }
function deleteSegment(i) { if(segments.length<=1){alert('Minimal 1 segmen!');return;} segments.splice(i,1); renderSegments(); }
function changeSegType(i,t) { segments[i].type=t; segments[i].value=t==='tahun'?'{{ date("Y") }}':t==='nomor'?'001':''; renderSegments(); }
function setSep(sep,btn) { separator=sep; document.querySelectorAll('.btn-sep').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); document.getElementById('customSep').value=''; updatePreview(); }
function setSepCustom(val) { if(val){separator=val; document.querySelectorAll('.btn-sep').forEach(b=>b.classList.remove('active'));} updatePreview(); }
function genNomor(idx) {
    return segments.map(seg=>{
        if(seg.type==='nomor'){const s=parseInt(seg.value)||1;const pad=seg.value.length||3;return String(s+idx).padStart(pad,'0');}
        if(seg.type==='bulan') return getMonth();
        return seg.value||'';
    }).filter(v=>v!=='').join(separator);
}
function updatePreview() { document.getElementById('numPreview').textContent=genNomor(0)||'—'; }
renderSegments();

// ════ TABS ════
function switchTab(tab, el) {
    document.querySelectorAll('.gen-tabs .nav-link').forEach(b=>b.classList.remove('active')); el.classList.add('active');
    document.getElementById('tabManual').classList.toggle('d-none', tab!=='manual');
    document.getElementById('tabUpload').classList.toggle('d-none', tab!=='upload');
}

// ════ PESERTA MANUAL ════
function renderPeserta() {
    const container = document.getElementById('pesertaList'); container.innerHTML='';
    pesertaList.forEach((p,i)=>{
        const card=document.createElement('div'); card.className='peserta-card';
        card.innerHTML=`<div style="font-size:.72rem;font-weight:700;color:var(--navy-mid);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px">Peserta ${i+1}</div>
            ${pesertaList.length>1?`<button class="btn-del-peserta" onclick="deletePeserta(${i})">✕</button>`:''}
            <div class="mb-2"><label class="form-label-sm">Nama <span style="color:#ef4444">*</span></label><input type="text" class="form-control form-control-sm" id="nama_${i}" value="${p.nama}" placeholder="Nama Lengkap" oninput="pesertaList[${i}].nama=this.value"></div>
            <div class="mb-2"><label class="form-label-sm">Perusahaan / Instansi <span style="font-size:.68rem;color:#9ca3af;font-weight:400;letter-spacing:0;text-transform:none"> — opsional</span></label><input type="text" class="form-control form-control-sm" value="${p.perusahaan}" placeholder="Opsional" oninput="pesertaList[${i}].perusahaan=this.value"></div>
            <div><label class="form-label-sm">Nomor <span style="font-size:.68rem;color:#9ca3af;font-weight:400;letter-spacing:0;text-transform:none"> — kosongkan untuk auto</span></label><input type="text" class="form-control form-control-sm" value="${p.nomor}" placeholder="Kosongkan = auto-number" oninput="pesertaList[${i}].nomor=this.value"></div>`;
        container.appendChild(card);
    });
    const isFull = pesertaList.length>=MAX_PESERTA;
    document.getElementById('btnAddPeserta').disabled=isFull;
    document.getElementById('pesertaCount').textContent=pesertaList.length+' / '+MAX_PESERTA+' peserta';
    document.getElementById('infoMaxPeserta').classList.toggle('d-none',!isFull);
}
function addPeserta() { if(pesertaList.length>=MAX_PESERTA)return; pesertaList.push({nama:'',perusahaan:'',nomor:''}); renderPeserta(); }
function deletePeserta(i) { pesertaList.splice(i,1); renderPeserta(); }
renderPeserta();

// ════ FILE UPLOAD ════
function handleFile(e) {
    const file=e.target.files[0]; if(!file) return;
    const reader=new FileReader();
    reader.onload=ev=>{
        let data=[];
        if(file.name.endsWith('.csv')){
            const lines=ev.target.result.trim().split('\n'), headers=lines[0].split(',').map(h=>h.trim().toLowerCase());
            for(let i=1;i<lines.length;i++){const cols=lines[i].split(',').map(c=>c.trim());const row={};headers.forEach((h,idx)=>row[h]=cols[idx]||'');if(row.nama)data.push(row);}
        } else {
            const wb=XLSX.read(ev.target.result,{type:'binary'}), ws=wb.Sheets[wb.SheetNames[0]];
            const json=XLSX.utils.sheet_to_json(ws,{defval:''});
            data=json.map(r=>{const n={};Object.keys(r).forEach(k=>n[k.toLowerCase().trim()]=String(r[k]).trim());return n;}).filter(r=>r.nama);
        }
        
        // Deteksi duplikat berdasarkan nama + perusahaan
        const totalBaris = data.length;
        const seen = new Set();
        const unique = [];
        let duplikatCount = 0;
        
        data.forEach(row => {
            const key = (row.nama || '').trim() + '|' + (row.perusahaan || '').trim();
            if (!seen.has(key)) {
                seen.add(key);
                unique.push(row);
            } else {
                duplikatCount++;
            }
        });
        
        parsedData=unique; 
        showPreview(unique); 
        updatePerfEstimate(unique.length);
        document.getElementById('btnGenAll').disabled=unique.length===0;
        
        // Info dengan detail duplikat
        let infoText = `📊 ${totalBaris} baris dalam file`;
        if (duplikatCount > 0) {
            infoText += ` → ${unique.length} unik (<strong>${duplikatCount} duplikat dihapus</strong>)`;
        } else {
            infoText += ` → <strong>Semua unik ✓</strong>`;
        }

        infoText += ` dari "${file.name}"`;

        document.getElementById('fileInfo').innerHTML = infoText;
    };
    file.name.endsWith('.csv')?reader.readAsText(file):reader.readAsBinaryString(file);
}

function showPreview(data) {
    if(!data.length) return;
    let html=`<div class="csv-preview"><table class="table table-sm table-hover mb-0"><thead><tr><th>#</th><th>Nama</th><th>Perusahaan</th><th>Nomor</th></tr></thead><tbody>`;
    data.slice(0,6).forEach((row,i)=>{
        const nCell=(row.nomor&&row.nomor.trim())?`<strong>${row.nomor}</strong>`:`<em class="text-muted">${genNomor(i)} (auto)</em>`;
        const pCell=(row.perusahaan&&row.perusahaan.trim())?row.perusahaan:`<span class="text-muted">—</span>`;
        html+=`<tr><td>${i+1}</td><td>${row.nama}</td><td>${pCell}</td><td>${nCell}</td></tr>`;
    });
    if(data.length>6) html+=`<tr><td colspan="4" class="text-center text-muted">... +${data.length-6} lainnya</td></tr>`;
    html+='</tbody></table></div>';
    document.getElementById('previewTable').innerHTML=html;
}

function updatePerfEstimate(count) {
    const el=document.getElementById('perfEstimate'), txt=document.getElementById('perfText');
    if(count<=1){el.classList.add('d-none');return;}
    el.classList.remove('d-none');
    if(count<=30) txt.textContent=`${count} peserta — batch langsung, ~${Math.ceil(count*0.5)}s`;
    else { const s=Math.ceil(count*0.3); txt.textContent=`${count} peserta — via queue, estimasi ${s>60?Math.ceil(s/60)+' menit':s+' detik'}`; }
}

// ════ TANGGAL ════
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
function ordinal(n) { const s=['th','st','nd','rd']; const v=n%100; return n+(s[(v-20)%10]||s[v]||s[0]); }
function fmtDateHuman(d) {
    if(!d) return '';
    const dt = new Date(d+'T00:00:00');
    return MONTHS[dt.getMonth()] + ' ' + ordinal(dt.getDate()) + ', ' + dt.getFullYear();
}
function fmtDateShort(d) {
    // Format ringkas untuk preview: "30 June 2025"
    if(!d) return '';
    const dt = new Date(d+'T00:00:00');
    return ordinal(dt.getDate()) + ' ' + MONTHS[dt.getMonth()] + ' ' + dt.getFullYear();
}
function getDatePreviewText() {
    const place = (document.getElementById('eventPlace').value||'').trim();
    const isMulti = document.getElementById('multiDayToggle').checked;
    if(isMulti){
        const f=fmtDateShort(document.getElementById('dateFrom').value);
        const t=fmtDateShort(document.getElementById('dateTo').value);
        const ds=(f&&t)?f+' – '+t:(f||t||'');
        if(!ds) return '';
        return 'Held on '+ds+(place?' in '+place:'');
    }
    const s=fmtDateShort(document.getElementById('dateStart').value);
    if(!s) return '';
    return 'Held on '+s+(place?' in '+place:'');
}
function getDatePayload() {
    // Kembalikan object terpisah — bukan satu string kalimat
    const isMulti = document.getElementById('multiDayToggle').checked;
    if(isMulti){
        return {
            date_start: document.getElementById('dateFrom').value,
            date_end:   document.getElementById('dateTo').value,
        };
    }
    return {
        date_start: document.getElementById('dateStart').value,
        date_end:   '',
    };
}
function toggleMultiDay() { const m=document.getElementById('multiDayToggle').checked; document.getElementById('singleDayInput').classList.toggle('d-none',m); document.getElementById('multiDayInput').classList.toggle('d-none',!m); updateDatePreview(); }
function updateDatePreview() { const t=getDatePreviewText(),p=document.getElementById('datePreview'); if(t){p.textContent='📅 '+t;p.style.display='block';}else p.style.display='none'; }
document.getElementById('eventPlace').addEventListener('input', updateDatePreview);
function updateDescCount(val) { document.getElementById('descCount').textContent=val.length+'/200'; }
updateDescCount(document.getElementById('certDesc').value);
function toTitleCase(str) { return str.trim().replace(/\b\w/g, c=>c.toUpperCase()); }

// ════ VALIDASI ════
function validateForm() {
    const errors=[];
    if(!document.getElementById('eventName').value.trim()) errors.push('Nama acara wajib diisi');
    if(!document.getElementById('signerName').value.trim()) errors.push('Nama penandatangan wajib diisi');
    if(!document.getElementById('signerTitle').value.trim()) errors.push('Jabatan penandatangan wajib diisi');
    const isMulti=document.getElementById('multiDayToggle').checked;
    if(!isMulti&&!document.getElementById('dateStart').value) errors.push('Tanggal pelaksanaan wajib diisi');
    if(isMulti&&(!document.getElementById('dateFrom').value||!document.getElementById('dateTo').value)) errors.push('Tanggal dari & sampai wajib diisi');
    segments.forEach((seg,i)=>{ if(seg.type==='teks'&&!seg.value.trim()) errors.push('Segmen teks #'+(i+1)+' kosong'); });
    return errors;
}

// ════ GENERATE MANUAL ════
async function generateManual() {
    pesertaList.forEach((p,i)=>{ p.nama=(document.getElementById('nama_'+i)?.value||'').trim(); });
    const errors=validateForm();
    pesertaList.forEach((p,i)=>{ if(!p.nama) errors.push('Nama peserta #'+(i+1)+' wajib diisi'); });
    if(errors.length){showNotif('Form belum lengkap',errors,'error');return;}

    let autoIdx=0;
    const participants=pesertaList.map(p=>({nama:toTitleCase(p.nama),perusahaan:p.perusahaan,nomor:p.nomor||genNomor(autoIdx++)}));
    const btn=document.getElementById('btnGenerate'); btn.disabled=true; btn.textContent='Memproses...';
    try { await doGenerate(participants); } finally { btn.disabled=false; btn.innerHTML='✦ Generate Sertifikat'; }
}

// ════ GENERATE ALL ════
async function generateAll() {
    if(!parsedData.length) return;
    const errors=validateForm(); if(errors.length){showNotif('Form belum lengkap',errors,'error');return;}
    const participants=parsedData.map((item,i)=>({nama:toTitleCase(item.nama),perusahaan:item.perusahaan||'',nomor:(item.nomor&&item.nomor.trim())?item.nomor.trim():genNomor(i)}));
    const btn=document.getElementById('btnGenAll'); btn.disabled=true; btn.textContent='Memproses...';
    try { await doGenerate(participants); } finally { btn.disabled=false; btn.innerHTML='✦ Generate Semua Sertifikat'; }
}

// ════ CORE GENERATE ════
async function doGenerate(participants) {
    const csrf=document.querySelector('meta[name="csrf-token"]').content;
    const datePayload = getDatePayload();
    const commonPayload={
        event_name:  document.getElementById('eventName').value.trim(),
        date_start:  datePayload.date_start,
        date_end:    datePayload.date_end,
        event_place: document.getElementById('eventPlace').value.trim(),
        signer_name: toTitleCase(document.getElementById('signerName').value),
        signer_title: toTitleCase(document.getElementById('signerTitle').value),
        cert_desc:   document.getElementById('certDesc').value,
    };

    const grid=document.getElementById('certGrid');
    grid.innerHTML='<div class="col-12 text-center py-4"><div class="spinner-border" style="color:var(--navy);width:2rem;height:2rem" role="status"></div><div class="mt-2 text-muted" style="font-size:.85rem">Memproses...</div></div>';
    document.getElementById('results').classList.remove('d-none');
    document.getElementById('results').scrollIntoView({behavior:'smooth'});

    if(participants.length===1) {
        const p=participants[0];
        try {
            const res=await fetch('{{ route("certificate.store") }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify({...commonPayload,nama:p.nama,perusahaan:p.perusahaan||null,nomor:p.nomor})});
            const data=await res.json(); if(!res.ok) throw new Error(data.message||'Gagal');
            renderSingleResult(p, data.pdf_url, data.verification_token);
        } catch(e) { showNotif('Gagal',e.message,'error'); grid.innerHTML=''; }
    } else {
        try {
            const res=await fetch('{{ route("certificate.batch.store") }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify({...commonPayload,participants})});
            const data=await res.json(); if(!res.ok) throw new Error(data.message||'Gagal');
            window._currentBatchToken=data.batch_token;
            showBatchProgress(data.batch_token, data.total);
        } catch(e) { showNotif('Gagal',e.message,'error'); grid.innerHTML=''; }
    }
}

// ════ WAIT BATCH ════
async function waitBatchDone(token) {
    return new Promise(resolve=>{
        const iv=setInterval(async()=>{
            try { const r=await fetch(`{{ url('/dashboard/certificates/batch') }}/${token}/progress`); const d=await r.json(); if(d.status==='done'||d.status==='failed'){clearInterval(iv);resolve(d);} } catch{clearInterval(iv);resolve(null);}
        },1500);
    });
}

// ════ RENDER SINGLE ════
function renderSingleResult(p, pdfUrl, token) {
    const grid=document.getElementById('certGrid'); grid.innerHTML='';
    const col=document.createElement('div'); col.className='col-md-6';
    col.innerHTML=`<div class="cert-card"><div class="cert-card-name">${p.nama}</div><div class="cert-card-nomor">${p.nomor}</div><button class="btn-dl" onclick="downloadPdf(this,'${pdfUrl}','${p.nama}')"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button></div>`;
    grid.appendChild(col);
    document.getElementById('btnDownloadZip').style.display='none';
}

// ════ RENDER BATCH ════
// ════ RENDER BATCH ════
async function renderBatchResult(batchToken) {
    try {
        const res  = await fetch(`{{ url('/dashboard/certificates/batch') }}/${batchToken}/certs`);
        const data = await res.json();
        if (!data.certificates || !data.certificates.length) return;

        const grid  = document.getElementById('certGrid');
        const total = data.certificates.length;
        grid.innerHTML = '';

        // Tampilkan semua sertifikat — tidak ada limit lagi
        data.certificates.forEach(cert => {
            const col = document.createElement('div');
            col.className = 'col-md-6';
            col.innerHTML = `<div class="cert-card">
                <div class="cert-card-name">${cert.nama}</div>
                <div class="cert-card-nomor">${cert.nomor}</div>
                <button class="btn-dl" onclick="downloadPdf(this,'${cert.pdf_url}','${cert.nama}')">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                </button>
            </div>`;
            grid.appendChild(col);
        });

        // Tampilkan info jumlah di bawah tombol ZIP
        const infoEl   = document.getElementById('batchSummaryInfo');
        const infoText = document.getElementById('batchSummaryText');
        infoText.innerHTML = `Menampilkan <strong>${total}</strong> sertifikat yang berhasil digenerate.`;
        infoEl.style.display = 'block';

    } catch(e) { console.warn('Gagal render batch:', e); }
}

// ════ PRE-GENERATE PDF ════
async function pregeneratePdf(token, statusEl) {
    if(statusEl){statusEl.className='pdf-status loading';statusEl.textContent='⏳ Menyiapkan PDF...';}
    try {
        const res=await fetch(`{{ url('/dashboard/certificates/pregenerate') }}/${token}`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}});
        const data=await res.json();
        if(res.ok&&data.success){if(statusEl){statusEl.className='pdf-status ready';statusEl.textContent='✓ PDF siap';}}
        else{if(statusEl){statusEl.className='pdf-status';statusEl.textContent='';}}
    } catch(e){if(statusEl){statusEl.className='pdf-status';statusEl.textContent='';}}
}

// ════ DOWNLOAD PDF ════
async function downloadPdf(btn, pdfUrl, nama) {
    btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyiapkan...';
    try {
        const res=await fetch(pdfUrl); if(!res.ok) throw new Error('Gagal mengambil PDF');
        const blob=await res.blob(); const url=URL.createObjectURL(blob);
        const a=document.createElement('a'); a.href=url; a.download='sertifikat_'+nama.toLowerCase().replace(/\s+/g,'_')+'.pdf';
        document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
    } catch(e){showNotif('Download gagal',e.message,'error');}
    finally{btn.disabled=false;btn.innerHTML='<i class="bi bi-file-earmark-pdf me-1"></i>Download PDF';}
}

// ════ FORCE CHECK (untuk batch yang stuck) ════
function forceCheckBatch() {
    if (!window._currentBatchToken) return;
    stuckCounter = 0;
    document.getElementById('batchStuckActions').classList.add('d-none');
    document.getElementById('perfTip').classList.add('d-none');
    document.getElementById('batchProgressText').textContent = 'Mengecek ulang...';

    // Reset polling jika sudah berhenti
    if (!pollingInterval) {
        pollingInterval = setInterval(() => pollBatchProgress(window._currentBatchToken), 1500);
    }
}

async function downloadZipFromServer() {
    if(!window._currentBatchToken) return;
    const btn=document.getElementById('btnDownloadZip'), orig=btn.innerHTML;
    btn.disabled=true; btn.innerHTML='<i class="bi bi-hourglass-split me-1"></i>Membuat ZIP...';

    try {
        const res = await fetch(`{{ url('/dashboard/certificates/batch') }}/${window._currentBatchToken}/zip`);

        // Cek content-type: ZIP langsung atau fallback JSON
        const contentType = res.headers.get('content-type') || '';

        if (contentType.includes('application/zip')) {
            // Server berhasil buat ZIP dari cache — langsung download
            if (!res.ok) throw new Error('Server error ' + res.status);
            const blob = await res.blob();
            const url  = URL.createObjectURL(blob);
            const disposition = res.headers.get('content-disposition') || '';
            const filenameMatch = disposition.match(/filename[^;=\n]*=(['"]?)([^'";\n]+)\1/);
            const zipName = filenameMatch ? filenameMatch[2] : 'sertifikat_batch.zip';
            const a    = document.createElement('a'); a.href=url; a.download=zipName;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showNotif('Download selesai', 'ZIP berhasil diunduh dari server.', 'success');

        } else {
            // Fallback: ZipArchive tidak tersedia, pakai JSZip client-side
            const data = await res.json();

            if (data.error) {
                showNotif('Belum siap', data.error, 'warn');
                return;
            }

            if (!data.fallback || !data.certificates || data.certificates.length === 0) {
                showNotif('Tidak ada PDF', 'Tidak ada sertifikat ditemukan.', 'warn');
                return;
            }

            const total = data.certificates.length;
            const zip   = new JSZip();
            let done = 0;

            btn.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>Download 0/${total}...`;

            for (const cert of data.certificates) {
                try {
                    const pdfRes = await fetch(cert.pdf_url);
                    if (!pdfRes.ok) throw new Error('HTTP ' + pdfRes.status);
                    const blob = await pdfRes.blob();
                    const safeNama  = (cert.nama||'peserta').toLowerCase().replace(/[^a-z0-9]+/g,'-');
                    const safeNomor = (cert.nomor||'cert').replace(/[\/\\:*?"<>|]/g,'-');
                    zip.file(safeNama+'_'+safeNomor+'.pdf', blob);
                    done++;
                } catch(e) { console.warn('Skip:', cert.nama, e.message); }
                btn.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>Download ${done}/${total}...`;
            }

            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Membuat ZIP...';
            const zipBlob = await zip.generateAsync({type:'blob'});
            const url = URL.createObjectURL(zipBlob);
            const a   = document.createElement('a'); a.href=url; a.download=data.zip_filename||'sertifikat_batch.zip';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showNotif('Download selesai', `${done} PDF berhasil didownload.`, 'success');
        }

    } catch(e) {
        showNotif('Gagal download ZIP', e.message, 'error');
    } finally {
        btn.disabled=false; btn.innerHTML=orig;
    }
}

// ════ BATCH PROGRESS MODAL ════
let pollingInterval = null;
let batchStartTime  = null;
let lastProcessed   = 0;
let lastPollTime    = null;
let stuckCounter    = 0; // hitung berapa kali tidak ada progress

function showBatchProgress(batchToken, total) {
    batchStartTime = Date.now();
    lastProcessed  = 0;
    lastPollTime   = Date.now();

    document.getElementById('batchProgressBar').style.width = '0%';
    document.getElementById('batchProgressPct').textContent = '0%';
    document.getElementById('batchProgressText').textContent = 'Mengirim ke queue...';
    document.getElementById('statTotal').textContent   = total;
    document.getElementById('statDone').textContent    = '0';
    document.getElementById('statFailed').textContent  = '0';
    document.getElementById('statCached').textContent  = '0';
    document.getElementById('failedList').classList.add('d-none');
    document.getElementById('batchDoneActions').classList.add('d-none');
    document.getElementById('etaInfo').classList.add('d-none');
    document.getElementById('perfTip').classList.add('d-none');
    document.getElementById('modalIcon').textContent = '⚙️';
    document.getElementById('modalTitle').textContent = 'Memproses Sertifikat...';
    document.getElementById('modalSubtitle').textContent = 'Harap tunggu, jangan tutup halaman ini.';

    // Tip berdasarkan jumlah
    const tipEl = document.getElementById('perfTip');
    const tipText = document.getElementById('perfTipText');
    if (total > 100) {
        tipEl.classList.remove('d-none');
        tipText.textContent = `Memproses ${total} sertifikat via queue. Jalankan lebih banyak worker untuk mempercepat: php artisan queue:work --max-jobs=50`;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalBatchProgress'));
    modal.show();
    pollingInterval = setInterval(() => pollBatchProgress(batchToken), 1500);
}

function formatSeconds(s) {
    if (!s || s <= 0) return '—';
    if (s < 60) return s + ' detik';
    const m = Math.floor(s/60), sec = s%60;
    return m + ' mnt ' + (sec>0?sec+' dtk':'');
}

async function pollBatchProgress(token) {
    try {
        const res  = await fetch(`{{ url('/dashboard/certificates/batch') }}/${token}/progress`);
        const data = await res.json();

        const now     = Date.now();
        const elapsed = Math.floor((now - batchStartTime) / 1000);

        // Deteksi stuck: tidak ada progress selama 60 detik
        if (data.processed === lastProcessed && data.status === 'processing') {
            stuckCounter++;
            if (stuckCounter >= 40) { // 40 x 1.5 detik = 60 detik
                document.getElementById('batchProgressText').textContent = '⚠ Worker tampaknya berhenti...';
                document.getElementById('perfTip').classList.remove('d-none');
                document.getElementById('perfTipText').textContent =
                    'Proses stuck di ' + data.processed + '/' + data.total +
                    '. Jalankan ulang worker, lalu klik Cek Ulang Progress.';
                document.getElementById('batchStuckActions').classList.remove('d-none');
            }
        } else {
            stuckCounter = 0;
            document.getElementById('perfTip').classList.add('d-none');
        }

        // Update progress bar
        document.getElementById('batchProgressBar').style.width  = data.percent + '%';
        document.getElementById('batchProgressPct').textContent  = data.percent + '%';
        document.getElementById('statDone').textContent    = data.processed - data.failed;
        document.getElementById('statFailed').textContent  = data.failed;
        document.getElementById('statCached').textContent  = data.cached_pdf ?? '—';

        // Hitung kecepatan (jobs/menit)
        const timeDiff    = (now - lastPollTime) / 1000;
        const jobDiff     = data.processed - lastProcessed;
        const ratePerSec  = timeDiff > 0 ? jobDiff / timeDiff : 0;
        const ratePerMin  = Math.round(ratePerSec * 60);
        lastProcessed = data.processed;
        lastPollTime  = now;

        // Update info
        if (data.processed > 0 && data.status === 'processing') {
            document.getElementById('etaInfo').classList.remove('d-none');
            document.getElementById('elapsedText').textContent = formatSeconds(elapsed);

            if (ratePerMin > 0) {
                document.getElementById('speedText').textContent = ratePerMin + ' sertifikat/menit';
            }

            const remaining = data.total - data.processed;
            const etaSec    = ratePerSec > 0 ? Math.ceil(remaining / ratePerSec) : (data.eta_seconds ?? null);
            document.getElementById('etaText').textContent = etaSec ? formatSeconds(etaSec) : 'Menghitung...';

            const cachedInfo = data.cached_pdf > 0 ? ` · ${data.cached_pdf} PDF siap` : '';
            document.getElementById('batchProgressText').textContent =
                `${data.processed} dari ${data.total} diproses${cachedInfo}`;
        } else if (data.processed === 0) {
            document.getElementById('batchProgressText').textContent = 'Menunggu worker...';
        }

        // Failed entries
        if (data.failed_entries && data.failed_entries.length > 0) {
            document.getElementById('failedList').classList.remove('d-none');
            document.getElementById('failedEntries').innerHTML =
                data.failed_entries.map(e => `<div class="mb-1">❌ <strong>${e.nama}</strong> — ${e.reason}</div>`).join('');
        }

        // Selesai
        if (data.status === 'done' || data.status === 'failed') {
            clearInterval(pollingInterval);
            document.getElementById('etaInfo').classList.add('d-none');
            document.getElementById('batchDoneActions').classList.remove('d-none');
            document.getElementById('modalIcon').textContent = data.failed > 0 ? '⚠️' : '✅';
            document.getElementById('modalTitle').textContent = 'Selesai!';
            document.getElementById('modalSubtitle').textContent = '';

            const berhasil = data.processed - data.failed;
            document.getElementById('doneMessage').textContent =
                `${berhasil} sertifikat berhasil, ${data.failed} gagal`;

            // Tampilkan status PDF cache yang akurat
            const pdfSiap = data.cached_pdf ?? 0;
            document.getElementById('doneSummary').textContent =
                `Selesai dalam ${formatSeconds(elapsed)} · ${pdfSiap} dari ${berhasil} PDF siap`;

            document.getElementById('batchProgressBar').style.width = '100%';
            document.getElementById('batchProgressPct').textContent = '100%';
            document.getElementById('batchProgressText').textContent = 'Selesai!';

            await renderBatchResult(token);

            // Tombol Download ZIP hanya aktif kalau semua PDF benar-benar sudah ada di cache
            const zipBtn = document.getElementById('btnDownloadZip');
            if (data.zip_ready) {
                zipBtn.style.display = 'inline-flex';
                zipBtn.disabled = false;
                zipBtn.title = '';
            } else {
                // PDF sebagian gagal di-cache — sembunyikan ZIP, user bisa download per-sertifikat
                zipBtn.style.display = 'none';
                if (data.failed === 0) {
                    showNotif('Download ZIP', 'Beberapa PDF belum siap di cache. Coba lagi sebentar.', 'warn');
                }
            }
        }
    } catch(e) { console.warn('Polling error:', e); }
}
</script>
@endpush
