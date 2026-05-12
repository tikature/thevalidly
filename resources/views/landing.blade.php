@extends('layouts.landing')

@section('title', 'Platform Sertifikat Digital')

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────── --}}
<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">
            Selamat Datang di<br><span>Validly</span>
        </h1>
        <p class="hero-desc">
            Sistem manajemen dan penerbitan sertifikat digital multi-lembaga.
            Kelola, terbitkan, dan verifikasi sertifikat dengan mudah dan aman.
        </p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
            <a href="{{ route('login') }}" class="btn-hero">
                <i class="bi bi-arrow-right-circle"></i>
                Mulai Sekarang
            </a>
            <a href="#verifikasi" class="btn-hero"
               style="background:transparent;border:2px solid rgba(255,255,255,.35);color:#fff">
                <i class="bi bi-patch-check"></i>
                Verifikasi Sertifikat
            </a>
        </div>
    </div>
</section>

{{-- ── Verifikasi Sertifikat ───────────────────────────────────── --}}
<section id="verifikasi" style="padding:72px 0;background:#0f1e3c">
    <div class="container">
        <div class="row align-items-center g-5">

            {{-- Kiri --}}
            <div class="col-lg-5">
                <p style="font-size:.72rem;font-weight:700;letter-spacing:2.5px;
                          text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:10px">
                    Verifikasi Sertifikat
                </p>
                <h2 style="font-size:clamp(1.5rem,3vw,2rem);font-weight:800;
                           color:#fff;line-height:1.3;margin-bottom:14px">
                    Cek Keaslian Sertifikat Secara Instan
                </h2>
                <p style="color:rgba(255,255,255,.55);font-size:.9rem;
                          line-height:1.75;margin-bottom:24px">
                    Setiap sertifikat Validly dilengkapi kode unik dan QR Code.
                    Siapapun dapat memverifikasi keaslian kapan saja.
                </p>
            </div>

            {{-- Kanan: box --}}
            <div class="col-lg-7">
                <div style="
                    background:#1a2e55;
                    border:1px solid rgba(255,255,255,.08);
                    border-radius:16px;
                    padding:28px 24px;
                ">
                    <h5 style="color:#fff;font-weight:700;font-size:1rem;margin-bottom:4px">
                        Verifikasi Sertifikat
                    </h5>
                    <p style="color:rgba(255,255,255,.4);font-size:.82rem;margin-bottom:20px">
                        Masukkan kode verifikasi, atau upload foto/PDF sertifikat
                    </p>

                    {{-- Input token --}}
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
                        <input type="text" id="verifyToken"
                               placeholder="Masukkan kode verifikasi..."
                               style="flex:1;min-width:180px;
                                      background:rgba(255,255,255,.06);
                                      border:1px solid rgba(255,255,255,.12);
                                      border-radius:8px;padding:10px 14px;
                                      color:#fff;font-size:.875rem;outline:none"
                               onkeydown="if(event.key==='Enter')doVerify()">
                        <button onclick="doVerify()"
                                style="background:#2563eb;border:none;border-radius:8px;
                                       padding:10px 18px;color:#fff;font-size:.875rem;
                                       font-weight:600;cursor:pointer;
                                       display:flex;align-items:center;gap:6px">
                            <i class="bi bi-search"></i> Verifikasi
                        </button>
                    </div>

                    {{-- Divider --}}
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
                        <div style="flex:1;height:1px;background:rgba(255,255,255,.07)"></div>
                        <span style="color:rgba(255,255,255,.25);font-size:.75rem">atau</span>
                        <div style="flex:1;height:1px;background:rgba(255,255,255,.07)"></div>
                    </div>

                    {{-- Upload --}}
                    <label id="uploadLabel" style="
                        display:flex;align-items:center;gap:10px;
                        background:rgba(255,255,255,.04);
                        border:1px dashed rgba(255,255,255,.15);
                        border-radius:10px;padding:12px 16px;
                        cursor:pointer;color:rgba(255,255,255,.5);font-size:.83rem
                    ">
                        <i class="bi bi-file-earmark-image" id="uploadIcon"
                           style="font-size:1.1rem;flex-shrink:0"></i>
                        <div>
                            <div id="uploadText" style="color:rgba(255,255,255,.65);font-weight:500">
                                Upload foto atau PDF sertifikat
                            </div>
                            <div style="font-size:.73rem;color:rgba(255,255,255,.3);margin-top:2px">
                                Format: JPG, PNG, PDF — QR code akan di-scan otomatis
                            </div>
                        </div>
                        <input type="file" id="uploadInput"
                               accept="image/*,application/pdf"
                               style="display:none"
                               onchange="handleFileUpload(this)">
                    </label>

                    {{-- Result --}}
                    <div id="scanResult" style="margin-top:10px;min-height:20px;font-size:.8rem"></div>

                    {{-- Progress PDF --}}
                    <div id="pdfProgress" style="display:none;margin-top:8px">
                        <div style="background:rgba(255,255,255,.07);border-radius:99px;height:3px;overflow:hidden">
                            <div id="pdfProgressBar"
                                 style="height:100%;background:#3b82f6;border-radius:99px;
                                        width:0%;transition:width .3s"></div>
                        </div>
                        <p id="pdfProgressText"
                           style="color:rgba(255,255,255,.35);font-size:.73rem;margin-top:5px;margin-bottom:0">
                            Memindai halaman PDF...
                        </p>
                    </div>

                    <p style="color:rgba(255,255,255,.2);font-size:.72rem;margin-top:14px;margin-bottom:0">
                        <i class="bi bi-info-circle me-1"></i>
                        Kode verifikasi tercetak di bawah QR Code pada sertifikat.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script>
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
    }

    function doVerify() {
        const token = document.getElementById('verifyToken').value.trim();
        if (!token) { document.getElementById('verifyToken').focus(); return; }
        window.location.href = '/verify/' + encodeURIComponent(token);
    }

    function handleFileUpload(input) {
        const file = input.files[0];
        if (!file) return;
        document.getElementById('uploadText').textContent = file.name;
        if (file.type === 'application/pdf') {
            document.getElementById('uploadIcon').className = 'bi bi-file-earmark-pdf';
            scanQrFromPdf(file);
        } else {
            document.getElementById('uploadIcon').className = 'bi bi-image';
            scanQrFromImage(file);
        }
    }

    function scanQrFromImage(file) {
        setResult('<span style="color:rgba(255,255,255,.4)">Memindai QR Code...</span>');
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            const result = scanCanvas(img, img.width, img.height);
            URL.revokeObjectURL(url);
            handleResult(result);
        };
        img.src = url;
    }

    async function scanQrFromPdf(file) {
        if (typeof pdfjsLib === 'undefined') {
            setResult('<span style="color:#fca5a5">PDF.js tidak tersedia. Coba upload gambar.</span>');
            return;
        }
        setResult('<span style="color:rgba(255,255,255,.4)">Membaca file PDF...</span>');
        showProgress(true);
        try {
            const pdf = await pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
            for (let i = 1; i <= pdf.numPages; i++) {
                updateProgress(i, pdf.numPages);
                const page     = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 2.5 });
                const canvas   = document.createElement('canvas');
                canvas.width   = viewport.width;
                canvas.height  = viewport.height;
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                const result = scanCanvas(canvas, canvas.width, canvas.height);
                if (result) { showProgress(false); handleResult(result); return; }
            }
            showProgress(false);
            setResult('<span style="color:#fca5a5"><i class="bi bi-x-circle me-1"></i>QR tidak ditemukan dalam PDF.</span>');
        } catch(e) {
            showProgress(false);
            setResult('<span style="color:#fca5a5"><i class="bi bi-x-circle me-1"></i>Gagal membaca PDF.</span>');
        }
    }

    function scanCanvas(source, w, h) {
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        c.getContext('2d').drawImage(source, 0, 0);
        const d = c.getContext('2d').getImageData(0, 0, w, h);
        const r = jsQR(d.data, w, h);
        return r ? r.data : null;
    }

    function handleResult(data) {
        if (data) {
            setResult('<span style="color:#86efac"><i class="bi bi-check-circle me-1"></i>QR ditemukan! Mengalihkan...</span>');
            setTimeout(() => { window.location.href = data; }, 1200);
        } else {
            setResult('<span style="color:#fca5a5"><i class="bi bi-x-circle me-1"></i>QR tidak ditemukan. Pastikan gambar/PDF jelas.</span>');
            document.getElementById('uploadText').textContent = 'Upload foto atau PDF sertifikat';
        }
    }

    function setResult(html) { document.getElementById('scanResult').innerHTML = html; }
    function showProgress(show) {
        document.getElementById('pdfProgress').style.display = show ? 'block' : 'none';
        if (!show) document.getElementById('pdfProgressBar').style.width = '0%';
    }
    function updateProgress(cur, total) {
        document.getElementById('pdfProgressBar').style.width = Math.round(cur/total*100) + '%';
        document.getElementById('pdfProgressText').textContent = `Memindai halaman ${cur} dari ${total}...`;
    }
</script>
@endpush
