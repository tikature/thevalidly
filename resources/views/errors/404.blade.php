@extends('errors.layout')

@section('title', '404 - Halaman Tidak Ditemukan')

@section('content')
<div class="error-icon"><i class="fa-solid fa-magnifying-glass-arrow-right" style="color: rgb(255, 255, 255);"></i></i></div>
<div class="error-code">404</div>
<div class="divider"></div>
<div class="error-title">Halaman Tidak Ditemukan</div>
<div class="error-desc">
    Halaman yang kamu cari tidak ada, sudah dipindahkan,
    atau mungkin URL-nya salah ketik.
</div>
@endsection
