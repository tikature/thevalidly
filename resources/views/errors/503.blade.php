@extends('errors.layout')

@section('title', '503 - Layanan Tidak Tersedia')

@section('content')
<div class="error-icon"><i class="fa-solid fa-screwdriver-wrench" style="color: rgb(255, 255, 255);"></i></div>
<div class="error-code">503</div>
<div class="divider"></div>
<div class="error-title">Sedang Dalam Pemeliharaan</div>
<div class="error-desc">
    Validly sedang dalam proses pemeliharaan atau pembaruan sistem.<br>
    Silakan coba beberapa saat lagi.
</div>
@endsection
