@extends('layouts.auth')

@section('title', 'Masuk')
@section('subtitle', 'Portal Admin Lembaga')

@section('content')
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

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Dasbor
        </button>
    </form>
@endsection