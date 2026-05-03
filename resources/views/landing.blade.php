@extends('layouts.landing')

@section('title', 'Platform Sertifikat Digital')

@section('content')
<section class="hero">
    <div class="hero-content">

        <h1 class="hero-title">
            Selamat Datang di<br><span>Validly</span>
        </h1>

        <p class="hero-desc">
            Sistem manajemen dan penerbitan sertifikat digital multi-lembaga.
            Kelola, terbitkan, dan verifikasi sertifikat dengan mudah dan aman.
        </p>

        <a href="{{ route('login') }}" class="btn-hero">
            <i class="bi bi-arrow-right-circle"></i>
            Mulai Sekarang
        </a>
    </div>
</section>
@endsection
