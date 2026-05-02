@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--navy)">
            <i class="bi bi-person-circle me-2" style="color:var(--gold)"></i>Edit Profil
        </h4>
        <small class="text-muted">{{ $user->institution->name ?? '' }}</small>
    </div>
</div>

@if(session('success'))
    <div class="alert border-0 mb-4 d-flex align-items-center gap-2"
         style="background:#f0fdf4;border-left:4px solid #16a34a !important;border-radius:10px;color:#15803d;font-size:.875rem">
        <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr class="my-4">
                    <p class="text-muted mb-3" style="font-size:.82rem">
                        <i class="bi bi-info-circle me-1"></i>Kosongkan password jika tidak ingin mengubahnya.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Password Baru</label>
                        <div style="position:relative">
                            <input type="password" name="password" id="profilePassword"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Minimal 8 karakter" style="padding-right:40px">
                            <button type="button" onclick="togglePw('profilePassword', this)" tabindex="-1"
                                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:1rem;padding:0;transition:color .2s"
                                    onmouseover="this.style.color='#0F1E3C'" onmouseout="this.style.color='#9ca3af'">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-600" style="font-size:.82rem;color:#6b7280;text-transform:uppercase;letter-spacing:1px">Konfirmasi Password</label>
                        <div style="position:relative">
                            <input type="password" name="password_confirmation" id="profilePasswordConfirm"
                                   class="form-control" placeholder="Ulangi password baru" style="padding-right:40px">
                            <button type="button" onclick="togglePw('profilePasswordConfirm', this)" tabindex="-1"
                                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:1rem;padding:0;transition:color .2s"
                                    onmouseover="this.style.color='#0F1E3C'" onmouseout="this.style.color='#9ca3af'">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100 fw-700"
                            style="background:var(--navy);color:var(--gold-light);border:none;border-radius:9px;padding:11px">
                        <i class="bi bi-check-circle me-1"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function togglePw(inputId, btn) {
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
@endpush
