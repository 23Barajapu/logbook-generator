@extends('layouts.app')

@section('title', 'Buat Penugasan PKL')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Buat Penugasan PKL Baru</h1>
        <div class="page-subtitle">Pilih mahasiswa, perusahaan mitra, dan dosen pembimbing.</div>
    </div>
    <div>
        <a href="{{ route('admin.internships') }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="{{ route('admin.internships.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label for="student_id" class="form-label">Pilih Mahasiswa <span style="color: var(--danger-text);">*</span></label>
            <select id="student_id" name="student_id" class="form-control" required>
                <option value="">-- Pilih Mahasiswa --</option>
                @foreach($students as $st)
                    <option value="{{ $st->id }}" {{ old('student_id') == $st->id ? 'selected' : '' }}>
                        {{ $st->nim }} - {{ $st->user->name ?? '-' }} ({{ $st->study_program }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="company_id" class="form-label">Pilih Perusahaan Mitra <span style="color: var(--danger-text);">*</span></label>
            <select id="company_id" name="company_id" class="form-control" required>
                <option value="">-- Pilih Perusahaan Mitra --</option>
                @foreach($companies as $comp)
                    <option value="{{ $comp->id }}" {{ old('company_id') == $comp->id ? 'selected' : '' }}>
                        {{ $comp->company_name }} (Mentor: {{ $comp->field_supervisor ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="supervisor_id" class="form-label">Pilih Dosen Pembimbing Akademik</label>
            <select id="supervisor_id" name="supervisor_id" class="form-control">
                <option value="">-- Belum Ditentukan / Pilih Nanti --</option>
                @foreach($supervisors as $spv)
                    <option value="{{ $spv->id }}" {{ old('supervisor_id') == $spv->id ? 'selected' : '' }}>
                        {{ $spv->user->name ?? '-' }} (NIP: {{ $spv->employee_number }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="start_date" class="form-label">Tanggal Mulai <span style="color: var(--danger-text);">*</span></label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}" required>
            </div>
            <div class="form-group">
                <label for="end_date" class="form-label">Tanggal Selesai <span style="color: var(--danger-text);">*</span></label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date', date('Y-m-d', strtotime('+3 months'))) }}" required>
            </div>
        </div>

        <div class="form-group">
            <label for="status" class="form-label">Status Penugasan <span style="color: var(--danger-text);">*</span></label>
            <select id="status" name="status" class="form-control" required>
                <option value="aktif" {{ old('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="selesai" {{ old('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="nonaktif" {{ old('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <div class="form-group">
            <label for="campus_logo" class="form-label">Logo Kampus <span style="font-weight: 400; color: var(--text-muted);">(Opsional, default: Logo Polsub)</span></label>
            <input type="file" id="campus_logo" name="campus_logo" class="form-control" accept="image/png,image/jpeg,image/jpg" style="padding: 8px;">
            <div class="form-hint">Format PNG atau JPG maksimal 2MB. Jika dikosongkan, dokumen logbook akan menggunakan logo default Polsub.</div>
            @error('campus_logo')
                <span class="form-error" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <button type="submit" class="btn btn-primary">Simpan Penugasan PKL</button>
        </div>
    </form>
</div>
@endsection
