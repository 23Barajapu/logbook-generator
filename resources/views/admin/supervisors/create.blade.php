@extends('layouts.app')

@section('title', 'Tambah Dosen Pembimbing')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Dosen Pembimbing</h1>
        <div class="page-subtitle">Registrasi akun dosen pembimbing akademik PKL.</div>
    </div>
    <div>
        <a href="{{ route('admin.supervisors') }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="{{ route('admin.supervisors.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap & Gelar <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: Dr. Budi Santoso, M.Kom.">
            </div>
            <div class="form-group">
                <label for="employee_number" class="form-label">NIP / NIDN <span style="font-weight:400;color:var(--text-muted);">(Opsional / Bisa Diisi Nanti)</span></label>
                <input type="text" id="employee_number" name="employee_number" class="form-control" value="{{ old('employee_number') }}" placeholder="Kosongkan jika belum ada NIP">
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

        <div class="form-group">
            <label for="position" class="form-label">Jabatan Fungsional / Posisi</label>
            <input type="text" id="position" name="position" class="form-control" value="{{ old('position') }}" placeholder="Contoh: Lektor Kepala / Dosen Pembimbing">
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <button type="submit" class="btn btn-primary">Simpan Pembimbing</button>
        </div>
    </form>
</div>
@endsection
