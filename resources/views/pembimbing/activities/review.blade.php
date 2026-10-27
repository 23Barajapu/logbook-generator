@extends('layouts.app')

@section('title', 'Review Aktivitas Hari Ke-' . $activity->day_number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Review Aktivitas Hari Ke-{{ $activity->day_number }}</h1>
        <div class="page-subtitle">
            Mahasiswa: <strong>{{ $internship->student->user->name ?? '-' }}</strong> (NIM: {{ $internship->student->nim ?? '-' }}) &bull; {{ $internship->company->company_name ?? '-' }}
        </div>
    </div>
    <div>
        <a href="{{ route('pembimbing.students.show', $internship) }}" class="btn btn-secondary">
            &larr; Kembali ke Logbook
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 24px; align-items: start;">
    <!-- Rincian Kegiatan -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 class="card-title">Informasi Kegiatan</h2>
                <span class="badge {{ $activity->status === 'Disetujui' ? 'badge-approved' : ($activity->status === 'Menunggu Approval' ? 'badge-pending' : 'badge-revision') }}">
                    {{ $activity->status }}
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; font-size: 14px; background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm);">
                <div>
                    <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Tanggal Kegiatan</strong>
                    <div style="font-weight: 600;">{{ $activity->activity_date ? $activity->activity_date->translatedFormat('l, d F Y') : '-' }}</div>
                </div>
                <div>
                    <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Divisi / Bidang Kerja</strong>
                    <div style="font-weight: 600;">{{ $activity->work_field }}</div>
                </div>
            </div>

            <h3 style="font-size: 14px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Uraian Aktivitas:</h3>
            <div style="font-size: 15px; line-height: 1.7; white-space: pre-line; color: var(--text-primary); margin-bottom: 16px;">
                {{ $activity->activities }}
            </div>

            @if($activity->tools_used)
                <h3 style="font-size: 14px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Perangkat & Aplikasi:</h3>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px;">
                    @foreach(explode(',', $activity->tools_used) as $tool)
                        <span class="badge badge-draft">{{ trim($tool) }}</span>
                    @endforeach
                </div>
            @endif

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                <div style="background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm);">
                    <strong style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">KENDALA</strong>
                    <p style="font-size: 13px; margin: 0;">{{ $activity->problems ?: 'Tidak ada kendala.' }}</p>
                </div>
                <div style="background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm);">
                    <strong style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">SOLUSI</strong>
                    <p style="font-size: 13px; margin: 0;">{{ $activity->solutions ?: 'Aktivitas berjalan baik.' }}</p>
                </div>
            </div>
        </div>

        <!-- Bukti Dokumentasi Kegiatan -->
        <div class="card">
            <h2 class="card-title" style="margin-bottom: 12px;">Bukti Dokumentasi Kegiatan ({{ $activity->attachments->count() }})</h2>
            @if($activity->attachments->isEmpty())
                <p style="font-size: 14px; color: var(--text-muted); margin: 0;">Mahasiswa tidak mengunggah lampiran untuk hari ini.</p>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
                    @foreach($activity->attachments as $att)
                        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 10px; background: var(--bg-surface);">
                            @if($att->isImage())
                                <div style="height: 100px; overflow: hidden; border-radius: var(--radius-sm); margin-bottom: 8px; background: var(--bg-elevated); display: flex; align-items: center; justify-content: center;">
                                    <img src="{{ asset('storage/' . $att->file_path) }}" alt="{{ $att->file_name }}" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                </div>
                            @else
                                <div style="height: 50px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: var(--bg-subtle); border-radius: var(--radius-sm); margin-bottom: 8px;">
                                    &#128196;
                                </div>
                            @endif
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-primary); word-break: break-all;">
                                {{ $att->file_name }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin: 2px 0 8px;">
                                {{ strtoupper($att->file_type) }} &bull; {{ round($att->file_size / 1024, 1) }} KB
                            </div>
                            <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="width: 100%;">
                                Buka File
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Form Putusan Approval Pembimbing -->
    <div class="card" style="border-top: 4px solid var(--accent);">
        <h2 class="card-title" style="margin-bottom: 16px;">Keputusan Pembimbing</h2>

        <form action="{{ route('pembimbing.activities.approval', $activity) }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label">Tindakan Verifikasi <span style="color: var(--danger-text);">*</span></label>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-surface);">
                        <input type="radio" name="status" value="Disetujui" {{ old('status', $activity->status) === 'Disetujui' ? 'checked' : '' }} required>
                        <div>
                            <strong style="color: var(--success-text); display: block;">Setujui Aktivitas</strong>
                            <span style="font-size: 12px; color: var(--text-muted);">Aktivitas valid dan resmi masuk ke buku logbook PKL.</span>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-surface);">
                        <input type="radio" name="status" value="Revisi" {{ old('status', $activity->status) === 'Revisi' ? 'checked' : '' }}>
                        <div>
                            <strong style="color: var(--danger-text); display: block;">Minta Revisi</strong>
                            <span style="font-size: 12px; color: var(--text-muted);">Aktivitas belum memenuhi standar dan harus diperbaiki mahasiswa.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Catatan / Umpan Balik untuk Mahasiswa</label>
                <textarea id="notes" name="notes" class="form-control" style="min-height: 100px;" placeholder="Tuliskan catatan apresiasi, koreksi, atau instruksi perbaikan...">{{ old('notes', $activity->latestApproval?->notes) }}</textarea>
                <div class="form-hint">Catatan ini akan tampil pada dashboard mahasiswa dan buku logbook.</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                Simpan Putusan Approval
            </button>
        </form>

        @if($activity->approvals->count() > 0)
            <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                <strong style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 8px;">
                    Riwayat Putusan Sebelumnya:
                </strong>
                @foreach($activity->approvals as $hist)
                    <div style="font-size: 12px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed var(--border-light);">
                        <span class="badge {{ $hist->status === 'Disetujui' ? 'badge-approved' : 'badge-revision' }}" style="font-size: 11px;">{{ $hist->status }}</span>
                        <span style="color: var(--text-muted); margin-left: 6px;">{{ $hist->approved_at ? $hist->approved_at->format('d/m/Y H:i') : '' }}</span>
                        @if($hist->notes)
                            <div style="color: var(--text-secondary); margin-top: 4px;">&ldquo;{{ $hist->notes }}&rdquo;</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
