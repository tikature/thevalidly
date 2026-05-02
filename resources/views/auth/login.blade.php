<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Validly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #0F1E3C; --navy-mid: #1a3260; --gold: #C9A84C; --gold-light: #E8D48B; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--navy) 0%, #1a3a6e 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            width: 100%; max-width: 420px;
            padding: 44px 40px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.3);
        }
        .login-brand {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--navy);
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 4px;
        }
        .login-brand span { color: var(--gold); }
        .login-sub {
            text-align: center;
            font-size: 0.8rem;
            color: #aaa;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 32px;
        }
        .form-label { font-size: 0.78rem; font-weight: 600; color: #555; letter-spacing: 1px; text-transform: uppercase; }
        .form-control {
            border: 1.5px solid #dde4f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: border-color .2s;
        }
        .form-control:focus {
            border-color: var(--navy-mid);
            box-shadow: 0 0 0 3px rgba(26,50,96,0.08);
        }
        /* Wrapper password + toggle */
        .input-password-wrap {
            position: relative;
        }
        .input-password-wrap .form-control {
            padding-right: 42px;
        }
        .btn-toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            line-height: 1;
            transition: color .2s;
        }
        .btn-toggle-password:hover { color: var(--navy); }
        .btn-login {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            letter-spacing: 1px;
            transition: all .2s;
        }
        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); }
        .divider { height: 1px; background: #eee; margin: 24px 0; }
        .back-link {
            text-align: center;
            font-size: 0.82rem;
            color: #aaa;
        }
        .back-link a { color: var(--navy-mid); font-weight: 500; text-decoration: none; }
        .back-link a:hover { color: var(--gold); }
        .reset-info {
            background: #f8faff;
            border: 1px solid #dde4f0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.78rem;
            color: #6b7280;
            line-height: 1.6;
        }
        .reset-info a { color: var(--navy-mid); font-weight: 600; text-decoration: none; }
        .reset-info a:hover { color: var(--gold); text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-brand">✦ Validly</div>
        <div class="login-sub">Portal Admin Lembaga</div>

        @if($errors->any())
            <div class="alert alert-danger border-0 mb-3" style="font-size:0.85rem; border-radius:10px; background:#fef2f2; color:#b91c1c;">
                <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Alamat Email</label>
                <input type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="admin@lembaga.com"
                       required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-password-wrap">
                    <input type="password" name="password" id="loginPassword"
                           class="form-control"
                           placeholder="Password Anda"
                           required>
                    <button type="button" class="btn-toggle-password" onclick="togglePassword('loginPassword', this)" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember" style="font-size:0.82rem; color:#666;">
                        Ingat saya
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Dasbor
            </button>
        </form>

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
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon  = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
</body>
</html>
