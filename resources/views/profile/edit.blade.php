@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold text-navy">
            <i class="bi bi-person-circle me-2 text-gold"></i>Edit Profil
        </h4>
        <small class="text-muted">{{ $user->institution->name ?? '' }}</small>
    </div>
</div>

{{-- Alert Success sudah ditangani oleh partials/alerts.blade.php di layout app --}}

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-validly shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label-caps">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label-caps">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr class="my-4">
                    <p class="text-muted mb-3 small">
                        <i class="bi bi-info-circle me-1"></i>Kosongkan password jika tidak ingin mengubahnya.
                    </p>

                    <div class="mb-3">
                        <label class="form-label-caps">Password Baru</label>
                        <div class="input-password-wrap">
                            <input type="password" name="password" id="profilePassword"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Minimal 8 karakter">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword('profilePassword', this)" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label-caps">Konfirmasi Password</label>
                        <div class="input-password-wrap">
                            <input type="password" name="password_confirmation" id="profilePasswordConfirm"
                                   class="form-control" placeholder="Ulangi password baru">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword('profilePasswordConfirm', this)" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-admin-primary w-100">
                        <i class="bi bi-check-circle me-1"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection