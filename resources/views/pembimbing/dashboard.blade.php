@extends('layouts.app')

@section('title', 'Dashboard Pembimbing')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Dashboard Pembimbing</h1>
        <div class="page-subtitle">
            {{ auth()->user()->name }} &bull; NIP/NIDN: {{ $supervisor?->employee_number ?? '—' }}
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('pembimbing.students') }}" class="btn btn-primary" id="btn-mahasiswa-bimbingan">
            Mahasiswa Bimbingan
        </a>
    </div>
</div>

<div class="grid-stats" role="region" aria-label="Ringkasan statistik pembimbing">
    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">👥</div>
        <div class="stat-label">Mahasiswa Bimbingan</div>
        <div class="stat-value">{{ $stats['total_students'] }}</div>
        <div class="stat-meta">Total mahasiswa aktif</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--warning-dim);" aria-hidden="true">⏳</div>
        <div class="stat-label">Menunggu Approval</div>
        <div class="stat-value" style="color:var(--warning-text);">{{ $stats['pending_approval'] }}</div>
        <div class="stat-meta">Aktivitas butuh verifikasi</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--success-dim);" aria-hidden="true">✓</div>
        <div class="stat-label">Sudah Dinilai</div>
        <div class="stat-value" style="color:var(--success-text);">{{ $stats['total_evaluated'] }}</div>
        <div class="stat-meta">Evaluasi akhir selesai</div>
    </div>
</div>

<!-- Antrean Approval Aktivitas -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2 class="card-title">Antrean Approval Aktivitas Mahasiswa</h2>
        <span class="badge {{ $pendingActivities->count() > 0 ? 'badge-pending' : 'badge-draft' }}">
            {{ $pendingActivities->count() }} Menunggu
        </span>
    </div>

    @if($pendingActivities->isEmpty())
        <div class="empty-state" style="padding: 24px;">
            <p class="empty-state-desc">Tidak ada aktivitas mahasiswa yang sedang menunggu persetujuan saat ini.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table" aria-label="Antrean approval aktivitas mahasiswa">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Hari Ke</th>
                        <th>Tanggal</th>
                        <th>Bidang & Uraian Kegiatan</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingActivities as $act)
                    <tr>
                        <td>
                            <strong style="display: block;">{{ $act->internship->student->user->name ?? '-' }}</strong>
                            <span style="font-size: 12px; color: var(--text-muted);">
                                NIM: {{ $act->internship->student->nim ?? '-' }}
                            </span>
                        </td>
                        <td style="font-weight: 700; text-align: center;">{{ $act->day_number }}</td>
                        <td>{{ $act->activity_date ? $act->activity_date->format('d/m/Y') : '-' }}</td>
                        <td style="max-width: 360px;">
                            <span class="badge badge-draft" style="font-size: 11px; margin-bottom: 4px;">{{ $act->work_field }}</span>
                            <div>{{ Str::limit($act->activities, 90) }}</div>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('pembimbing.activities.review', $act) }}" class="btn btn-primary btn-sm">
                                Review & Putuskan
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Daftar Mahasiswa Bimbingan -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Mahasiswa yang Dibimbing</h2>
        <a href="{{ route('pembimbing.students') }}" class="btn btn-secondary btn-sm" id="btn-lihat-semua-mahasiswa">Lihat Semua</a>
    </div>

    @if($internships->isEmpty())
        <div class="empty-state" style="padding: 24px;">
            <p class="empty-state-desc">Belum ada mahasiswa yang ditugaskan kepada Anda.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Instansi / Perusahaan</th>
                        <th>Periode</th>
                        <th>Nilai Akhir</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($internships as $intern)
                    <tr>
                        <td>
                            <strong style="display: block;">{{ $intern->student->user->name ?? '-' }}</strong>
                            <span style="font-size: 12px; color: var(--text-muted);">
                                NIM: {{ $intern->student->nim ?? '-' }} &bull; {{ $intern->student->study_program ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $intern->company->company_name ?? '-' }}</strong>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                Mentor: {{ $intern->company->field_supervisor ?? '-' }}
                            </div>
                        </td>
                        <td>
                            {{ $intern->start_date ? $intern->start_date->format('d/m/Y') : '-' }} &mdash; {{ $intern->end_date ? $intern->end_date->format('d/m/Y') : '-' }}
                        </td>
                        <td>
                            @if($intern->evaluation)
                                <span class="badge badge-approved" style="font-size: 13px;">{{ $intern->evaluation->final_score }}</span>
                            @else
                                <span class="badge badge-draft">Belum Dinilai</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('pembimbing.students.show', $intern) }}" class="btn btn-secondary btn-sm">
                                Lihat Logbook
                            </a>
                            <a href="{{ route('pembimbing.students.evaluate', $intern) }}" class="btn btn-primary btn-sm">
                                {{ $intern->evaluation ? 'Edit Nilai' : 'Beri Nilai' }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
