<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Mencegah browser menyimpan halaman authenticated di cache --}}
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">

    <link rel="icon" type="image/svg+xml" href="{{ asset('validly-logo1.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('validly-logo.png') }}">
    <title>@yield('title', 'Validly') - Platform Sertifikat Digital</title>

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

{{-- ═══════════════════════════════════════════════════════════════
     Global fetch error handler — tersedia di semua halaman admin
     Panggil: const ok = await handleHttpError(res);
              if (!ok) return;   ← stop eksekusi jika error
     ═══════════════════════════════════════════════════════════════ --}}
<script>

async function handleHttpError(res, notifFn) {
 
    if (res.ok) {
        const ct = res.headers.get('content-type') || '';
        if (ct.includes('text/html')) {
            const notif = (typeof showNotif !== 'undefined')
                ? (t, m, tp) => showNotif(t, m, tp)
                : (t, m, tp) => (typeof showToast !== 'undefined' ? showToast(m||t, tp) : alert(m||t));
            notif('Sesi berakhir', 'Akun kamu telah dinonaktifkan atau sesi berakhir. Mengarahkan ke halaman login...', 'warn');
            setTimeout(() => { window.location.href = '{{ route("login") }}'; }, 2000);
            return false;
        }
        return true;
    }

    // Cari fungsi notifikasi — admin pakai showNotif, superadmin pakai showToast
    const notify = notifFn
        || (typeof showNotif  !== 'undefined' ? showNotif  : null)
        || (typeof showToast  !== 'undefined' ? showToast  : null)
        || ((msg, sub) => alert(sub || msg));

    // Wrapper agar signature showNotif(title, msg, type) & showToast(msg, type) sama-sama jalan
    const notif = (title, msg, type) => {
        if (typeof showNotif !== 'undefined') showNotif(title, msg, type || 'error');
        else if (typeof showToast !== 'undefined') showToast(msg || title, type || 'error');
        else alert(msg || title);
    };

    switch (res.status) {
        case 401:
            notif('Sesi berakhir', 'Kamu belum login atau sesi telah habis. Mengarahkan ke halaman login...', 'warn');
            setTimeout(() => { window.location.href = '{{ route("login") }}'; }, 1800);
            return false;

        case 403:
            notif('Akses ditolak', 'Kamu tidak memiliki izin untuk melakukan aksi ini.', 'error');
            return false;

        case 419:
            notif('Sesi kedaluwarsa', 'Token keamanan halaman sudah kadaluwarsa. Halaman akan dimuat ulang...', 'warn');
            setTimeout(() => location.reload(), 1800);
            return false;

        case 422: {
            let body = {};
            try { body = await res.json(); } catch {}
            const msg = body.message || body.error
                || (body.errors ? Object.values(body.errors).flat()[0] : null)
                || 'Data yang dikirim tidak valid.';
            notif('Permintaan tidak valid', msg, 'warn');
            return false;
        }

        case 429:
            notif('Terlalu banyak permintaan', 'Tunggu sebentar, lalu coba lagi.', 'warn');
            return false;

        default: {
            let body = {};
            try { body = await res.json(); } catch {}
            const msg = body.message || body.error || ('Terjadi kesalahan server (HTTP ' + res.status + ').');
            notif('Terjadi kesalahan', msg, 'error');
            return false;
        }
    }
}
</script>

@stack('scripts')
{{-- Guard tombol Back browser: jika halaman diambil dari bfcache setelah logout/nonaktif,
     reload paksa agar middleware Laravel bisa redirect ke login --}}
<script>
window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
        // Halaman dikembalikan dari bfcache (tombol Back)
        // Reload paksa agar server bisa cek session — jika sudah logout, akan redirect ke login
        window.location.reload();
    }
});
</script>
</body>
</html>