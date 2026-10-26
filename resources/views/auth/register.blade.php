@extends('layouts.app')

@section('title', 'Pendaftaran Mahasiswa Baru')

@section('content')
<div class="auth-page" style="padding: 40px 16px;">
    <div class="auth-card auth-card-wide">
        <div class="auth-logo">
            <div class="auth-logo-mark" aria-hidden="true">D</div>
            <h1 class="auth-title">Pendaftaran Mahasiswa</h1>
            <p class="auth-subtitle">Buat akun untuk mencatat logbook dan menyusun laporan PKL</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Mohon periksa formulir pendaftaran:</strong>
                <ul style="margin: 6px 0 0 16px; padding: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST" novalidate>
            @csrf

            <div class="auth-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                1. Data Akun & Mahasiswa
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Nama Lengkap <span style="color:var(--danger-text)">*</span></label>
                    <input type="text" id="name" name="name"
                           class="form-control"
                           value="{{ old('name') }}"
                           required autofocus
                           placeholder="Contoh: Budi Santoso">
                    @error('name')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="nim" class="form-label">Nomor Induk Mahasiswa (NIM) <span style="color:var(--danger-text)">*</span></label>
                    <input type="text" id="nim" name="nim"
                           class="form-control"
                           value="{{ old('nim') }}"
                           required
                           placeholder="Contoh: 210102001">
                    @error('nim')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="study_program" class="form-label">Program Studi <span style="color:var(--danger-text)">*</span></label>
                    <select id="study_program" name="study_program" class="form-control" required>
                        <option value="">-- Pilih Program Studi --</option>
                        <option value="D3 Sistem Informasi" {{ old('study_program') === 'D3 Sistem Informasi' ? 'selected' : '' }}>D3 Sistem Informasi</option>
                        <option value="D4 Teknologi Rekayasa Perangkat Lunak" {{ old('study_program') === 'D4 Teknologi Rekayasa Perangkat Lunak' ? 'selected' : '' }}>D4 Teknologi Rekayasa Perangkat Lunak</option>
                        <option value="D4 Manajemen Informatika" {{ old('study_program') === 'D4 Manajemen Informatika' ? 'selected' : '' }}>D4 Manajemen Informatika</option>
                        <option value="D3 Teknik Informatika" {{ old('study_program') === 'D3 Teknik Informatika' ? 'selected' : '' }}>D3 Teknik Informatika</option>
                        <option value="Teknik Komputer" {{ old('study_program') === 'Teknik Komputer' ? 'selected' : '' }}>Teknik Komputer</option>
                    </select>
                    @error('study_program')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row" style="gap: 12px;">
                    <div class="form-group">
                        <label for="batch_year" class="form-label">Angkatan <span style="color:var(--danger-text)">*</span></label>
                        <input type="number" id="batch_year" name="batch_year"
                               class="form-control"
                               value="{{ old('batch_year', date('Y')) }}"
                               min="2000" max="2099" required>
                        @error('batch_year')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone_number" class="form-label">No. WhatsApp / HP</label>
                        <input type="tel" id="phone_number" name="phone_number"
                               class="form-control"
                               value="{{ old('phone_number') }}"
                               placeholder="08123456789">
                        @error('phone_number')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Alamat Email <span style="color:var(--danger-text)">*</span></label>
                <input type="email" id="email" name="email"
                       class="form-control"
                       value="{{ old('email') }}"
                       required autocomplete="username"
                       placeholder="email.aktif@mahasiswa.ac.id">
                <div class="form-hint">Email ini digunakan untuk masuk ke akun logbook Anda.</div>
                @error('email')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password <span style="color:var(--danger-text)">*</span></label>
                    <input type="password" id="password" name="password"
                           class="form-control"
                           required autocomplete="new-password"
                           placeholder="Minimal 8 karakter">
                    @error('password')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Ulangi Password <span style="color:var(--danger-text)">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           required autocomplete="new-password"
                           placeholder="Ketik ulang password">
                </div>
            </div>

            <div class="auth-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                2. Data Lokasi & Waktu PKL / Magang
            </div>

            <div class="form-group">
                <label for="company_name" class="form-label">Nama Instansi / Perusahaan Tempat PKL</label>
                <input type="text" id="company_name" name="company_name"
                       class="form-control"
                       value="{{ old('company_name') }}"
                       list="company-suggestions"
                       placeholder="Contoh: PT. Inovasi Digital Nusantara / Dinas Kominfo">
                <datalist id="company-suggestions">
                    @foreach($companies as $company)
                        <option value="{{ $company->company_name }}"></option>
                    @endforeach
                </datalist>
                <div class="form-hint">Pilih dari saran atau ketik nama instansi tempat Anda melaksanakan PKL.</div>
                @error('company_name')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="company_address" class="form-label">Alamat Instansi / Perusahaan</label>
                <textarea id="company_address" name="company_address"
                          class="form-control"
                          rows="2"
                          placeholder="Contoh: Jl. Diponegoro No. 45, Bandung">{{ old('company_address') }}</textarea>
                @error('company_address')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="field_supervisor" class="form-label">Nama Pembimbing Lapangan</label>
                    <input type="text" id="field_supervisor" name="field_supervisor"
                           class="form-control"
                           value="{{ old('field_supervisor') }}"
                           placeholder="Contoh: Rian Hidayat, S.T.">
                    @error('field_supervisor')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="field_supervisor_position" class="form-label">Jabatan Pembimbing Lapangan</label>
                    <input type="text" id="field_supervisor_position" name="field_supervisor_position"
                           class="form-control"
                           value="{{ old('field_supervisor_position') }}"
                           placeholder="Contoh: Senior Software Engineer / Ka. Seksi IT">
                    @error('field_supervisor_position')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date" class="form-label">Tanggal Mulai PKL</label>
                    <input type="date" id="start_date" name="start_date"
                           class="form-control"
                           value="{{ old('start_date') }}">
                    @error('start_date')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="end_date" class="form-label">Tanggal Selesai PKL</label>
                    <input type="date" id="end_date" name="end_date"
                           class="form-control"
                           value="{{ old('end_date') }}">
                    @error('end_date')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="auth-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                3. Data Dosen Pembimbing Akademik
            </div>

            <div class="form-group">
                <label for="supervisor_id" class="form-label">Pilih Dosen Pembimbing Terdaftar (Jika Sudah Ada)</label>
                <select id="supervisor_id" name="supervisor_id" class="form-control">
                    <option value="">-- Pilih Dosen Pembimbing Terdaftar --</option>
                    @foreach($supervisors as $spv)
                        <option value="{{ $spv->id }}" {{ old('supervisor_id') == $spv->id ? 'selected' : '' }}>
                            {{ $spv->user->name ?? '-' }}{{ !empty($spv->employee_number) ? ' (NIP: ' . $spv->employee_number . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-hint">Jika dosen pembimbing Anda belum ada di daftar di atas, isi formulir di bawah ini:</div>
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

            <button type="submit" id="btn-register" class="btn btn-primary btn-lg btn-block" style="margin-top: 10px;">
                Daftar & Masuk ke Dashboard
            </button>
        </form>

        <div class="auth-footer-link">
            Sudah memiliki akun? <a href="{{ route('login') }}">Masuk di sini</a>
        </div>
    </div>
</div>
@endsection
