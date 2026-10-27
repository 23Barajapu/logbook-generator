# ADR 0002: Batch Import Dokumen (DOCX & PDF) dan Pencatatan Otomatis Aktivitas Logbook

## Status
Accepted

## Konteks
Mahasiswa seringkali telah mencatat logbook harian mereka dalam file Microsoft Word (`.docx`) atau dokumen PDF (`.pdf`), baik mingguan maupun keseluruhan periode PKL, lengkap dengan teks tabel dan gambar dokumentasi kegiatan. Memasukkan data ini satu per satu secara manual ke web sangat memakan waktu. Diperlukan fitur import multi-file (DOCX & PDF) yang secara cerdas membaca format tabel standar, mengekstrak gambar bukti secara otomatis, memfilter ikon/logo yang tidak relevan, mendeteksi konflik nomor hari, memungkinkan penambahan foto langsung di pratinjau, serta menyediakan tampilan review simetris sebelum disimpan.

## Keputusan Arsitektur
1. **Layanan Khusus (`DocxImportService`)**:
   - Isolasi logika ekstraksi dari controller mengikuti prinsip Single Responsibility Principle (SRP).
   - Mendukung format file `.docx` (via native PHP `ZipArchive`, `DOMDocument`, dan `DOMXPath`) serta `.pdf` (via `smalot/pdfparser` dan PHP GD).
   - Mengekstrak gambar tertanam di file DOCX (`word/media/`) dan PDF (XObject gambar dengan filter cerdas ukuran > 100px dan > 1KB untuk menyingkirkan bullet point/ikon) ke folder sementara `temp_imports/{token}/`.
2. **Parser Tanggal & Kolom Adaptif**:
   - Mendeteksi penanda `Hari Ke-X, Tanggal ...`.
   - Mengubah nama bulan bahasa Indonesia (`Januari` - `Desember`, format singkat `Okt`, `Ags`, dll.) ke format ISO `YYYY-MM-DD`.
   - Memecah kolom ketiga menjadi `tools_used`, `problems`, dan `solutions`.
3. **Alur 2 Tahap (Upload &rarr; Preview &rarr; Konfirmasi Simpan)**:
   - Data sementara disimpan di sesi dan storage publik sementara.
   - Halaman pratinjau menyediakan kontrol pemilihan per hari (checkbox), penanganan konflik (*overwrite* vs *skip*), inline-editing teks, penghapusan gambar yang tidak diinginkan, serta upload foto dokumentasi baru secara manual (`new_activity_images`).
4. **Desain Antarmuka Simetris & Responsif**:
   - Layout dual-column dengan tinggi sejajar (*equal height*) pada halaman upload.
   - 4-stat card ringkasan, grid 2-kolom bidang dan kegiatan, grid 3-kolom tools/masalah/solusi, serta thumbnail galeri foto yang rapi dan proporsional.
5. **Status Default `Draft` & Anti-Duplikasi Dokumen**:
   - Seluruh aktivitas hasil impor disimpan dengan status `Draft`.
   - Hash SHA-256 dicatat pada tabel `imported_documents` dan `activity_attachments` untuk mencegah impor dan upload file ganda yang tidak disengaja.

## Konsekuensi
- Mahasiswa dapat mengunggah hingga 10 file DOCX dan/atau PDF sekaligus (maksimal 25MB per file).
- Gambar bukti dokumentasi dari DOCX maupun PDF otomatis terekstrak secara bersih tanpa membawa artefak ikon atau garis pemisah tabel.
- Mahasiswa memiliki fleksibilitas penuh untuk menambah foto bukti dokumentasi langsung di halaman pratinjau sebelum menyimpan ke logbook.
