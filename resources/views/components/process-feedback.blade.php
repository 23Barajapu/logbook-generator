{{-- Komponen Feedback Animasi Upload & Download (Apple-Grade Process Motion) --}}

<!-- 1. Overlay Animasi Upload & Ekstraksi -->
<div id="upload-process-overlay" class="process-overlay" role="dialog" aria-modal="true" aria-labelledby="upload-process-title" aria-live="assertive">
    <div class="process-card">
        <div class="process-icon-wrap">
            <div class="process-ring-pulse"></div>
            <div class="process-ring-spinner"></div>
            <div class="process-icon-inner">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </div>
        </div>

        <div class="process-title" id="upload-process-title">
            Sedang Memproses Dokumen
        </div>

        <div class="process-step-container">
            <div class="process-step-text" id="upload-process-step">
                Mengunggah berkas ke server...
            </div>
        </div>

        <div class="process-track">
            <div class="process-fill"></div>
        </div>

        <div class="process-subtext">
            Mohon tidak menutup peramban selama proses berlangsung.
        </div>

        <div style="margin-top: 16px;">
            <button type="button" onclick="window.dismissUploadProgress()" style="background: none; border: 1px solid var(--border); color: var(--text-muted); font-size: 11.5px; padding: 5px 12px; border-radius: var(--r-sm); cursor: pointer; transition: color 150ms var(--ease-out);">
                Tutup Jendela Ini
            </button>
        </div>
    </div>
</div>

<!-- 2. Toast Animasi Download Dokumen (Non-blocking Dynamic Island) -->
<div id="download-progress-toast" class="download-toast" role="status" aria-live="polite">
    <div class="download-icon-wrap" id="download-toast-icon">
        <svg class="download-icon-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
    </div>

    <div class="download-body">
        <div class="download-title" id="download-toast-title">
            Menyiapkan Unduhan
        </div>
        <div class="download-step-text" id="download-toast-step">
            1/3: Mengumpulkan data logbook...
        </div>
        <div class="download-progress-bar">
            <div class="download-progress-fill" id="download-toast-fill"></div>
        </div>
    </div>

    <button type="button" class="download-close-btn" onclick="window.dismissDownloadProgress()" aria-label="Tutup notifikasi unduhan">&times;</button>
</div>

<script>
(function() {
    let uploadInterval = null;
    let uploadTimeout = null;
    let downloadTimeout = null;
    let downloadInterval = null;

    /**
     * Tampilkan Animasi & Keterangan Proses Upload
     */
    window.showUploadProgress = function(options = {}) {
        const overlay = document.getElementById('upload-process-overlay');
        const titleEl = document.getElementById('upload-process-title');
        const stepEl = document.getElementById('upload-process-step');
        if (!overlay || !stepEl) return;

        if (uploadInterval) clearInterval(uploadInterval);
        if (uploadTimeout) clearTimeout(uploadTimeout);

        const title = options.title || 'Sedang Memproses Dokumen';
        const steps = options.steps && options.steps.length > 0 
            ? options.steps 
            : [
                'Mengunggah berkas dokumen ke server...',
                'Membaca penanda hari & struktur tabel...',
                'Mengekstrak teks & bukti foto dokumentasi...',
                'Menyusun pratinjau aktivitas logbook...'
            ];
        const stepInterval = options.stepInterval || 1400;

        titleEl.textContent = title;
        stepEl.textContent = steps[0];
        stepEl.classList.remove('fading');

        overlay.classList.remove('closing');
        overlay.classList.add('active');

        let currentIdx = 0;
        uploadInterval = setInterval(() => {
            currentIdx++;
            if (currentIdx < steps.length) {
                stepEl.classList.add('fading');
                setTimeout(() => {
                    stepEl.textContent = steps[currentIdx];
                    stepEl.classList.remove('fading');
                }, 180);
            }
        }, stepInterval);

        // Safety fallback: auto-dismiss setelah 60 detik jika koneksi lambat/selesai
        uploadTimeout = setTimeout(() => {
            window.dismissUploadProgress();
        }, 60000);
    };

    /**
     * Tutup Animasi Upload
     */
    window.dismissUploadProgress = function() {
        const overlay = document.getElementById('upload-process-overlay');
        if (!overlay) return;
        if (uploadInterval) clearInterval(uploadInterval);
        if (uploadTimeout) clearTimeout(uploadTimeout);

        overlay.classList.add('closing');
        setTimeout(() => {
            overlay.classList.remove('active', 'closing');
        }, 160);
    };

    /**
     * Tampilkan Animasi & Keterangan Proses Download (Non-blocking)
     */
    window.showDownloadProgress = function(options = {}) {
        const toast = document.getElementById('download-progress-toast');
        const iconWrap = document.getElementById('download-toast-icon');
        const titleEl = document.getElementById('download-toast-title');
        const stepEl = document.getElementById('download-toast-step');
        const fillEl = document.getElementById('download-toast-fill');
        if (!toast) return;

        if (downloadTimeout) clearTimeout(downloadTimeout);
        if (downloadInterval) clearInterval(downloadInterval);

        const format = (options.format || 'file').toLowerCase();
        const isPdf = format.includes('pdf');
        const isDocx = format.includes('docx') || format.includes('word');
        const title = options.title || (isPdf ? 'Mengunduh Dokumen PDF' : (isDocx ? 'Mengunduh Dokumen Word' : 'Menyiapkan Unduhan'));

        const defaultSteps = [
            '1/3: Mengumpulkan data catatan aktivitas...',
            isPdf ? '2/3: Merender dokumen PDF & format cetak...' : '2/3: Menata format tabel dokumen Word...',
            '3/3: Mengirim berkas ke peramban...',
            'Selesai! Unduhan berhasil dimulai.'
        ];
        const steps = options.steps && options.steps.length > 0 ? options.steps : defaultSteps;

        iconWrap.className = 'download-icon-wrap';
        if (isPdf) {
            iconWrap.classList.add('is-pdf');
        }

        iconWrap.innerHTML = `
            <svg class="download-icon-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        `;

        titleEl.textContent = title;
        stepEl.textContent = steps[0];
        stepEl.style.opacity = '1';

        fillEl.style.transition = 'none';
        fillEl.style.width = '0%';

        toast.classList.remove('closing');
        toast.classList.add('active');

        requestAnimationFrame(() => {
            fillEl.style.transition = 'width 2.8s cubic-bezier(0.16, 1, 0.3, 1)';
            fillEl.style.width = '100%';
        });

        let stepIdx = 0;
        const stepDelay = 900;
        downloadInterval = setInterval(() => {
            stepIdx++;
            if (stepIdx < steps.length - 1) {
                stepEl.style.opacity = '0';
                setTimeout(() => {
                    stepEl.textContent = steps[stepIdx];
                    stepEl.style.opacity = '1';
                }, 160);
            } else if (stepIdx === steps.length - 1) {
                clearInterval(downloadInterval);
                stepEl.style.opacity = '0';
                setTimeout(() => {
                    stepEl.textContent = steps[steps.length - 1];
                    stepEl.style.opacity = '1';
                    iconWrap.classList.add('is-success');
                    iconWrap.innerHTML = `
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    `;
                }, 160);
            }
        }, stepDelay);

        downloadTimeout = setTimeout(() => {
            window.dismissDownloadProgress();
        }, 4500);
    };

    /**
     * Tutup Toast Download
     */
    window.dismissDownloadProgress = function() {
        const toast = document.getElementById('download-progress-toast');
        if (!toast || !toast.classList.contains('active')) return;

        if (downloadTimeout) clearTimeout(downloadTimeout);
        if (downloadInterval) clearInterval(downloadInterval);

        toast.classList.add('closing');
        setTimeout(() => {
            toast.classList.remove('active', 'closing');
        }, 180);
    };

    // Bersihkan overlay bila halaman dimuat kembali dari cache peramban (bfcache)
    window.addEventListener('pageshow', function() {
        window.dismissUploadProgress();
    });

    // Pasang listener tombol ESC untuk dismiss overlay
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.dismissUploadProgress();
            window.dismissDownloadProgress();
        }
    });

    /**
     * Pasang Listener Otomatis yang Bersih & Aman Tanpa Menghambat Alur Native Peramban
     */
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Form Upload Handler (Non-blocking)
        document.querySelectorAll('form[data-upload-form]').forEach(form => {
            form.addEventListener('submit', function(e) {
                // Beri waktu handler lain (seperti validasi checkbox) berjalan
                setTimeout(() => {
                    if (e.defaultPrevented) return;
                    if (!form.checkValidity()) return;

                    const title = form.getAttribute('data-process-title') || 'Sedang Memproses Dokumen';
                    let steps = null;
                    const stepsAttr = form.getAttribute('data-process-steps');
                    if (stepsAttr) {
                        try {
                            steps = JSON.parse(stepsAttr);
                        } catch(err) {
                            steps = stepsAttr.split(',').map(s => s.trim());
                        }
                    }

                    window.showUploadProgress({
                        title: title,
                        steps: steps
                    });
                }, 10);
            });
        });

        // 2. Download Links Handler (HANYA mendengarkan, TIDAK mengubah DOM atau pointer-events)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-download], a[href*="/docx"], a[href*="/pdf"], a[href*="/template"]');
            if (!btn) return;

            // Jangan sentuh link navigasi biasa seperti summary
            const href = btn.getAttribute('href') || '';
            if (href.includes('/summary') || btn.classList.contains('modal-close')) return;

            const downloadAttr = (btn.getAttribute('data-download') || '').toLowerCase();
            const isPdf = href.includes('/pdf') || downloadAttr.includes('pdf') || btn.textContent.toLowerCase().includes('pdf');
            const isDocx = href.includes('/docx') || downloadAttr.includes('docx') || href.includes('template') || btn.textContent.toLowerCase().includes('docx');

            const format = isPdf ? 'pdf' : (isDocx ? 'docx' : 'file');
            const title = btn.getAttribute('data-download-title') || (isPdf ? 'Mengunduh Dokumen PDF' : 'Mengunduh Dokumen Word');

            // Tampilkan toast non-blocking (biarkan native browser download link tetap berjalan secara paralel)
            window.showDownloadProgress({
                format: format,
                title: title
            });
        });
    });
})();
</script>
