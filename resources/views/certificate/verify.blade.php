<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Sertifikat — Validly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0f1e3c; --gold:#c9a84c; --gold-light:#e8d48b; }

        * { box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif; margin:0;
            background:#f8fafc; min-height:100vh;
            display:flex; flex-direction:column; color:#1a1a2e;
        }

        /* Navbar */
        .navbar-validly {
            background:var(--navy);
            border-bottom:1px solid rgba(201,168,76,.15);
            padding:16px 0;
        }
        .navbar-brand-text {
            font-family:'Playfair Display',serif;
            font-size:1.4rem; color:var(--gold-light);
            letter-spacing:1px; text-decoration:none;
        }

        /* Hero */
        .hero {
            background:var(--navy);
            text-align:center; padding:60px 24px 80px;
            position:relative; overflow:hidden;
        }
        .hero::before {
            content:''; position:absolute; inset:0;
            background-image:radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
            background-size:28px 28px;
        }
        .hero::after {
            content:''; position:absolute;
            width:500px; height:500px; border-radius:50%;
            background:radial-gradient(circle, rgba(201,168,76,.07) 0%, transparent 65%);
            top:-150px; left:50%; transform:translateX(-50%);
            pointer-events:none;
        }
        .hero-content { position:relative; z-index:1; }

        .valid-badge {
            display:inline-flex; align-items:center; gap:8px;
            background:rgba(22,163,74,.15);
            border:1px solid rgba(22,163,74,.3);
            color:#4ade80; font-size:.72rem; font-weight:700;
            letter-spacing:2px; text-transform:uppercase;
            padding:6px 16px; border-radius:20px; margin-bottom:18px;
        }
        .valid-badge::before {
            content:''; width:7px; height:7px;
            background:#4ade80; border-radius:50%; flex-shrink:0;
        }

        .hero h1 {
            font-family:'Playfair Display',serif;
            font-size:clamp(1.8rem,4vw,2.6rem);
            color:#fff; font-weight:600;
            margin-bottom:8px; line-height:1.25;
        }
        .hero p { color:rgba(255,255,255,.5); font-size:.9rem; margin:0; }

        /* Card */
        .cert-card {
            background:#fff; border-radius:20px; padding:36px;
            max-width:680px; margin:-40px auto 0;
            position:relative; z-index:1;
            box-shadow:0 20px 60px rgba(15,30,60,.12);
            border-top:4px solid var(--gold);
        }

        /* Fields */
        .cert-field-label {
            font-size:.65rem; font-weight:700;
            letter-spacing:2px; text-transform:uppercase;
            color:#9ca3af; margin-bottom:4px;
        }
        .cert-field-value { font-size:.95rem; font-weight:600; color:var(--navy); }
        .cert-nama {
            font-family:'Playfair Display',serif;
            font-size:2rem; font-style:italic;
            color:var(--navy); margin-bottom:4px; line-height:1.2;
        }
        .cert-divider { height:1px; background:#f0f4ff; margin:20px 0; }
        .cert-meta { display:flex; gap:12px; flex-wrap:wrap; }
        .cert-meta-item {
            background:#f8fafc; border:1px solid #eef2f9;
            border-radius:8px; padding:10px 14px;
            flex:1; min-width:130px;
        }

        .institution-badge {
            display:inline-flex; align-items:center; gap:8px;
            background:var(--navy); color:var(--gold-light);
            font-size:.78rem; font-weight:700;
            padding:8px 14px; border-radius:10px;
        }

        .token-box {
            background:#f8fafc; border:1px solid #eef2f9;
            border-radius:10px; padding:12px 16px;
            font-family:monospace; font-size:.78rem;
            color:#6b7280; word-break:break-all; margin-top:20px;
        }

        /* Footer */
        .footer-landing {
            text-align:center; color:#9ca3af;
            font-size:.75rem; padding:28px; margin-top:40px;
        }
    </style>
</head>
<body>

<nav class="navbar-validly">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('landing') }}" class="navbar-brand-text">✦ Validly</a>
        <span style="font-size:.72rem;color:rgba(255,255,255,.35);letter-spacing:1px;text-transform:uppercase">
            Sistem Verifikasi Sertifikat
        </span>
    </div>
</nav>

<div class="hero">
    <div class="hero-content">
        <div class="valid-badge">Sertifikat Valid</div>
        <h1>Sertifikat Terverifikasi</h1>
        <p>Sertifikat ini asli dan diterbitkan melalui platform Validly</p>
    </div>
</div>

<div class="container" style="padding-bottom:60px">
    <div class="cert-card">

        {{-- Nama peserta --}}
        <div class="cert-field-label">Diberikan Kepada</div>
        <div class="cert-nama">{{ $certificate->nama }}</div>

        @if($certificate->perusahaan)
        <div style="font-size:.875rem;color:#6b7280;margin-bottom:16px">
            <i class="bi bi-building me-1"></i>{{ $certificate->perusahaan }}
        </div>
        @endif

        <div class="cert-divider"></div>

        {{-- Meta info --}}
        <div class="cert-meta mb-4">
            <div class="cert-meta-item">
                <div class="cert-field-label">Nomor Sertifikat</div>
                <div class="cert-field-value">{{ $certificate->nomor }}</div>
            </div>
            <div class="cert-meta-item">
                <div class="cert-field-label">Tanggal Terbit</div>
                <div class="cert-field-value">{{ $certificate->issued_at->format('d M Y') }}</div>
            </div>
            <div class="cert-meta-item">
                <div class="cert-field-label">Tanggal Pelaksanaan</div>
                <div class="cert-field-value">{{ $certificate->date_start ? $certificate->date_start->format('d M Y') : '-' }}{{ $certificate->date_end && $certificate->date_end->ne($certificate->date_start) ? ' – '.$certificate->date_end->format('d M Y') : '' }}</div>
            </div>
        </div>

        {{-- Nama acara --}}
        <div class="cert-field-label">Nama Kegiatan</div>
        <div class="cert-field-value mb-4" style="font-size:1.05rem">
            {{ $certificate->event_name }}
        </div>

        <div class="cert-divider"></div>

        {{-- Lembaga & Status --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="cert-field-label mb-1">Diterbitkan Oleh</div>
                <div class="institution-badge">
                    <i class="bi bi-building-check"></i>
                    {{ $certificate->institution->name ?? '-' }}
                </div>
            </div>
            <div style="text-align:right">
                <div class="cert-field-label mb-1">Status</div>
                <span style="background:#dcfce7;color:#16a34a;font-size:.78rem;font-weight:700;
                             padding:6px 14px;border-radius:20px;display:inline-flex;
                             align-items:center;gap:5px">
                    <i class="bi bi-patch-check-fill"></i> VALID
                </span>
            </div>
        </div>

        {{-- Token --}}
        <div class="token-box">
            <span style="color:#9ca3af;font-size:.68rem;text-transform:uppercase;
                         letter-spacing:1px;display:block;margin-bottom:4px">
                ID Verifikasi
            </span>
            {{ $certificate->verification_token }}
        </div>

    </div>
</div>

<div class="footer-landing">
    &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital.
    Halaman ini dapat diakses publik untuk memverifikasi keaslian sertifikat.
</div>

</body>
</html>
