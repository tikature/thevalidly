<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat — {{ $certificate->nama }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0f1e3c; --gold:#c9a84c; --gold-light:#e8d48b; }
        * { box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif; margin:0;
            background:var(--navy); min-height:100vh;
            display:flex; flex-direction:column; color:#fff;
        }
        .navbar-validly {
            background:rgba(15,30,60,.9); backdrop-filter:blur(10px);
            border-bottom:1px solid rgba(201,168,76,.15); padding:16px 0;
        }
        .navbar-brand-text {
            font-family:'Playfair Display',serif; font-size:1.4rem;
            color:var(--gold-light); letter-spacing:1px; text-decoration:none;
        }
        .hero {
            text-align:center; padding:52px 24px 72px;
            position:relative; overflow:hidden;
        }
        .hero::before {
            content:''; position:absolute; inset:0;
            background-image:radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
            background-size:28px 28px;
        }
        .hero-content { position:relative; z-index:1; }
        .hero-badge {
            display:inline-block; background:rgba(201,168,76,.15);
            border:1px solid rgba(201,168,76,.35); color:var(--gold-light);
            font-size:.72rem; font-weight:600; letter-spacing:2px;
            text-transform:uppercase; padding:5px 14px; border-radius:20px; margin-bottom:16px;
        }
        .hero h1 {
            font-family:'Inter',sans-serif;
            font-size:clamp(1.6rem,4vw,2.4rem); color:#fff;
            margin-bottom:8px; line-height:1.25;
        }
        .hero p { color:rgba(255,255,255,.5); font-size:.9rem; margin:0; }
        .cert-card {
            /* background:#fff; */
            border-radius:18px;
            padding:28px;
            max-width:800px;
            margin:-36px auto 48px;
            /* box-shadow:0 24px 64px rgba(0,0,0,.25);
            border-top:4px solid var(--gold);
            color:#111; */
        }
        .pdf-preview-wrap {
            width:100%; border-radius:10px; overflow:hidden;
            background:#f0f4f8; margin-bottom:22px;
            min-height:200px; position:relative;
        }
        #pdfCanvas {
            width:100%; display:block; border-radius:8px;
            box-shadow:0 2px 12px rgba(0,0,0,.1);
        }
        .pdf-loading {
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            padding:48px; gap:10px;
            color:var(--navy); font-size:.85rem;
        }
        .info-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        .info-item {
            flex:1; min-width:130px; background:#f8fafc;
            border:1px solid #eef2f9; border-radius:8px; padding:10px 14px;
        }
        .info-label {
            font-size:.65rem; font-weight:700; letter-spacing:2px;
            text-transform:uppercase; color:#9ca3af; margin-bottom:3px;
        }
        .info-value { font-size:.875rem; font-weight:600; color:var(--navy); }
        .btn-download {
            background:var(--gold); color:var(--navy); border:none;
            border-radius:10px; padding:13px 28px; font-weight:700;
            font-size:.9rem; display:inline-flex; align-items:center;
            gap:8px; cursor:pointer; transition:all .2s; text-decoration:none;
        }
        .btn-download:hover { background:var(--gold-light); color:var(--navy); transform:translateY(-2px); }
        .btn-download:disabled { opacity:.6; cursor:not-allowed; transform:none; }
        .btn-verify {
            background:#f0f4ff;
            color:var(--navy);
            border:1.5px solid #c7d7ff;
            border-radius:10px;
            padding:13px 28px;
            font-weight:700;
            font-size:.9rem;
            display:inline-flex;
            align-items:center;
            gap:8px;
            text-decoration:none;
            transition:all .2s;
        }
        .btn-verify:hover {
            background:#e0e8ff;
            color:var(--navy);
            transform:translateY(-2px);
        }
        .verify-link { font-size:.75rem; color:#9ca3af; text-align:center; margin-top:14px; }
        .verify-link a { color:#1a3260; font-weight:600; text-decoration:none; }
        .verify-link a:hover { text-decoration:underline; }
        .footer-landing {
            text-align:center; color:rgba(255,255,255,.25);
            font-size:.75rem; padding:20px; margin-top:auto;
        }
    </style>
</head>
<body>

<nav class="navbar-validly">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('landing') }}" class="navbar-brand-text">✦ Validly</a>
        <span style="font-size:.72rem;color:rgba(255,255,255,.35);letter-spacing:1px;text-transform:uppercase">
            Sertifikat Anda
        </span>
    </div>
</nav>

<div class="hero">
    <div class="hero-content">
        <div class="hero-badge">Sertifikat Anda</div>
        <h1>Halo, <strong>{{ $certificate->nama }}!<strong></h1>
        <p>Sertifikat untuk <strong style="color:var(--gold-light)">{{ $certificate->event_name }}</strong> siap didownload.</p>
    </div>
</div>

<div class="container">
    <div class="cert-card">

        {{-- Preview PDF via PDF.js --}}
        <div class="pdf-preview-wrap" id="previewWrap">
            <div class="pdf-loading" id="pdfLoading">
                <div class="spinner-border" style="width:1.8rem;height:1.8rem;color:var(--navy)" role="status"></div>
                <span>Memuat pratinjau sertifikat...</span>
            </div>
            <canvas id="pdfCanvas" style="display:none"></canvas>
        </div>

        <div class="d-flex gap-3 justify-content-center flex-wrap mb-4">
            <button class="btn-download" id="btnDownload" onclick="downloadPdf(this)">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </button>
            <a href="{{ $certificate->verificationUrl() }}" class="btn-verify" target="_blank">
                <i class="bi bi-patch-check"></i> Verifikasi Sertifikat
            </a>
        </div>

        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Nomor Sertifikat</div>
                <div class="info-value">{{ $certificate->nomor }}</div>
            </div>
            @if($certificate->event_date)
            <div class="info-item">
                <div class="info-label">Tanggal Pelaksanaan</div>
                <div class="info-value">{{ $certificate->event_date }}</div>
            </div>
            @endif
            <div class="info-item">
                <div class="info-label">Diterbitkan</div>
                <div class="info-value">{{ $certificate->issued_at->format('d M Y') }}</div>
            </div>
        </div>

        @if($certificate->perusahaan)
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Instansi / Perusahaan</div>
                <div class="info-value">{{ $certificate->perusahaan }}</div>
            </div>
        </div>
        @endif

        <div class="verify-link">
            <i class="bi bi-patch-check me-1"></i>
            Verifikasi keaslian sertifikat ini di
            <a href="{{ $certificate->verificationUrl() }}" target="_blank" style="color: white; text-decoration: none;">halaman verifikasi</a>
        </div>
    </div>
</div>

<div class="footer-landing">
    &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital
</div>

{{-- PDF.js: preload awal sebelum DOM selesai --}}
<link rel="preload" as="fetch" href="{{ route('certificate.pdf', $certificate->verification_token) }}" crossorigin>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

    const PDF_URL      = '{{ route('certificate.pdf', $certificate->verification_token) }}';
    const PDF_FILENAME = 'sertifikat_{{ preg_replace('/[^a-z0-9]+/', '-', strtolower($certificate->nama)) }}.pdf';

    // Fetch PDF sekali, simpan sebagai Blob — bisa dipakai ulang berkali-kali.
    // ArrayBuffer ter-consume setelah dipakai PDF.js, Blob tidak.
    const pdfBlobPromise = fetch(PDF_URL)
        .then(r => { if (!r.ok) throw new Error('fetch failed'); return r.blob(); });

    // ── Render preview ────────────────────────────────────────
    async function renderPdfPreview() {
        try {
            const blob        = await pdfBlobPromise;
            const arrayBuffer = await blob.arrayBuffer(); // buat salinan baru tiap kali
            const pdf         = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            const page        = await pdf.getPage(1);
            const viewport    = page.getViewport({ scale: 1.5 });

            const canvas  = document.getElementById('pdfCanvas');
            canvas.width  = viewport.width;
            canvas.height = viewport.height;

            await page.render({
                canvasContext: canvas.getContext('2d'),
                viewport,
            }).promise;

            document.getElementById('pdfLoading').style.display = 'none';
            canvas.style.display = 'block';

        } catch(e) {
            document.getElementById('pdfLoading').innerHTML =
                '<i class="bi bi-exclamation-circle" style="font-size:1.5rem;color:#ccc"></i>' +
                '<span style="color:#aaa">Pratinjau tidak tersedia. Silakan download PDF.</span>';
        }
    }

    // ── Download — reuse Blob yang sudah di-fetch ─────────────
    async function downloadPdf(btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyiapkan PDF...';
        try {
            const blob = await pdfBlobPromise; // Blob aman dipakai berkali-kali
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href = url; a.download = PDF_FILENAME;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch(e) {
            alert('Download gagal: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-file-earmark-pdf"></i> Download PDF';
        }
    }

    // Render segera setelah DOM siap — tidak tunggu semua asset load
    document.addEventListener('DOMContentLoaded', renderPdfPreview);
</script>
</body>
</html>
