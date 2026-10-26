<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DailyLog PKL') — Sistem Logbook PKL</title>
    <meta name="description" content="Platform manajemen aktivitas harian, approval logbook, dan generator dokumen PKL.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('styles')
</head>
<body>

@auth
<div class="app-shell">
    {{-- Sidebar overlay (mobile) --}}
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()" aria-hidden="true"></div>

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar" aria-label="Navigasi Utama">
        <a href="{{ url('/') }}" class="sidebar-brand">
            <div class="sidebar-brand-mark" aria-hidden="true">D</div>
            <div>
                <div class="sidebar-brand-name">DailyLog PKL</div>
                <div class="sidebar-brand-role">{{ strtoupper(auth()->user()->role) }}</div>
            </div>
        </a>

        <nav class="sidebar-nav">
            @if(auth()->user()->isStudent())
                <span class="sidebar-label">Mahasiswa</span>
                <a href="{{ route('mahasiswa.dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('mahasiswa.dashboard') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('mahasiswa.dashboard') ? 'page' : 'false' }}">
                    <span aria-hidden="true">◎</span> Dashboard
                </a>
                <a href="{{ route('mahasiswa.activities') }}"
                   class="sidebar-link {{ request()->routeIs('mahasiswa.activities*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('mahasiswa.activities*') ? 'page' : 'false' }}">
                    <span aria-hidden="true">◈</span> Aktivitas Harian
                </a>
                <a href="{{ route('mahasiswa.internship.edit') }}"
                   class="sidebar-link {{ request()->routeIs('mahasiswa.internship*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('mahasiswa.internship*') ? 'page' : 'false' }}">
                    <span aria-hidden="true">◉</span> Data PKL & Pembimbing
                </a>
                @php $sidebarInternship = auth()->user()->student?->activeInternship(); @endphp
                @if($sidebarInternship)
                    <a href="{{ route('documents.summary', $sidebarInternship) }}"
                       class="sidebar-link {{ request()->routeIs('documents.summary') ? 'active' : '' }}">
                        <span aria-hidden="true">📄</span> Rekap & Laporan
                    </a>
                @endif

            @elseif(auth()->user()->isSupervisor())
                <span class="sidebar-label">Pembimbing</span>
                <a href="{{ route('pembimbing.dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('pembimbing.dashboard') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('pembimbing.dashboard') ? 'page' : 'false' }}">
                    <span aria-hidden="true">◎</span> Dashboard
                </a>
                <a href="{{ route('pembimbing.students') }}"
                   class="sidebar-link {{ request()->routeIs('pembimbing.students*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('pembimbing.students*') ? 'page' : 'false' }}">
                    <span aria-hidden="true">◈</span> Mahasiswa Bimbingan
                </a>

            @elseif(auth()->user()->isAdmin())
                <span class="sidebar-label">Administrator</span>
                <a href="{{ route('admin.dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}">
                    <span aria-hidden="true">◎</span> Dashboard
                </a>

                <span class="sidebar-label">Master Data</span>
                <a href="{{ route('admin.students') }}"
                   class="sidebar-link {{ request()->routeIs('admin.students*') ? 'active' : '' }}">
                    <span aria-hidden="true">◈</span> Mahasiswa
                </a>
                <a href="{{ route('admin.supervisors') }}"
                   class="sidebar-link {{ request()->routeIs('admin.supervisors*') ? 'active' : '' }}">
                    <span aria-hidden="true">◈</span> Pembimbing
                </a>
                <a href="{{ route('admin.companies') }}"
                   class="sidebar-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}">
                    <span aria-hidden="true">◈</span> Perusahaan
                </a>
                <a href="{{ route('admin.internships') }}"
                   class="sidebar-link {{ request()->routeIs('admin.internships*') ? 'active' : '' }}">
                    <span aria-hidden="true">◉</span> Penugasan PKL
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div style="flex:1;min-width:0;">
                    <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                    <div class="sidebar-user-email">{{ auth()->user()->email }}</div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm w-full" aria-label="Keluar dari akun">
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Main area --}}
    <div class="main-area">
        <header class="topbar">
            <button class="topbar-hamburger" id="hamburgerBtn" onclick="openSidebar()"
                    aria-label="Buka navigasi" aria-expanded="false" aria-controls="sidebar">
                ☰
            </button>
            <div class="topbar-breadcrumb">
                DailyLog PKL &rsaquo; <strong>@yield('title', 'Halaman')</strong>
            </div>
        </header>

        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <div>
                        <strong style="display:block;margin-bottom:6px;">Terdapat kesalahan pada input:</strong>
                        <ul style="padding-left:18px;">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="site-footer">
            &copy; {{ date('Y') }} DailyLog PKL &mdash; Platform Manajemen Aktivitas & Generator Logbook PKL.
        </footer>
    </div>
</div>

@else
{{-- Auth pages (no sidebar) --}}
<main>
    @if(session('success'))
        <div style="position:fixed;top:16px;right:16px;z-index:100;">
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        </div>
    @endif
    @yield('content')
</main>
@endauth

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
    document.getElementById('hamburgerBtn').setAttribute('aria-expanded', 'true');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.getElementById('hamburgerBtn').setAttribute('aria-expanded', 'false');
}
</script>

@yield('scripts')
</body>
</html>
