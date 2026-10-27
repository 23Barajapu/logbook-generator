@extends('layouts.app')

@section('title', 'Catat Aktivitas Harian Baru')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Catat Aktivitas Harian</h1>
        <div class="page-subtitle">Isi rincian aktivitas pelaksanaan Praktik Kerja Lapangan hari ini.</div>
    </div>
    <div>
        <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary">
            &larr; Kembali ke Daftar
        </a>
    </div>
</div>

<div class="card" style="max-width: 840px;">
    <form action="{{ route('mahasiswa.activities.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label for="day_number" class="form-label">Hari Ke- <span style="color: var(--danger-text);">*</span></label>
                <input type="number" id="day_number" name="day_number" class="form-control" value="{{ old('day_number', $nextDayNumber) }}" required min="1">
                <div class="form-hint">Nomor urut hari kerja PKL.</div>
            </div>

            <div class="form-group">
                <label for="activity_date" class="form-label">Tanggal Pelaksanaan <span style="color: var(--danger-text);">*</span></label>
                <input type="date" id="activity_date" name="activity_date" class="form-control" value="{{ old('activity_date', date('Y-m-d')) }}" required>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="work_field" class="form-label">Bidang / Divisi Pekerjaan <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="work_field" name="work_field" class="form-control" value="{{ old('work_field') }}" required placeholder="Contoh: Backend Development, Network Engineering, Quality Assurance">
            </div>
        </div>

        <div class="form-group">
            <label for="activities" class="form-label">Uraian Kegiatan / Aktivitas <span style="color: var(--danger-text);">*</span></label>
            <textarea id="activities" name="activities" class="form-control" style="min-height: 120px;" required placeholder="Jelaskan secara detail apa yang Anda kerjakan hari ini...">{{ old('activities') }}</textarea>
            <div class="form-hint">Tuliskan ringkasan tugas, modul yang diselesaikan, atau diskusi tim yang diikuti.</div>
        </div>

        <div class="form-group">
            <label for="tools_used" class="form-label">Tools / Perangkat / Aplikasi Digunakan</label>
            <input type="text" id="tools_used" name="tools_used" class="form-control" value="{{ old('tools_used') }}" placeholder="Contoh: VS Code, Git, PostgreSQL, Docker, Figma, Postman">
            <div class="form-hint">Pisahkan dengan koma jika lebih dari satu tools.</div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label for="problems" class="form-label">Kendala / Masalah yang Dihadapi</label>
                <textarea id="problems" name="problems" class="form-control" placeholder="Tuliskan kendala teknis atau non-teknis bila ada...">{{ old('problems') }}</textarea>
            </div>

            <div class="form-group">
                <label for="solutions" class="form-label">Solusi yang Dilakukan</label>
                <textarea id="solutions" name="solutions" class="form-control" placeholder="Tuliskan tindakan atau cara menyelesaikan kendala tersebut...">{{ old('solutions') }}</textarea>
            </div>
        </div>

        <div class="form-group" style="border: 1px dashed var(--border-color); padding: 16px; border-radius: var(--radius-md); background: var(--bg-subtle);">
            <label for="attachments" class="form-label">Upload Bukti Dokumentasi Kegiatan</label>
            <input type="file" id="attachments" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx">
            <div class="form-hint" style="margin-top: 6px;">
                Maksimal <strong>10 file</strong> per aktivitas. Ukuran maksimal <strong>20 MB</strong> per file. Format yang didukung: <strong>JPG, JPEG, PNG, PDF, DOCX, XLSX</strong>.
            </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light); flex-wrap: wrap;">
            <button type="submit" name="action_type" value="draft" class="btn btn-secondary">
                Simpan Sebagai Draft
            </button>
            <button type="submit" name="action_type" value="submit" class="btn btn-primary">
                Simpan & Ajukan ke Pembimbing
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
