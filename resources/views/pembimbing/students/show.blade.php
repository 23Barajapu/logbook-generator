@extends('layouts.app')

@section('title', 'Logbook: ' . ($internship->student->user->name ?? 'Mahasiswa'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $internship->student->user->name ?? '-' }}</h1>
        <div class="page-subtitle">
            NIM: {{ $internship->student->nim ?? '-' }} &bull; {{ $internship->student->study_program ?? '-' }} &bull; Angkatan: {{ $internship->student->batch_year ?? '-' }}
        </div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('pembimbing.students') }}" class="btn btn-secondary">
            &larr; Kembali
        </a>
        <a href="{{ route('pembimbing.students.evaluate', $internship) }}" class="btn btn-primary">
            {{ $internship->evaluation ? 'Ubah Penilaian PKL' : 'Beri Penilaian PKL' }}
        </a>
        <a href="{{ route('documents.pdf', $internship) }}" class="btn btn-secondary">
            Download PDF
        </a>
        <a href="{{ route('documents.docx', $internship) }}" class="btn btn-secondary">
            Download DOCX
        </a>
    </div>
</div>

<!-- Info Perusahaan & Penilaian -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom: 12px;">Data Penugasan PKL</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 14px;">
            <div>
                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Perusahaan Mitra</strong>
                <p style="font-weight: 600; margin-top: 2px;">{{ $internship->company->company_name ?? '-' }}</p>
                <p style="font-size: 12px; color: var(--text-secondary);">{{ $internship->company->address ?? '-' }}</p>
            </div>
            <div>
                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Pembimbing Lapangan</strong>
                <p style="font-weight: 600; margin-top: 2px;">{{ $internship->company->field_supervisor ?? '-' }}</p>
                <p style="font-size: 12px; color: var(--text-secondary);">{{ $internship->company->field_supervisor_position ?? 'Mentor' }}</p>
            </div>
            <div>
                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Periode Magang</strong>
                <p style="margin-top: 2px;">
                    {{ $internship->start_date ? $internship->start_date->format('d M Y') : '-' }} &mdash; {{ $internship->end_date ? $internship->end_date->format('d M Y') : '-' }}
                </p>
            </div>
            <div>
                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Status Magang</strong>
                <p style="margin-top: 2px;"><span class="badge badge-approved">{{ strtoupper($internship->status) }}</span></p>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom: 12px;">Status Nilai Akhir</h2>
        @if($internship->evaluation)
            <div style="text-align: center; padding: 12px 0;">
                <div style="font-size: 36px; font-weight: 700; color: var(--success-text);">
                    {{ $internship->evaluation->final_score }}
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Skala 0 - 100</div>
                <div style="margin-top: 12px; font-size: 12px; text-align: left; background: var(--bg-subtle); padding: 8px 12px; border-radius: var(--radius-sm);">
                    Hadir: <strong>{{ $internship->evaluation->attendance_score }}</strong> | Disiplin: <strong>{{ $internship->evaluation->discipline_score }}</strong> | Aktivitas: <strong>{{ $internship->evaluation->activity_score }}</strong> | Laporan: <strong>{{ $internship->evaluation->report_score }}</strong>
                </div>
            </div>
        @else
            <div style="text-align: center; padding: 20px 0;">
                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 12px;">Mahasiswa ini belum memiliki penilaian akhir.</p>
                <a href="{{ route('pembimbing.students.evaluate', $internship) }}" class="btn btn-primary btn-sm">
                    Input Penilaian Sekarang
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Daftar Aktivitas Harian Mahasiswa -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Aktivitas Harian ({{ $internship->activities->count() }} Hari)</h2>
    </div>

    @if($internship->activities->isEmpty())
        <div class="empty-state" style="padding: 24px;">
            <p class="empty-state-desc">Mahasiswa belum menginputkan catatan aktivitas harian.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">Hari</th>
                        <th style="width: 110px;">Tanggal</th>
                        <th style="width: 150px;">Bidang Kerja</th>
                        <th>Aktivitas & Bukti</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 140px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($internship->activities as $act)
                    <tr class="{{ $act->status === 'Menunggu Approval' ? 'table-row-pending' : '' }}">
                        <td style="font-weight: 700; text-align: center;">{{ $act->day_number }}</td>
                        <td>{{ $act->activity_date ? $act->activity_date->format('d/m/Y') : '-' }}</td>
                        <td style="font-weight: 600;">{{ $act->work_field }}</td>
                        <td>
                            <div style="color: var(--text-primary);">{{ Str::limit($act->activities, 120) }}</div>
                            @if($act->attachments->count() > 0)
                                <div style="margin-top: 4px; font-size: 11px; color: var(--accent-blue);">
                                    &#128206; {{ $act->attachments->count() }} File Lampiran Tersedia
                                </div>
                            @endif
                            @php $latestAppr = $act->approvals->last(); @endphp
                            @if($latestAppr && $latestAppr->notes)
                                <div style="font-size: 12px; color: var(--text-secondary); background: var(--bg-subtle); padding: 4px 8px; border-radius: var(--radius-sm); margin-top: 4px;">
                                    Catatan: {{ $latestAppr->notes }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($act->status === 'Disetujui')
                                <span class="badge badge-approved">Disetujui</span>
                            @elseif($act->status === 'Menunggu Approval')
                                <span class="badge badge-pending">Menunggu Approval</span>
                            @elseif($act->status === 'Revisi')
                                <span class="badge badge-revision">Perlu Revisi</span>
                            @else
                                <span class="badge badge-draft">Draft</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('pembimbing.activities.review', $act) }}" class="btn btn-secondary btn-sm">
                                Review & Putusan
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
