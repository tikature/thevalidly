@extends('layouts.landing')

@section('title', 'Validly — Platform Sertifikat Digital Terpercaya')

@section('content')

{{-- NAVBAR --}}
<nav class="nav-main" id="navbar">
    <a href="#" class="nav-logo">
        <div class="nav-logo-icon">V</div>
        Validly
    </a>
    <div class="nav-links d-none d-lg-flex align-items-center">
        <a href="#fitur">Fitur</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#verifikasi">Verifikasi</a>
        <a href="#kontak">Kontak</a>
        <a href="{{ route('login') }}" class="btn-nav-cta ms-3">
            Masuk <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="d-lg-none d-flex align-items-center gap-2">
        <a href="{{ route('login') }}" class="nav-mobile-login">Masuk</a>
        <button id="mobileMenuBtn" onclick="toggleMobileMenu()" class="mobile-hamburger" aria-label="Toggle menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>
</nav>

{{-- Mobile Dropdown Menu --}}
<div id="mobileMenu">
    <nav class="mobile-menu-nav">
        <a href="#fitur" onclick="closeMobileMenu(this)">
            <i class="bi bi-grid-3x3-gap"></i> Fitur
        </a>
        <a href="#cara-kerja" onclick="closeMobileMenu(this)">
            <i class="bi bi-play-circle"></i> Cara Kerja
        </a>
        <a href="#verifikasi" onclick="closeMobileMenu(this)">
            <i class="bi bi-patch-check"></i> Verifikasi
        </a>
        <a href="#kontak" onclick="closeMobileMenu(this)">
            <i class="bi bi-envelope"></i> Kontak
        </a>
        <div class="mobile-menu-divider"></div>
        <a href="{{ route('login') }}" class="mobile-cta-btn">
            <i class="bi bi-box-arrow-in-right"></i> Masuk ke Platform
        </a>
    </nav>
</div>

{{-- HERO --}}
<section class="hero">
    <div class="hero-beam"></div>
    <div class="hero-inner">
        <div class="container">
            <h1 class="hero-h1">
                Sertifikat Resmi,<br>
                Otomatis,<br>
                <span class="accent">Tanpa Repot</span>
            </h1>
            <p class="hero-p">
                Validly membantu lembaga pelatihan dan pendidikan menerbitkan sertifikat digital
                profesional secara massal — dengan nomor otomatis, tanda tangan, cap, dan logo institusi Anda.
            </p>
            <div class="hero-btns">
                <a href="#kontak" class="btn-primary-hero">
                    Bergabung Sekarang <i class="bi bi-arrow-right"></i>
                </a>
                <a href="#cara-kerja" class="btn-ghost-hero">
                    <i class="bi bi-play-circle"></i> Lihat Cara Kerja
                </a>
            </div>

            <div class="mockup-wrap mx-auto">
                <div class="float-pill pill-top">
                    <div class="pill-dot" style="background:#16a34a"></div>
                    Sertifikat berhasil dibuat
                </div>
                <div class="mockup-card">
                    <div class="mk-id">VAL-{{ date('y') }}-001</div>
                    <div class="mk-lbl">Diberikan Kepada</div>
                    <div class="mk-name">Budi Santoso, S.T.</div>
                    <div class="mk-org">PT. Maju Bersama</div>
                    <div class="mk-bar a"></div>
                    <div class="mk-bar b"></div>
                    <div class="mk-bar c"></div>
                    <div class="mk-foot">
                        <div class="mk-signer">
                            <strong>Dr. Ahmad Fauzi, M.Pd.</strong>
                            <span>Direktur Lembaga</span>
                        </div>
                        <div class="mk-seal"><i class="bi bi-patch-check-fill"></i></div>
                    </div>
                </div>
                <div class="float-pill pill-bot">
                    <div class="pill-dot" style="background:var(--navy)"></div>
                    Auto-numbered · VAL-{{ date('y') }}-001
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FITUR --}}
<section class="section-features" id="fitur">
    <div class="container">
        <div class="text-center mb-5">
            <div class="sec-label">Fitur Platform</div>
            <h2 class="sec-h2">Semua yang Anda Butuhkan,<br>Dalam Satu Platform</h2>
            <p class="sec-p mx-auto" style="max-width:500px">Dirancang khusus untuk lembaga yang ingin menerbitkan sertifikat secara cepat, konsisten, dan profesional.</p>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
                <div class="f-card">
                    <div class="f-icon">📄</div>
                    <h5>Generator Massal</h5>
                    <p>Upload Excel/CSV ratusan peserta, semua sertifikat dibuat otomatis dalam detik.</p>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="f-card">
                    <div class="f-icon">🔢</div>
                    <h5>Auto Numbering</h5>
                    <p>Format nomor bebas dikustomisasi — tambah segmen, atur urutan, pilih pemisah.</p>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="f-card">
                    <div class="f-icon">✍️</div>
                    <h5>TTD & Cap Digital</h5>
                    <p>Upload tanda tangan dan stempel institusi. Atur posisi dan ukuran bebas.</p>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="f-card">
                    <div class="f-icon">📦</div>
                    <h5>Download ZIP</h5>
                    <p>Unduh semua sertifikat dalam satu klik, dikemas rapi dalam file ZIP siap kirim.</p>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="f-card">
                    <div class="f-icon">🔍</div>
                    <h5>Verifikasi Online</h5>
                    <p>QR Code dan halaman verifikasi publik yang dapat diakses siapa saja kapan saja.</p>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="f-card">
                    <div class="f-icon">🏛</div>
                    <h5>Multi Lembaga</h5>
                    <p>Satu platform, banyak lembaga. Super Admin mengelola dari panel terpusat.</p>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="f-card" style="background:var(--light)">
                    <div class="f-icon" style="background:#fff">🖼️</div>
                    <h5>Background Custom</h5>
                    <p>Upload background milik lembaga atau gunakan template default Validly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="f-card" style="background:var(--light)">
                    <div class="f-icon" style="background:#fff">🔐</div>
                    <h5>Akses Berbasis Peran</h5>
                    <p>Super Admin dan Admin Lembaga punya hak akses berbeda — aman dan terpisah.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="f-card" style="background:var(--light)">
                    <div class="f-icon" style="background:#fff">⚡</div>
                    <h5>Tanpa Instalasi</h5>
                    <p>Semua berjalan di browser. Tidak perlu software tambahan apapun.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section class="section-how" id="cara-kerja">
    <div class="container">
        <div class="text-center mb-5">
            <div class="sec-label">Cara Kerja</div>
            <h2 class="sec-h2">Sertifikat Siap dalam 3 Langkah</h2>
            <p class="sec-p mx-auto" style="max-width:440px">Dari setup hingga download tidak lebih dari beberapa menit.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-n">01</div>
                    <div>
                        <h5>Isi Pengaturan</h5>
                        <p>Masukkan nama acara, tanggal, penandatangan, lalu upload logo, tanda tangan, dan cap institusi.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-n">02</div>
                    <div>
                        <h5>Input Data Peserta</h5>
                        <p>Tambahkan peserta manual atau upload Excel/CSV untuk ratusan peserta sekaligus.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-n">03</div>
                    <div>
                        <h5>Generate & Download</h5>
                        <p>Klik Generate — pratinjau muncul langsung. Download PNG atau ZIP semua sekaligus.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- VERIFIKASI --}}
<section class="section-verify" id="verifikasi">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="sec-label">Verifikasi Sertifikat</div>
                <h2 class="sec-h2">Cek Keaslian Sertifikat Secara Instan</h2>
                <p class="sec-p">Setiap sertifikat Validly dilengkapi kode unik dan QR Code. Siapapun dapat memverifikasi keaslian kapan saja.</p>
                <ul class="list-unstyled mt-4" style="font-size:.875rem;color:var(--gray)">
                    <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color:var(--navy)"></i>Scan QR Code di sertifikat</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color:var(--navy)"></i>Atau masukkan kode verifikasi</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color:var(--navy)"></i>Atau upload foto / PDF sertifikat</li>
                </ul>
            </div>
            <div class="col-lg-7">
                <div class="verify-box">
                    <div class="position-relative" style="z-index:2">
                        <h5 class="fw-bold mb-1" style="color:#fff;font-size:1.1rem">Verifikasi Sertifikat</h5>
                        <p style="color:rgba(255,255,255,.45);font-size:.85rem;margin-bottom:20px">Masukkan kode verifikasi atau upload foto / PDF sertifikat</p>

                        {{-- Input token --}}
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <input type="text" id="verifyToken" class="verify-input"
                                   placeholder="Masukkan kode verifikasi...">
                            <button type="button" class="btn-verify" onclick="doVerify()">
                                <i class="bi bi-search"></i> Verifikasi
                            </button>
                        </div>

                        {{-- Divider --}}
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                            <div style="flex:1;height:1px;background:rgba(255,255,255,.1)"></div>
                            <span style="color:rgba(255,255,255,.3);font-size:.75rem">atau</span>
                            <div style="flex:1;height:1px;background:rgba(255,255,255,.1)"></div>
                        </div>

                        {{-- Upload --}}
                        <label id="uploadQrLabel" style="cursor:pointer;display:flex;align-items:center;gap:10px;
                            background:rgba(255,255,255,.07);border:1.5px dashed rgba(255,255,255,.18);
                            border-radius:9px;padding:11px 14px;font-size:.8rem;color:rgba(255,255,255,.55);
                            transition:all .2s">
                            <i class="bi bi-file-earmark-image" id="uploadQrIcon" style="font-size:1.1rem;flex-shrink:0"></i>
                            <div>
                                <div id="uploadQrText" style="color:rgba(255,255,255,.65);font-weight:500">
                                    Upload foto atau PDF sertifikat
                                </div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.3);margin-top:2px">
                                    Format: JPG, PNG, PDF — QR code akan di-scan otomatis
                                </div>
                            </div>
                            <input type="file" id="uploadQrInput" accept="image/*,application/pdf"
                                   style="display:none" onchange="handleFileUpload(this)">
                        </label>

                        {{-- Scan result --}}
                        <div id="qrScanResult" class="mt-2" style="font-size:.78rem;min-height:20px"></div>

                        {{-- PDF progress --}}
                        <div id="pdfProgress" style="display:none;margin-top:8px">
                            <div style="background:rgba(255,255,255,.1);border-radius:99px;height:3px;overflow:hidden">
                                <div id="pdfProgressBar"
                                     style="height:100%;background:#fff;border-radius:99px;
                                            width:0%;transition:width .3s"></div>
                            </div>
                            <p id="pdfProgressText"
                               style="color:rgba(255,255,255,.35);font-size:.73rem;margin-top:5px;margin-bottom:0">
                                Memindai halaman PDF...
                            </p>
                        </div>

                        <p class="mt-3 mb-0" style="font-size:.75rem;color:rgba(255,255,255,.25)">
                            <i class="bi bi-info-circle me-1"></i>
                            Kode verifikasi dapat ditemukan di bawah QR Code pada sertifikat.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- KONTAK --}}
<section class="section-contact" id="kontak">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="sec-label">Bergabung dengan Validly</div>
                <h2 class="sec-h2">Siap Mulai Menerbitkan Sertifikat?</h2>
                <p class="sec-p">Hubungi tim kami untuk mendapatkan akses platform Validly bagi lembaga Anda. Kami siap membantu dari awal hingga siap digunakan.</p>
            </div>
            <div class="col-lg-7">
                <div class="contact-card">
                    <h5 class="fw-bold mb-4" style="color:var(--navy);font-size:1.05rem;font-weight:700">Hubungi Kami</h5>
                    <div class="contact-item">
                        <div class="contact-icon">🌐</div>
                        <div>
                            <div class="contact-label">Website</div>
                            <a href="https://oemahwebsite.com" target="_blank" class="contact-value">oemahwebsite.com</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">📱</div>
                        <div>
                            <div class="contact-label">WhatsApp</div>
                            <a href="https://wa.me/628112522117" target="_blank" class="contact-value">+62 811-252-2117</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">✉️</div>
                        <div>
                            <div class="contact-label">Email</div>
                            <a href="mailto:atika@oemahwebsite.com" class="contact-value">atika@oemahwebsite.com</a>
                        </div>
                    </div>
                    <div class="contact-item mb-4">
                        <div class="contact-icon">📍</div>
                        <div>
                            <div class="contact-label">Lokasi</div>
                            <div class="contact-value">Purwokerto, Jawa Tengah</div>
                        </div>
                    </div>
                    <a href="https://wa.me/628112522117" target="_blank" class="btn-wa">
                        <i class="bi bi-whatsapp"></i> Chat via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="section-cta">
    <div class="container position-relative" style="z-index:2">
        <h2 class="cta-h2">Mulai Bersama Validly<br>Hari Ini</h2>
        <p class="cta-p">Percayakan penerbitan sertifikat lembaga Anda kepada Validly by Oemah Website Purwokerto.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="#kontak" class="btn-primary-hero">
                Hubungi Kami <i class="bi bi-arrow-right"></i>
            </a>
            <a href="{{ route('login') }}" class="btn-ghost-hero">
                <i class="bi bi-box-arrow-in-right"></i> Login Platform
            </a>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer>
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="foot-logo">
                    <div class="foot-logo-icon">V</div>
                    Validly
                </div>
                <p class="foot-tag">Platform generator sertifikat digital untuk lembaga pelatihan dan pendidikan.</p>
                <p class="foot-tag mt-2" style="color:rgba(255,255,255,.12)">by Oemah Website Purwokerto<br>2211104042</p> 
            </div>
            <div class="col-6 col-lg-2">
                <div class="foot-h">Platform</div>
                <ul class="foot-ul">
                    <li><a href="#fitur">Fitur</a></li>
                    <li><a href="#cara-kerja">Cara Kerja</a></li>
                    <li><a href="#verifikasi">Verifikasi</a></li>
                    <li><a href="{{ route('login') }}">Login</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="foot-h">Fitur</div>
                <ul class="foot-ul">
                    <li><a href="#">Generator Massal</a></li>
                    <li><a href="#">Auto Numbering</a></li>
                    <li><a href="#">TTD Digital</a></li>
                    <li><a href="#">Verifikasi QR</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="foot-h">Kontak</div>
                <ul class="foot-ul">
                    <li><a href="https://oemahwebsite.com" target="_blank">oemahwebsite.com</a></li>
                    <li><a href="#kontak">WhatsApp</a></li>
                    <li><a href="#kontak">Email</a></li>
                    <li><a href="#kontak">Purwokerto, Jateng</a></li>
                </ul>
            </div>
        </div>
        <div class="foot-bottom">
            <div class="foot-copy">&copy; {{ date('Y') }} <strong>Validly</strong> — Platform Generator Sertifikat Digital.</div>
            <div class="foot-by">by <a href="https://oemahwebsite.com" target="_blank">Oemah Website Purwokerto</a></div>
        </div>
    </div>
</footer>

@endsection

@push('styles')
<style>
    :root {
        --navy:     #0F1E3C;
        --navy-mid: #1a3260;
        --navy-lt:  #2a4a8a;
        --blue-soft:#e8edf8;
        --blue-mid: #dce6f7;
        --text:     #1a1a2e;
        --gray:     #6b7280;
        --light:    #f8fafc;
        --white:    #ffffff;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    html, body {
        margin: 0; padding: 0;
        width: 100%; max-width: 100vw; overflow-x: hidden;
    }
    body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text); background: #fff; }

    /* ── KILL navbar & footer dari layouts/landing.blade.php ── */
    .navbar-validly { display: none !important; }
    .footer-landing  { display: none !important; }

    /* Navbar kita selalu punya background minimal agar tidak transparan penuh */
    .nav-main {
        background: rgba(15,30,60,0.6);
    }
    .nav-main.scrolled {
        background: rgba(255,255,255,0.97);
    }

    /* ── NAVBAR ── */
    .nav-main {
        position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        padding: 13px 60px;
        display: flex; align-items: center; justify-content: space-between;
        transition: all .3s;
    }
    .nav-main.scrolled {
        background: rgba(255,255,255,0.97);
        backdrop-filter: blur(20px);
        box-shadow: 0 1px 0 rgba(15,30,60,0.08);
        padding: 13px 60px;
    }
    .nav-logo {
        font-family:'Playfair Display',serif;
        font-weight: 800; font-size:1.4rem; color: var(--white);
        text-decoration: none; letter-spacing: 1px;
        display: flex; align-items: center; gap: 10px;
    }
    .nav-main.scrolled .nav-logo { color: var(--navy); }
    .nav-main.scrolled #mobileMenuBtn { background: var(--blue-soft) !important; border-color: #dde4f0 !important; }
    .nav-main.scrolled #mobileMenuBtn i { color: var(--navy) !important; }
    .nav-main.scrolled .d-lg-none a { color: var(--navy) !important; }
    .nav-logo-icon {
        width: 32px; height: 32px; background: var(--navy);
        border-radius: 9px; display: flex; align-items: center; justify-content: center;
        font-size: 0.9rem; color: #fff; font-weight: 900;
    }
    .nav-main.scrolled .nav-logo-icon { background: var(--navy); }
    .nav-links { display: flex; align-items: center; gap: 32px; }
    .nav-links a { color: rgba(255,255,255,0.75); font-size: 0.875rem; font-weight: 500; text-decoration: none; transition: color .2s; }
    .nav-main.scrolled .nav-links a { color: var(--gray); }
    .nav-links a:hover { color: #fff; }
    .nav-main.scrolled .nav-links a:hover { color: var(--navy); }
    .btn-nav-cta {
        background: var(--navy); color: #fff !important;
        font-weight: 700; font-size: 0.82rem;
        padding: 10px 22px; border-radius: 9px;
        text-decoration: none; transition: all .2s; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 7px;
    }
    .btn-nav-cta:hover { background: var(--navy-mid); transform: translateY(-1px); }

    /* ── HERO ── */
    .hero {
        min-height: 100vh;
        background: linear-gradient(160deg, var(--navy) 0%, var(--navy-mid) 55%, #2a4a8a 100%);
        display: flex; flex-direction: column;
        position: relative; overflow: hidden;
    }
    .hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: radial-gradient(rgba(255,255,255,.06) 1px, transparent 1px);
        background-size: 28px 28px;
        pointer-events: none;
    }
    .hero-beam {
        position: absolute; width: 600px; height: 600px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,.05) 0%, transparent 70%);
        top: -100px; right: 5%; pointer-events: none;
    }
    .hero-inner {
        flex: 1; display: flex; align-items: center; justify-content: center;
        padding: 130px 60px 80px; position: relative; z-index: 2; text-align: center;
    }
    .hero-eyebrow {
        display: inline-flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
        color: rgba(255,255,255,.85); font-size: .72rem; font-weight: 600;
        letter-spacing: 2px; text-transform: uppercase;
        padding: 6px 16px; border-radius: 20px; margin-bottom: 28px;
    }
    .hero-h1 {
        font-family: 'DM Serif Display', serif;
        font-size: clamp(2.8rem, 6vw, 5rem);
        color: #fff; line-height: 1.08; letter-spacing: -1px; margin-bottom: 22px;
    }
    .hero-h1 .accent { color: rgba(255,255,255,.6); font-style: italic; }
    .hero-p {
        font-size: 1.05rem; color: rgba(255,255,255,.55);
        line-height: 1.75; max-width: 520px; margin: 0 auto 38px;
    }
    .hero-btns { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
    .btn-primary-hero {
        background: #fff; color: var(--navy); font-weight: 700;
        padding: 14px 30px; border-radius: 10px; font-size: .9rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 8px 28px rgba(0,0,0,.2); transition: all .25s;
    }
    .btn-primary-hero:hover { background: #f0f4ff; color: var(--navy); transform: translateY(-2px); box-shadow: 0 14px 36px rgba(0,0,0,.25); }
    .btn-ghost-hero {
        background: rgba(255,255,255,.08); border: 1.5px solid rgba(255,255,255,.2);
        color: rgba(255,255,255,.85); font-weight: 500;
        padding: 14px 26px; border-radius: 10px; font-size: .9rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        transition: all .25s;
    }
    .btn-ghost-hero:hover { background: rgba(255,255,255,.14); color: #fff; border-color: rgba(255,255,255,.35); }

    /* Mockup */
    .mockup-wrap {
        position: relative; display: inline-block;
        margin-top: 56px; max-width: calc(100% - 32px);
    }
    .mockup-card {
        width: 340px; max-width: 100%;
        background: #fff; border-radius: 18px; padding: 26px 30px;
        box-shadow: 0 32px 72px rgba(0,0,0,.3); position: relative; text-align: left;
    }
    .mockup-card::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: var(--navy); border-radius: 18px 18px 0 0;
    }
    .mk-id { font-size: .62rem; color: #c0c8d8; letter-spacing: 2px; text-transform: uppercase; text-align: right; margin-bottom: 16px; }
    .mk-lbl { font-size: .62rem; letter-spacing: 2px; text-transform: uppercase; color: #c0c8d8; margin-bottom: 4px; }
    .mk-name { font-family: 'DM Serif Display', serif; font-size: 1.35rem; font-style: italic; color: var(--navy); margin-bottom: 2px; }
    .mk-org { font-size: .73rem; color: #9ca3af; margin-bottom: 14px; }
    .mk-bar { height: 3px; border-radius: 2px; margin-bottom: 5px; }
    .mk-bar.a { background: var(--navy); width: 70%; }
    .mk-bar.b { background: var(--blue-mid); width: 50%; }
    .mk-bar.c { background: #f0f4ff; width: 40%; }
    .mk-foot { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding-top: 14px; border-top: 1px solid #f0f0f0; }
    .mk-signer strong { display: block; color: var(--navy); font-size: .76rem; font-weight: 700; }
    .mk-signer span { color: #bbb; font-size: .68rem; }
    .mk-seal { width: 32px; height: 32px; border-radius: 50%; background: var(--navy); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .75rem; }
    .float-pill {
        position: absolute; background: #fff; border-radius: 10px;
        padding: 8px 14px; box-shadow: 0 8px 24px rgba(0,0,0,.12);
        display: flex; align-items: center; gap: 7px;
        font-size: .72rem; font-weight: 600; color: var(--navy); white-space: nowrap; z-index: 2;
    }
    .pill-dot { width: 7px; height: 7px; border-radius: 50%; }
    .pill-top { top: -12px; right: -16px; }
    .pill-bot { bottom: -12px; left: -16px; }

    /* ── FEATURES ── */
    .section-features { padding: 100px 0; background: #fff; }
    .sec-label {
        font-size: .7rem; letter-spacing: 3px; text-transform: uppercase;
        color: var(--navy-mid); font-weight: 700; margin-bottom: 10px;
    }
    .sec-h2 {
        font-family: 'DM Serif Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        color: var(--navy); line-height: 1.2; margin-bottom: 14px;
    }
    .sec-p { color: var(--gray); font-size: .95rem; line-height: 1.75; }
    .f-card {
        border: 1.5px solid #eef2f9; border-radius: 14px;
        padding: 24px 20px; background: #fff; transition: all .25s; height: 100%;
    }
    .f-card:hover { border-color: var(--navy); box-shadow: 0 8px 32px rgba(15,30,60,.08); transform: translateY(-3px); }
    .f-icon {
        width: 44px; height: 44px; border-radius: 11px;
        background: var(--blue-soft); display: flex; align-items: center;
        justify-content: center; font-size: 1.1rem; margin-bottom: 14px;
    }
    .f-card h5 { font-size: .92rem; font-weight: 700; color: var(--navy); margin-bottom: 7px; }
    .f-card p { font-size: .82rem; color: var(--gray); line-height: 1.65; margin: 0; }

    /* ── HOW ── */
    .section-how { padding: 100px 0; background: var(--light); }
    .step-card {
        display: flex; gap: 16px; padding: 22px; background: #fff;
        border-radius: 13px; border: 1.5px solid #eef2f9; height: 100%; transition: all .2s;
    }
    .step-card:hover { border-color: var(--navy); box-shadow: 0 4px 20px rgba(15,30,60,.07); }
    .step-n {
        width: 36px; height: 36px; border-radius: 10px; background: var(--navy);
        color: #fff; font-weight: 800; font-size: .8rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .step-card h5 { font-size: .9rem; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
    .step-card p { font-size: .8rem; color: var(--gray); line-height: 1.65; margin: 0; }

    /* ── VERIFIKASI ── */
    .section-verify { padding: 100px 0; background: #fff; }
    .verify-box {
        background: var(--navy); border-radius: 20px; padding: 48px;
        color: #fff; position: relative; overflow: hidden;
    }
    .verify-box::before {
        content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
        background: rgba(255,255,255,.04); top: -100px; right: -60px;
    }
    .verify-input {
        flex: 1; min-width: 200px; border: 1.5px solid rgba(255,255,255,.15);
        border-radius: 10px; background: rgba(255,255,255,.07); color: #fff;
        padding: 12px 16px; font-size: .875rem; outline: none; transition: border-color .2s;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .verify-input::placeholder { color: rgba(255,255,255,.35); }
    .verify-input:focus { border-color: rgba(255,255,255,.5); }
    .btn-verify {
        background: #fff; color: var(--navy); font-weight: 700;
        padding: 12px 24px; border-radius: 10px; font-size: .875rem;
        border: none; cursor: pointer; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 7px; transition: all .2s;
    }
    .btn-verify:hover { background: #f0f4ff; transform: translateY(-1px); }

    /* ── KONTAK ── */
    .section-contact { padding: 100px 0; background: var(--light); }
    .contact-card {
        background: #fff; border-radius: 20px; padding: 44px;
        border: 1.5px solid #eef2f9; box-shadow: 0 8px 40px rgba(15,30,60,.06);
    }
    .contact-item { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
    .contact-icon {
        width: 44px; height: 44px; border-radius: 11px; background: var(--blue-soft);
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }
    .contact-label { font-size: .7rem; letter-spacing: 1.5px; text-transform: uppercase; color: #9ca3af; font-weight: 600; margin-bottom: 2px; }
    .contact-value { font-size: .92rem; font-weight: 600; color: var(--navy); text-decoration: none; }
    .contact-value:hover { color: var(--navy-mid); }
    .btn-wa {
        background: var(--navy); color: #fff; border: none; border-radius: 10px;
        padding: 13px 28px; font-weight: 700; font-size: .9rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        width: 100%; justify-content: center; transition: all .2s;
    }
    .btn-wa:hover { background: var(--navy-mid); color: #fff; transform: translateY(-1px); }

    /* ── CTA ── */
    .section-cta {
        padding: 100px 60px; background: var(--navy);
        text-align: center; position: relative; overflow: hidden;
    }
    .section-cta::before {
        content: ''; position: absolute; inset: 0;
        background-image: radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
        background-size: 28px 28px; pointer-events: none;
    }
    .cta-h2 {
        font-family: 'DM Serif Display', serif;
        font-size: clamp(2rem, 4vw, 3rem);
        color: #fff; line-height: 1.15; margin-bottom: 16px;
    }
    .cta-p { color: rgba(255,255,255,.45); font-size: .95rem; margin-bottom: 32px; max-width: 440px; margin-left: auto; margin-right: auto; }

    /* ── FOOTER ── */
    footer { background: #04091a; padding: 60px 0 28px; }
    .foot-logo { font-weight: 800; font-size: 1.15rem; color: #fff; display: flex; align-items: center; gap: 9px; margin-bottom: 10px; }
    .foot-logo-icon { width: 28px; height: 28px; background: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .8rem; color: var(--navy); font-weight: 900; }
    .foot-tag { font-size: .78rem; color: rgba(255,255,255,.25); line-height: 1.65; max-width: 230px; }
    .foot-h { font-size: .67rem; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.2); font-weight: 700; margin-bottom: 14px; }
    .foot-ul { list-style: none; padding: 0; margin: 0; }
    .foot-ul li { margin-bottom: 9px; }
    .foot-ul a { color: rgba(255,255,255,.38); font-size: .82rem; text-decoration: none; transition: color .2s; }
    .foot-ul a:hover { color: rgba(255,255,255,.8); }
    .foot-bottom { border-top: 1px solid rgba(255,255,255,.06); padding-top: 22px; margin-top: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .foot-copy { font-size: .74rem; color: rgba(255,255,255,.18); }
    .foot-copy strong { color: rgba(255,255,255,.4); }
    .foot-by { font-size: .72rem; color: rgba(255,255,255,.14); }
    .foot-by a { color: rgba(255,255,255,.28); text-decoration: none; }
    .foot-by a:hover { color: rgba(255,255,255,.6); }

    /* ── MOBILE NAV ELEMENTS ── */
    .nav-mobile-login {
        color: rgba(255,255,255,.85); font-size: .85rem; font-weight: 600;
        text-decoration: none; white-space: nowrap; transition: color .2s;
    }
    .nav-main.scrolled .nav-mobile-login { color: var(--navy); }

    /* Hamburger button */
    .mobile-hamburger {
        background: rgba(255,255,255,.1); border: 1.5px solid rgba(255,255,255,.18);
        border-radius: 8px; padding: 8px 10px; cursor: pointer;
        display: flex; flex-direction: column; gap: 5px; transition: all .3s;
    }
    .nav-main.scrolled .mobile-hamburger { background: var(--blue-soft); border-color: #dde4f0; }
    .hamburger-line {
        display: block; width: 20px; height: 2px;
        background: #fff; border-radius: 2px;
        transition: all .35s cubic-bezier(.4,0,.2,1);
        transform-origin: center;
    }
    .nav-main.scrolled .hamburger-line { background: var(--navy); }
    .mobile-hamburger.is-open .hamburger-line:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .mobile-hamburger.is-open .hamburger-line:nth-child(2) { transform: scaleX(0); opacity: 0; }
    .mobile-hamburger.is-open .hamburger-line:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* Dropdown menu */
    #mobileMenu {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 996;
        background: var(--navy);
        border-bottom: 1px solid rgba(255,255,255,.07);
        box-shadow: 0 8px 32px rgba(0,0,0,.3);
        /* slide down: mulai dari atas (tersembunyi di balik navbar) */
        transform: translateY(-110%);
        transition: transform .38s cubic-bezier(.4,0,.2,1);
        padding-top: 64px; /* tinggi navbar */
    }
    #mobileMenu.is-open {
        transform: translateY(0);
    }

    .mobile-menu-nav {
        display: flex; flex-direction: column;
        padding: 10px 12px 14px;
        gap: 2px;
    }
    .mobile-menu-nav a {
        display: flex; align-items: center; gap: 12px;
        color: rgba(255,255,255,.75); font-size: .92rem; font-weight: 600;
        text-decoration: none; padding: 12px 14px; border-radius: 10px;
        opacity: 0; transform: translateY(-8px);
        transition: background .2s, color .2s, opacity .28s ease, transform .28s ease;
    }
    .mobile-menu-nav a i { font-size: 1rem; opacity: .65; flex-shrink: 0; }
    .mobile-menu-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
    .mobile-menu-nav a:hover i { opacity: 1; }

    /* Stagger entrance */
    #mobileMenu.is-open .mobile-menu-nav a:nth-child(1) { opacity:1; transform:none; transition-delay:.06s; }
    #mobileMenu.is-open .mobile-menu-nav a:nth-child(2) { opacity:1; transform:none; transition-delay:.10s; }
    #mobileMenu.is-open .mobile-menu-nav a:nth-child(3) { opacity:1; transform:none; transition-delay:.14s; }
    #mobileMenu.is-open .mobile-menu-nav a:nth-child(4) { opacity:1; transform:none; transition-delay:.18s; }
    #mobileMenu.is-open .mobile-menu-nav a:nth-child(6) { opacity:1; transform:none; transition-delay:.22s; }

    .mobile-menu-divider {
        height: 1px; background: rgba(255,255,255,.07); margin: 6px 4px;
    }

    .mobile-cta-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        background: #fff; color: var(--navy) !important; font-weight: 700; font-size: .88rem;
        padding: 12px 20px; border-radius: 10px; text-decoration: none;
        opacity: 0; transform: translateY(-6px);
        transition: background .2s, color .2s, opacity .28s ease .22s, transform .28s ease .22s;
        margin-top: 2px;
    }
    #mobileMenu.is-open .mobile-cta-btn { opacity:1; transform:none; }
    .mobile-cta-btn:hover { background: #f0f4ff !important; color: var(--navy) !important; }

    body.menu-open { overflow: hidden; }

    @media(max-width:991px) {
        .nav-main, .nav-main.scrolled { padding: 14px 20px; }
        .nav-links.d-lg-flex { display: none !important; }
        .hero-inner { padding: 110px 20px 80px; }
        .mockup-wrap { margin-top: 40px; }
        .mockup-card { width: 300px; }
        .section-features, .section-how, .section-verify, .section-contact { padding: 64px 0; }
        .section-cta { padding: 64px 20px; }
        footer { padding: 48px 0 24px; }
        .verify-box { padding: 28px; }
        .contact-card { padding: 28px; }
    }
    @media(max-width:576px) {
        .hero-h1 { font-size: 2.3rem; }
        .sec-h2 { font-size: 1.7rem; }
        .cta-h2 { font-size: 1.8rem; }
        .f-card { padding: 18px 14px; }
        .hero-btns { flex-direction: column; align-items: center; }
        .btn-primary-hero, .btn-ghost-hero { width: 100%; justify-content: center; max-width: 300px; }
        .hero-inner { padding: 100px 20px 60px; }
        .mockup-card { width: 100%; max-width: 280px; }
        .float-pill { font-size: .65rem; padding: 6px 10px; }
        .pill-top { top: -8px; right: -8px; }
        .pill-bot { bottom: -8px; left: -8px; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script>
    // PDF.js worker
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
    }

    // ── Mobile menu (dropdown) ──
    const mobileMenu = document.getElementById('mobileMenu');
    const menuBtn    = document.getElementById('mobileMenuBtn');
    let menuIsOpen   = false;

    function openMobileMenu() {
        menuIsOpen = true;
        mobileMenu.classList.add('is-open');
        menuBtn.classList.add('is-open');
        document.body.classList.add('menu-open');
    }

    function closeMobileMenuNow() {
        menuIsOpen = false;
        mobileMenu.classList.remove('is-open');
        menuBtn.classList.remove('is-open');
        document.body.classList.remove('menu-open');
    }

    function toggleMobileMenu() {
        menuIsOpen ? closeMobileMenuNow() : openMobileMenu();
    }

    // Klik link → tutup dulu, baru scroll
    function closeMobileMenu(el) {
        const href = typeof el === 'string' ? el : el.getAttribute('href');
        closeMobileMenuNow();
        setTimeout(() => {
            const target = document.querySelector(href);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 350);
        return false;
    }

    // Tutup saat klik di luar menu
    document.addEventListener('click', e => {
        if (menuIsOpen && !mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
            closeMobileMenuNow();
        }
    });

    // Tutup saat scroll
    window.addEventListener('scroll', () => {
        if (menuIsOpen) closeMobileMenuNow();
    }, { passive: true });

    // ── Navbar scroll ──
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    // ── Smooth scroll ──
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const href = a.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    // ── Verifikasi token ──
    function doVerify() {
        const token = document.getElementById('verifyToken').value.trim();
        if (!token) { document.getElementById('verifyToken').focus(); return; }
        window.location.href = '/verify/' + encodeURIComponent(token);
    }
    document.getElementById('verifyToken')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') doVerify();
    });

    // ── File upload handler ──
    function handleFileUpload(input) {
        const file = input.files[0];
        if (!file) return;
        document.getElementById('uploadQrText').textContent = file.name;
        if (file.type === 'application/pdf') {
            document.getElementById('uploadQrIcon').className = 'bi bi-file-earmark-pdf';
            scanQrFromPdf(file);
        } else {
            document.getElementById('uploadQrIcon').className = 'bi bi-image';
            scanQrFromImage(file);
        }
    }

    // ── Scan dari gambar ──
    function scanQrFromImage(file) {
        setResult('<span style="color:rgba(255,255,255,.4)">Memindai QR Code...</span>');
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            const result = scanCanvas(img, img.width, img.height);
            URL.revokeObjectURL(url);
            handleResult(result);
        };
        img.src = url;
    }

    // ── Scan dari PDF (halaman demi halaman) ──
    async function scanQrFromPdf(file) {
        if (typeof pdfjsLib === 'undefined') {
            setResult('<span style="color:#fca5a5">PDF.js tidak tersedia. Coba upload gambar.</span>');
            return;
        }
        setResult('<span style="color:rgba(255,255,255,.4)">Membaca file PDF...</span>');
        showProgress(true);
        try {
            const pdf = await pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
            for (let i = 1; i <= pdf.numPages; i++) {
                updateProgress(i, pdf.numPages);
                const page     = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 2.5 });
                const canvas   = document.createElement('canvas');
                canvas.width   = viewport.width;
                canvas.height  = viewport.height;
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                const result = scanCanvas(canvas, canvas.width, canvas.height);
                if (result) { showProgress(false); handleResult(result); return; }
            }
            showProgress(false);
            setResult('<span style="color:#fca5a5"><i class="bi bi-x-circle me-1"></i>QR tidak ditemukan dalam PDF.</span>');
        } catch (e) {
            showProgress(false);
            setResult('<span style="color:#fca5a5"><i class="bi bi-x-circle me-1"></i>Gagal membaca PDF.</span>');
        }
    }

    // ── Helper: scan canvas dengan jsQR ──
    function scanCanvas(source, w, h) {
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        c.getContext('2d').drawImage(source, 0, 0);
        const d = c.getContext('2d').getImageData(0, 0, w, h);
        const r = jsQR(d.data, w, h);
        return r ? r.data : null;
    }

    // ── Helper: handle hasil scan ──
    function handleResult(data) {
        if (data) {
            setResult('<span style="color:#86efac"><i class="bi bi-check-circle me-1"></i>QR ditemukan! Mengalihkan...</span>');
            setTimeout(() => { window.location.href = data; }, 1200);
        } else {
            setResult('<span style="color:#fca5a5"><i class="bi bi-x-circle me-1"></i>QR tidak ditemukan. Pastikan gambar/PDF jelas.</span>');
            document.getElementById('uploadQrText').textContent = 'Upload foto atau PDF sertifikat';
            document.getElementById('uploadQrIcon').className = 'bi bi-file-earmark-image';
        }
    }

    // ── Helper: set result text ──
    function setResult(html) {
        document.getElementById('qrScanResult').innerHTML = html;
    }

    // ── Helper: progress bar PDF ──
    function showProgress(show) {
        document.getElementById('pdfProgress').style.display = show ? 'block' : 'none';
        if (!show) document.getElementById('pdfProgressBar').style.width = '0%';
    }
    function updateProgress(cur, total) {
        document.getElementById('pdfProgressBar').style.width = Math.round(cur / total * 100) + '%';
        document.getElementById('pdfProgressText').textContent = `Memindai halaman ${cur} dari ${total}...`;
    }
</script>
@endpush