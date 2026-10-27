@extends('layouts.app')

@section('title', 'Edit Penugasan PKL')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Penugasan PKL</h1>
        <div class="page-subtitle">Mahasiswa: {{ $internship->student->user->name ?? '-' }} ({{ $internship->student->nim }})</div>
    </div>
    <div>
        <a href="{{ route('admin.internships') }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="{{ route('admin.internships.update', $internship) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="student_id" class="form-label">Pilih Mahasiswa <span style="color: var(--danger-text);">*</span></label>
            <select id="student_id" name="student_id" class="form-control" required>
                @foreach($students as $st)
                    <option value="{{ $st->id }}" {{ old('student_id', $internship->student_id) == $st->id ? 'selected' : '' }}>
                        {{ $st->nim }} - {{ $st->user->name ?? '-' }} ({{ $st->study_program }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="company_id" class="form-label">Pilih Perusahaan Mitra <span style="color: var(--danger-text);">*</span></label>
            <select id="company_id" name="company_id" class="form-control" required>
                @foreach($companies as $comp)
                    <option value="{{ $comp->id }}" {{ old('company_id', $internship->company_id) == $comp->id ? 'selected' : '' }}>
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
                    <option value="{{ $spv->id }}" {{ old('supervisor_id', $internship->supervisor_id) == $spv->id ? 'selected' : '' }}>
                        {{ $spv->user->name ?? '-' }} (NIP: {{ $spv->employee_number }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="start_date" class="form-label">Tanggal Mulai <span style="color: var(--danger-text);">*</span></label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date', $internship->start_date ? $internship->start_date->format('Y-m-d') : '') }}" required>
            </div>
            <div class="form-group">
                <label for="end_date" class="form-label">Tanggal Selesai <span style="color: var(--danger-text);">*</span></label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date', $internship->end_date ? $internship->end_date->format('Y-m-d') : '') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label for="status" class="form-label">Status Penugasan <span style="color: var(--danger-text);">*</span></label>
            <select id="status" name="status" class="form-control" required>
                <option value="aktif" {{ old('status', $internship->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="selesai" {{ old('status', $internship->status) === 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="nonaktif" {{ old('status', $internship->status) === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Logo Kampus Saat Ini</label>
            <div style="display: flex; align-items: center; gap: 16px; padding: 12px; background: var(--bg-hover); border-radius: var(--r-md); border: 1px solid var(--border-light);">
                <div style="width: 60px; height: 60px; background: #ffffff; border-radius: 6px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; padding: 4px; overflow: hidden; flex-shrink: 0;">
                    <img src="{{ $internship->campus_logo_url }}" alt="Logo Kampus" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
                <div>
                    @if($internship->campus_logo)
                        <div style="font-weight: 600; font-size: 13px; color: var(--text-primary);">Logo Kustom Terpasang</div>
                        <label style="display: inline-flex; align-items: center; gap: 8px; margin-top: 6px; font-size: 12px; cursor: pointer; color: var(--danger-text);">
                            <input type="checkbox" name="remove_campus_logo" value="1">
                            Kembalikan ke logo bawaan Polsub
                        </label>
                    @else
                        <div style="font-weight: 600; font-size: 13px; color: var(--text-primary);">Logo Bawaan (Politeknik Negeri Subang)</div>
                        <div class="form-hint" style="margin-top: 2px;">Dokumen logbook saat ini menggunakan logo default Polsub.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="campus_logo" class="form-label">Ganti Logo Kampus <span style="font-weight: 400; color: var(--text-muted);">(Opsional)</span></label>
            <input type="file" id="campus_logo" name="campus_logo" class="form-control" accept="image/png,image/jpeg,image/jpg" style="padding: 8px;">
            <div class="form-hint">Format PNG atau JPG maksimal 2MB.</div>
            @error('campus_logo')
                <span class="form-error" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <button type="submit" class="btn btn-primary">Simpan Perubahan Penugasan</button>
        </div>
    </form>
</div>
@endsection
