@extends('layouts.app')

@section('title', 'Detail Aktivitas Hari Ke-' . $activity->day_number)

@section('content')
<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
            <h1 class="page-title">Aktivitas Hari Ke-{{ $activity->day_number }}</h1>
            @if($activity->status === 'Disetujui')
                <span class="badge badge-approved">Disetujui</span>
            @elseif($activity->status === 'Menunggu Approval')
                <span class="badge badge-pending">Menunggu Approval</span>
            @elseif($activity->status === 'Revisi')
                <span class="badge badge-revision">Perlu Revisi</span>
            @else
                <span class="badge badge-draft">Draft</span>
            @endif
        </div>
        <div class="page-subtitle">
            Tanggal: {{ $activity->activity_date ? $activity->activity_date->translatedFormat('l, d F Y') : '-' }} &bull; Bidang: {{ $activity->work_field }}
        </div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary">
            &larr; Daftar Aktivitas
        </a>
        @if(in_array($activity->status, ['Draft', 'Revisi']))
            <a href="{{ route('mahasiswa.activities.edit', $activity) }}" class="btn btn-secondary">
                Edit Aktivitas
            </a>
            <form action="{{ route('mahasiswa.activities.submit', $activity) }}" method="POST" style="display:inline;" onsubmit="return confirm('Ajukan aktivitas ini ke pembimbing?');">
                @csrf
                <button type="submit" class="btn btn-primary">
                    Ajukan Approval
                </button>
            </form>
        @endif
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Konten Utama Aktivitas -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card">
            <h2 class="card-title" style="margin-bottom: 12px;">Uraian Kegiatan</h2>
            <div style="font-size: 15px; line-height: 1.7; white-space: pre-line; color: var(--text-primary);">
                {{ $activity->activities }}
            </div>

            @if($activity->tools_used)
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-light);">
                    <strong style="font-size: 13px; color: var(--text-muted); display: block; margin-bottom: 4px;">Tools / Aplikasi yang Digunakan:</strong>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        @foreach(explode(',', $activity->tools_used) as $tool)
                            <span class="badge badge-draft" style="font-size: 12px;">{{ trim($tool) }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="card">
            <h2 class="card-title" style="margin-bottom: 12px;">Kendala & Solusi</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div style="background: var(--bg-subtle); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
                    <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 6px;">Kendala yang Dihadapi</strong>
                    <p style="font-size: 14px; color: var(--text-primary); margin: 0;">
                        {{ $activity->problems ?: 'Tidak ada kendala yang berarti.' }}
                    </p>
                </div>
                <div style="background: var(--bg-subtle); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
                    <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 6px;">Solusi yang Diterapkan</strong>
                    <p style="font-size: 14px; color: var(--text-primary); margin: 0;">
                        {{ $activity->solutions ?: 'Aktivitas berjalan lancar sesuai rencana.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Lampiran Bukti Kegiatan -->
        <div class="card">
            <h2 class="card-title" style="margin-bottom: 12px;">Bukti Dokumentasi Kegiatan ({{ $activity->attachments->count() }})</h2>
            @if($activity->attachments->isEmpty())
                <p style="font-size: 14px; color: var(--text-muted); margin: 0;">Tidak ada file lampiran yang diunggah untuk aktivitas ini.</p>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                    @foreach($activity->attachments as $att)
                        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 10px; background: var(--bg-surface); display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                @if($att->isImage())
                                    <div style="height: 120px; overflow: hidden; border-radius: var(--radius-sm); margin-bottom: 8px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                        <img src="{{ asset('storage/' . $att->file_path) }}" alt="{{ $att->file_name }}" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                    </div>
                                @else
                                    <div style="height: 60px; display: flex; align-items: center; justify-content: center; font-size: 28px; background: var(--bg-subtle); border-radius: var(--radius-sm); margin-bottom: 8px;">
                                        &#128196;
                                    </div>
                                @endif
                                <div style="font-size: 13px; font-weight: 600; color: var(--text-primary); word-break: break-all;">
                                    {{ $att->file_name }}
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    {{ strtoupper($att->file_type) }} &bull; {{ round($att->file_size / 1024, 1) }} KB
                                </div>
                            </div>
                            <div style="margin-top: 10px;">
                                <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="width: 100%;">
                                    Unduh / Buka
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Riwayat Persetujuan / Catatan Pembimbing -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <div class="card">
            <h2 class="card-title" style="margin-bottom: 12px;">Riwayat Persetujuan</h2>
            @if($activity->approvals->isEmpty())
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">
                    Aktivitas ini belum pernah direview oleh pembimbing akademik.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($activity->approvals as $appr)
                        <div style="border-left: 3px solid {{ $appr->status === 'Disetujui' ? 'var(--success-border)' : 'var(--danger-border)' }}; padding-left: 12px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                                <strong style="font-size: 13px;">{{ $appr->status }}</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">{{ $appr->approved_at ? $appr->approved_at->format('d/m/Y H:i') : '-' }}</span>
                            </div>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                Oleh: {{ $appr->supervisor->user->name ?? 'Pembimbing' }}
                            </div>
                            @if($appr->notes)
                                <div style="font-size: 13px; color: var(--text-primary); margin-top: 4px; background: var(--bg-subtle); padding: 8px; border-radius: var(--radius-sm);">
                                    &ldquo;{{ $appr->notes }}&rdquo;
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <h2 class="card-title" style="margin-bottom: 8px;">Informasi Dokumen</h2>
            <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">
                <p>Status: <strong>{{ $activity->status }}</strong></p>
                <p>Dibuat: {{ $activity->created_at->format('d/m/Y H:i') }}</p>
                <p>Terakhir Diubah: {{ $activity->updated_at->format('d/m/Y H:i') }}</p>
                <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-light);">
                    <a href="{{ route('documents.pdf', $internship) }}" class="btn btn-secondary btn-sm" style="width: 100%; margin-bottom: 6px;">
                        Cetak PDF Logbook
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
