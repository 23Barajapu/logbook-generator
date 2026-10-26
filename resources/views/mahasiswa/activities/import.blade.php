@extends('layouts.app')

@section('title', 'Import Logbook dari DOCX')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Import Aktivitas dari File Word (.docx)</h1>
        <div class="page-subtitle">Unggah satu atau beberapa file DOCX logbook Anda. Sistem akan mengekstrak aktivitas harian dan gambar bukti secara otomatis.</div>
    </div>
    <div>
        <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary">
            ← Kembali ke Daftar Aktivitas
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
    <!-- Form Upload Section -->
    <div class="card" style="padding: 24px;">
        <form action="{{ route('mahasiswa.activities.import.process') }}" method="POST" enctype="multipart/form-data" id="import-form">
            @csrf

            <div style="margin-bottom: 20px;">
                <label class="form-label" style="font-size: 15px; font-weight: 600;">Pilih File Dokumen (.docx)</label>
                
                <!-- Drag and Drop Dropzone -->
                <div id="dropzone" style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease;">
                    <div style="font-size: 40px; margin-bottom: 12px;">📁</div>
                    <div style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
                        Tarik & Lepaskan file DOCX di sini, atau <span style="color: #2563eb; text-decoration: underline;">pilih file</span>
                    </div>
                    <div style="font-size: 13px; color: #64748b;">
                        Mendukung pemilihan hingga 10 file sekaligus (Maksimal 25 MB per file)
                    </div>
                    <input type="file" name="files[]" id="file-input" multiple accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display: none;">
                </div>
            </div>

            <!-- Selected Files List -->
            <div id="file-list-container" style="display: none; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 14px; font-weight: 600; color: #334155;">File Terpilih (<span id="file-count">0</span>):</span>
                    <button type="button" id="btn-clear-files" style="background: none; border: none; color: #ef4444; font-size: 13px; cursor: pointer;">Hapus Semua</button>
                </div>
                <div id="file-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 240px; overflow-y: auto;">
                    <!-- Dynamic File Item -->
                </div>
            </div>

            <div style="display: flex; gap: 12px; align-items: center;">
                <button type="submit" class="btn btn-primary" id="btn-submit-import" disabled style="padding: 10px 24px; font-weight: 600;">
                    🚀 Ekstrak & Tinjau Logbook
                </button>
                <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <!-- Petunjuk Format Dokumen -->
    <div class="card" style="padding: 20px; background: #ffffff;">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span>ℹ️</span> Panduan Format DOCX
        </h2>
        <p style="font-size: 13px; color: #475569; line-height: 1.5; margin-bottom: 14px;">
            Agar sistem dapat membaca logbook dengan tepat, pastikan file DOCX Anda memiliki struktur standar:
        </p>

        <ul style="font-size: 13px; color: #334155; line-height: 1.6; padding-left: 18px; margin-bottom: 16px;">
            <li><strong>Judul Hari:</strong> Teks diawali <code>Hari Ke-[Nomor]</code> (contoh: <em>Hari Ke-1, Tanggal 3 Agustus 2026</em>).</li>
            <li><strong>Tabel 3 Kolom:</strong> Tepat di bawah judul hari:
                <ol style="margin-top: 4px; padding-left: 16px;">
                    <li>Bidang Pekerjaan (misal: <em>Quality Control</em>)</li>
                    <li>Kegiatan & Bukti (deskripsi dan gambar foto kegiatan)</li>
                    <li>Tools, Masalah & Solusi</li>
                </ol>
            </li>
            <li><strong>Gambar Bukti:</strong> Gambar yang ditempel di kolom kegiatan akan otomatis diekstrak sebagai lampiran kegiatan.</li>
        </ul>

        <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 10px 12px; border-radius: 4px; font-size: 12px; color: #1e40af; line-height: 1.4;">
            💡 <strong>Pratinjau Aman:</strong> Data tidak akan langsung disimpan ke database sebelum Anda meninjaunya pada halaman Pratinjau.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    const fileListContainer = document.getElementById('file-list-container');
    const fileList = document.getElementById('file-list');
    const fileCount = document.getElementById('file-count');
    const btnSubmit = document.getElementById('btn-submit-import');
    const btnClear = document.getElementById('btn-clear-files');
    const form = document.getElementById('import-form');

    let selectedFiles = [];

    dropzone.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.style.borderColor = '#2563eb';
            dropzone.style.background = '#eff6ff';
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.style.borderColor = '#cbd5e1';
            dropzone.style.background = '#f8fafc';
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const files = Array.from(e.dataTransfer.files).filter(f => f.name.toLowerCase().endsWith('.docx'));
        if (files.length === 0) {
            alert('Hanya file dokumen Microsoft Word (.docx) yang diperbolehkan.');
            return;
        }
        addFiles(files);
    });

    fileInput.addEventListener('change', (e) => {
        addFiles(Array.from(e.target.files));
    });

    function addFiles(newFiles) {
        newFiles.forEach(file => {
            if (!file.name.toLowerCase().endsWith('.docx')) {
                alert(`File "${file.name}" dilewati karena bukan .docx`);
                return;
            }
            if (file.size > 25 * 1024 * 1024) {
                alert(`File "${file.name}" melebihi batas 25 MB.`);
                return;
            }
            // avoid duplicates by name
            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                if (selectedFiles.length >= 10) {
                    alert('Batas maksimal adalah 10 file per proses impor.');
                    return;
                }
                selectedFiles.push(file);
            }
        });
        updateUI();
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        updateUI();
    }

    btnClear.addEventListener('click', () => {
        selectedFiles = [];
        updateUI();
    });

    function updateUI() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;

        fileCount.textContent = selectedFiles.length;

        if (selectedFiles.length > 0) {
            fileListContainer.style.display = 'block';
            btnSubmit.removeAttribute('disabled');
            fileList.innerHTML = '';

            selectedFiles.forEach((file, idx) => {
                const sizeKb = (file.size / 1024).toFixed(1);
                const sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb + ' KB';

                const item = document.createElement('div');
                item.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;';
                item.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                        <span style="color: #2563eb;">📄</span>
                        <span style="font-weight: 500; color: #1e293b; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; max-width: 320px;" title="${file.name}">${file.name}</span>
                        <span style="color: #94a3b8; font-size: 11px;">(${sizeStr})</span>
                    </div>
                    <button type="button" data-index="${idx}" class="btn-remove-file" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 6px; font-size: 16px;">&times;</button>
                `;
                fileList.appendChild(item);
            });

            document.querySelectorAll('.btn-remove-file').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const idx = parseInt(e.target.getAttribute('data-index'));
                    removeFile(idx);
                });
            });
        } else {
            fileListContainer.style.display = 'none';
            btnSubmit.setAttribute('disabled', 'disabled');
            fileList.innerHTML = '';
        }
    }

    form.addEventListener('submit', () => {
        btnSubmit.setAttribute('disabled', 'disabled');
        btnSubmit.innerHTML = '⏳ Sedang Memproses & Mengekstrak DOCX...';
    });
});
</script>
@endsection
