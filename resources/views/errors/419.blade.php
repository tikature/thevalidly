@extends('errors.layout')

@section('title', '419 — Sesi Kedaluwarsa')

@section('content')
<div class="error-icon">⏰</div>
<div class="error-code">419</div>
<div class="divider"></div>
<div class="error-title">Sesi Kedaluwarsa</div>
<div class="error-desc">
    Sesi kamu telah berakhir karena tidak aktif terlalu lama.
    Silakan muat ulang halaman dan coba lagi.
</div>
<div class="d-flex gap-3 justify-content-center flex-wrap mb-3">
    <a href="{{ url()->previous() }}" class="btn-home" style="margin-bottom:0">
        <i class="bi bi-arrow-clockwise"></i>Muat Ulang
    </a>
</div>
@endsection
