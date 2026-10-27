@extends('layouts.app')

@section('title', 'Rekap Aktivitas & Laporan PKL')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Rekap Aktivitas & Laporan Akhir PKL</h1>
        <div class="page-subtitle">
            Mahasiswa: <strong>{{ $internship->student->user->name ?? '-' }}</strong> &bull; {{ $internship->company->company_name ?? '-' }}
        </div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('documents.pdf', $internship) }}" class="btn btn-primary">
            Download PDF Resmi
        </a>
        <a href="{{ route('documents.docx', $internship) }}" class="btn btn-secondary">
            Download Dokumen DOCX
        </a>
    </div>
</div>

<!-- Statistik Ringkas Sesuai PRD Section 8.2 -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-label">Total Hari PKL</div>
        <div class="stat-value">{{ $totalDays }}</div>
        <div class="stat-meta">Hari kerja terdata</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Disetujui Pembimbing</div>
        <div class="stat-value" style="color: var(--success-text);">{{ $approvedCount }}</div>
        <div class="stat-meta">Persentase: {{ $totalDays > 0 ? round(($approvedCount / $totalDays) * 100, 1) : 0 }}%</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Menunggu Verifikasi</div>
        <div class="stat-value" style="color: var(--warning-text);">{{ $pendingCount }}</div>
        <div class="stat-meta">Aktivitas dalam antrean</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Nilai Akhir PKL</div>
        <div class="stat-value" style="color: var(--accent);">
            {{ $internship->evaluation ? $internship->evaluation->final_score : '-' }}
        </div>
        <div class="stat-meta">{{ $internship->evaluation ? 'Skala 100' : 'Belum dievaluasi' }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Statistik Bidang Kerja -->
    <div class="card">
        <h2 class="card-title" style="margin-bottom: 12px;">Distribusi Bidang / Divisi Pekerjaan</h2>
        @if($workFieldStats->isEmpty())
            <p style="font-size: 13px; color: var(--text-muted);">Belum ada data aktivitas terinput.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($workFieldStats as $field => $count)
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <strong>{{ $field }}</strong>
                            <span>{{ $count }} Hari ({{ round(($count / $totalDays) * 100) }}%)</span>
                        </div>
                        <div style="height: 8px; background: var(--bg-subtle); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ ($count / $totalDays) * 100 }}%; background: var(--accent);"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Statistik Tools yang Digunakan -->
    <div class="card">
        <h2 class="card-title" style="margin-bottom: 12px;">Frekuensi Tools & Perangkat Digunakan</h2>
        @if(empty($toolsStats))
            <p style="font-size: 13px; color: var(--text-muted);">Belum ada perangkat atau aplikasi yang dicatat.</p>
        @else
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach($toolsStats as $tool => $freq)
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: var(--radius-md); font-size: 13px;">
                        <strong>{{ $tool }}</strong>
                        <span class="badge badge-draft" style="font-size: 11px;">{{ $freq }}x digunakan</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Laporan Akhir & Hasil Evaluasi -->
@if($internship->evaluation)
<div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--success);">
    <div class="card-header">
        <h2 class="card-title">Rincian Penilaian Akhir Akademik</h2>
        <span class="badge badge-approved" style="font-size: 15px; padding: 4px 12px;">Nilai Kumulatif: {{ $internship->evaluation->final_score }}</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 16px;">
        <div class="stat-card" style="padding: 12px;">
            <div class="stat-label">Kehadiran (20%)</div>
            <div style="font-size: 24px; font-weight: 700;">{{ $internship->evaluation->attendance_score }}</div>
            <div class="stat-meta">Kontribusi: {{ round($internship->evaluation->attendance_score * 0.20, 2) }}</div>
        </div>
        <div class="stat-card" style="padding: 12px;">
            <div class="stat-label">Disiplin (20%)</div>
            <div style="font-size: 24px; font-weight: 700;">{{ $internship->evaluation->discipline_score }}</div>
            <div class="stat-meta">Kontribusi: {{ round($internship->evaluation->discipline_score * 0.20, 2) }}</div>
        </div>
        <div class="stat-card" style="padding: 12px;">
            <div class="stat-label">Aktivitas (30%)</div>
            <div style="font-size: 24px; font-weight: 700;">{{ $internship->evaluation->activity_score }}</div>
            <div class="stat-meta">Kontribusi: {{ round($internship->evaluation->activity_score * 0.30, 2) }}</div>
        </div>
        <div class="stat-card" style="padding: 12px;">
            <div class="stat-label">Laporan (30%)</div>
            <div style="font-size: 24px; font-weight: 700;">{{ $internship->evaluation->report_score }}</div>
            <div class="stat-meta">Kontribusi: {{ round($internship->evaluation->report_score * 0.30, 2) }}</div>
        </div>
    </div>
    @if($internship->evaluation->notes)
        <div style="background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
            <strong style="font-size: 13px; color: var(--text-primary);">Catatan Evaluasi Dosen Pembimbing:</strong>
            <p style="font-size: 14px; margin-top: 4px; color: var(--text-secondary);">{{ $internship->evaluation->notes }}</p>
        </div>
    @endif
</div>
@endif

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Aktivitas Terverifikasi untuk Dokumen Resmi</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 70px; text-align: center;">Hari</th>
                    <th style="width: 110px;">Tanggal</th>
                    <th style="width: 160px;">Divisi</th>
                    <th>Aktivitas</th>
                    <th style="width: 130px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($internship->activities as $act)
                <tr>
                    <td style="font-weight: 700; text-align: center;">{{ $act->day_number }}</td>
                    <td>{{ $act->activity_date ? $act->activity_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $act->work_field }}</td>
                    <td>{{ Str::limit($act->activities, 100) }}</td>
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
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada aktivitas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
