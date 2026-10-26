# Product Requirement Document (PRD)
# Sistem Generator Logbook PKL / Magang
## Platform Manajemen Aktivitas Harian, Approval Pembimbing, dan Generator Dokumen Akademik

---

| Dokumen Info | Detail |
| :--- | :--- |
| Nama Proyek | DailyLog PKL |
| Jenis Sistem | Academic Internship Logbook Generator |
| Versi Dokumen | v2.0.0 |
| Status | Ready for Development |
| Target Pengguna | Mahasiswa, Pembimbing, Admin |
| Target Deployment | Web Application |
| Teknologi Utama | Laravel 12 + PostgreSQL |
| Fokus Sistem | Pencatatan Aktivitas, Approval, dan Generate Logbook Otomatis |

---

# 1. Latar Belakang

Proses pencatatan aktivitas Praktik Kerja Lapangan (PKL), Magang, MBKM, maupun kegiatan akademik serupa masih banyak dilakukan secara manual menggunakan dokumen Word yang diisi setiap hari.

Metode tersebut sering menimbulkan permasalahan:

- Aktivitas harian terlambat dicatat.
- Bukti kegiatan tersebar di berbagai media.
- Pembimbing kesulitan melakukan monitoring.
- Penyusunan logbook membutuhkan waktu lama.
- Format dokumen sering tidak konsisten.

Sistem DailyLog PKL dikembangkan untuk mendigitalisasi proses pencatatan aktivitas harian sekaligus menghasilkan dokumen logbook secara otomatis sesuai format institusi.

---

# 2. Tujuan Sistem

## Tujuan Utama

- Mempermudah pencatatan aktivitas harian PKL.
- Menyediakan sistem approval pembimbing.
- Menghasilkan logbook otomatis dalam format PDF dan DOCX.
- Mempermudah monitoring progres PKL.
- Menstandarkan format laporan aktivitas harian.

---

# 3. Arsitektur Sistem

```text
┌────────────────────────────┐
│      Web Application       │
│         Laravel 12         │
├────────────────────────────┤
│ Authentication             │
│ Master Data PKL            │
│ Aktivitas Harian           │
│ Approval Pembimbing        │
│ Generator Dokumen          │
│ Penilaian PKL              │
└──────────────┬─────────────┘
               │
               ▼

      ┌─────────────────┐
      │   PostgreSQL    │
      └─────────────────┘
```

Arsitektur menggunakan pendekatan monolithic web application untuk memudahkan implementasi, maintenance, dan deployment.

---

# 4. Role Pengguna

## 4.1 Mahasiswa

Hak akses:

- Login sistem
- Mengelola aktivitas harian
- Upload bukti kegiatan
- Melihat status approval
- Generate logbook
- Download PDF
- Download DOCX

---

## 4.2 Pembimbing

Hak akses:

- Melihat aktivitas mahasiswa
- Memberikan catatan
- Menyetujui aktivitas
- Meminta revisi aktivitas
- Memberikan penilaian PKL

---

## 4.3 Admin

Hak akses:

- Mengelola data mahasiswa
- Mengelola data pembimbing
- Mengelola data perusahaan
- Mengelola template dokumen
- Mengelola periode PKL

---

# 5. Modul Master Data

## Data Mahasiswa

- NIM
- Nama Lengkap
- Program Studi
- Angkatan
- Email
- Nomor Telepon

## Data Pembimbing

- NIP/NIDN
- Nama
- Jabatan
- Email

## Data Perusahaan

- Nama Perusahaan
- Alamat
- Pembimbing Lapangan
- Jabatan Pembimbing Lapangan

## Data PKL

- Mahasiswa
- Perusahaan
- Tanggal Mulai
- Tanggal Selesai
- Status

---

# 6. Modul Aktivitas Harian

## Form Aktivitas

| Field | Keterangan |
|---------|---------|
| Hari Ke | Nomor Hari PKL |
| Tanggal | Tanggal Aktivitas |
| Bidang Pekerjaan | Bidang atau Divisi |
| Kegiatan | Deskripsi Aktivitas |
| Tools Digunakan | Perangkat atau Aplikasi |
| Masalah | Kendala yang Dihadapi |
| Solusi | Solusi yang Dilakukan |
| Bukti Kegiatan | Lampiran Dokumentasi |

---

## Upload Bukti

Format yang didukung:

- JPG
- JPEG
- PNG
- PDF
- DOCX
- XLSX

Ukuran maksimum:

- 20 MB per file

Jumlah maksimum:

- 10 file per aktivitas

---

# 7. Workflow Approval

```text
Mahasiswa
    │
    ▼
Input Aktivitas
    │
    ▼
Menunggu Approval
    │
    ├── Disetujui
    │       │
    │       ▼
    │   Masuk Logbook
    │
    └── Revisi
            │
            ▼
      Mahasiswa Memperbaiki
```

Status aktivitas:

- Draft
- Menunggu Approval
- Disetujui
- Revisi

---

# 8. Modul Generator Dokumen

## 8.1 Buku Aktivitas Harian

Output:

- Cover
- Lembar Pengesahan
- Aktivitas Harian
- Halaman Tanda Tangan

Format:

- DOCX
- PDF

---

## 8.2 Rekap Aktivitas

Menampilkan:

- Total Hari PKL
- Total Aktivitas
- Statistik Bidang Kerja
- Statistik Tools

---

## 8.3 Laporan Akhir PKL

Berisi:

- Ringkasan Aktivitas
- Rekap Kegiatan
- Statistik Kehadiran
- Statistik Approval

---

# 9. Modul Penilaian

## Komponen Penilaian

| Aspek | Bobot |
|---------|---------|
| Kehadiran | 20% |
| Disiplin | 20% |
| Aktivitas Harian | 30% |
| Laporan PKL | 30% |

Total:

- 100%

---

# 10. Struktur Database

```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150),
    email VARCHAR(191) UNIQUE,
    password VARCHAR(255),
    role VARCHAR(30)
);

CREATE TABLE students (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    nim VARCHAR(50),
    study_program VARCHAR(150),
    batch_year INT
);

CREATE TABLE supervisors (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    employee_number VARCHAR(50)
);

CREATE TABLE companies (
    id BIGSERIAL PRIMARY KEY,
    company_name VARCHAR(255),
    address TEXT
);

CREATE TABLE internships (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT,
    company_id BIGINT,
    start_date DATE,
    end_date DATE
);

CREATE TABLE activities (
    id BIGSERIAL PRIMARY KEY,
    internship_id BIGINT,
    day_number INT,
    activity_date DATE,
    work_field VARCHAR(150),
    activities TEXT,
    tools_used TEXT,
    problems TEXT,
    solutions TEXT,
    status VARCHAR(50)
);

CREATE TABLE activity_attachments (
    id BIGSERIAL PRIMARY KEY,
    activity_id BIGINT,
    file_name VARCHAR(255),
    file_path TEXT
);

CREATE TABLE approvals (
    id BIGSERIAL PRIMARY KEY,
    activity_id BIGINT,
    supervisor_id BIGINT,
    notes TEXT,
    approved_at TIMESTAMP
);

CREATE TABLE evaluations (
    id BIGSERIAL PRIMARY KEY,
    internship_id BIGINT,
    attendance_score DECIMAL(5,2),
    discipline_score DECIMAL(5,2),
    activity_score DECIMAL(5,2),
    report_score DECIMAL(5,2),
    final_score DECIMAL(5,2)
);
```

---

# 11. Non-Functional Requirements

## Performance

- Waktu respon halaman < 3 detik.
- Generate PDF maksimal 30 detik.
- Mendukung minimal 500 pengguna aktif.

## Security

- Password Hashing BCrypt.
- Role Based Access Control.
- Validasi Upload File.
- Session Authentication.

## Compatibility

- Google Chrome
- Microsoft Edge
- Mozilla Firefox
- Safari

---

# 12. Roadmap Pengembangan

## Phase 1 (MVP)

- Authentication
- Master Data
- Aktivitas Harian
- Approval Pembimbing
- Generate DOCX
- Generate PDF

## Phase 2

- Penilaian PKL
- Rekap Aktivitas
- Dashboard Statistik
- Template Dokumen Dinamis

## Phase 3

- Integrasi SIAKAD
- Mobile Responsive Enhancement
- Multi Kampus Support

---

# 13. Outcome

Sistem DailyLog PKL memungkinkan mahasiswa mencatat aktivitas harian secara digital, memperoleh persetujuan pembimbing secara terstruktur, dan menghasilkan dokumen logbook PKL otomatis dalam format PDF maupun DOCX sesuai standar institusi akademik.
