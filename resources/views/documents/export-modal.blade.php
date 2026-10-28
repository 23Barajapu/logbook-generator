{{-- Modal Ekspor Logbook: Keseluruhan & Per Minggu --}}
<div id="modal-export" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-export-title">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-export-title">Unduh Dokumen Logbook</h2>
            <button type="button" class="modal-close" onclick="closeExportModal()" aria-label="Tutup modal">&times;</button>
        </div>
        <div class="modal-body">
            {{-- Pilihan 1: Unduh Keseluruhan --}}
            <div class="export-option-card">
                <div class="export-option-title">
                    <span>1. Dokumen Keseluruhan (Full Logbook)</span>
                    <span class="badge badge-primary">Lengkap</span>
                </div>
                <div class="export-option-desc">
                    Mencakup seluruh catatan aktivitas harian PKL sejak awal hingga akhir kegiatan dalam satu dokumen lengkap.
                </div>
                <div style="display: flex; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                    <a href="{{ route('documents.docx', $internship) }}" class="btn btn-primary" id="btn-export-all-docx" data-download="docx" data-download-title="Mengunduh Seluruh Logbook (DOCX)">
                        Unduh DOCX (.docx)
                    </a>
                    <a href="{{ route('documents.pdf', $internship) }}" class="btn btn-secondary" id="btn-export-all-pdf" data-download="pdf" data-download-title="Mengunduh Seluruh Logbook (PDF)">
                        Unduh PDF (.pdf)
                    </a>
                </div>
            </div>

            {{-- Pilihan 2: Unduh Per Minggu --}}
            <div class="export-option-card">
                <div class="export-option-title">
                    <span>2. Dokumen Per Minggu</span>
                    <span class="badge badge-warning">Mingguan</span>
                </div>
                <div class="export-option-desc">
                    Pilih minggu PKL tertentu untuk mengunduh laporan aktivitas mingguan (Senin – Jumat).
                </div>
                @if(!empty($availableWeeks) && count($availableWeeks) > 0)
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="select-export-week" class="form-label" style="margin-bottom: 0;">Pilih Periode Minggu:</label>
                        <select id="select-export-week" class="form-control" onchange="updateWeeklyDownloadLinks()">
                            @foreach($availableWeeks as $w)
                                <option value="{{ $w['week_number'] }}" 
                                        data-start="{{ $w['start_date'] }}" 
                                        data-end="{{ $w['end_date'] }}"
                                        data-week="{{ $w['week_number'] }}">
                                    {{ $w['label'] }} — {{ $w['activities_count'] }} aktivitas
                                </option>
                            @endforeach
                        </select>
                        <div style="display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap;">
                            <a id="btn-export-week-docx" href="#" class="btn btn-primary" data-download="docx" data-download-title="Mengunduh Logbook Mingguan (DOCX)">
                                Unduh DOCX Minggu Ini
                            </a>
                            <a id="btn-export-week-pdf" href="#" class="btn btn-secondary" data-download="pdf" data-download-title="Mengunduh Logbook Mingguan (PDF)">
                                Unduh PDF Minggu Ini
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-muted text-sm">
                        Belum ada aktivitas yang dicatat untuk dikelompokkan ke dalam minggu.
                    </div>
                @endif
            </div>

            {{-- Pilihan 3: Filter Rentang Tanggal Tertentu --}}
            <div class="export-option-card">
                <div class="export-option-title">
                    <span>3. Rentang Tanggal Kustom</span>
                    <span class="badge badge-secondary">Kustom</span>
                </div>
                <div class="export-option-desc">
                    Tentukan tanggal awal dan akhir sesuai kebutuhan laporan Anda.
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 140px;">
                        <label for="custom-start-date" class="form-label" style="font-size: 11px;">Mulai:</label>
                        <input type="date" id="custom-start-date" class="form-control" style="font-size: 12px; padding: 6px 10px;">
                    </div>
                    <div style="flex: 1; min-width: 140px;">
                        <label for="custom-end-date" class="form-label" style="font-size: 11px;">Sampai:</label>
                        <input type="date" id="custom-end-date" class="form-control" style="font-size: 12px; padding: 6px 10px;">
                    </div>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 4px;">
                    <button type="button" class="btn btn-secondary" onclick="downloadCustomDate('docx')">
                        Unduh DOCX
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="downloadCustomDate('pdf')">
                        Unduh PDF
                    </button>
                </div>
            </div>

            {{-- Pilihan 4: Unduh Template Laporan Kosong --}}
            <div class="export-option-card">
                <div class="export-option-title">
                    <span>4. Template Laporan PKL (.docx)</span>
                    <span class="badge badge-info">Template</span>
                </div>
                <div class="export-option-desc">
                    Unduh file template dokumen Microsoft Word resmi berstandar institusional untuk diisi secara offline atau dibagikan ke pengguna / mahasiswa lain.
                </div>
                <div style="display: flex; gap: 8px; margin-top: 4px;">
                    <a href="{{ route('documents.template') }}" class="btn btn-secondary" id="btn-export-template-docx" data-download="docx" data-download-title="Mengunduh Template Laporan (.docx)">
                        Unduh Template (.docx)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openExportModal() {
    const modal = document.getElementById('modal-export');
    if (modal) {
        modal.classList.add('active');
        updateWeeklyDownloadLinks();
    }
}

function closeExportModal() {
    const modal = document.getElementById('modal-export');
    if (modal && modal.classList.contains('active')) {
        modal.classList.add('closing');
        setTimeout(() => {
            modal.classList.remove('active', 'closing');
        }, 160);
    }
}

function updateWeeklyDownloadLinks() {
    const sel = document.getElementById('select-export-week');
    if (!sel || !sel.selectedOptions || sel.selectedOptions.length === 0) return;
    
    const opt = sel.selectedOptions[0];
    const start = opt.getAttribute('data-start');
    const end = opt.getAttribute('data-end');
    const week = opt.getAttribute('data-week');
    
    const baseDocx = "{{ route('documents.docx', $internship) }}";
    const basePdf = "{{ route('documents.pdf', $internship) }}";
    
    const btnDocx = document.getElementById('btn-export-week-docx');
    const btnPdf = document.getElementById('btn-export-week-pdf');
    
    if (btnDocx) {
        btnDocx.href = `${baseDocx}?start_date=${start}&end_date=${end}&week=${week}`;
    }
    if (btnPdf) {
        btnPdf.href = `${basePdf}?start_date=${start}&end_date=${end}&week=${week}`;
    }
}

function downloadCustomDate(format) {
    const start = document.getElementById('custom-start-date').value;
    const end = document.getElementById('custom-end-date').value;
    
    if (!start || !end) {
        showAlertModal('Silakan pilih tanggal awal dan tanggal akhir terlebih dahulu.', 'Rentang Tanggal Belum Lengkap', 'warning');
        return;
    }
    
    if (window.showDownloadProgress) {
        window.showDownloadProgress({
            format: format,
            title: format === 'pdf' ? 'Mengunduh Dokumen PDF Kustom' : 'Mengunduh Dokumen Word Kustom'
        });
    }

    const baseUrl = format === 'pdf' 
        ? "{{ route('documents.pdf', $internship) }}" 
        : "{{ route('documents.docx', $internship) }}";
        
    window.location.href = `${baseUrl}?start_date=${start}&end_date=${end}`;
}

// Tutup modal jika klik di backdrop
window.addEventListener('click', function(e) {
    const modal = document.getElementById('modal-export');
    if (e.target === modal) {
        closeExportModal();
    }
});
</script>
