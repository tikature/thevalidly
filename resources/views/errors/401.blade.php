@extends('errors.layout')

@section('title', '401 - Belum Masuk')

@section('content')
<div class="error-icon"><i class="fa-solid fa-lock" style="color: rgb(255, 255, 255);"></i></div>
<div class="error-code">401</div>
<div class="divider"></div>
<div class="error-title">Belum Masuk</div>
<div class="error-desc">
    Kamu harus login terlebih dahulu untuk mengakses halaman ini.
</div>
<div class="d-flex gap-3 justify-content-center flex-wrap mb-3">
    <a href="{{ route('login') }}" class="btn-home" style="margin-bottom:0">
        <i class="bi bi-box-arrow-in-right"></i>Masuk
    </a>
</div>
@endsection
