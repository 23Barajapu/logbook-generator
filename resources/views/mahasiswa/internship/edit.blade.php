@extends('layouts.app')

@section('title', 'Data PKL & Dosen Pembimbing')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Data PKL & Dosen Pembimbing</h1>
        <div class="page-subtitle">Kelola informasi instansi magang dan dosen pembimbing akademik Anda</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('mahasiswa.dashboard') }}" class="btn btn-secondary">&larr; Kembali ke Dashboard</a>
    </div>
</div>

<div class="card" style="max-width: 780px;">
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Mohon periksa formulir berikut:</strong>
            <ul style="margin: 6px 0 0 16px; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('mahasiswa.internship.update') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        {{-- 1. DATA INSTANSI --}}
        <div class="auth-section-title" style="margin-top: 0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            1. Data Instansi / Perusahaan PKL
        </div>

        <div class="form-group">
            <label for="company_name" class="form-label">Nama Instansi / Perusahaan Tempat PKL <span style="color:var(--danger-text)">*</span></label>
            <input type="text" id="company_name" name="company_name"
                   class="form-control"
                   value="{{ old('company_name', $internship->company->company_name ?? '') }}"
                   list="company-suggestions"
                   required
                   placeholder="Contoh: PT. Inovasi Digital Nusantara / Dinas Kominfo">
            <datalist id="company-suggestions">
                @foreach($companies as $company)
                    <option value="{{ $company->company_name }}"></option>
                @endforeach
            </datalist>
            <div class="form-hint">Pilih dari rekomendasi atau ketik langsung nama instansi tempat Anda PKL.</div>
            @error('company_name')
                <span class="form-error" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="company_address" class="form-label">Alamat Instansi / Perusahaan</label>
            <textarea id="company_address" name="company_address"
                      class="form-control"
                      rows="2"
                      placeholder="Contoh: Jl. Diponegoro No. 45, Bandung">{{ old('company_address', $internship->company->address ?? '') }}</textarea>
            @error('company_address')
                <span class="form-error" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="field_supervisor" class="form-label">Nama Pembimbing Lapangan (Instansi)</label>
                <input type="text" id="field_supervisor" name="field_supervisor"
                       class="form-control"
                       value="{{ old('field_supervisor', $internship->company->field_supervisor ?? '') }}"
                       placeholder="Contoh: Rian Hidayat, S.T.">
                @error('field_supervisor')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="field_supervisor_position" class="form-label">Jabatan Pembimbing Lapangan</label>
                <input type="text" id="field_supervisor_position" name="field_supervisor_position"
                       class="form-control"
                       value="{{ old('field_supervisor_position', $internship->company->field_supervisor_position ?? '') }}"
                       placeholder="Contoh: Senior Software Engineer / Ka. Seksi IT">
                @error('field_supervisor_position')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- 2. DATA DOSEN PEMBIMBING --}}
        <div class="auth-section-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            2. Data Dosen Pembimbing Akademik
        </div>

        <div class="form-group">
            <label for="supervisor_id" class="form-label">Pilih Dosen Pembimbing Terdaftar (Jika Sudah Ada di Sistem)</label>
            <select id="supervisor_id" name="supervisor_id" class="form-control">
                <option value="">-- Pilih Dosen Pembimbing Terdaftar --</option>
                @foreach($supervisors as $spv)
                    <option value="{{ $spv->id }}" {{ old('supervisor_id', $internship->supervisor_id ?? '') == $spv->id ? 'selected' : '' }}>
                        {{ $spv->user->name ?? '-' }}{{ !empty($spv->employee_number) ? ' (NIP: ' . $spv->employee_number . ')' : '' }}
                    </option>
                @endforeach
            </select>
            <div class="form-hint">Jika nama dosen pembimbing Anda belum terdaftar di sistem, Anda dapat menginputnya pada formulir di bawah ini:</div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="supervisor_name" class="form-label">Nama Lengkap & Gelar Dosen Pembimbing</label>
                <input type="text" id="supervisor_name" name="supervisor_name"
                       class="form-control"
                       value="{{ old('supervisor_name') }}"
                       placeholder="Contoh: Dr. Ir. Hendra Gunawan, M.T.">
                @error('supervisor_name')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="supervisor_nip" class="form-label">NIP / NIDN Dosen <span style="font-weight:400;color:var(--text-muted);">(Opsional)</span></label>
                <input type="text" id="supervisor_nip" name="supervisor_nip"
                       class="form-control"
                       value="{{ old('supervisor_nip') }}"
                       placeholder="Kosongkan jika belum ada NIP">
                @error('supervisor_nip')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- 3. PERIODE WAKTU PKL --}}
        <div class="auth-section-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            3. Periode Waktu Praktik Kerja Lapangan
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date" class="form-label">Tanggal Mulai PKL <span style="color:var(--danger-text)">*</span></label>
                <input type="date" id="start_date" name="start_date"
                       class="form-control"
                       value="{{ old('start_date', $internship->start_date ? $internship->start_date->format('Y-m-d') : date('Y-m-d')) }}"
                       required>
                @error('start_date')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="end_date" class="form-label">Tanggal Selesai PKL <span style="color:var(--danger-text)">*</span></label>
                <input type="date" id="end_date" name="end_date"
                       class="form-control"
                       value="{{ old('end_date', $internship->end_date ? $internship->end_date->format('Y-m-d') : date('Y-m-d', strtotime('+3 months'))) }}"
                       required>
                @error('end_date')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- 4. LOGO KAMPUS LOGBOOK --}}
        <div class="auth-section-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            4. Logo Kampus untuk Sampul Logbook
        </div>

        <div class="form-group">
            <label class="form-label">Logo Kampus Saat Ini</label>
            <div style="display: flex; align-items: center; gap: 16px; padding: 12px; background: var(--bg-hover); border-radius: var(--radius); border: 1px solid var(--border-light);">
                <div style="width: 70px; height: 70px; background: #ffffff; border-radius: 6px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; padding: 4px; overflow: hidden; flex-shrink: 0;">
                    <img src="{{ $internship?->campus_logo_url ?? asset('images/logo-polsub.png') }}"
                         alt="Logo Kampus"
                         style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
                <div>
                    @if($internship?->campus_logo)
                        <div style="font-weight: 600; font-size: 13px; color: var(--text-color);">Logo Kustom Terpasang</div>
                        <div class="form-hint" style="margin-top: 2px;">Logo ini digunakan pada halaman cover dokumen logbook (DOCX dan PDF).</div>
                        <label style="display: inline-flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; cursor: pointer; color: var(--danger-text);">
                            <input type="checkbox" name="remove_campus_logo" value="1">
                            Kembalikan ke logo bawaan Polsub
                        </label>
                    @else
                        <div style="font-weight: 600; font-size: 13px; color: var(--text-color);">Logo Bawaan (Politeknik Negeri Subang)</div>
                        <div class="form-hint" style="margin-top: 2px;">Logo resmi Polsub digunakan secara otomatis. Anda dapat mengunggah logo kampus baru di bawah jika diperlukan.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="campus_logo" class="form-label">Unggah Logo Kampus Baru <span style="font-weight: 400; color: var(--text-muted);">(Opsional)</span></label>
            <input type="file"
                   id="campus_logo"
                   name="campus_logo"
                   class="form-control"
                   accept="image/png,image/jpeg,image/jpg"
                   style="padding: 8px;">
            <div class="form-hint">Mendukung format PNG atau JPG dengan ukuran maksimal 2MB. Format PNG berlatar transparan disarankan untuk hasil cetak terbaik.</div>
            @error('campus_logo')
                <span class="form-error" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <a href="{{ route('mahasiswa.dashboard') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Data PKL & Pembimbing</button>
        </div>
    </form>
</div>
@endsection
