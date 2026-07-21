@extends('errors.layout')

@section('title', '500 - Kesalahan Server')

@section('content')
<div class="error-icon"><i class="fa-solid fa-arrows-rotate" style="color: rgb(255, 255, 255);"></i></div>
<div class="error-code">500</div>
<div class="divider"></div>
<div class="error-title">Terjadi Kesalahan</div>
<div class="error-desc">
    Server mengalami masalah tak terduga saat memproses permintaanmu.
    Silakan coba lagi beberapa saat.
</div>
@endsection
