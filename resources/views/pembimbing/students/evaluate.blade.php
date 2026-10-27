@extends('layouts.app')

@section('title', 'Penilaian PKL: ' . ($internship->student->user->name ?? 'Mahasiswa'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Penilaian Praktik Kerja Lapangan</h1>
        <div class="page-subtitle">
            Mahasiswa: <strong>{{ $internship->student->user->name ?? '-' }}</strong> &bull; NIM: {{ $internship->student->nim ?? '-' }} &bull; Tempat: {{ $internship->company->company_name ?? '-' }}
        </div>
    </div>
    <div>
        <a href="{{ route('pembimbing.students.show', $internship) }}" class="btn btn-secondary">
            &larr; Kembali ke Detail Mahasiswa
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom: 8px;">Formulir Evaluasi Komponen Nilai</h2>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
            Masukkan nilai tiap aspek dengan skala <strong>0 sampai 100</strong>. Sistem menghitung akumulasi bobot secara otomatis.
        </p>

        <form action="{{ route('pembimbing.students.evaluate.save', $internship) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="attendance_score" class="form-label" style="display: flex; justify-content: space-between;">
                    <span>1. Nilai Kehadiran <span style="color: var(--danger-text);">*</span></span>
                    <strong style="color: var(--text-muted);">Bobot: 20%</strong>
                </label>
                <input type="number" id="attendance_score" name="attendance_score" class="form-control score-input" value="{{ old('attendance_score', $evaluation?->attendance_score) }}" required min="0" max="100" step="0.1" placeholder="0 - 100">
                <div class="form-hint">Tingkat kehadiran mahasiswa di tempat PKL dan absensi harian.</div>
            </div>

            <div class="form-group">
                <label for="discipline_score" class="form-label" style="display: flex; justify-content: space-between;">
                    <span>2. Nilai Disiplin & Etika <span style="color: var(--danger-text);">*</span></span>
                    <strong style="color: var(--text-muted);">Bobot: 20%</strong>
                </label>
                <input type="number" id="discipline_score" name="discipline_score" class="form-control score-input" value="{{ old('discipline_score', $evaluation?->discipline_score) }}" required min="0" max="100" step="0.1" placeholder="0 - 100">
                <div class="form-hint">Kepatuhan tata tertib, sikap kerja, dan tanggung jawab tugas.</div>
            </div>

            <div class="form-group">
                <label for="activity_score" class="form-label" style="display: flex; justify-content: space-between;">
                    <span>3. Nilai Kualitas Aktivitas Harian <span style="color: var(--danger-text);">*</span></span>
                    <strong style="color: var(--text-muted);">Bobot: 30%</strong>
                </label>
                <input type="number" id="activity_score" name="activity_score" class="form-control score-input" value="{{ old('activity_score', $evaluation?->activity_score) }}" required min="0" max="100" step="0.1" placeholder="0 - 100">
                <div class="form-hint">Kompleksitas tugas teknis, ketepatan penyelesaian, dan kelengkapan bukti harian.</div>
            </div>

            <div class="form-group">
                <label for="report_score" class="form-label" style="display: flex; justify-content: space-between;">
                    <span>4. Nilai Laporan Akhir PKL <span style="color: var(--danger-text);">*</span></span>
                    <strong style="color: var(--text-muted);">Bobot: 30%</strong>
                </label>
                <input type="number" id="report_score" name="report_score" class="form-control score-input" value="{{ old('report_score', $evaluation?->report_score) }}" required min="0" max="100" step="0.1" placeholder="0 - 100">
                <div class="form-hint">Kerapian sistematika penulisan laporan dan capaian hasil PKL.</div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Catatan Evaluasi / Komentar Pembimbing</label>
                <textarea id="notes" name="notes" class="form-control" style="min-height: 100px;" placeholder="Tuliskan ulasan pencapaian kompetensi atau saran pengembangan untuk mahasiswa...">{{ old('notes', $evaluation?->notes) }}</textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                <button type="submit" class="btn btn-primary">
                    Simpan Nilai Akhir PKL
                </button>
            </div>
        </form>
    </div>

    <!-- Panel Kalkulasi Nilai Otomatis -->
    <div class="card" style="border-top: 4px solid var(--accent);">
        <h2 class="card-title" style="margin-bottom: 12px;">Estimasi Nilai Akhir</h2>
        <div style="text-align: center; padding: 16px 0;">
            <div id="finalScoreDisplay" style="font-size: 48px; font-weight: 800; color: var(--success-text);">
                {{ $evaluation ? $evaluation->final_score : '0.00' }}
            </div>
            <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Skala Akhir (100%)</div>
        </div>

        <div style="font-size: 13px; background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); line-height: 1.6;">
            <strong>Rumus Bobot Sesuai PRD:</strong>
            <ul style="padding-left: 20px; margin-top: 6px;">
                <li>Kehadiran: <strong>20%</strong></li>
                <li>Disiplin: <strong>20%</strong></li>
                <li>Aktivitas Harian: <strong>30%</strong></li>
                <li>Laporan PKL: <strong>30%</strong></li>
            </ul>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updateCalculation() {
    const att = parseFloat(document.getElementById('attendance_score').value) || 0;
    const dis = parseFloat(document.getElementById('discipline_score').value) || 0;
    const act = parseFloat(document.getElementById('activity_score').value) || 0;
    const rep = parseFloat(document.getElementById('report_score').value) || 0;

    const final = (att * 0.20) + (dis * 0.20) + (act * 0.30) + (rep * 0.30);
    document.getElementById('finalScoreDisplay').textContent = final.toFixed(2);
}

document.querySelectorAll('.score-input').forEach(input => {
    input.addEventListener('input', updateCalculation);
});
</script>
@endsection
