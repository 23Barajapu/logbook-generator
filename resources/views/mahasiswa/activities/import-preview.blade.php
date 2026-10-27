@extends('layouts.app')

@section('title', 'Pratinjau Hasil Impor Logbook')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pratinjau Hasil Ekstraksi Logbook</h1>
        <div class="page-subtitle">Periksa, lengkapi, atau tambahkan foto bukti aktivitas sebelum disimpan ke dalam database logbook Anda.</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <form action="{{ route('mahasiswa.activities.import.cancel') }}" method="POST" data-confirm="Apakah Anda yakin ingin membatalkan proses impor ini? Seluruh data ekstraksi sementara akan dihapus." data-confirm-title="Batalkan Impor" data-confirm-btn="Ya, Batalkan" data-confirm-variant="danger">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-secondary" style="color: var(--danger-text); border-color: rgba(239, 68, 68, 0.4);">
                Batalkan Impor
            </button>
        </form>
    </div>
</div>

@if(!empty($stats['skipped_duplicates']) && count($stats['skipped_duplicates']) > 0)
    <div class="alert alert-warning" style="margin-bottom: 24px;">
        <div style="font-weight: 700; margin-bottom: 6px;">
            {{ count($stats['skipped_duplicates']) }} file otomatis dilewati (terdeteksi duplikat dokumen):
        </div>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.6;">
            @foreach($stats['skipped_duplicates'] as $dup)
                <li><strong>{{ $dup['file_name'] }}</strong> &mdash; <span class="text-muted">{{ $dup['reason'] }}</span></li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Statistik Ringkasan Simetris & Bersih -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 20px 22px; margin-bottom: 0; border-top: 3px solid var(--accent);">
        <div style="font-size: 11.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
            Total Hari Ditemukan
        </div>
        <div style="font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1;">
            {{ $stats['total_extracted'] }}
        </div>
    </div>

    <div class="card" style="padding: 20px 22px; margin-bottom: 0; border-top: 3px solid var(--success);">
        <div style="font-size: 11.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
            Aktivitas Hari Baru
        </div>
        <div style="font-size: 28px; font-weight: 800; color: var(--success-text); line-height: 1;">
            {{ $stats['total_extracted'] - $stats['conflict_count'] }}
        </div>
    </div>

    @if($stats['conflict_count'] > 0)
        <div class="card" style="padding: 20px 22px; margin-bottom: 0; border-top: 3px solid var(--warning);">
            <div style="font-size: 11.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                Hari Sudah Ada
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--warning-text); line-height: 1;">
                {{ $stats['conflict_count'] }}
            </div>
        </div>
    @else
        <div class="card" style="padding: 20px 22px; margin-bottom: 0; border-top: 3px solid var(--border);">
            <div style="font-size: 11.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                Hari Sudah Ada
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-muted); line-height: 1;">
                0
            </div>
        </div>
    @endif

    <div class="card" style="padding: 20px 22px; margin-bottom: 0; border-top: 3px solid var(--info);">
        <div style="font-size: 11.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
            Foto Bukti Terekstrak
        </div>
        <div style="font-size: 28px; font-weight: 800; color: var(--info-text); line-height: 1;">
            {{ $stats['image_count'] }}
        </div>
    </div>
</div>

<!-- Main Form Konfirmasi Impor -->
<form action="{{ route('mahasiswa.activities.import.confirm') }}" method="POST" enctype="multipart/form-data" id="confirm-import-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <!-- Toolbar Kontrol Batch Simetris -->
    <div class="card" style="padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border: 1px solid var(--border);">
        <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; cursor: pointer; color: var(--text-primary); user-select: none;">
                <input type="checkbox" id="check-all" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent);">
                <span>Pilih Semua Aktivitas (<span id="selected-counter">{{ count($activities) }}</span> / {{ count($activities) }})</span>
            </label>

            @if($stats['conflict_count'] > 0)
                <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                    <span style="color: var(--text-secondary); font-weight: 500;">Aksi untuk Hari Duplikat:</span>
                    <select id="global-conflict-action" class="form-control" style="padding: 6px 12px; font-size: 13px; width: auto; height: auto;">
                        <option value="overwrite">Timpa Data Lama</option>
                        <option value="skip">Lewati (Pertahankan Data Lama)</option>
                    </select>
                </div>
            @endif
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="submit" class="btn btn-primary" id="btn-save-import-top" style="padding: 10px 22px; font-weight: 700;">
                Simpan ke Logbook (Status Draft)
            </button>
        </div>
    </div>

    <!-- Daftar Card Aktivitas Per Hari -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        @foreach($activities as $idx => $item)
            @php
                $isPdfSource = str_ends_with(strtolower($item['source_file']), '.pdf');
                $extLabel = $isPdfSource ? 'PDF' : 'DOCX';
            @endphp
            <div class="card activity-item-card {{ $item['is_conflict'] ? 'is-conflict' : 'is-new' }}" style="padding: 24px; margin-bottom: 0;" id="card-{{ $idx }}">
                <!-- Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <input type="checkbox" name="activities[{{ $idx }}][selected]" value="1" class="activity-checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent);">
                        
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--text-secondary);">Hari Ke:</span>
                            <input type="number" name="activities[{{ $idx }}][day_number]" value="{{ $item['day_number'] }}" min="1" required class="form-control" style="width: 75px; padding: 6px 8px; font-size: 14px; font-weight: 700; text-align: center;">
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--text-secondary);">Tanggal:</span>
                            <input type="date" name="activities[{{ $idx }}][activity_date]" value="{{ $item['activity_date'] }}" required class="form-control" style="width: 160px; padding: 6px 10px; font-size: 13px;">
                        </div>

                        @if($item['is_conflict'])
                            <span class="badge badge-warning" style="padding: 5px 10px; font-size: 12px; display: inline-flex; align-items: center;">
                                Hari Ke-{{ $item['day_number'] }} Sudah Ada di Database
                            </span>
                        @else
                            <span class="badge badge-approved" style="padding: 5px 10px; font-size: 12px; display: inline-flex; align-items: center;">
                                Hari Baru
                            </span>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        @if($item['is_conflict'])
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px;">
                                <label style="font-weight: 600; color: var(--warning-text);">Aksi:</label>
                                <select name="activities[{{ $idx }}][conflict_action]" class="form-control conflict-select" style="padding: 5px 10px; font-size: 12px; width: auto; height: auto;">
                                    <option value="overwrite" {{ $item['conflict_action'] === 'overwrite' ? 'selected' : '' }}>Timpa Data Lama</option>
                                    <option value="skip" {{ $item['conflict_action'] === 'skip' ? 'selected' : '' }}>Lewati</option>
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="activities[{{ $idx }}][conflict_action]" value="overwrite">
                        @endif

                        <span style="font-size: 11.5px; color: var(--text-muted); background: var(--bg-elevated); padding: 5px 10px; border-radius: var(--r-sm); border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 6px;">
                            <span style="font-size: 10px; font-weight: 700; padding: 1px 4px; border-radius: 3px; background: var(--bg-surface); border: 1px solid var(--border);">{{ $extLabel }}</span>
                            <span style="max-width: 200px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" title="{{ $item['source_file'] }}">{{ $item['source_file'] }}</span>
                        </span>
                    </div>
                </div>

                <!-- Form Inputs Baris 1: Bidang & Uraian Kegiatan -->
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 18px; margin-bottom: 18px;">
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                            Bidang / Divisi Pekerjaan
                        </label>
                        <input type="text" name="activities[{{ $idx }}][work_field]" value="{{ $item['work_field'] }}" required class="form-control" style="font-size: 13.5px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                            Uraian Kegiatan Harian
                        </label>
                        <textarea name="activities[{{ $idx }}][activities]" rows="3" required class="form-control" style="font-size: 13px; line-height: 1.55;">{{ $item['activities'] }}</textarea>
                    </div>
                </div>

                <!-- Form Inputs Baris 2: 3 Kolom Simetris Tools, Masalah, Solusi -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 18px;">
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: 0.03em;">
                            Tools yang Digunakan
                        </label>
                        <textarea name="activities[{{ $idx }}][tools_used]" rows="2" class="form-control" style="font-size: 12.5px;">{{ $item['tools_used'] }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: 0.03em;">
                            Kendala / Masalah
                        </label>
                        <textarea name="activities[{{ $idx }}][problems]" rows="2" class="form-control" style="font-size: 12.5px;">{{ $item['problems'] }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: 0.03em;">
                            Solusi yang Diterapkan
                        </label>
                        <textarea name="activities[{{ $idx }}][solutions]" rows="2" class="form-control" style="font-size: 12.5px;">{{ $item['solutions'] }}</textarea>
                    </div>
                </div>

                <!-- Form Inputs Baris 3: Galeri Gambar Bukti & Tambah Foto Manual -->
                <div style="background: var(--bg-elevated); padding: 16px 18px; border-radius: var(--r-md); border: 1px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                            Foto Bukti Dokumentasi Kegiatan (<span id="img-count-{{ $idx }}">{{ count($item['images'] ?? []) }}</span> file)
                        </div>
                        <div>
                            <input type="file" name="new_activity_images[{{ $idx }}][]" id="file-new-img-{{ $idx }}" data-idx="{{ $idx }}" multiple accept="image/jpeg,image/png,image/jpg" style="display: none;" onchange="handleNewImagesUpload(this, this.dataset.idx)">
                            <label for="file-new-img-{{ $idx }}" class="btn btn-secondary btn-sm" style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; padding: 5px 12px;">
                                Tambah / Ganti Foto
                            </label>
                        </div>
                    </div>

                    <!-- Container Foto (Gabungan Foto Terekstrak + Preview Upload Baru) -->
                    <div id="images-container-{{ $idx }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-start;">
                        @if(!empty($item['images']))
                            @foreach($item['images'] as $imgIdx => $img)
                                <div class="image-thumb-box" id="img-box-{{ $idx }}-{{ $imgIdx }}" style="position: relative; width: 120px; height: 90px; border-radius: var(--r-sm); overflow: hidden; border: 1px solid var(--border); background: var(--bg-surface); box-shadow: var(--shadow-sm);">
                                    <img src="{{ $img['preview_url'] ?? asset('storage/' . ($img['temp_path'] ?? '')) }}" alt="{{ $img['file_name'] ?? 'Foto' }}" style="width: 100%; height: 100%; object-fit: cover;" title="{{ $img['file_name'] ?? 'Foto' }}">
                                    
                                    <!-- Hidden Inputs untuk Gambar Ekstraksi -->
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][file_name]" value="{{ $img['file_name'] ?? '' }}">
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][temp_path]" value="{{ $img['temp_path'] ?? '' }}">
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][file_type]" value="{{ $img['file_type'] ?? '' }}">
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][file_size]" value="{{ $img['file_size'] ?? 0 }}">

                                    <button type="button" data-act-idx="{{ $idx }}" data-img-idx="{{ $imgIdx }}" onclick="removeExtractedImage(this.dataset.actIdx, this.dataset.imgIdx)" style="position: absolute; top: 3px; right: 3px; background: rgba(0, 0, 0, 0.65); color: #fff; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; line-height: 1;" title="Hapus foto ini">&times;</button>
                                </div>
                            @endforeach
                        @endif

                        <!-- Area Preview Foto Baru yang Diupload -->
                        <div id="new-preview-container-{{ $idx }}" style="display: flex; gap: 12px; flex-wrap: wrap;"></div>
                    </div>

                    @if(!empty($item['images']))
                        <div id="empty-img-notice-{{ $idx }}" style="display: none; font-size: 12px; color: var(--text-muted); font-style: italic; padding: 4px 0;">
                            Belum ada foto bukti untuk hari ini. Klik "Tambah / Ganti Foto" untuk mengunggah dokumentasi.
                        </div>
                    @else
                        <div id="empty-img-notice-{{ $idx }}" style="font-size: 12px; color: var(--text-muted); font-style: italic; padding: 4px 0;">
                            Belum ada foto bukti untuk hari ini. Klik "Tambah / Ganti Foto" untuk mengunggah dokumentasi.
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Bottom Sticky Save Bar Simetris -->
    <div class="card" style="position: sticky; bottom: 20px; z-index: 10; margin-top: 24px; padding: 16px 24px; box-shadow: var(--shadow-lg); display: flex; justify-content: space-between; align-items: center; background: var(--bg-elevated); border: 1px solid var(--accent); border-radius: var(--r-lg);">
        <div style="font-size: 13.5px; color: var(--text-secondary);">
            Aktivitas yang dipilih akan disimpan ke database logbook Anda dengan status <strong style="color: var(--accent); font-weight: 700;">Draft</strong>.
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" class="btn btn-primary" id="btn-save-import-bottom" style="padding: 10px 24px; font-weight: 700;">
                Konfirmasi &amp; Simpan Logbook
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('check-all');
    const checkboxes = document.querySelectorAll('.activity-checkbox');
    const counter = document.getElementById('selected-counter');
    const globalConflictSelect = document.getElementById('global-conflict-action');
    const conflictSelects = document.querySelectorAll('.conflict-select');
    const form = document.getElementById('confirm-import-form');
    const btnSaveTop = document.getElementById('btn-save-import-top');
    const btnSaveBottom = document.getElementById('btn-save-import-bottom');

    function updateCounter() {
        const checkedCount = document.querySelectorAll('.activity-checkbox:checked').length;
        counter.textContent = checkedCount;
        checkAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
        
        if (checkedCount === 0) {
            btnSaveTop?.setAttribute('disabled', 'disabled');
            btnSaveBottom?.setAttribute('disabled', 'disabled');
        } else {
            btnSaveTop?.removeAttribute('disabled');
            btnSaveBottom?.removeAttribute('disabled');
        }
    }

    checkAll.addEventListener('change', function () {
        checkboxes.forEach(cb => {
            cb.checked = checkAll.checked;
        });
        updateCounter();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });

    if (globalConflictSelect) {
        globalConflictSelect.addEventListener('change', function () {
            conflictSelects.forEach(sel => {
                sel.value = globalConflictSelect.value;
            });
        });
    }

    form.addEventListener('submit', function (e) {
        const checkedCount = document.querySelectorAll('.activity-checkbox:checked').length;
        if (checkedCount === 0) {
            e.preventDefault();
            showAlertModal('Pilih setidaknya satu aktivitas untuk disimpan ke database.', 'Aktivitas Belum Dipilih', 'warning');
            return;
        }
        btnSaveTop?.setAttribute('disabled', 'disabled');
        btnSaveBottom?.setAttribute('disabled', 'disabled');
        if (btnSaveTop) btnSaveTop.innerHTML = 'Menyimpan ke Database...';
        if (btnSaveBottom) btnSaveBottom.innerHTML = 'Menyimpan ke Database...';
    });
});

// Fungsi Hapus Foto Ekstraksi dari Pratinjau
function removeExtractedImage(actIdx, imgIdx) {
    const box = document.getElementById(`img-box-${actIdx}-${imgIdx}`);
    if (box) {
        box.remove();
        updateImageCount(actIdx);
    }
}

// Preview Upload Gambar Baru via Input File
function handleNewImagesUpload(input, actIdx) {
    const container = document.getElementById(`new-preview-container-${actIdx}`);
    if (!container || !input.files) return;

    container.innerHTML = '';
    const files = Array.from(input.files);

    files.forEach((file, fIdx) => {
        if (!file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const previewBox = document.createElement('div');
            previewBox.style.cssText = 'position: relative; width: 120px; height: 90px; border-radius: var(--r-sm); overflow: hidden; border: 2px dashed var(--accent); background: var(--bg-surface); box-shadow: var(--shadow-sm);';
            previewBox.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}" style="width: 100%; height: 100%; object-fit: cover;" title="Baru: ${file.name}">
                <span style="position: absolute; bottom: 2px; left: 2px; background: var(--accent); color: #fff; font-size: 9.5px; font-weight: 700; padding: 1px 4px; border-radius: 2px;">BARU</span>
            `;
            container.appendChild(previewBox);
        };
        reader.readAsDataURL(file);
    });

    updateImageCount(actIdx);
}

function updateImageCount(actIdx) {
    const extractedCount = document.querySelectorAll(`#images-container-${actIdx} .image-thumb-box`).length;
    const fileInput = document.getElementById(`file-new-img-${actIdx}`);
    const newCount = fileInput && fileInput.files ? fileInput.files.length : 0;
    const total = extractedCount + newCount;

    const countSpan = document.getElementById(`img-count-${actIdx}`);
    if (countSpan) countSpan.textContent = total;

    const emptyNotice = document.getElementById(`empty-img-notice-${actIdx}`);
    if (emptyNotice) {
        emptyNotice.style.display = total === 0 ? 'block' : 'none';
    }
}
</script>
@endsection
