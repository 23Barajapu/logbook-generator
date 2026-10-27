@extends('layouts.app')

@section('title', 'Dashboard Administrator')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Dashboard Administrator</h1>
        <div class="page-subtitle">Pusat kendali master data, penempatan PKL, pembimbing, dan institusi mitra.</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.internships.create') }}" class="btn btn-primary" id="btn-tambah-penugasan">
            + Tambah Penugasan PKL
        </a>
    </div>
</div>

<div class="grid-stats" role="region" aria-label="Ringkasan statistik sistem">
    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        </div>
        <div class="stat-label">Total Mahasiswa</div>
        <div class="stat-value">{{ $stats['total_students'] }}</div>
        <div class="stat-meta"><a href="{{ route('admin.students') }}">Kelola data &rarr;</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
        </div>
        <div class="stat-label">Dosen Pembimbing</div>
        <div class="stat-value">{{ $stats['total_supervisors'] }}</div>
        <div class="stat-meta"><a href="{{ route('admin.supervisors') }}">Kelola pembimbing &rarr;</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="22" x2="9" y2="22.01"></line><line x1="15" y1="22" x2="15" y2="22.01"></line><line x1="9" y1="6" x2="9" y2="6.01"></line><line x1="15" y1="6" x2="15" y2="6.01"></line><line x1="9" y1="10" x2="9" y2="10.01"></line><line x1="15" y1="10" x2="15" y2="10.01"></line><line x1="9" y1="14" x2="9" y2="14.01"></line><line x1="15" y1="14" x2="15" y2="14.01"></line><line x1="9" y1="18" x2="9" y2="18.01"></line><line x1="15" y1="18" x2="15" y2="18.01"></line></svg>
        </div>
        <div class="stat-label">Perusahaan Mitra</div>
        <div class="stat-value">{{ $stats['total_companies'] }}</div>
        <div class="stat-meta"><a href="{{ route('admin.companies') }}">Kelola mitra &rarr;</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--accent-dim);" aria-hidden="true">◎</div>
        <div class="stat-label">PKL Aktif</div>
        <div class="stat-value" style="color:var(--accent-hover);">{{ $stats['active_internships'] }}</div>
        <div class="stat-meta"><a href="{{ route('admin.internships') }}">Kelola penugasan &rarr;</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Penugasan PKL Mahasiswa Terbaru</h2>
        <a href="{{ route('admin.internships') }}" class="btn btn-secondary btn-sm" id="btn-lihat-semua-penugasan">
            Lihat Semua
        </a>
    </div>

    @if($recentInternships->isEmpty())
        <div class="empty-state" style="padding:32px;">
            <p class="empty-state-desc">Belum ada data penugasan PKL.</p>
            <a href="{{ route('admin.internships.create') }}" class="btn btn-primary btn-sm" id="btn-buat-penugasan">
                + Buat Penugasan Baru
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table" aria-label="Penugasan PKL terbaru">
                <thead>
                    <tr>
                        <th scope="col">Mahasiswa</th>
                        <th scope="col">Perusahaan</th>
                        <th scope="col">Dosen Pembimbing</th>
                        <th scope="col">Periode</th>
                        <th scope="col">Status</th>
                        <th scope="col" style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentInternships as $intern)
                    <tr>
                        <td>
                            <strong style="display:block;">{{ $intern->student->user->name ?? '—' }}</strong>
                            <span style="font-size:11px;color:var(--text-muted);">NIM: {{ $intern->student->nim ?? '—' }}</span>
                        </td>
                        <td style="font-weight:600;">{{ $intern->company->company_name ?? '—' }}</td>
                        <td>{{ $intern->supervisor->user->name ?? 'Belum Ditentukan' }}</td>
                        <td>
                            {{ $intern->start_date?->format('d/m/Y') ?? '—' }} &mdash; {{ $intern->end_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td>
                            <span class="badge {{ $intern->status === 'aktif' ? 'badge-approved' : 'badge-draft' }}">
                                {{ strtoupper($intern->status) }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('admin.internships.edit', $intern) }}"
                               class="btn btn-secondary btn-sm"
                               id="btn-edit-internship-{{ $intern->id }}">Edit</a>
                            <a href="{{ route('documents.summary', $intern) }}"
                               class="btn btn-secondary btn-sm"
                               id="btn-rekap-internship-{{ $intern->id }}">Rekap</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
