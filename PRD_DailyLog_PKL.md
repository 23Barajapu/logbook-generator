# Product Requirement Document (PRD)
# Sistem Generator Buku Aktivitas Harian PKL / Magang
## Arsitektur Terpisah: Mahasiswa Portal & Dashboard Pembimbing (Dual-Frontend Academic Logbook Platform)

---

| Dokumen Info | Detail |
| :--- | :--- |
| Nama Proyek | DailyLog PKL |
| Official Brand | DailyLog Academic Platform |
| Versi Dokumen | v1.0.0 |
| Status | Approved for Development |
| Target Deployment | Semester Genap TA 2026/2027 |
| Pemisahan Aplikasi | 1. Mahasiswa Portal (`logbook.kampus.ac.id`)  2. Dashboard Pembimbing (`supervisor.kampus.ac.id`)  3. Core API (`api.kampus.ac.id`) |

---

# 1. Arsitektur Topologi & Pemisahan Sistem

Platform DailyLog menggunakan arsitektur terpusat dengan dua frontend terpisah yang mengakses satu API dan database yang sama.

## Komponen Utama
- Portal Mahasiswa
- Dashboard Pembimbing
- Core Backend API
- PostgreSQL
- Redis

## Keuntungan
1. Monitoring real-time.
2. Approval digital.
3. Generator logbook otomatis.
4. Dukungan PKL, Magang, MBKM, dan KKN.

---

# 2. Portal Mahasiswa

## Dashboard
- Total Hari PKL
- Hari Terisi
- Menunggu Approval
- Disetujui

## Input Aktivitas Harian

### Field
| Field | Tipe |
|---------|---------|
| Hari Ke | Integer |
| Tanggal | Date |
| Bidang Pekerjaan | Dropdown |
| Kegiatan | Rich Text |
| Tools Digunakan | Multi Select |
| Masalah | Textarea |
| Solusi | Textarea |
| Bukti Kegiatan | Upload |

## Upload Bukti
- JPG
- PNG
- PDF
- DOCX
- XLSX

## Generator Dokumen
- Buku Aktivitas Harian
- Rekap Mingguan
- Rekap Bulanan
- Laporan Akhir PKL

---

# 3. Dashboard Pembimbing

## Approval Aktivitas
- Setujui
- Revisi
- Beri Catatan

## Monitoring
- Jurusan
- Perusahaan
- Angkatan
- Status Approval

## Penilaian
| Aspek | Bobot |
|---------|---------|
| Kehadiran | 20% |
| Disiplin | 20% |
| Kualitas Laporan | 30% |
| Aktivitas Harian | 30% |

---

# 4. Generator Dokumen

## Cover Generator
Input:
- Nama
- NIM
- Program Studi
- Perusahaan
- Tahun Akademik
- Logo Kampus

## Lembar Pengesahan
Input:
- Nama Mahasiswa
- NIM
- Pembimbing Lapangan
- Jabatan
- Tanggal Pengesahan

## Aktivitas Harian
Template:
- Hari Ke
- Tanggal
- Bidang Pekerjaan
- Kegiatan
- Tools
- Masalah
- Solusi

---

# 5. Role & Permission

## Mahasiswa
- CRUD Aktivitas
- Upload Bukti
- Generate Dokumen

## Pembimbing
- Review Aktivitas
- Approval
- Penilaian

## Admin
- Kelola Mahasiswa
- Kelola Pembimbing
- Kelola Template

---

# 6. Validasi Input

| Endpoint | Parameter | Aturan |
|-----------|-----------|---------|
| POST /activities | hari_ke | Integer > 0 |
| POST /activities | tanggal | Wajib |
| POST /activities | kegiatan | Minimal 10 karakter |
| POST /activities | tools | Array |

---

# 7. Database

```sql
CREATE TABLE students (
    id BIGSERIAL PRIMARY KEY,
    nim VARCHAR(50) UNIQUE,
    name VARCHAR(150),
    study_program VARCHAR(150),
    company_name VARCHAR(255)
);

CREATE TABLE activities (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT REFERENCES students(id),
    day_number INT,
    activity_date DATE,
    work_field VARCHAR(150),
    activities TEXT,
    tools_used TEXT,
    problems TEXT,
    solutions TEXT
);
```

---

# 8. API

## Mahasiswa
- POST /api/v1/activities
- GET /api/v1/activities
- PUT /api/v1/activities/{id}
- DELETE /api/v1/activities/{id}
- POST /api/v1/generate/logbook

## Pembimbing
- GET /api/v1/supervisor/students
- GET /api/v1/supervisor/activities
- POST /api/v1/supervisor/approve

---

# 9. Roadmap

## Phase 1
- Login
- Input Aktivitas
- Approval
- DOCX Generator
- PDF Generator

## Phase 2
- QR Verification
- Digital Signature
- Template Dinamis

## Phase 3
- Android App
- iOS App
- Integrasi SIAKAD
- AI Activity Assistant
