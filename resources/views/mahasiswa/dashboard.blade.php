@extends('layouts.app')

@section('title', 'Dashboard Mahasiswa')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Selamat Datang, {{ auth()->user()->name }}</h1>
        <div class="page-subtitle">
            NIM: {{ auth()->user()->student?->nim ?? '—' }} &bull; {{ auth()->user()->student?->study_program ?? '—' }}
        </div>
    </div>
    @if($internship)
    <div class="page-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('mahasiswa.activities.create') }}" class="btn btn-primary" id="btn-catat-aktivitas">
            + Catat Aktivitas
        </a>
        <button type="button" class="btn btn-secondary" onclick="openExportModal()" id="btn-open-export-modal">
            📄 Unduh Logbook (.docx / .pdf)
        </button>
        {{-- Quick link langsung untuk backward compat --}}
        <a href="{{ route('documents.docx', $internship) }}" class="btn btn-secondary" id="btn-download-docx" style="display: none;">
            ↓ DOCX
        </a>
        <a href="{{ route('documents.pdf', $internship) }}" class="btn btn-secondary" id="btn-download-pdf" style="display: none;">
            ↓ PDF
        </a>
    </div>
    @endif
</div>

@if(!$internship)
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📋</div>
        <h2 class="empty-state-title">Belum Terdaftar di PKL</h2>
        <p class="empty-state-desc">Anda belum mengisi informasi tempat PKL atau dosen pembimbing. Silakan input sekarang untuk memulai pengisian logbook.</p>
        <a href="{{ route('mahasiswa.internship.edit') }}" class="btn btn-primary" style="margin-top: 14px;">
            + Input Data PKL & Dosen Pembimbing
        </a>
    </div>
@else
    {{-- Stat cards --}}
    <div class="grid-stats" role="region" aria-label="Ringkasan statistik aktivitas">
        <div class="stat-card">
            <div class="stat-icon" aria-hidden="true">📅</div>
            <div class="stat-label">Total Aktivitas</div>
            <div class="stat-value">{{ $stats['total_activities'] }}</div>
            <div class="stat-meta">Hari tercatat</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--success-dim);" aria-hidden="true">✓</div>
            <div class="stat-label">Disetujui</div>
            <div class="stat-value" style="color:var(--success-text);">{{ $stats['approved'] }}</div>
            <div class="stat-meta">Masuk logbook resmi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--warning-dim);" aria-hidden="true">⏳</div>
            <div class="stat-label">Menunggu Approval</div>
            <div class="stat-value" style="color:var(--warning-text);">{{ $stats['pending'] }}</div>
            <div class="stat-meta">Dalam antrean dosen</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--danger-dim);" aria-hidden="true">!</div>
            <div class="stat-label">Perlu Revisi</div>
            <div class="stat-value" style="color:var(--danger-text);">{{ $stats['revision'] }}</div>
            <div class="stat-meta">Harus diperbaiki</div>
        </div>
    </div>

    {{-- Info PKL --}}
    <div class="card">
        <div class="card-header" style="flex-wrap: wrap;">
            <div>
                <h2 class="card-title">Informasi Penugasan PKL</h2>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <span class="badge badge-approved">{{ strtoupper($internship->status) }}</span>
                <a href="{{ route('mahasiswa.internship.edit') }}" class="btn btn-secondary btn-sm" id="btn-edit-pkl">
                    ✏️ Edit Data PKL & Pembimbing
                </a>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-item-label">Perusahaan / Instansi</div>
                <div class="info-item-value">{{ $internship->company->company_name ?? '—' }}</div>
                <div class="info-item-sub">{{ $internship->company->address ?? '' }}</div>
            </div>
            <div class="info-item">
                <div class="info-item-label">Pembimbing Lapangan</div>
                <div class="info-item-value">{{ $internship->company->field_supervisor ?? '—' }}</div>
                <div class="info-item-sub">{{ $internship->company->field_supervisor_position ?? 'Mentor' }}</div>
            </div>
            <div class="info-item">
                <div class="info-item-label">Dosen Pembimbing Akademik</div>
                <div class="info-item-value">{{ $internship->supervisor->user->name ?? '—' }}</div>
                <div class="info-item-sub">NIP: {{ $internship->supervisor->employee_number ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-item-label">Periode Magang</div>
                <div class="info-item-value">
                    {{ $internship->start_date?->format('d M Y') ?? '—' }} &mdash; {{ $internship->end_date?->format('d M Y') ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Penilaian akhir --}}
    @if($internship->evaluation)
    <div class="card" style="border-left: 3px solid var(--accent);">
        <div class="card-header">
            <h2 class="card-title">Hasil Penilaian Akhir PKL</h2>
            <span class="badge badge-approved" style="font-size:13px;padding:5px 12px;">
                Nilai Akhir: {{ $internship->evaluation->final_score }}
            </span>
        </div>
        <div class="score-grid">
            <div class="score-item">
                <div class="score-item-label">Kehadiran (20%)</div>
                <div class="score-item-value">{{ $internship->evaluation->attendance_score }}</div>
            </div>
            <div class="score-item">
                <div class="score-item-label">Disiplin (20%)</div>
                <div class="score-item-value">{{ $internship->evaluation->discipline_score }}</div>
            </div>
            <div class="score-item">
                <div class="score-item-label">Aktivitas (30%)</div>
                <div class="score-item-value">{{ $internship->evaluation->activity_score }}</div>
            </div>
            <div class="score-item">
                <div class="score-item-label">Laporan (30%)</div>
                <div class="score-item-value">{{ $internship->evaluation->report_score }}</div>
            </div>
        </div>
        @if($internship->evaluation->notes)
            <div style="font-size:13px;color:var(--text-secondary);background:var(--bg-elevated);padding:12px;border-radius:var(--r-md);">
                <strong>Catatan Evaluasi:</strong> {{ $internship->evaluation->notes }}
            </div>
        @endif
    </div>
    @endif

    {{-- Aktivitas terbaru --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Aktivitas Harian Terbaru</h2>
            <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary btn-sm" id="btn-lihat-semua">
                Lihat Semua
            </a>
        </div>

        @if($recentActivities->isEmpty())
            <div class="empty-state" style="padding:32px 16px;">
                <p class="empty-state-desc">Belum ada catatan aktivitas. Mulai catat hari pertama Anda.</p>
                <a href="{{ route('mahasiswa.activities.create') }}" class="btn btn-primary" id="btn-catat-pertama">
                    Catat Aktivitas Pertama
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table" aria-label="Aktivitas harian terbaru">
                    <thead>
                        <tr>
                            <th scope="col">Hari</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Bidang Kerja</th>
                            <th scope="col">Kegiatan</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivities as $act)
                        <tr>
                            <td style="font-weight:700;text-align:center;">{{ $act->day_number }}</td>
                            <td>{{ $act->activity_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $act->work_field }}</td>
                            <td style="max-width:280px;">
                                <div style="font-weight:500;">{{ Str::limit($act->activities, 80) }}</div>
                                @if($act->attachments->count() > 0)
                                    <span style="font-size:11px;color:var(--text-muted);margin-top:3px;display:block;">
                                        📎 {{ $act->attachments->count() }} lampiran
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($act->status === 'Disetujui')
                                    <span class="badge badge-approved">Disetujui</span>
                                @elseif($act->status === 'Menunggu Approval')
                                    <span class="badge badge-pending">Menunggu</span>
                                @elseif($act->status === 'Revisi')
                                    <span class="badge badge-revision">Revisi</span>
                                @else
                                    <span class="badge badge-draft">Draft</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('mahasiswa.activities.show', $act) }}"
                                   class="btn btn-secondary btn-sm"
                                   id="btn-detail-{{ $act->id }}">Detail</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

@if($internship)
    @include('documents.export-modal')
@endif
@endsection
