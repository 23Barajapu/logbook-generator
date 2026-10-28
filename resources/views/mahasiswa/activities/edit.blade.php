@extends('layouts.app')

@section('title', 'Edit Aktivitas Hari Ke-' . $activity->day_number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Aktivitas Hari Ke-{{ $activity->day_number }}</h1>
        <div class="page-subtitle">Status Saat Ini: <span class="badge {{ $activity->status === 'Revisi' ? 'badge-revision' : 'badge-draft' }}">{{ $activity->status }}</span></div>
    </div>
    <div>
        <a href="{{ route('mahasiswa.activities.show', $activity) }}" class="btn btn-secondary">
            &larr; Batal & Kembali
        </a>
    </div>
</div>

@if($activity->latestApproval && $activity->latestApproval->notes)
    <div class="alert alert-warning">
        <strong>Catatan Evaluasi / Permintaan Revisi Pembimbing:</strong>
        <p style="margin-top: 4px;">{{ $activity->latestApproval->notes }}</p>
    </div>
@endif

<div class="card" style="max-width: 840px;">
    <form action="{{ route('mahasiswa.activities.update', $activity) }}" method="POST" enctype="multipart/form-data" data-upload-form data-process-title="Memperbarui Aktivitas" data-process-steps='["Memeriksa data aktivitas...", "Mengunggah lampiran baru...", "Menyimpan perubahan ke database..."]'>
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label for="day_number" class="form-label">Hari Ke- <span style="color: var(--danger-text);">*</span></label>
                <input type="number" id="day_number" name="day_number" class="form-control" value="{{ old('day_number', $activity->day_number) }}" required min="1">
            </div>

            <div class="form-group">
                <label for="activity_date" class="form-label">Tanggal Pelaksanaan <span style="color: var(--danger-text);">*</span></label>
                <input type="date" id="activity_date" name="activity_date" class="form-control" value="{{ old('activity_date', $activity->activity_date ? $activity->activity_date->format('Y-m-d') : '') }}" required>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="work_field" class="form-label">Bidang / Divisi Pekerjaan <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="work_field" name="work_field" class="form-control" value="{{ old('work_field', $activity->work_field) }}" required>
            </div>
        </div>

        <div class="form-group">
            <label for="activities" class="form-label">Uraian Kegiatan / Aktivitas <span style="color: var(--danger-text);">*</span></label>
            <textarea id="activities" name="activities" class="form-control" style="min-height: 120px;" required>{{ old('activities', $activity->activities) }}</textarea>
        </div>

        <div class="form-group">
            <label for="tools_used" class="form-label">Tools / Perangkat Digunakan</label>
            <input type="text" id="tools_used" name="tools_used" class="form-control" value="{{ old('tools_used', $activity->tools_used) }}">
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label for="problems" class="form-label">Kendala / Masalah yang Dihadapi</label>
                <textarea id="problems" name="problems" class="form-control">{{ old('problems', $activity->problems) }}</textarea>
            </div>

            <div class="form-group">
                <label for="solutions" class="form-label">Solusi yang Dilakukan</label>
                <textarea id="solutions" name="solutions" class="form-control">{{ old('solutions', $activity->solutions) }}</textarea>
            </div>
        </div>

        <!-- Lampiran yang Sudah Diupload -->
        @if($activity->attachments->count() > 0)
            <div class="form-group" style="margin-top: 16px;">
                <label class="form-label">Lampiran Bukti Tersimpan (Centang untuk menghapus)</label>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach($activity->attachments as $att)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-sm);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" rel="noopener" style="font-weight: 500;">
                                    &#128196; {{ $att->file_name }}
                                </a>
                                <span style="font-size: 11px; color: var(--text-muted);">
                                    ({{ round($att->file_size / 1024, 1) }} KB)
                                </span>
                            </div>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--danger-text); cursor: pointer;">
                                <input type="checkbox" name="delete_attachments[]" value="{{ $att->id }}">
                                Hapus file ini
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="form-group" style="border: 1px dashed var(--border-color); padding: 16px; border-radius: var(--radius-md); background: var(--bg-subtle); margin-top: 16px;">
            <label for="attachments" class="form-label">Tambah Lampiran Baru (Opsional)</label>
            <input type="file" id="attachments" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx">
            <div class="form-hint" style="margin-top: 6px;">
                Maksimal 10 file total. Format didukung: JPG, JPEG, PNG, PDF, DOCX, XLSX (max 20 MB/file).
            </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light); flex-wrap: wrap;">
            <button type="submit" name="action_type" value="draft" class="btn btn-secondary">
                Simpan Perubahan (Tetap Draft)
            </button>
            <button type="submit" name="action_type" value="submit" class="btn btn-primary">
                Simpan & Ajukan Ulang ke Pembimbing
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('attachments');
    if (!input) return;

    input.addEventListener('change', function() {
        const files = Array.from(input.files);
        const seen = new Set();
        const valid = [];
        let duplicateCount = 0;

        files.forEach(file => {
            const key = file.name + '___' + file.size;
            if (seen.has(key)) {
                duplicateCount++;
            } else {
                seen.add(key);
                valid.push(file);
            }
        });

        if (duplicateCount > 0) {
            const dt = new DataTransfer();
            valid.forEach(f => dt.items.add(f));
            input.files = dt.files;
            showAlertModal(`${duplicateCount} file kembar terdeteksi dan otomatis disaring dari daftar upload.`, 'File Duplikat Disaring', 'info');
        }
    });
});
</script>
@endsection
