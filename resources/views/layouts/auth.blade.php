<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — Validly</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <div class="login-card">
        <div class="login-brand">✦ Validly</div>
        <div class="login-sub">@yield('subtitle')</div>

        {{-- Slot untuk pesan error/success --}}
        @if($errors->any())
            <div class="alert alert-danger border-0 mb-3 auth-alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
            </div>
        @endif

        @yield('content')

        <div class="divider"></div>

        <div class="reset-info">
            <i class="bi bi-info-circle me-1"></i>
            Lupa password? Kirim permintaan reset ke
            <a href="mailto:mail@oemahwebsite.com">mail@oemahwebsite.com</a>
        </div>

        <div class="back-link mt-3">
            <a href="{{ route('landing') }}">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    @stack('scripts')
</body>
</html>