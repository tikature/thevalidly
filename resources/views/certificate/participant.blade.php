<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat — {{ $certificate->nama }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0F1E3C; --navy-mid:#1a3260; --gold:#C9A84C; --gold-lt:#E8D48B; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:#f8fafc; }
        .nav-main { background:var(--navy); padding:14px 32px; display:flex; align-items:center; justify-content:space-between; }
        .brand { font-weight:800; font-size:1.2rem; color:var(--gold-lt); text-decoration:none; display:flex; align-items:center; gap:8px; }
        .brand-icon { width:28px; height:28px; background:var(--gold); border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:.8rem; color:var(--navy); font-weight:900; }
        .hero { background:linear-gradient(135deg,var(--navy),#1a3a6e); padding:50px 0 70px; text-align:center; }
        .hero h1 { font-size:1.8rem; font-weight:800; color:#fff; margin-bottom:6px; }
        .hero p { color:rgba(255,255,255,.5); font-size:.9rem; }
        .cert-card { background:#fff; border-radius:20px; padding:36px; max-width:700px; margin:-30px auto 40px; box-shadow:0 20px 60px rgba(15,30,60,.12); border-top:4px solid var(--gold); }
        .pdf-icon { font-size:4rem; margin-bottom:16px; }
        .cert-nama { font-size:1.6rem; font-weight:800; color:var(--navy); margin-bottom:4px; }
        .cert-nomor { font-family:monospace; font-size:.9rem; background:#f0f4ff; color:var(--navy-mid); padding:4px 12px; border-radius:6px; display:inline-block; margin-bottom:20px; }
        .info-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
        .info-item { flex:1; min-width:140px; background:#f8fafc; border:1px solid #eef2f9; border-radius:8px; padding:10px 14px; }
        .info-label { font-size:.68rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:#9ca3af; margin-bottom:3px; }
        .info-value { font-size:.9rem; font-weight:600; color:var(--navy); }
        .btn-download { background:var(--gold); color:var(--navy); border:none; border-radius:10px; padding:14px 32px; font-weight:700; font-size:.95rem; display:inline-flex; align-items:center; gap:8px; cursor:pointer; transition:all .2s; text-decoration:none; }
        .btn-download:hover { background:var(--gold-lt); color:var(--navy); transform:translateY(-2px); }
        .btn-download:disabled { opacity:.6; cursor:not-allowed; transform:none; }
        .btn-verify { display:inline-flex; align-items:center; gap:6px; background:#f0f4ff; color:var(--navy-mid); border:none; border-radius:10px; padding:12px 24px; font-weight:600; font-size:.88rem; text-decoration:none; transition:all .2s; }
        .btn-verify:hover { background:#e0e7ff; color:var(--navy); }
        .verify-badge { display:inline-flex; align-items:center; gap:6px; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:20px; padding:5px 14px; font-size:.78rem; font-weight:700; margin-bottom:16px; }
        footer { text-align:center; padding:24px; font-size:.75rem; color:#aaa; }
        footer strong { color:var(--gold); }
    </style>
</head>
<body>

<nav class="nav-main">
    <a href="{{ route('landing') }}" class="brand">
        <div class="brand-icon">V</div>Validly
    </a>
    <span style="font-size:.72rem;color:rgba(255,255,255,.4);letter-spacing:1px;text-transform:uppercase">
        Halaman Peserta
    </span>
</nav>

<div class="hero">
    <div class="pdf-icon">📄</div>
    <h1>{{ $certificate->nama }}</h1>
    <p>Sertifikat untuk kegiatan <strong style="color:var(--gold-lt)">{{ $certificate->event_name }}</strong></p>
</div>

<div class="container">
    <div class="cert-card text-center">

        <div class="verify-badge">
            <i class="bi bi-patch-check-fill"></i>
            Sertifikat Terverifikasi
        </div>

        <div class="cert-nama">{{ $certificate->nama }}</div>
        <div class="cert-nomor">{{ $certificate->nomor }}</div>

        <div class="info-row text-start">
            <div class="info-item">
                <div class="info-label">Kegiatan</div>
                <div class="info-value">{{ $certificate->event_name }}</div>
            </div>
            @if($certificate->event_date)
            <div class="info-item">
                <div class="info-label">Tanggal</div>
                <div class="info-value">{{ $certificate->event_date }}</div>
            </div>
            @endif
            @if($certificate->institution)
            <div class="info-item">
                <div class="info-label">Institusi</div>
                <div class="info-value">{{ $certificate->institution->name }}</div>
            </div>
            @endif
            @if($certificate->perusahaan)
            <div class="info-item">
                <div class="info-label">Asal Instansi</div>
                <div class="info-value">{{ $certificate->perusahaan }}</div>
            </div>
            @endif
        </div>

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <button class="btn-download" id="btnDownload" onclick="downloadPdf(this)">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </button>
        </div>

        <div class="mt-4" style="font-size:.75rem;color:#9ca3af">
            Sertifikat ini diterbitkan oleh <strong>{{ $certificate->institution->name ?? 'Validly' }}</strong>
            dan dapat diverifikasi secara digital.
        </div>
    </div>
</div>

<footer>
    Powered by <strong>Validly</strong> · Platform Sertifikat Digital
</footer>

<script>
async function downloadPdf(btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyiapkan PDF...';
    try {
        const res = await fetch('{{ route("certificate.pdf", $certificate->verification_token) }}');
        if (!res.ok) throw new Error('Gagal mengunduh');
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'sertifikat_{{ Str::slug($certificate->nama) }}.pdf';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch(e) {
        alert('Download gagal: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-file-earmark-pdf"></i> Download PDF';
    }
}
</script>
</body>
</html>
