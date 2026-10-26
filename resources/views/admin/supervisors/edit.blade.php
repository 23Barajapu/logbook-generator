@extends('layouts.app')

@section('title', 'Edit Dosen Pembimbing')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Dosen Pembimbing</h1>
        <div class="page-subtitle">{{ $supervisor->user->name ?? '-' }} &bull; NIP: {{ $supervisor->employee_number }}</div>
    </div>
    <div>
        <a href="{{ route('admin.supervisors') }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="{{ route('admin.supervisors.update', $supervisor) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap & Gelar <span style="color: var(--danger-text);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $supervisor->user->name) }}" required>
            </div>
            <div class="form-group">
                <label for="employee_number" class="form-label">NIP / NIDN <span style="font-weight:400;color:var(--text-muted);">(Opsional / Bisa Diisi Nanti)</span></label>
                <input type="text" id="employee_number" name="employee_number" class="form-control" value="{{ old('employee_number', $supervisor->employee_number) }}" placeholder="Kosongkan jika belum ada NIP">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="email" class="form-label">Email (Untuk Login) <span style="color: var(--danger-text);">*</span></label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $supervisor->user->email) }}" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Ganti Password (Kosongkan jika tidak diubah)</label>
                <input type="password" id="password" name="password" class="form-control" minlength="6" placeholder="Biarkan kosong jika tidak ganti">
            </div>
        </div>

        <div class="form-group">
            <label for="position" class="form-label">Jabatan Fungsional / Posisi</label>
            <input type="text" id="position" name="position" class="form-control" value="{{ old('position', $supervisor->position) }}">
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
