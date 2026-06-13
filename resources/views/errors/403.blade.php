@extends('errors.layout')

@section('title', '403 - Akses Ditolak')

@section('content')
<div class="error-icon"><i class="fa-solid fa-shield-halved" style="color: rgb(255, 255, 255);"></i></div>
<div class="error-code">403</div>
<div class="divider"></div>
<div class="error-title">Akses Ditolak</div>
<div class="error-desc">
    Akun kamu tidak memiliki izin untuk mengakses halaman ini.<br><br>
    Jika kamu merasa ini keliru, pastikan kamu login
    dengan akun yang sesuai dengan halaman yang dituju.
</div>
@endsection
