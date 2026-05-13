@php
    use Illuminate\Support\Str;
    $bulanId        = ['Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];
    $issueDateLabel = $certificate->issued_at->format('d') . ' '
                    . $bulanId[$certificate->issued_at->format('n') - 1] . ' '
                    . $certificate->issued_at->format('Y');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat — {{ Str::title($certificate->nama) }}</title>
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
            border-radius:18px; padding:28px;
            max-width:800px; margin:-36px auto 48px;
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

        /* ── Action buttons ── */
        .action-buttons {
            display: flex; gap: 10px; justify-content: center;
            flex-wrap: wrap; margin-bottom: 24px;
        }
        .btn-download {
            background:var(--gold); color:var(--navy); border:none;
            border-radius:10px; padding:13px 24px; font-weight:700;
            font-size:.875rem; display:inline-flex; align-items:center;
            gap:8px; cursor:pointer; transition:all .2s; text-decoration:none;
        }
        .btn-download:hover { background:var(--gold-light); color:var(--navy); transform:translateY(-2px); }
        .btn-download:disabled { opacity:.6; cursor:not-allowed; transform:none; }

        .btn-verify {
            background:#f0f4ff; color:var(--navy);
            border:1.5px solid #c7d7ff; border-radius:10px;
            padding:13px 24px; font-weight:700; font-size:.875rem;
            display:inline-flex; align-items:center; gap:8px;
            text-decoration:none; transition:all .2s;
        }
        .btn-verify:hover { background:#e0e8ff; color:var(--navy); transform:translateY(-2px); }

        /* LinkedIn button */
        .btn-linkedin {
            background: #0A66C2; color: #fff;
            border: none; border-radius: 10px;
            padding: 13px 24px; font-weight: 700; font-size: .875rem;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; transition: all .2s; cursor: pointer;
        }
        .btn-linkedin:hover { background: #004182; color: #fff; transform: translateY(-2px); }
        .btn-linkedin svg { width: 16px; height: 16px; fill: #fff; flex-shrink: 0; }

        /* LinkedIn modal */
        .li-modal-backdrop {
            position: fixed; inset: 0; z-index: 1050;
            background: rgba(0,0,0,.6); backdrop-filter: blur(4px);
            display: flex; align-items: flex-start; justify-content: center;
            padding: 20px; overflow-y: auto;
            opacity: 0; pointer-events: none;
            transition: opacity .25s ease;
        }
        .li-modal-backdrop.is-open { opacity: 1; pointer-events: all; }
        .li-modal {
            background: #fff; border-radius: 16px;
            padding: 28px; max-width: 480px; width: 100%;
            color: #111; transform: translateY(12px);
            transition: transform .25s ease;
            margin: auto;
        }
        .li-modal-backdrop.is-open .li-modal { transform: translateY(0); }
        .li-modal-header {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 18px;
        }
        .li-modal-header svg { width: 22px; height: 22px; fill: #0A66C2; flex-shrink: 0; }
        .li-modal-header h5 { font-size: 1rem; font-weight: 700; color: #0f1e3c; margin: 0; }
        .li-field { margin-bottom: 14px; }
        .li-field label {
            display: block; font-size: .7rem; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: #9ca3af; margin-bottom: 5px;
        }
        .li-field-value {
            background: #f8fafc; border: 1.5px solid #eef2f9;
            border-radius: 8px; padding: 9px 13px;
            font-size: .875rem; font-weight: 600; color: #0f1e3c; line-height: 1.5;
        }
        .li-desc-value {
            background: #f8fafc; border: 1.5px solid #eef2f9;
            border-radius: 8px; padding: 10px 13px;
            font-size: .82rem; color: #374151; line-height: 1.7;
            white-space: pre-line;
        }
        .li-modal-actions { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .li-btn-go {
            flex: 1; background: #0A66C2; color: #fff; border: none;
            border-radius: 9px; padding: 11px 20px; font-weight: 700;
            font-size: .875rem; cursor: pointer; transition: background .2s;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            text-decoration: none;
        }
        .li-btn-go:hover { background: #004182; color: #fff; }
        .li-btn-cancel {
            background: #f1f5f9; color: #374151; border: none;
            border-radius: 9px; padding: 11px 20px; font-weight: 600;
            font-size: .875rem; cursor: pointer; transition: background .2s;
        }
        .li-btn-cancel:hover { background: #e2e8f0; }
        .li-note { font-size: .72rem; color: #9ca3af; margin-top: 12px; line-height: 1.6; }

        .verify-link { font-size:.75rem; color:#9ca3af; text-align:center; margin-top:14px; }
        .verify-link a { color:white; font-weight:600; text-decoration:none; }
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
        <h1>Halo, <strong>{{ Str::title($certificate->nama) }}!</strong></h1>
        <p>Sertifikat untuk <strong style="color:var(--gold-light)">{{ Str::title($certificate->event_name) }}</strong> siap didownload.</p>
    </div>
</div>

<div class="container">
    <div class="cert-card">

        {{-- Preview PDF --}}
        <div class="pdf-preview-wrap" id="previewWrap">
            <div class="pdf-loading" id="pdfLoading">
                <div class="spinner-border" style="width:1.8rem;height:1.8rem;color:var(--navy)" role="status"></div>
                <span>Memuat pratinjau sertifikat...</span>
            </div>
            <canvas id="pdfCanvas" style="display:none"></canvas>
        </div>

        {{-- Action buttons --}}
        <div class="action-buttons">
            <button class="btn-download" id="btnDownload" onclick="downloadPdf(this)">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </button>
            <a href="{{ $certificate->verificationUrl() }}" class="btn-verify" target="_blank">
                <i class="bi bi-patch-check"></i> Verifikasi
            </a>
            <button class="btn-linkedin" onclick="openLinkedIn()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.45 20.45h-3.554v-5.57c0-1.328-.024-3.037-1.85-3.037-1.851 0-2.135 1.445-2.135 2.938v5.669H9.356V9h3.413v1.561h.047c.476-.9 1.636-1.85 3.368-1.85 3.601 0 4.267 2.37 4.267 5.455v6.284zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.017H3.555V9h3.564v11.45zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
                Tambah ke LinkedIn
            </button>
        </div>

        {{-- Info rows --}}
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
                <div class="info-value">{{ $issueDateLabel }}</div>
            </div>
        </div>

        @if($certificate->perusahaan)
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Instansi / Perusahaan</div>
                <div class="info-value">{{ Str::title($certificate->perusahaan) }}</div>
            </div>
        </div>
        @endif

        @if($certificate->event_place || $certificate->signer_name)
        <div class="info-row">
            @if($certificate->event_place)
            <div class="info-item">
                <div class="info-label">Tempat Pelaksanaan</div>
                <div class="info-value">{{ Str::title($certificate->event_place) }}</div>
            </div>
            @endif
            @if($certificate->signer_name)
            <div class="info-item">
                <div class="info-label">Ditandatangani Oleh</div>
                <div class="info-value">
                    {{ Str::title($certificate->signer_name) }}
                    @if($certificate->signer_title)
                        <div style="font-size:.75rem;font-weight:400;color:#9ca3af;margin-top:2px">
                            {{ Str::title($certificate->signer_title) }}
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif

        <div class="verify-link">
            <i class="bi bi-patch-check me-1"></i>
            Verifikasi keaslian sertifikat ini di
            <a href="{{ $certificate->verificationUrl() }}" target="_blank">halaman verifikasi</a>
        </div>
    </div>
</div>

<div class="footer-landing">
    &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital
</div>

{{-- ── LinkedIn Modal ── --}}
<div class="li-modal-backdrop" id="liModalBackdrop" onclick="closeLinkedInOnBackdrop(event)">
    <div class="li-modal">
        <div class="li-modal-header">
            <svg viewBox="0 0 24 24"><path d="M20.45 20.45h-3.554v-5.57c0-1.328-.024-3.037-1.85-3.037-1.851 0-2.135 1.445-2.135 2.938v5.669H9.356V9h3.413v1.561h.047c.476-.9 1.636-1.85 3.368-1.85 3.601 0 4.267 2.37 4.267 5.455v6.284zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.017H3.555V9h3.564v11.45zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            <h5>Tambah ke Profil LinkedIn</h5>
        </div>

        <p style="font-size:.82rem;color:#6b7280;margin-bottom:18px">
            Data berikut akan dikirim ke LinkedIn untuk ditambahkan ke bagian
            <strong>Licenses &amp; Certifications</strong> di profil Anda.
        </p>

        <div class="li-field">
            <label>Nama Sertifikasi</label>
            <div class="li-field-value" id="liName"></div>
        </div>
        <div class="li-field">
            <label>Organisasi Penerbit</label>
            <div class="li-field-value" id="liOrg"></div>
        </div>
        <div class="li-field">
            <label>Tanggal Terbit</label>
            <div class="li-field-value" id="liDate"></div>
        </div>
        <div class="li-field">
            <label>URL Kredensial</label>
            <div class="li-field-value" id="liUrl" style="font-size:.78rem;word-break:break-all"></div>
        </div>
        <div class="li-field">
            <label>Template Deskripsi <span style="color:#9ca3af;font-weight:400;text-transform:none;letter-spacing:0">(salin ke bagian deskripsi LinkedIn)</span></label>
            <div class="li-desc-value" id="liDesc"></div>
        </div>

        <div class="li-modal-actions">
            <a id="liGoBtn" href="#" target="_blank" class="li-btn-go">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="#fff"><path d="M20.45 20.45h-3.554v-5.57c0-1.328-.024-3.037-1.85-3.037-1.851 0-2.135 1.445-2.135 2.938v5.669H9.356V9h3.413v1.561h.047c.476-.9 1.636-1.85 3.368-1.85 3.601 0 4.267 2.37 4.267 5.455v6.284zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.017H3.555V9h3.564v11.45zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                Buka LinkedIn
            </a>
            <button class="li-btn-cancel" onclick="closeLinkedIn()">Tutup</button>
        </div>
        <p class="li-note">
            <i class="bi bi-info-circle me-1"></i>
            LinkedIn akan membuka halaman "Add to Profile". Salin template deskripsi di atas
            ke kolom <em>Description</em> di sana jika tersedia.
        </p>
    </div>
</div>

{{-- PDF.js --}}
<link rel="preload" as="fetch" href="{{ route('certificate.pdf', $certificate->verification_token) }}" crossorigin>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

    const PDF_URL      = '{{ route('certificate.pdf', $certificate->verification_token) }}';
    const PDF_FILENAME = 'sertifikat_{{ preg_replace('/[^a-z0-9]+/', '-', strtolower($certificate->nama)) }}.pdf';

    // ── Data sertifikat untuk LinkedIn ──────────────────────────
    const CERT = {
        name:           '{{ addslashes(Str::title($certificate->event_name)) }}',
        org:            '{{ addslashes(Str::title($certificate->institution->name ?? config('app.name', 'Validly'))) }}',
        nomor:          '{{ addslashes($certificate->nomor) }}',
        issueYear:      {{ $certificate->issued_at->format('Y') }},
        issueMonth:     {{ $certificate->issued_at->format('n') }},
        issueDateLabel: '{{ $issueDateLabel }}',
        eventDate:      '{{ addslashes($certificate->event_date ?? '') }}',
        verifyUrl:      '{{ $certificate->verificationUrl() }}',
        recipient:      '{{ addslashes(Str::title($certificate->nama)) }}',
        company:        '{{ addslashes(Str::title($certificate->perusahaan ?? '')) }}',
        signerName:     '{{ addslashes(Str::title($certificate->signer_name ?? '')) }}',
        signerTitle:    '{{ addslashes(Str::title($certificate->signer_title ?? '')) }}',
        eventPlace:     '{{ addslashes(Str::title($certificate->event_place ?? '')) }}',
    };

    // ── LinkedIn "Add to Profile" URL ───────────────────────────
    function buildLinkedInUrl() {
        const p = new URLSearchParams({
            startTask:        'CERTIFICATION_NAME',
            name:             CERT.name,
            organizationName: CERT.org,
            issueYear:        CERT.issueYear,
            issueMonth:       CERT.issueMonth,
            certUrl:          CERT.verifyUrl,
            certId:           CERT.nomor,
        });
        return 'https://www.linkedin.com/profile/add?' + p.toString();
    }

    // ── Template deskripsi ───────────────────────────────────────
    function buildDescription() {
        let opening = `Baru saja menyelesaikan ${CERT.name} yang diselenggarakan oleh ${CERT.org}`;
        if (CERT.eventDate && CERT.eventPlace) {
            opening += ` pada ${CERT.eventDate} di ${CERT.eventPlace}.`;
        } else if (CERT.eventDate) {
            opening += ` pada ${CERT.eventDate}.`;
        } else if (CERT.eventPlace) {
            opening += ` di ${CERT.eventPlace} pada ${CERT.issueDateLabel}.`;
        } else {
            opening += ` pada ${CERT.issueDateLabel}.`;
        }

        let middle = `Selama program ini, saya mendalami banyak insight baru yang sangat relevan untuk implementasi di industri saat ini`;
        if (CERT.company) {
            middle += `, khususnya untuk kolaborasi bersama ${CERT.company}.`;
        } else {
            middle += `.`;
        }

        let thanks;
        if (CERT.signerName) {
            const signerFull = CERT.signerTitle
                ? `${CERT.signerName} selaku ${CERT.signerTitle}`
                : CERT.signerName;
            thanks = `Terima kasih kepada ${signerFull} dan seluruh tim panitia atas kesempatannya. Siap mengaplikasikan ilmu ini ke projek-projek mendatang!`;
        } else {
            thanks = `Terima kasih kepada ${CERT.org} dan seluruh tim panitia atas kesempatannya. Siap mengaplikasikan ilmu ini ke projek-projek mendatang!`;
        }

        return [
            opening,
            ``,
            middle,
            ``,
            thanks,
            ``,
            `🔢 No. Sertifikat : ${CERT.nomor}`,
            `✅ Cek validasi sertifikat di sini: ${CERT.verifyUrl}`,
        ].join('\n');
    }

    // ── Modal ────────────────────────────────────────────────────
    function openLinkedIn() {
        document.getElementById('liName').textContent  = CERT.name;
        document.getElementById('liOrg').textContent   = CERT.org;
        document.getElementById('liDate').textContent  = CERT.issueDateLabel;
        document.getElementById('liUrl').textContent   = CERT.verifyUrl;
        document.getElementById('liDesc').textContent  = buildDescription();
        document.getElementById('liGoBtn').href        = buildLinkedInUrl();
        document.getElementById('liModalBackdrop').classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeLinkedIn() {
        document.getElementById('liModalBackdrop').classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function closeLinkedInOnBackdrop(e) {
        if (e.target === document.getElementById('liModalBackdrop')) closeLinkedIn();
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLinkedIn(); });

    // ── PDF preview & download ───────────────────────────────────
    const pdfBlobPromise = fetch(PDF_URL)
        .then(r => { if (!r.ok) throw new Error('fetch failed'); return r.blob(); });

    async function renderPdfPreview() {
        try {
            const blob        = await pdfBlobPromise;
            const arrayBuffer = await blob.arrayBuffer();
            const pdf         = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            const page        = await pdf.getPage(1);
            const viewport    = page.getViewport({ scale: 1.5 });
            const canvas      = document.getElementById('pdfCanvas');
            canvas.width      = viewport.width;
            canvas.height     = viewport.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            document.getElementById('pdfLoading').style.display = 'none';
            canvas.style.display = 'block';
        } catch(e) {
            document.getElementById('pdfLoading').innerHTML =
                '<i class="bi bi-exclamation-circle" style="font-size:1.5rem;color:#ccc"></i>' +
                '<span style="color:#aaa">Pratinjau tidak tersedia. Silakan download PDF.</span>';
        }
    }

    async function downloadPdf(btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyiapkan PDF...';
        try {
            const blob = await pdfBlobPromise;
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

    document.addEventListener('DOMContentLoaded', renderPdfPreview);
</script>
</body>
</html>
