<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Sertifikat — {{ $batch->event_name }}</title>
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
        .hero h1 { font-size:2rem; font-weight:800; color:#fff; margin-bottom:8px; }
        .hero p { color:rgba(255,255,255,.5); font-size:.9rem; }
        .badge-inst { background:rgba(201,168,76,.15); border:1px solid rgba(201,168,76,.25); color:var(--gold-lt); font-size:.75rem; font-weight:600; padding:5px 14px; border-radius:20px; display:inline-flex; align-items:center; gap:6px; margin-bottom:16px; }
        .list-card { background:#fff; border-radius:16px; max-width:800px; margin:-30px auto 40px; box-shadow:0 20px 60px rgba(15,30,60,.1); overflow:hidden; }
        .list-header { background:var(--navy); color:var(--gold-lt); padding:16px 24px; font-size:.78rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; display:flex; justify-content:space-between; align-items:center; }
        .cert-row { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-bottom:1px solid #f0f4ff; transition:background .15s; }
        .cert-row:last-child { border-bottom:none; }
        .cert-row:hover { background:#f8fafc; }
        .cert-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--navy),var(--navy-mid)); color:var(--gold-lt); font-weight:700; font-size:.85rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-right:14px; }
        .cert-nama { font-weight:700; color:var(--navy); font-size:.9rem; }
        .cert-meta { font-size:.75rem; color:#9ca3af; }
        .cert-nomor { font-family:monospace; font-size:.78rem; background:#f0f4ff; color:var(--navy-mid); padding:3px 8px; border-radius:5px; margin-right:8px; }
        .btn-dl-sm { background:var(--gold); color:var(--navy); border:none; border-radius:7px; padding:7px 14px; font-size:.78rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .2s; text-decoration:none; white-space:nowrap; }
        .btn-dl-sm:hover { background:var(--gold-lt); color:var(--navy); transform:translateY(-1px); }
        .btn-verify-sm { background:#f0f4ff; color:var(--navy-mid); border:none; border-radius:7px; padding:7px 12px; font-size:.78rem; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:all .2s; }
        .btn-verify-sm:hover { background:#e0e8ff; color:var(--navy); }
        .empty-state { text-align:center; padding:60px 24px; color:#9ca3af; }
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
        Daftar Sertifikat
    </span>
</nav>

<div class="hero">
    <div class="badge-inst">
        <i class="bi bi-building-check"></i>
        {{ $batch->institution->name }}
    </div>
    <h1>{{ $batch->event_name }}</h1>
    <p>{{ $batch->event_date }} &middot; {{ $batch->certificates->count() }} peserta</p>
</div>

<div class="container">
    <div class="list-card">
        <div class="list-header">
            <span><i class="bi bi-people me-2"></i>Daftar Peserta</span>
            <span>{{ $batch->certificates->count() }} sertifikat</span>
        </div>

        @forelse($batch->certificates as $cert)
        <div class="cert-row">
            <div class="d-flex align-items-center flex-grow-1">
                <div class="cert-avatar">
                    {{ strtoupper(substr($cert->nama, 0, 2)) }}
                </div>
                <div>
                    <div class="cert-nama">{{ $cert->nama }}</div>
                    <div class="cert-meta">
                        @if($cert->perusahaan)
                            <i class="bi bi-building me-1"></i>{{ $cert->perusahaan }} &middot;
                        @endif
                        {{ $cert->nomor }}
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 ms-3">
                <a href="{{ $cert->participantUrl() }}" class="btn-dl-sm" target="_blank">
                    <i class="bi bi-download"></i> Download
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

<footer>&copy; {{ date('Y') }} <strong>Validly</strong> — Platform Sertifikat Digital</footer>

</body>
</html>
