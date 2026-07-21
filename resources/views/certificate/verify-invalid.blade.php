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

        * { box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif; margin:0;
            background:#f8fafc; min-height:100vh;
            display:flex; flex-direction:column;
        }

        /* Navbar */
        .navbar-validly {
            background:var(--navy);
            border-bottom:1px solid rgba(201,168,76,.15);
            padding:16px 0;
        }
        .navbar-brand-text {
            font-family:'Playfair Display',serif;
            font-size:1.4rem; color: #fff; 
            letter-spacing:1px; text-decoration:none;
        }

        /* Center wrap */
        .wrap {
            flex:1; display:flex;
            align-items:center; justify-content:center;
            padding:48px 16px;
        }

        /* Card */
        .card-invalid {
            background:#fff; border-radius:20px;
            padding:44px 36px; max-width:480px; width:100%;
            text-align:center;
            box-shadow:0 20px 60px rgba(15,30,60,.1);
            border-top:4px solid #ef4444;
        }

        .icon-wrap {
            width:64px; height:64px; border-radius:50%;
            background:#fef2f2; border:1px solid #fecaca;
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 20px;
            font-size:1.6rem; color:#ef4444;
        }

        .card-invalid h4 {
            font-family:'Inter',sans-serif;
            font-size:1.4rem; font-weight:600;
            color:var(--navy); margin-bottom:10px;
        }

        .card-invalid p {
            color:#6b7280; font-size:.9rem;
            line-height:1.65; margin-bottom:20px;
        }

        .token-box {
            background:#f8fafc; border:1px solid #eef2f9;
            border-radius:10px; padding:10px 14px;
            font-family:monospace; font-size:.78rem;
            color:#9ca3af; word-break:break-all;
            margin-bottom:24px; text-align:left;
        }
        .token-box span {
            display:block; font-size:.65rem;
            text-transform:uppercase; letter-spacing:1px;
            color:#d1d5db; margin-bottom:3px;
        }

        .btn-home {
            background:var(--navy); color:var(--gold-light);
            border:none; border-radius:10px;
            padding:11px 24px; font-weight:700;
            font-size:.875rem; text-decoration:none;
            display:inline-flex; align-items:center; gap:7px;
            transition:all .2s;
        }
        .btn-home:hover { background:#1a3260; color:var(--gold-light); transform:translateY(-1px); }

        .btn-retry {
            background:var(--gold); color:var(--navy);
            border:none; border-radius:10px;
            padding:11px 24px; font-weight:700;
            font-size:.875rem; text-decoration:none;
            display:inline-flex; align-items:center; gap:7px;
            transition:all .2s;
        }
        .btn-retry:hover { background:var(--gold-light); color:var(--navy); transform:translateY(-1px); }

        /* Footer */
        .footer-landing {
            text-align:center; color:#9ca3af;
            font-size:.75rem; padding:20px;
        }
    </style>
</head>
<body>

<nav class="navbar-validly">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('landing') }}" class="navbar-brand-text"><img src="{{ asset('validly-logo1.svg') }}" alt="Validly" style="height:30px;width:30px;margin-right:5px;vertical-align:middle;filter:brightness(0) invert(1)">Validly</a>
        <span style="font-size:.72rem;color:rgba(255,255,255,.35);letter-spacing:1px;text-transform:uppercase">
            Sistem Verifikasi Sertifikat
        </span>
    </div>
</nav>

<div class="wrap">
    <div class="card-invalid">

        <div class="icon-wrap">
            <i class="bi bi-x-circle-fill"></i>
        </div>

        <h4><strong>Sertifikat Tidak Ditemukan</strong></h4>
        <p>Kode verifikasi tidak cocok dengan sertifikat manapun. Pastikan kode yang dimasukkan sudah benar.</p>

        @if(!empty($token))
        <div class="token-box">
            <span>Kode yang dicari</span>
            {{ $token }}
        </div>
        @endif

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('landing') }}" class="btn-home">
                <i class="bi bi-house"></i> Beranda
            </a>
            <a href="/#verifikasi" class="btn-retry">
                <i class="bi bi-arrow-counterclockwise"></i> Coba Lagi
            </a>
        </div>

    </div>
</div>

<div class="footer-landing">
    &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital
</div>

</body>
</html>
