@extends('errors.layout')

@section('title', '429 — Terlalu Banyak Permintaan')

@section('content')
<div class="error-icon">🚦</div>
<div class="error-code">429</div>
<div class="divider"></div>
<div class="error-title">Terlalu Banyak Permintaan</div>
<div class="error-desc">
    Kamu mengirim terlalu banyak permintaan dalam waktu singkat.
    Tunggu sebentar lalu coba lagi.
</div>
@endsection
