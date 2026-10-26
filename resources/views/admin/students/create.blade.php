@extends('layouts.app')

@section('title', 'Tambah Mahasiswa')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Mahasiswa Baru</h1>
        <div class="page-subtitle">Pendaftaran akun pengguna dan identitas mahasiswa PKL.</div>
    </div>
    <div>
        <a href="{{ route('admin.students') }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="{{ route('admin.students.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label for="nim" class="form-label">NIM <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="nim" name="nim" class="form-control" value="{{ old('nim') }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="email" class="form-label">Email (Untuk Login) <span style="color: var(--danger-text);">*</span></label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password <span style="color: var(--danger-text);">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="study_program" class="form-label">Program Studi <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="study_program" name="study_program" class="form-control" value="{{ old('study_program') }}" required placeholder="Contoh: S1 Teknik Informatika">
            </div>
            <div class="form-group">
                <label for="batch_year" class="form-label">Tahun Angkatan</label>
                <input type="number" id="batch_year" name="batch_year" class="form-control" value="{{ old('batch_year', date('Y') - 2) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="phone_number" class="form-label">Nomor Telepon / WhatsApp</label>
            <input type="text" id="phone_number" name="phone_number" class="form-control" value="{{ old('phone_number') }}" placeholder="08xxxxxxxxxx">
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <button type="submit" class="btn btn-primary">Simpan Mahasiswa</button>
        </div>
    </form>
</div>
@endsection
