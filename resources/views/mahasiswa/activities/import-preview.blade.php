@extends('layouts.app')

@section('title', 'Pratinjau Hasil Impor DOCX')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pratinjau Hasil Ekstraksi Logbook</h1>
        <div class="page-subtitle">Periksa dan sesuaikan data aktivitas sebelum disimpan ke dalam database logbook Anda.</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <form action="{{ route('mahasiswa.activities.import.cancel') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan proses impor ini?');">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-secondary" style="color: #dc2626; border-color: #fca5a5;">
                ✕ Batalkan Impor
            </button>
        </form>
    </div>
</div>

<!-- Statistik Ringkasan -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div style="font-size: 28px; background: #eff6ff; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">📅</div>
        <div>
            <div style="font-size: 22px; font-weight: 700; color: #1e293b;">{{ $stats['total_extracted'] }}</div>
            <div style="font-size: 12px; color: #64748b; font-weight: 500;">Total Hari Ditemukan</div>
        </div>
    </div>

    <div class="card" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div style="font-size: 28px; background: #f0fdf4; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">✨</div>
        <div>
            <div style="font-size: 22px; font-weight: 700; color: #16a34a;">{{ $stats['total_extracted'] - $stats['conflict_count'] }}</div>
            <div style="font-size: 12px; color: #64748b; font-weight: 500;">Aktivitas Hari Baru</div>
        </div>
    </div>

    <div class="card" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div style="font-size: 28px; background: {{ $stats['conflict_count'] > 0 ? '#fef3c7' : '#f8fafc' }}; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">⚠️</div>
        <div>
            <div style="font-size: 22px; font-weight: 700; color: {{ $stats['conflict_count'] > 0 ? '#d97706' : '#64748b' }};">{{ $stats['conflict_count'] }}</div>
            <div style="font-size: 12px; color: #64748b; font-weight: 500;">Hari Sudah Ada (Konflik)</div>
        </div>
    </div>

    <div class="card" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div style="font-size: 28px; background: #faf5ff; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">🖼️</div>
        <div>
            <div style="font-size: 22px; font-weight: 700; color: #9333ea;">{{ $stats['image_count'] }}</div>
            <div style="font-size: 12px; color: #64748b; font-weight: 500;">Gambar Bukti Terekstrak</div>
        </div>
    </div>
</div>

<!-- Main Form Konfirmasi Impor -->
<form action="{{ route('mahasiswa.activities.import.confirm') }}" method="POST" id="confirm-import-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <!-- Toolbar Kontrol Batch -->
    <div class="card" style="padding: 14px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: #ffffff;">
        <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; color: #1e293b;">
                <input type="checkbox" id="check-all" checked style="width: 17px; height: 17px; cursor: pointer;">
                Pilih Semua (<span id="selected-counter">{{ count($activities) }}</span> / {{ count($activities) }})
            </label>

            @if($stats['conflict_count'] > 0)
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                    <span style="color: #64748b;">Aksi untuk Hari Duplikat:</span>
                    <select id="global-conflict-action" class="form-control" style="padding: 4px 8px; font-size: 13px; width: auto; height: auto;">
                        <option value="overwrite">Timpa Data Lama</option>
                        <option value="skip">Lewati (Jangan Ubah)</option>
                    </select>
                </div>
            @endif
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="submit" class="btn btn-primary" id="btn-save-import" style="padding: 9px 20px; font-weight: 600;">
                💾 Simpan ke Logbook (Status Draft)
            </button>
        </div>
    </div>

    <!-- Daftar Card Aktivitas -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        @foreach($activities as $idx => $item)
            <div class="card activity-item-card" style="padding: 20px; border-left: 5px solid {{ $item['is_conflict'] ? '#f59e0b' : '#10b981' }};" id="card-{{ $idx }}">
                <!-- Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <input type="checkbox" name="activities[{{ $idx }}][selected]" value="1" class="activity-checkbox" checked style="width: 18px; height: 18px; cursor: pointer;">
                        
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 13px; font-weight: 600; color: #475569;">Hari Ke:</span>
                            <input type="number" name="activities[{{ $idx }}][day_number]" value="{{ $item['day_number'] }}" min="1" required class="form-control" style="width: 75px; padding: 4px 8px; font-size: 14px; font-weight: 700; text-align: center;">
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 13px; font-weight: 600; color: #475569;">Tanggal:</span>
                            <input type="date" name="activities[{{ $idx }}][activity_date]" value="{{ $item['activity_date'] }}" required class="form-control" style="width: 155px; padding: 4px 8px; font-size: 13px;">
                        </div>

                        @if($item['is_conflict'])
                            <span style="background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                ⚠️ Hari Ke-{{ $item['day_number'] }} Sudah Ada di Database
                            </span>
                        @else
                            <span style="background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                ✓ Hari Baru
                            </span>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px;">
                        @if($item['is_conflict'])
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 12px;">
                                <label style="font-weight: 600; color: #b45309;">Aksi:</label>
                                <select name="activities[{{ $idx }}][conflict_action]" class="form-control conflict-select" style="padding: 2px 8px; font-size: 12px; width: auto; height: auto;">
                                    <option value="overwrite" {{ $item['conflict_action'] === 'overwrite' ? 'selected' : '' }}>Timpa Data Lama</option>
                                    <option value="skip" {{ $item['conflict_action'] === 'skip' ? 'selected' : '' }}>Lewati</option>
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="activities[{{ $idx }}][conflict_action]" value="overwrite">
                        @endif

                        <span style="font-size: 11px; color: #94a3b8; background: #f8fafc; padding: 2px 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                            📄 {{ $item['source_file'] }}
                        </span>
                    </div>
                </div>

                <!-- Card Body Form Inputs -->
                <div style="display: grid; grid-template-columns: 240px 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Bidang / Divisi Pekerjaan</label>
                        <input type="text" name="activities[{{ $idx }}][work_field]" value="{{ $item['work_field'] }}" required class="form-control" style="font-size: 13px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Deskripsi Kegiatan Harian</label>
                        <textarea name="activities[{{ $idx }}][activities]" rows="3" required class="form-control" style="font-size: 13px; line-height: 1.5;">{{ $item['activities'] }}</textarea>
                    </div>
                </div>

                <!-- 3 Kolom Tools, Masalah, Solusi -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 600; color: #475569;">Tools yang Digunakan</label>
                        <textarea name="activities[{{ $idx }}][tools_used]" rows="2" class="form-control" style="font-size: 12px;">{{ $item['tools_used'] }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 600; color: #475569;">Kendala / Masalah</label>
                        <textarea name="activities[{{ $idx }}][problems]" rows="2" class="form-control" style="font-size: 12px;">{{ $item['problems'] }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 600; color: #475569;">Solusi yang Diterapkan</label>
                        <textarea name="activities[{{ $idx }}][solutions]" rows="2" class="form-control" style="font-size: 12px;">{{ $item['solutions'] }}</textarea>
                    </div>
                </div>

                <!-- Bukti Gambar Yang Terekstrak -->
                @if(!empty($item['images']))
                    <div style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <span>📷</span> Bukti Gambar Terekstrak ({{ count($item['images']) }} file)
                        </div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            @foreach($item['images'] as $imgIdx => $img)
                                <div style="position: relative; width: 100px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e1; background: #ffffff;">
                                    <img src="{{ $img['preview_url'] }}" alt="{{ $img['file_name'] }}" style="width: 100%; height: 100%; object-fit: cover;" title="{{ $img['file_name'] }}">
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][file_name]" value="{{ $img['file_name'] }}">
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][temp_path]" value="{{ $img['temp_path'] }}">
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][file_type]" value="{{ $img['file_type'] }}">
                                    <input type="hidden" name="activities[{{ $idx }}][images][{{ $imgIdx }}][file_size]" value="{{ $img['file_size'] }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div style="font-size: 12px; color: #94a3b8; font-style: italic;">
                        (Tidak ada lampiran gambar pada hari ini)
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Bottom Sticky Save Bar -->
    <div class="card" style="position: sticky; bottom: 20px; z-index: 10; margin-top: 24px; padding: 14px 24px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15); display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
        <div style="font-size: 14px; color: #475569;">
            Data akan disimpan ke database dengan status <strong style="color: #2563eb;">Draft</strong>.
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                💾 Konfirmasi & Simpan Logbook
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
    const btnSave = document.getElementById('btn-save-import');

    function updateCounter() {
        const checkedCount = document.querySelectorAll('.activity-checkbox:checked').length;
        counter.textContent = checkedCount;
        checkAll.checked = (checkedCount === checkboxes.length);
        if (checkedCount === 0) {
            btnSave.setAttribute('disabled', 'disabled');
        } else {
            btnSave.removeAttribute('disabled');
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
            alert('Pilih setidaknya satu aktivitas untuk disimpan.');
            return;
        }
        btnSave.setAttribute('disabled', 'disabled');
        btnSave.innerHTML = '⏳ Menyimpan ke Database...';
    });
});
</script>
@endsection
