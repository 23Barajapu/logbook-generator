@extends('layouts.app')

@section('title', 'Edit Mahasiswa: ' . ($student->user->name ?? ''))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Data Mahasiswa</h1>
        <div class="page-subtitle">{{ $student->user->name ?? '-' }} &bull; NIM: {{ $student->nim }}</div>
    </div>
    <div>
        <a href="{{ route('admin.students') }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="{{ route('admin.students.update', $student) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $student->user->name) }}" required>
            </div>
            <div class="form-group">
                <label for="nim" class="form-label">NIM <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="nim" name="nim" class="form-control" value="{{ old('nim', $student->nim) }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="email" class="form-label">Email (Untuk Login) <span style="color: var(--danger-text);">*</span></label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $student->user->email) }}" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Ganti Password (Kosongkan jika tidak diubah)</label>
                <input type="password" id="password" name="password" class="form-control" minlength="6" placeholder="Biarkan kosong jika tidak ganti">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="study_program" class="form-label">Program Studi <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="study_program" name="study_program" class="form-control" value="{{ old('study_program', $student->study_program) }}" required>
            </div>
            <div class="form-group">
                <label for="batch_year" class="form-label">Tahun Angkatan</label>
                <input type="number" id="batch_year" name="batch_year" class="form-control" value="{{ old('batch_year', $student->batch_year) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="phone_number" class="form-label">Nomor Telepon / WhatsApp</label>
            <input type="text" id="phone_number" name="phone_number" class="form-control" value="{{ old('phone_number', $student->phone_number) }}">
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
