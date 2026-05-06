<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Tidak Ditemukan — Validly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --navy:#0F1E3C; --gold:#C9A84C; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:#f8fafc; min-height:100vh; display:flex; flex-direction:column; }
        .verify-nav { background:var(--navy); padding:14px 32px; }
        .verify-brand { font-weight:800; font-size:1.2rem; color:#E8D48B; text-decoration:none; display:flex; align-items:center; gap:8px; }
        .brand-icon { width:28px; height:28px; background:var(--gold); border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:.8rem; color:var(--navy); font-weight:900; }
        .wrap { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 16px; }
        .card-invalid { background:#fff; border-radius:20px; padding:48px 40px; max-width:480px; text-align:center; box-shadow:0 20px 60px rgba(15,30,60,.1); border-top:4px solid #ef4444; }
    </style>
</head>
<body>
<nav class="verify-nav"><a href="{{ route('landing') }}" class="verify-brand"><div class="brand-icon">V</div>Validly</a></nav>
<div class="wrap">
    <div class="card-invalid">
        <div style="font-size:2.5rem;margin-bottom:16px">❌</div>
        <h4 class="fw-bold mb-2" style="color:var(--navy)">Sertifikat Tidak Ditemukan</h4>
        <p class="text-muted mb-4">Kode verifikasi tidak cocok dengan sertifikat manapun. Pastikan kode yang dimasukkan benar.</p>
        @if(!empty($token))
        <div style="background:#f8fafc;border:1px solid #eef2f9;border-radius:10px;padding:10px 14px;font-size:.78rem;color:#9ca3af;word-break:break-all;margin-bottom:24px;font-family:monospace">{{ $token }}</div>
        @endif
        <a href="{{ route('landing') }}" class="btn" style="background:var(--navy);color:#E8D48B;border:none;border-radius:10px;padding:11px 28px;font-weight:700">
            <i class="bi bi-house me-1"></i>Beranda
        </a>
    </div>
</div>
</body>
</html>
