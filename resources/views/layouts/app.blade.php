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
    
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

@include('partials.navbar')

{{-- MAIN CONTENT --}}
<main class="py-4">
    <div class="container-fluid px-4">
        
        {{-- Panggil file partials alert di sini --}}
        @include('partials.alerts')

        @yield('content')

    </div>
</main>

<footer class="footer-small">
    &copy; {{ date('Y') }} <strong>Validly</strong> — Platform Generator Sertifikat Digital
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/auth.js') }}"></script>
<script src="https://kit.fontawesome.com/8623d586f1.js" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>