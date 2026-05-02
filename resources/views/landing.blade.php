<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validly — Platform Sertifikat Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0F1E3C;
            --gold: #C9A84C;
            --gold-light: #E8D48B;
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background: var(--navy);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar-validly {
            background: rgba(15, 30, 60, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(201, 168, 76, 0.15);
            padding: 16px 0;
        }
        .navbar-brand-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--gold-light) !important;
            letter-spacing: 1px;
            text-decoration: none;
        }
        .btn-login {
            background: var(--gold);
            color: var(--navy);
            font-weight: 600;
            font-size: 0.875rem;
            padding: 8px 22px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-login:hover {
            background: var(--gold-light);
            color: var(--navy);
            transform: translateY(-1px);
        }

        /* Hero */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 80px 24px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 640px;
        }
        .hero-badge {
            display: inline-block;
            background: rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.35);
            color: var(--gold-light);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 20px;
            margin-bottom: 28px;
        }
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            color: #fff;
            line-height: 1.2;
            margin-bottom: 20px;
        }
        .hero-title span {
            color: var(--gold-light);
        }
        .hero-desc {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.7;
            margin-bottom: 40px;
        }
        .btn-hero {
            background: var(--gold);
            color: var(--navy);
            font-weight: 700;
            font-size: 0.95rem;
            padding: 14px 36px;
            border-radius: 10px;
            text-decoration: none;
            transition: all .25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-hero:hover {
            background: var(--gold-light);
            color: var(--navy);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(201,168,76,0.3);
        }

        /* Footer */
        .footer-landing {
            text-align: center;
            color: rgba(255,255,255,0.25);
            font-size: 0.75rem;
            padding: 20px;
        }
    </style>
</head>
<body>

{{-- Navbar --}}
<nav class="navbar-validly">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('landing') }}" class="navbar-brand-text">✦ Validly</a>
        <a href="{{ route('login') }}" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
        </a>
    </div>
</nav>

{{-- Hero --}}
<section class="hero">
    <div class="hero-content">

        <h1 class="hero-title">
            Selamat Datang di<br><span>Validly</span>
        </h1>

        <p class="hero-desc">
            Sistem manajemen dan penerbitan sertifikat digital multi-lembaga.
            Kelola, terbitkan, dan verifikasi sertifikat dengan mudah dan aman.
        </p>

        <a href="{{ route('login') }}" class="btn-hero">
            <i class="bi bi-arrow-right-circle"></i>
            Mulai Sekarang
        </a>
    </div>
</section>

<div class="footer-landing">
    &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
