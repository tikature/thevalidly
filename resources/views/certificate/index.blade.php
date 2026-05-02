@extends('layouts.app')

@section('title', 'Generator Sertifikat')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--navy)">
            <i class="bi bi-award me-2" style="color:var(--gold)"></i>Generator Sertifikat
        </h4>
        <small class="text-muted">{{ auth()->user()->institution->name ?? '' }}</small>
    </div>
</div>

{{-- Placeholder: fitur generator akan diimplementasi pada Iterasi 2 --}}
<div class="card border-0 shadow-sm text-center py-5" style="border-radius:14px">
    <div class="card-body">
        <div class="mb-3" style="font-size:3rem; color: var(--gold)">
            <i class="bi bi-award"></i>
        </div>
        <h5 class="fw-bold mb-2" style="color:var(--navy)">Fitur Generator</h5>
        <p class="text-muted mb-0" style="font-size:.9rem">
            Hallo
        </p>
    </div>
</div>

@endsection
