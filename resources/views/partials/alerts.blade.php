{{-- Success Alert --}}
@if(session('success') && !request()->routeIs('superadmin.*'))
    <div class="alert alert-success alert-dismissible fade show border-0 mb-4" role="alert"
         style="background:#e6f9f0; color:#1a6b3c; border-left:4px solid #1a6b3c !important; border-radius:8px;">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Error Alert (Manual Error) --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert"
         style="background:#fef2f2; color:#dc3545; border-left:4px solid #dc3545 !important; border-radius:8px;">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Validation Errors (Opsional: Muncul jika ada error validasi di luar modal) --}}
@if($errors->any() && !request()->routeIs('superadmin.*'))
    <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert"
         style="background:#fef2f2; color:#dc3545; border-left:4px solid #dc3545 !important; border-radius:8px;">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif