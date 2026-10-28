@extends('layouts.app')

@section('title', 'Import Logbook (DOCX / PDF)')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Import Aktivitas dari File Word (.docx) &amp; PDF (.pdf)</h1>
        <div class="page-subtitle">Unggah satu atau beberapa file DOCX atau PDF logbook Anda. Sistem akan mengekstrak aktivitas harian dan gambar bukti dokumentasi secara otomatis.</div>
    </div>
    <div>
        <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary">
            &larr; Kembali ke Daftar Aktivitas
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: stretch;">
    <!-- Form Upload Section Card -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
        <form action="{{ route('mahasiswa.activities.import.process') }}" method="POST" enctype="multipart/form-data" id="import-form" style="display: flex; flex-direction: column; height: 100%;" data-upload-form data-process-title="Mengekstrak Dokumen Logbook" data-process-steps='["Mengunggah berkas dokumen (.docx / .pdf)...", "Membaca penanda hari & tabel 3 kolom...", "Mengekstrak teks & bukti foto dokumentasi...", "Menyusun pratinjau aktivitas logbook..."]'>
            @csrf

            <div style="flex: 1;">
                <label class="form-label" style="font-size: 14.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Pilih File Dokumen (.docx / .pdf)</span>
                    <span style="font-size: 12px; font-weight: 500; color: var(--text-muted);">Maks. 10 File &bull; 25 MB/file</span>
                </label>
                
                <!-- Drag and Drop Dropzone -->
                <div id="dropzone" class="dropzone-container">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" style="color: var(--accent); margin-bottom: 14px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <div style="font-size: 15.5px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">
                        Tarik &amp; Lepaskan file DOCX atau PDF di sini, atau <span style="color: var(--accent); text-decoration: underline; font-weight: 700;">pilih file</span>
                    </div>
                    <div style="font-size: 12.5px; color: var(--text-muted); max-width: 420px; line-height: 1.5;">
                        Mendukung dokumen logbook Microsoft Word (<strong>.docx</strong>) dan Adobe Acrobat (<strong>.pdf</strong>)
                    </div>
                    <input type="file" name="files[]" id="file-input" multiple accept=".docx,.pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf" style="display: none;">
                </div>

                <!-- Selected Files List Container -->
                <div id="file-list-container" style="display: none; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--border);">
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em;">
                            File Terpilih (<span id="file-count" style="color: var(--accent);">0</span>)
                        </span>
                        <button type="button" id="btn-clear-files" style="background: none; border: none; color: var(--danger-text); font-size: 12.5px; cursor: pointer; font-weight: 600; padding: 2px 6px;">
                            Hapus Semua
                        </button>
                    </div>
                    <div id="file-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 260px; overflow-y: auto; padding-right: 4px;">
                        <!-- Dynamic File Items Rendered via JS -->
                    </div>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border);">
                <button type="submit" class="btn btn-primary" id="btn-submit-import" disabled style="padding: 10px 24px; font-weight: 600;">
                    Ekstrak &amp; Tinjau Logbook
                </button>
                <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>

    <!-- Petunjuk Format Dokumen Card -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
        <div>
            <h2 style="font-size: 15.5px; font-weight: 700; margin-bottom: 14px; color: var(--text-primary);">
                Panduan Format Dokumen
            </h2>
            <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 16px;">
                Sistem membaca dokumen logbook secara otomatis berdasarkan penanda standar berikut:
            </p>

            <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px;">
                <div style="background: var(--bg-elevated); padding: 12px 14px; border-radius: var(--r-md); border: 1px solid var(--border);">
                    <div style="font-weight: 700; font-size: 13px; color: var(--text-primary); margin-bottom: 4px;">
                        Penanda Hari &amp; Tanggal
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                        Diawali dengan teks pola: <code style="background: var(--bg-surface); color: var(--accent); padding: 2px 6px; border-radius: var(--r-sm); border: 1px solid var(--border);">Hari Ke-[Nomor], Tanggal [Tanggal]</code> (contoh: <em>Hari Ke-1, Tanggal 3 Agustus 2026</em>).
                    </div>
                </div>

                <div style="background: var(--bg-elevated); padding: 12px 14px; border-radius: var(--r-md); border: 1px solid var(--border);">
                    <div style="font-weight: 700; font-size: 13px; color: var(--text-primary); margin-bottom: 4px;">
                        Tabel Aktivitas (3 Kolom)
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                        <ul style="margin: 4px 0 0 0; padding-left: 18px;">
                            <li><strong>Kolom 1:</strong> Bidang / Divisi Pekerjaan</li>
                            <li><strong>Kolom 2:</strong> Uraian Kegiatan Harian &amp; Foto Bukti</li>
                            <li><strong>Kolom 3:</strong> Tools, Masalah &amp; Solusi</li>
                        </ul>
                    </div>
                </div>

                <div style="background: var(--bg-elevated); padding: 12px 14px; border-radius: var(--r-md); border: 1px solid var(--border);">
                    <div style="font-weight: 700; font-size: 13px; color: var(--text-primary); margin-bottom: 4px;">
                        Ekstraksi Foto Bukti Otomatis
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                        Foto dokumentasi di dalam file DOCX atau PDF akan otomatis diekstrak menjadi lampiran kegiatan. Anda juga dapat menambah foto secara manual di halaman pratinjau.
                    </div>
                </div>
            </div>

            <div style="background: var(--bg-surface); border: 1.5px dashed var(--accent); padding: 16px; border-radius: var(--r-md); margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13.5px; color: var(--text-primary); margin-bottom: 6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--accent);">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <span>Butuh Template Siap Pakai?</span>
                </div>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                    Unduh template resmi Microsoft Word (<strong>.docx</strong>) berstandar institusional yang sudah disesuaikan dengan format tabel 3 kolom dan penanda hari sistem ini.
                </p>
                <a href="{{ route('mahasiswa.activities.import.template') }}" class="btn btn-secondary btn-sm" id="btn-download-import-template" data-download="docx" data-download-title="Mengunduh Template Logbook (.docx)" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; padding: 8px 14px; text-decoration: none;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Unduh Template Laporan (.docx)
                </a>
            </div>
        </div>

        <div style="background: var(--info-dim); border-left: 4px solid var(--info); padding: 12px 14px; border-radius: var(--r-sm); font-size: 12px; color: var(--info-text); line-height: 1.5;">
            <strong>Pratinjau &amp; Kontrol Penuh:</strong> Data hasil ekstraksi tidak akan langsung masuk database. Anda dapat meninjau, mengedit teks, atau memilih aktivitas mana yang ingin disimpan.
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
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const files = Array.from(e.dataTransfer.files).filter(f => {
            const ext = f.name.toLowerCase().split('.').pop();
            return ext === 'docx' || ext === 'pdf';
        });
        if (files.length === 0) {
            showAlertModal('Hanya file dokumen Microsoft Word (.docx) atau Adobe Acrobat (.pdf) yang diperbolehkan.', 'Format File Tidak Sesuai', 'warning');
            return;
        }
        addFiles(files);
    });

    fileInput.addEventListener('change', (e) => {
        addFiles(Array.from(e.target.files));
    });

    function addFiles(newFiles) {
        newFiles.forEach(file => {
            const ext = file.name.toLowerCase().split('.').pop();
            if (ext !== 'docx' && ext !== 'pdf') {
                showAlertModal(`File "${file.name}" dilewati karena bukan file dokumen Word (.docx) atau PDF (.pdf).`, 'Format Tidak Didukung', 'warning');
                return;
            }
            if (file.size > 25 * 1024 * 1024) {
                showAlertModal(`File "${file.name}" melebihi batas ukuran maksimal 25 MB.`, 'Ukuran File Terlalu Besar', 'warning');
                return;
            }
            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                if (selectedFiles.length >= 10) {
                    showAlertModal('Batas maksimal adalah 10 file per proses impor.', 'Batas File Terlampaui', 'warning');
                    return;
                }
                selectedFiles.push(file);
            } else {
                showAlertModal(`File "${file.name}" sudah ada di antrean daftar unggah dan dilewati.`, 'File Kembar Dilewati', 'info');
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

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        if (selectedFiles.length > 0) {
            fileListContainer.style.display = 'block';
            btnSubmit.removeAttribute('disabled');
            fileList.innerHTML = '';

            selectedFiles.forEach((file, idx) => {
                const rawExt = file.name.toLowerCase().split('.').pop() || '';
                const ext = escapeHtml(rawExt);
                const safeName = escapeHtml(file.name);
                const isPdf = rawExt === 'pdf';
                const extBadgeColor = isPdf ? 'color: var(--danger-text); background: var(--danger-dim); border-color: rgba(239, 68, 68, 0.3);' : 'color: var(--accent); background: var(--accent-dim); border-color: var(--border);';
                
                const sizeKb = (file.size / 1024).toFixed(1);
                const sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb + ' KB';

                const item = document.createElement('div');
                item.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--r-sm); font-size: 13px;';
                item.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px; overflow: hidden;">
                        <span style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; padding: 2px 7px; border-radius: 4px; border: 1px solid; ${extBadgeColor}">${ext}</span>
                        <span style="font-weight: 500; color: var(--text-primary); text-overflow: ellipsis; white-space: nowrap; overflow: hidden; max-width: 300px;" title="${safeName}">${safeName}</span>
                        <span style="color: var(--text-muted); font-size: 11.5px;">(${sizeStr})</span>
                    </div>
                    <button type="button" data-index="${idx}" class="btn-remove-file" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 2px 8px; font-size: 18px; line-height: 1;" title="Hapus file">&times;</button>
                `;
                fileList.appendChild(item);
            });

            document.querySelectorAll('.btn-remove-file').forEach(btn => {
                btn.addEventListener('mouseenter', () => btn.style.color = 'var(--danger-text)');
                btn.addEventListener('mouseleave', () => btn.style.color = 'var(--text-muted)');
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
        btnSubmit.style.opacity = '0.75';
        btnSubmit.style.cursor = 'wait';
        btnSubmit.innerHTML = 'Memproses &amp; Mengekstrak Dokumen...';
    });
});
</script>
@endsection
