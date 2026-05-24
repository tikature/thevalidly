<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — Validly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0f1e3c; --navy-mid:#1a3260; --gold:#c9a84c; --gold-light:#e8d48b; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 60%,#1e3a5f 100%);
            min-height:100vh;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            color:#fff;
            padding:24px;
        }
        .error-card {
            background:rgba(255,255,255,.07);
            backdrop-filter:blur(12px);
            border:1px solid rgba(255,255,255,.12);
            border-radius:24px;
            padding:48px 40px;
            text-align:center;
            max-width:480px;
            width:100%;
            box-shadow:0 24px 64px rgba(0,0,0,.4);
        }
        .error-code {
            font-family:'Inter',sans-serif;
            font-size:5rem;
            font-weight:700;
            color:var(--gold-light);
            line-height:1;
            margin-bottom:8px;
            letter-spacing:4px;
        }
        .error-icon { font-size:3rem; margin-bottom:16px; }
        .error-title {
            font-size:1.25rem;
            font-weight:700;
            color:#fff;
            margin-bottom:10px;
        }
        .error-desc {
            font-size:.875rem;
            color:rgba(255,255,255,.65);
            line-height:1.7;
            margin-bottom:28px;
        }
        .btn-home {
            background:var(--gold);
            color:var(--navy);
            border:none;
            border-radius:10px;
            padding:11px 28px;
            font-size:.875rem;
            font-weight:700;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:8px;
            transition:all .2s;
        }
        .btn-home:hover { background:var(--gold-light); color:var(--navy); transform:translateY(-1px); }
        .btn-back {
            background:rgba(255,255,255,.1);
            color:rgba(255,255,255,.8);
            border:1px solid rgba(255,255,255,.2);
            border-radius:10px;
            padding:11px 28px;
            font-size:.875rem;
            font-weight:600;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:8px;
            transition:all .2s;
            cursor:pointer;
        }
        .btn-back:hover { background:rgba(255,255,255,.18); color:#fff; }
        .brand {
            font-family:'Playfair Display',serif;
            font-size:1.1rem;
            color:var(--gold-light);
            margin-bottom:32px;
            letter-spacing:1px;
            opacity:.8;
        }
        .divider {
            width:40px;
            height:2px;
            background:var(--gold);
            margin:16px auto 24px;
            border-radius:2px;
            opacity:.6;
        }
    </style>
</head>
<body>
    <div class="brand">✦ Validly</div>
    <div class="error-card">
        @yield('content')
        <div class="d-flex gap-3 justify-content-center flex-wrap mt-2">
            <button onclick="history.back()" class="btn-back">
                <i class="bi bi-arrow-left"></i>Kembali
            </button>
            <a href="{{ url('/') }}" class="btn-home">
                <i class="bi bi-house-door"></i>Beranda
            </a>
        </div>
    </div>
    <div style="margin-top:24px;font-size:.72rem;color:rgba(255,255,255,.3)">
        &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital
    </div>
</body>
</html>
