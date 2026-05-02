<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Validly') — Platform Sertifikat Digital</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:       #0F1E3C;
            --navy-mid:   #1a3260;
            --navy-light: #2a4a8a;
            --gold:       #C9A84C;
            --gold-light: #E8D48B;
            --bg-page:    #F4F7FC;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-page);
            color: #1a1a2e;
        }

        /* Navbar */
        .navbar-validly {
            background: var(--navy);
            box-shadow: 0 2px 12px rgba(15,30,60,0.15);
        }
        .navbar-brand-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--gold-light) !important;
            letter-spacing: 1px;
        }
        .navbar-validly .nav-link {
            color: rgba(255,255,255,0.75) !important;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color .2s;
        }
        .navbar-validly .nav-link:hover,
        .navbar-validly .nav-link.active {
            color: var(--gold-light) !important;
        }
        .btn-logout {
            background: transparent;
            border: 1px solid rgba(201,168,76,0.4);
            color: var(--gold-light) !important;
            font-size: 0.8rem;
            padding: 4px 14px;
            border-radius: 20px;
            transition: all .2s;
        }
        .btn-logout:hover {
            background: var(--gold);
            color: var(--navy) !important;
        }

        /* Cards */
        .card-validly {
            border: 1px solid #dde4f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(15,30,60,0.06);
        }
        .card-validly .card-header {
            background: var(--navy);
            color: #fff;
            border-radius: 11px 11px 0 0;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        /* Badge role */
        .badge-superadmin { background: #1a3260; color: #E8D48B; }
        .badge-admin      { background: #e8f0fe; color: #1a3260; }

        /* Footer */
        .footer-small {
            text-align: center;
            font-size: 0.75rem;
            color: #aaa;
            padding: 20px 0 30px;
            margin-top: 40px;
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- NAVBAR --}}
<nav class="navbar navbar-expand-lg navbar-validly py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand navbar-brand-text" href="{{ route('landing') }}">
            ✦ Validly
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center gap-1">

                @auth
                    @if(auth()->user()->isSuperAdmin())
                        {{-- Super Admin: hanya 1 menu --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.*') ? 'active' : '' }}"
                               href="{{ route('superadmin.index') }}">
                                <i class="bi bi-shield-check me-1"></i>Super Admin
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <span class="text-white-50" style="font-size:0.8rem">
                                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                            </span>
                        </li>
                    @else
                        {{-- Admin Lembaga: hanya Generator & Profile --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('certificate.*') ? 'active' : '' }}"
                               href="{{ route('certificate.index') }}">
                                <i class="bi bi-award me-1"></i>Generator
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                               href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-circle me-1"></i>
                                {{ auth()->user()->name }}
                            </a>
                        </li>
                    @endif

                    {{-- Logout --}}
                    <li class="nav-item ms-2">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-logout">
                                <i class="bi bi-box-arrow-right me-1"></i>Keluar
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="btn btn-logout" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                        </a>
                    </li>
                @endauth

            </ul>
        </div>
    </div>
</nav>

{{-- MAIN CONTENT --}}
<main class="py-4">
    <div class="container-fluid px-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 mb-4" role="alert"
                 style="background:#e6f9f0; color:#1a6b3c; border-left:4px solid #1a6b3c !important; border-radius:8px;">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert"
                 style="border-left:4px solid #dc3545 !important; border-radius:8px;">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')

    </div>
</main>

<div class="footer-small">
    &copy; {{ date('Y') }} <strong>Validly</strong> — Platform Generator Sertifikat Digital
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
