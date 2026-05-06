<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Sertifikat — Validly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --navy:#0F1E3C; --gold:#C9A84C; --gold-lt:#E8D48B; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:#f8fafc; }
        .verify-nav { background:var(--navy); padding:14px 32px; display:flex; align-items:center; justify-content:space-between; }
        .verify-brand { font-weight:800; font-size:1.2rem; color:var(--gold-lt); text-decoration:none; display:flex; align-items:center; gap:8px; }
        .brand-icon { width:28px; height:28px; background:var(--gold); border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:.8rem; color:var(--navy); font-weight:900; }
        .hero { background:linear-gradient(135deg,var(--navy),#1a3a6e); padding:50px 0 70px; text-align:center; }
        .badge-valid { background:rgba(22,163,74,.15); border:1px solid rgba(22,163,74,.3); color:#16a34a; font-size:.78rem; font-weight:700; padding:6px 16px; border-radius:20px; display:inline-flex; align-items:center; gap:6px; margin-bottom:16px; }
        .cert-card { background:#fff; border-radius:20px; padding:36px; max-width:680px; margin:-30px auto 40px; box-shadow:0 20px 60px rgba(15,30,60,.12); border-top:4px solid #16a34a; }
        .info-row { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
        .info-item { flex:1; min-width:140px; background:#f8fafc; border:1px solid #eef2f9; border-radius:8px; padding:10px 14px; }
        .info-label { font-size:.68rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:#9ca3af; margin-bottom:3px; }
        .info-value { font-size:.9rem; font-weight:600; color:var(--navy); }
        footer { text-align:center; padding:24px; font-size:.75rem; color:#aaa; }
        footer strong { color:var(--gold); }
    </style>
</head>
<body>
<nav class="verify-nav">
    <a href="{{ route('landing') }}" class="verify-brand"><div class="brand-icon">V</div>Validly</a>
</nav>
<div class="hero">
    <div class="badge-valid"><i class="bi bi-patch-check-fill"></i>Sertifikat Valid & Terverifikasi</div>
    <h1 style="font-size:1.8rem;font-weight:800;color:#fff;margin-bottom:6px">{{ $certificate->nama }}</h1>
    <p style="color:rgba(255,255,255,.5);font-size:.9rem">{{ $certificate->event_name }}</p>
</div>
<div class="container">
    <div class="cert-card">
        <div class="info-row">
            <div class="info-item"><div class="info-label">Nomor Sertifikat</div><div class="info-value">{{ $certificate->nomor }}</div></div>
            <div class="info-item"><div class="info-label">Tanggal Pelaksanaan</div><div class="info-value">{{ $certificate->event_date }}</div></div>
            <div class="info-item"><div class="info-label">Diterbitkan</div><div class="info-value">{{ $certificate->issued_at->format('d M Y') }}</div></div>
        </div>
        @if($certificate->perusahaan)
        <div class="info-row">
            <div class="info-item"><div class="info-label">Instansi / Perusahaan</div><div class="info-value">{{ $certificate->perusahaan }}</div></div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-item"><div class="info-label">Diterbitkan oleh</div><div class="info-value">{{ $certificate->institution->name ?? '-' }}</div></div>
            <div class="info-item"><div class="info-label">Penandatangan</div><div class="info-value">{{ $certificate->signer_name ?? '-' }}</div></div>
        </div>
    </div>
</div>
<footer>&copy; {{ date('Y') }} <strong>Validly</strong> — Platform Sertifikat Digital</footer>
</body>
</html>
