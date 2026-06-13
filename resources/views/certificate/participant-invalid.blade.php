<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Tidak Ditemukan - Validly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('validly-logo1.svg') }}">
    <style>
        :root { --navy:#0f1e3c; --gold:#c9a84c; --gold-light:#e8d48b; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif; margin: 0;
            background: var(--navy); min-height: 100vh;
            display: flex; flex-direction: column; color: #fff;
        }
        .navbar-validly {
            background: rgba(15,30,60,.9); backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(201,168,76,.15); padding: 16px 0;
        }
        .navbar-brand-text {
            font-family: 'Playfair Display', serif; font-size: 1.4rem;
            color: #fff; letter-spacing: 1px; text-decoration: none;
        }

        /* Hero — sama seperti participant */
        .hero {
            text-align: center; padding: 52px 24px 72px;
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        .hero-content { position: relative; z-index: 1; }
        .hero-badge {
            display: inline-block; background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.35); color: #fca5a5;
            font-size: .72rem; font-weight: 600; letter-spacing: 2px;
            text-transform: uppercase; padding: 5px 14px; border-radius: 20px; margin-bottom: 16px;
        }
        .hero h1 {
            font-family:'Inter',sans-serif;
            font-size: clamp(1.6rem, 4vw, 2.4rem); color: #fff;
            margin-bottom: 8px; line-height: 1.25;
        }
        .hero p { color: rgba(255,255,255,.5); font-size: .9rem; margin: 0; }

        /* Card */
        .invalid-card {
            max-width: 520px; margin: -36px auto 48px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px; padding: 40px 32px;
            text-align: center;
        }

        /* Icon */
        .invalid-icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: rgba(239,68,68,.12);
            border: 1.5px solid rgba(239,68,68,.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px; font-size: 1.8rem; color: #fca5a5;
        }

        .invalid-title {
            font-size: 1.15rem; font-weight: 700; color: #fff;
            margin-bottom: 10px;
        }
        .invalid-desc {
            font-size: .875rem; color: rgba(255,255,255,.45);
            line-height: 1.7; margin-bottom: 28px;
        }

        /* Possible reasons */
        .reasons {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px; padding: 18px 20px;
            text-align: left; margin-bottom: 28px;
        }
        .reasons-title {
            font-size: .7rem; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: rgba(255,255,255,.3);
            margin-bottom: 12px;
        }
        .reason-item {
            display: flex; align-items: flex-start; gap: 10px;
            font-size: .82rem; color: rgba(255,255,255,.5);
            margin-bottom: 8px; line-height: 1.5;
        }
        .reason-item:last-child { margin-bottom: 0; }
        .reason-item i { color: rgba(255,255,255,.2); margin-top: 2px; flex-shrink: 0; }

        /* Actions */
        .invalid-actions {
            display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;
        }
        .btn-home {
            background: var(--gold); color: var(--navy); border: none;
            border-radius: 10px; padding: 12px 24px; font-weight: 700;
            font-size: .875rem; display: inline-flex; align-items: center;
            gap: 8px; text-decoration: none; transition: all .2s; cursor: pointer;
        }
        .btn-home:hover { background: var(--gold-light); color: var(--navy); transform: translateY(-2px); }

        .btn-verify-manual {
            background: rgba(255,255,255,.07);
            border: 1.5px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.75); border-radius: 10px;
            padding: 12px 24px; font-weight: 600; font-size: .875rem;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; transition: all .2s;
        }
        .btn-verify-manual:hover {
            background: rgba(255,255,255,.12); color: #fff;
            border-color: rgba(255,255,255,.25);
        }

        .footer-landing {
            text-align: center; color: rgba(255,255,255,.25);
            font-size: .75rem; padding: 20px; margin-top: auto;
        }
    </style>
</head>
<body>

<nav class="navbar-validly">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('landing') }}" class="navbar-brand-text"><img src="{{ asset('validly-logo1.svg') }}" alt="Validly" style="height:30px;width:30px;margin-right:5px;vertical-align:middle;filter:brightness(0) invert(1)">Validly</a>
        <span style="font-size:.72rem;color:rgba(255,255,255,.35);letter-spacing:1px;text-transform:uppercase">
            Sertifikat Tidak Ditemukan
        </span>
    </div>
</nav>

<div class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="bi bi-x-circle me-1"></i> Tidak Ditemukan
        </div>
        <h1>Sertifikat Tidak Dapat Diakses</h1>
        <p>Link yang Anda gunakan tidak valid atau sudah tidak aktif.</p>
    </div>
</div>

<div class="container">
    <div class="invalid-card">

        <div class="invalid-icon">
            <i class="bi bi-patch-exclamation"></i>
        </div>

        <div class="invalid-title">Sertifikat Tidak Ditemukan</div>
        <div class="invalid-desc">
            Kami tidak dapat menemukan sertifikat yang sesuai dengan link ini.
            Pastikan link yang Anda gunakan benar dan lengkap.
        </div>

        <div class="reasons">
            <div class="reasons-title">Kemungkinan penyebab</div>
            <div class="reason-item">
                <i class="bi bi-dot"></i>
                URL tidak lengkap atau ada karakter yang terpotong
            </div>
            <div class="reason-item">
                <i class="bi bi-dot"></i>
                Sertifikat belum diterbitkan untuk akun ini
            </div>
            <div class="reason-item">
                <i class="bi bi-dot"></i>
                Anda membuka link dari perangkat atau browser yang berbeda
            </div>
        </div>

        <div class="invalid-actions">
            <a href="{{ route('landing') }}" class="btn-home">
                <i class="bi bi-house"></i> Kembali ke Beranda
            </a>
            <a href="{{ route('landing') }}#verifikasi" class="btn-verify-manual">
                <i class="bi bi-search"></i> Verifikasi Manual
            </a>
        </div>

    </div>
</div>

<div class="footer-landing">
    &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital
</div>

</body>
</html>