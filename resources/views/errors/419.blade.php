@extends('errors.layout')

@section('title', '419 - Sesi Kedaluwarsa')

@section('content')
<div class="error-icon"><i class="fa-solid fa-user-clock" style="color: rgb(255, 255, 255);"></i></div>
<div class="error-code">419</div>
<div class="divider"></div>
<div class="error-title">Sesi Kedaluwarsa</div>
<div class="error-desc">
    Halaman ini sudah terlalu lama dibuka tanpa aktivitas,
    sehingga token keamanannya kedaluwarsa.<br><br>
    Silakan login ulang.
</div>
@endsection
