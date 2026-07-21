<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('validly-logo1.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Sertifikat — {{ $batch->event_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0f1e3c; --gold:#c9a84c; --gold-light:#e8d48b; }

        * { box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif; margin:0;
            background:#f8fafc; min-height:100vh;
            display:flex; flex-direction:column; color:#111;
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
            text-align:center; padding:48px 24px 64px;
            position:relative; overflow:hidden;
            background:var(--navy);
        }
        .hero::before {
            content:''; position:absolute; inset:0;
            background-image:radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
            background-size:28px 28px;
        }
        .hero-content { position:relative; z-index:1; }
        .hero-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:rgba(201,168,76,.15);
            border:1px solid rgba(201,168,76,.35);
            color:var(--gold-light); font-size:.72rem; font-weight:600;
            letter-spacing:2px; text-transform:uppercase;
            padding:5px 14px; border-radius:20px; margin-bottom:16px;
        }
        .hero h1 {
            font-family:'Inter',sans-serif;
            font-size:clamp(1.6rem,4vw,2.4rem);
            color:#fff; margin-bottom:8px; line-height:1.25;
        }
        .hero p { color:rgba(255,255,255,.5); font-size:.9rem; margin:0; }

        /* Card */
        .list-card {
            background:#fff; border-radius:18px;
            max-width:760px; margin:-28px auto 48px;
            box-shadow:0 8px 32px rgba(15,30,60,.1);
            border-top:4px solid var(--gold);
            overflow:hidden; color:#111;
            position:relative; z-index:1;
        }
        .list-header {
            background:var(--navy); color:var(--gold-light);
            padding:14px 24px; font-size:.75rem; font-weight:700;
            letter-spacing:1.5px; text-transform:uppercase;
            display:flex; justify-content:space-between; align-items:center;
        }

        /* Baris peserta */
        .cert-row {
            display:flex; align-items:center;
            justify-content:space-between;
            padding:14px 24px;
            border-bottom:1px solid #f0f4ff;
            transition:background .15s;
        }
        .cert-row:last-child { border-bottom:none; }
        .cert-row:hover { background:#f8fafc; }

        .cert-avatar {
            width:38px; height:38px; border-radius:50%;
            background:var(--navy); color:var(--gold-light);
            font-weight:700; font-size:.82rem;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; margin-right:14px;
        }
        .cert-nama { font-weight:700; color:var(--navy); font-size:.9rem; }
        .cert-meta { font-size:.75rem; color:#9ca3af; margin-top:2px; }
        .cert-nomor {
            font-family:monospace; font-size:.75rem;
            background:#f0f4ff; color:#1a3260;
            padding:2px 8px; border-radius:5px;
        }

        /* Tombol */
        .btn-dl-sm {
            background:var(--gold); color:var(--navy);
            border:none; border-radius:8px; padding:8px 14px;
            font-size:.78rem; font-weight:700; cursor:pointer;
            display:inline-flex; align-items:center; gap:5px;
            transition:all .2s; text-decoration:none; white-space:nowrap;
        }
        .btn-dl-sm:hover { background:var(--gold-light); color:var(--navy); transform:translateY(-1px); }
        
        /* Tombol Outline Gold */
        .btn-vr-sm {
            background: #ffffff; /* Sisaanya (background) putih */
            color: var(--gold); /* Teks gold */
            border: 1.5px solid var(--gold); /* Border gold */
            border-radius: 8px; 
            padding: 6px 12px; /* Sedikit disesuaikan karena ada ketebalan border */
            font-size: .78rem; 
            font-weight: 700; 
            cursor: pointer;
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
            transition: all .2s; 
            text-decoration: none; 
            white-space: nowrap;
        }

        .btn-vr-sm:hover { 
            background: var(--navy); /* Opsional: berubah jadi gold saat hover */
            color: #ffffff;          /* Opsional: teks jadi putih saat hover */
            transform: translateY(-1px); 
        }

        /* Empty state */
        .empty-state { text-align:center; padding:56px 24px; color:#9ca3af; }

        /* Footer */
        .footer-landing {
            text-align:center; color:#9ca3af;
            font-size:.75rem; padding:20px; margin-top:auto;
        }
    </style>
</head>
<body>

<nav class="navbar-validly">
    <div class="container d-flex align-items-center justify-content-between">
        <a class="navbar-brand navbar-brand-text" href="{{ route('landing') }}">
            <img src="{{ asset('validly-logo1.svg') }}" alt="Validly" 
            style="height: 25px; width: 25px; margin-right: 3px; background: var(--gold); border-radius: 7px; padding: 2px;">
            <span style="color: var(--gold); font-size: 25px; font-family: serif; font-weight: bold;">Validly</span>
        </a>
        <span style="font-size:.72rem;color:rgba(255,255,255,.35);letter-spacing:1px;text-transform:uppercase">
            Daftar Sertifikat
        </span>
    </div>
</nav>

<div class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="bi bi-building-check"></i>
            {{ $batch->institution->name }}
        </div>
        <h1><strong>{{ $batch->event_name }}</strong></h1>
        <p>
            {{ $batch->date_start ? $batch->date_start->format('d M Y') : '-' }}{{ $batch->date_end && $batch->date_end->ne($batch->date_start) ? ' – '.$batch->date_end->format('d M Y') : '' }}
            &middot; {{ $batch->certificates->count() }} peserta &middot; {{ $batch->event_place }}
        </p>
        <div class="mt-4">
            <a href="{{ route('certificate.batch.zip.public', $batch->batch_token) }}"
               class="btn-dl-sm"
               style="display:inline-flex;align-items:center;gap:8px;background:var(--gold);color:var(--navy);border:none;border-radius:10px;padding:12px 24px;font-weight:700;font-size:.875rem;text-decoration:none;transition:all .2s">
                <i class="bi bi-file-earmark-zip"></i> Download Semua PDF (ZIP)
            </a>
        </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="list-card">
        <div class="list-header">
            <span><i class="bi bi-people me-2"></i>Daftar Peserta</span>
            <span>{{ $batch->certificates->count() }} sertifikat</span>
        </div>

        @forelse($batch->certificates as $cert)
        <div class="cert-row">
            <div class="d-flex align-items-center flex-grow-1 min-width-0">
                <div class="cert-avatar">
                    {{ strtoupper(substr($cert->nama, 0, 2)) }}
                </div>
                <div style="min-width:0">
                    <div class="cert-nama">{{ $cert->nama }}</div>
                    <div class="cert-meta d-flex align-items-center gap-2 flex-wrap">
                        @if($cert->perusahaan)
                            <span><i class="bi bi-building me-1"></i>{{ $cert->perusahaan }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="d-flex gap-3 justify-content-center flex-wrap mb-6">
                <a href="{{ $cert->participantUrl() }}"
                   class="btn-dl-sm"
                   target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>
                <a href="{{ $cert->verificationUrl() }}" class="btn-vr-sm" target="_blank">
                    <i class="bi bi-patch-check"></i> Verifikasi
                </a>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px"></i>
            Belum ada sertifikat dalam batch ini.
        </div>
        @endforelse
    </div>
</div>

<div class="footer-landing">
    &copy; {{ date('Y') }} Validly — Platform Generator Sertifikat Digital
</div>

</body>
</html>
