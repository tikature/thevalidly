@extends('errors.layout')

@section('title', '422 - Permintaan Tidak Valid')

@section('content')
<div class="error-icon"><i class="fa-solid fa-triangle-exclamation" style="color: rgb(255, 255, 255);"></i></div>
<div class="error-code">422</div>
<div class="divider"></div>
<div class="error-title">Permintaan Tidak Valid</div>
<div class="error-desc">
    Data yang dikirim tidak dapat diproses.<br>
    Periksa kembali isian form atau batas kuota yang berlaku, lalu coba lagi.
</div>
@endsection
