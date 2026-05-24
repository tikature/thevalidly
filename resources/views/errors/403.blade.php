@extends('errors.layout')

@section('title', '403 — Akses Ditolak')

@section('content')
<div class="error-icon">🔒</div>
<div class="error-code">403</div>
<div class="divider"></div>
<div class="error-title">Akses Ditolak</div>
<div class="error-desc">
    Kamu tidak memiliki izin untuk mengakses halaman ini.
    Pastikan kamu sudah login dengan akun yang tepat.
</div>
@endsection
