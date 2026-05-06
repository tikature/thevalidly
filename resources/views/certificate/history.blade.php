@extends('layouts.app')
@section('title', 'Riwayat Sertifikat')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--navy)"><i class="bi bi-clock-history me-2" style="color:var(--gold)"></i>Riwayat Sertifikat</h4>
        <small class="text-muted">{{ auth()->user()->institution->name ?? '' }}</small>
    </div>
    <a href="{{ route('certificate.index') }}" class="btn btn-sm" style="background:var(--navy);color:var(--gold-light);border:none;border-radius:8px;font-size:.8rem;font-weight:600;padding:8px 16px">
        <i class="bi bi-plus-circle me-1"></i>Generate Baru
    </a>
</div>

{{-- Search --}}
<form method="GET" class="d-flex gap-2 mb-4">
    <input type="text" name="search" class="form-control" placeholder="Cari nama, nomor, acara..." value="{{ request('search') }}" style="border:1.5px solid #dde4f0;border-radius:8px">
    <button type="submit" class="btn" style="background:var(--navy);color:var(--gold-light);border:none;border-radius:8px;font-weight:600;padding:8px 20px">Cari</button>
    @if(request('search'))<a href="{{ route('certificate.history') }}" class="btn btn-outline-secondary" style="border-radius:8px">Reset</a>@endif
</form>

<div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
    <table class="table table-hover mb-0">
        <thead style="background:var(--navy)">
            <tr>
                <th style="color:var(--gold-light);font-size:.75rem;padding:14px 20px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Nama Peserta</th>
                <th style="color:var(--gold-light);font-size:.75rem;padding:14px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Tanggal Kegiatan</th>
                <th style="color:var(--gold-light);font-size:.75rem;padding:14px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Nomor</th>
                <th style="color:var(--gold-light);font-size:.75rem;padding:14px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Nama Acara</th>
                <th style="color:var(--gold-light);font-size:.75rem;padding:14px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Tanggal Terbit</th>
                <th style="color:var(--gold-light);font-size:.75rem;padding:14px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($certificates as $cert)
            <tr>
                <td style="padding:14px 20px;vertical-align:middle">
                    <div class="fw-600" style="color:var(--navy);font-weight:600">{{ $cert->nama }}</div>
                    @if($cert->perusahaan)<div style="font-size:.75rem;color:#9ca3af"><i class="bi bi-building me-1"></i>{{ $cert->perusahaan }}</div>@endif
                </td>
                <td style="padding:14px;vertical-align:middle;font-size:.85rem;color:#6b7280;white-space:nowrap">
                    {{ $cert->event_date ?: '—' }}
                </td>
                <td style="padding:14px;vertical-align:middle">
                    <span style="background:#f0f4ff;color:var(--navy-mid);font-size:.75rem;padding:3px 8px;border-radius:5px;font-weight:600">{{ $cert->nomor }}</span>
                </td>
                <td style="padding:14px;vertical-align:middle;font-size:.85rem;color:#374151;max-width:200px">
                    {{ Str::limit($cert->event_name, 40) }}
                </td>
                <td style="padding:14px;vertical-align:middle;font-size:.85rem;color:#6b7280;white-space:nowrap">
                    <div>{{ $cert->issued_at->format('d M Y') }}</div>
                    @if($cert->issuedBy)<div style="font-size:.72rem;color:#a5b4fc;margin-top:2px"><i class="bi bi-person-check me-1"></i>{{ $cert->issuedBy->name }}</div>@endif
                </td>
                <td style="padding:14px;vertical-align:middle">
                    <div class="d-flex gap-1">
                        <a href="{{ route('certificate.pdf', $cert->verification_token) }}" class="btn btn-sm" style="background:var(--navy);color:var(--gold-light);border:none;border-radius:6px;font-size:.72rem" title="Download PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <a href="{{ $cert->verificationUrl() }}" target="_blank" class="btn btn-sm" style="background:#f0f4ff;color:var(--navy-mid);border:none;border-radius:6px;font-size:.72rem" title="Verifikasi">
                            <i class="bi bi-patch-check"></i>
                        </a>
                        <form method="POST" action="{{ route('certificate.destroy', $cert) }}" onsubmit="return confirm('Hapus sertifikat {{ addslashes($cert->nama) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="background:#fef2f2;color:#b91c1c;border:none;border-radius:6px;font-size:.72rem" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada sertifikat. <a href="{{ route('certificate.index') }}">Generate sekarang →</a></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($certificates->hasPages())
<div class="d-flex justify-content-center mt-4">{{ $certificates->links() }}</div>
@endif
<div class="text-center mt-2 text-muted" style="font-size:.78rem">Menampilkan {{ $certificates->firstItem() }}–{{ $certificates->lastItem() }} dari {{ $certificates->total() }} sertifikat</div>

@endsection
