
{{-- NAVBAR --}}
<nav class="navbar navbar-expand-lg navbar-validly py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand navbar-brand-text" href="{{ route('landing') }}">
            <img src="{{ asset('validly-logo1.svg') }}" alt="Validly" 
            style="height: 25px; width: 25px; margin-right: 3px; background: var(--gold); border-radius: 7px; padding: 2px;">
            <span style="color: var(--gold); font-size: 25px; font-family: serif; font-weight: bold;">Validly</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center gap-1">

                @auth
                    @if(auth()->user()->isSuperAdmin())
                        {{-- Super Admin: hanya 1 menu --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.*') ? 'active' : '' }}"
                               href="{{ route('superadmin.index') }}">
                                <i class="bi bi-shield-check me-1"></i>Super Admin
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <span class="text-white-50" style="font-size:0.8rem">
                                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                            </span>
                        </li>
                    @else
                        {{-- Admin Lembaga: hanya Generator & Profile --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('certificate.index') ? 'active' : '' }}"
                               href="{{ route('certificate.index') }}">
                                <i class="bi bi-award me-1"></i>Generator
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('certificate.history') ? 'active' : '' }}"
                               href="{{ route('certificate.history') }}">
                                <i class="bi bi-clock-history me-1"></i>Riwayat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                               href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-circle me-1"></i>
                                {{ auth()->user()->name }}
                            </a>
                        </li>
                    @endif

                    {{-- Logout --}}
                    <li class="nav-item ms-2">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-logout">
                                <i class="bi bi-box-arrow-right me-1"></i>Keluar
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="btn btn-logout" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                        </a>
                    </li>
                @endauth

            </ul>
        </div>
    </div>
</nav>