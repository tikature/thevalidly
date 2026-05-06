<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

@page { size: 841.89px 595.28px; margin: 0; }

body {
    font-family: DejaVu Serif, serif;
    width: 841.89px;
    height: 595.28px;
    position: relative;
    overflow: hidden;
    background: #ffffff;
}

/* Background */
.bg-layer {
    position: absolute;
    top: 0; left: 0;
    width: 841.89px;
    height: 595.28px;
    z-index: 0;
}
.bg-layer img {
    width: 841.89px;
    height: 595.28px;
}

/* Watermark */
.watermark {
    position: absolute;
    top: 200px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 80px;
    font-weight: bold;
    color: #0F1E3C;
    opacity: 0.04;
    transform: rotate(-25deg);
    z-index: 1;
    letter-spacing: 4px;
}

/* Seluruh konten di tengah */
/* BARU - tambah inset 20px atas bawah */
.main {
    position: absolute;
    top: 20px; left: 0;
    width: 841.89px;
    height: calc(595.28px - 40px);
    z-index: 2;
    text-align: center;
}

/* Logo */
.logo {
    position: absolute;
    top: 28px;
    left: 0;
    width: 841.89px;
    text-align: center;
}
.logo img {
    height: 54px;
}

/* CERTIFICATE */
.title {
    position: absolute;
    top: 88px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 48px;
    font-weight: bold;
    font-family: DejaVu Sans, sans-serif;
    color: #111111;
    letter-spacing: -1px;
    line-height: 1;
}
.title-no-logo { top: 40px; }

/* Nama institusi */
.inst-name {
    position: absolute;
    top: 152px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    color: #111111;
    text-transform: uppercase;
    letter-spacing: 2.5px;
}
.inst-name-no-logo { top: 104px; }

/* Garis divider */
.divider {
    position: absolute;
    top: 170px;
    left: 320px;
    width: 200px;
    height: 1px;
    background: #cccccc;
}
.divider-no-logo { top: 122px; }

/* Nomor */
.cert-number {
    position: absolute;
    top: 178px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 13px;
    color: #555555;
    letter-spacing: 0.5px;
}
.cert-number-no-logo { top: 130px; }

/* This is to certify that */
.certify {
    position: absolute;
    top: 200px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 14px;
    color: #444444;
    font-style: italic;
    font-family: DejaVu Serif, serif;
}
.certify-no-logo { top: 152px; }

/* Nama peserta */
.participant {
    position: absolute;
    top: 226px;
    left: 120px;
    width: 600px;
    text-align: center;
    font-size: 34px;
    font-style: italic;
    font-family: DejaVu Serif, serif;
    color: #111111;
    line-height: 1.1;
}
.participant-no-logo { top: 178px; }

/* Underline nama */
.name-line {
    position: absolute;
    top: 270px;
    left: 220px;
    width: 400px;
    height: 1.2px;
    background: #111111;
}
.name-line-no-logo { top: 222px; }

/* Perusahaan */
.company {
    position: absolute;
    top: 279px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 12px;
    color: #555555;
}
.company-no-logo { top: 231px; }

/* ===== WRAPPER: cert-desc + event-name + date-line ===== */
/* Posisi awal sama seperti cert-desc semula, tapi konten di dalamnya flow normal */
.content-bottom {
    position: absolute;
    top: 302px;
    left: 80px;
    width: 680px;
    text-align: center;
}
.content-bottom.no-logo            { top: 254px; }
.content-bottom.no-company         { top: 284px; }
.content-bottom.no-logo-no-company { top: 236px; }

.cert-desc {
    font-size: 14px;
    color: #444444;
    line-height: 1.5;
    margin-bottom: 6px;
}

.event-name {
    font-size: 17px;
    font-weight: bold;
    color: #111111;
    margin-bottom: 4px;
}

.date-line {
    font-size: 12px;
    color: #555555;
    font-style: italic;
}

/* === AREA TANDA TANGAN === */

/* Cap */
.cap {
    position: absolute;
    bottom: 58px;
    left: 310px;
    z-index: 3;
}
.cap img {
    height: 90px;
    opacity: 0.88;
}

/* TTD di atas garis */
.ttd {
    position: absolute;
    bottom: 98px;
    left: 0;
    width: 841.89px;
    text-align: center;
    z-index: 3;
}
.ttd img {
    height: 60px;
}

/* Garis TTD */
.sig-line {
    position: absolute;
    bottom: 92px;
    left: 340px;
    width: 160px;
    height: 1px;
    background: #111111;
    z-index: 3;
}

/* Nama penandatangan */
.signer-name {
    position: absolute;
    bottom: 72px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 14px;
    font-weight: bold;
    color: #111111;
    text-decoration: underline;
    z-index: 3;
}

/* Jabatan */
.signer-title {
    position: absolute;
    bottom: 56px;
    left: 0;
    width: 841.89px;
    text-align: center;
    font-size: 11.5px;
    color: #555555;
    z-index: 3;
}
</style>
</head>
<body>

{{-- Background --}}
@if($institution->background_path)
    <div class="bg-layer">
        <img src="{{ $bgPath }}">
    </div>
@elseif($institution->logo_path)
    {{-- Logo sebagai watermark jika tidak ada background --}}
    <div style="position:absolute;top:50%;left:50%;
                transform:translate(-50%,-50%);
                opacity:0.06;z-index:1;text-align:center">
        <img src="{{ $logoPath }}" style="width:380px;opacity:1">
    </div>
@else
    <div class="watermark">{{ $institution->name }}</div>
@endif

<div class="main">

    {{-- Logo --}}
    @if($institution->logo_path)
    <div class="logo">
        <img src="{{ $logoPath }}">
    </div>
    @endif

    {{-- CERTIFICATE --}}
    <div class="title {{ !$institution->logo_path ? 'title-no-logo' : '' }}">CERTIFICATE</div>

    {{-- Nama institusi --}}
    <div class="inst-name {{ !$institution->logo_path ? 'inst-name-no-logo' : '' }}">
        {{ $institution->name }}
    </div>

    {{-- Divider --}}
    <div class="divider {{ !$institution->logo_path ? 'divider-no-logo' : '' }}"></div>

    {{-- Nomor --}}
    <div class="cert-number {{ !$institution->logo_path ? 'cert-number-no-logo' : '' }}">
        {{ $certificate->nomor }}
    </div>

    {{-- This is to certify that --}}
    <div class="certify {{ !$institution->logo_path ? 'certify-no-logo' : '' }}">
        This is to certify that
    </div>

    {{-- Nama Peserta --}}
    @php
        $namaDisplay = collect(explode(' ', $certificate->nama))
            ->map(fn($w) => ucfirst(strtolower($w)))->implode(' ');
        $hasLogo     = (bool) $institution->logo_path;
        $hasCompany  = (bool) $certificate->perusahaan;
    @endphp
    <div class="participant {{ !$hasLogo ? 'participant-no-logo' : '' }}">
        {{ $namaDisplay }}
    </div>

    {{-- Underline nama --}}
    <div class="name-line {{ !$hasLogo ? 'name-line-no-logo' : '' }}"></div>

    {{-- Perusahaan --}}
    @if($hasCompany)
    <div class="company {{ !$hasLogo ? 'company-no-logo' : '' }}">
        {{ $certificate->perusahaan }}
    </div>
    @endif

    {{-- Cert Desc + Event Name + Tanggal dalam satu wrapper agar flow --}}
    @php
        $bottomClass = '';
        if (!$hasLogo && !$hasCompany) $bottomClass = 'no-logo-no-company';
        elseif (!$hasLogo)             $bottomClass = 'no-logo';
        elseif (!$hasCompany)          $bottomClass = 'no-company';
    @endphp
    <div class="content-bottom {{ $bottomClass }}">
        <div class="cert-desc">
            {{ $certificate->cert_desc ?? 'Has Successfully Completed a Training Course on:' }}
        </div>
        <div class="event-name">{{ $certificate->event_name }}</div>
        @if($certificate->event_date)
        <div class="date-line">{{ $certificate->event_date }}</div>
        @endif
    </div>

</div>

{{-- Cap --}}
@if($institution->cap_path)
<div class="cap">
    <img src="{{ $capPath }}">
</div>
@endif

{{-- TTD --}}
@if($institution->ttd_path)
<div class="ttd">
    <img src="{{ $ttdPath }}">
</div>
@endif

{{-- Garis TTD --}}
<div class="sig-line"></div>

{{-- Nama penandatangan --}}
<div class="signer-name">{{ $certificate->signer_name }}</div>

{{-- Jabatan --}}
<div class="signer-title">{{ $certificate->signer_title }}</div>

</body>
</html>