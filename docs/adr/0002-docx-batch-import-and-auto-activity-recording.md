# ADR 0002: Batch Import DOCX dan Pencatatan Otomatis Aktivitas Logbook

## Status
Accepted

## Konteks
Mahasiswa seringkali telah mencatat logbook harian mereka dalam beberapa file Microsoft Word (`.docx`) baik mingguan maupun keseluruhan periode PKL, lengkap dengan teks tabel dan gambar dokumentasi kegiatan. Memasukkan data ini satu per satu secara manual ke web sangat memakan waktu. Diperlukan fitur import multi-file DOCX yang secara cerdas membaca format tabel standar, mengekstrak gambar bukti, mendeteksi konflik nomor hari, dan menyediakan tahap review pratinjau sebelum disimpan.

## Keputusan Arsitektur
1. **Layanan Khusus (`DocxImportService`)**:
   - Isolasi logika ekstraksi dari controller mengikuti prinsip SRP.
   - Menggunakan native PHP `ZipArchive`, `DOMDocument`, dan `DOMXPath` untuk parsing `word/document.xml` dan `word/_rels/document.xml.rels`.
   - Mengekstrak gambar tertanam di `word/media/` dan menyimpannya di folder sementara `temp_imports/{token}/`.
2. **Parser Tanggal & Kolom Adaptif**:
   - Mendeteksi penanda `Hari Ke-X`.
   - Mengubah nama bulan bahasa Indonesia (`Januari` - `Desember`, format singkat `Okt`, `Ags`, dll.) ke format ISO `YYYY-MM-DD`.
   - Memecah kolom ketiga menjadi `tools_used`, `problems`, dan `solutions`.
3. **Alur 2 Tahap (Upload &rarr; Preview &rarr; Konfirmasi Simpan)**:
   - Data sementara disimpan di sesi dan storage publik sementara.
   - Halaman pratinjau menyediakan kontrol pemilihan per hari (checkbox), penanganan konflik (*overwrite* vs *skip*), dan inline-editing.
4. **Status Default `Draft`**:
   - Seluruh aktivitas hasil impor disimpan dengan status `Draft` agar mahasiswa dapat memeriksa dan melengkapi sebelum pengajuan persetujuan ke pembimbing.
5. **Kebersihan Storage**:
   - Saat konfirmasi, file gambar dipindahkan permanen ke `attachments/{activity_id}/` dan didaftarkan ke `activity_attachments`.
   - Saat pembatalan atau setelah simpan selesai, folder sementara dibersihkan.

## Konsekuensi
- Mahasiswa dapat mengunggah hingga 10 file DOCX sekaligus (maksimal 25MB per file).
- Data gambar dan teks diekstrak dengan akurasi tinggi dan aspect ratio asli terjaga.
