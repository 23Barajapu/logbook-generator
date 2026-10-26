@extends('layouts.app')

@section('title', 'Tambah Perusahaan Mitra')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Perusahaan Mitra Baru</h1>
        <div class="page-subtitle">Daftarkan tempat pelaksanaan Praktik Kerja Lapangan.</div>
    </div>
    <div>
        <a href="{{ route('admin.companies') }}" class="btn btn-secondary">&larr; Kembali</a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="{{ route('admin.companies.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="company_name" class="form-label">Nama Perusahaan / Instansi <span style="color: var(--danger-text);">*</span></label>
            <input type="text" id="company_name" name="company_name" class="form-control" value="{{ old('company_name') }}" required placeholder="Contoh: PT Teknologi Inovasi Mandiri">
        </div>

        <div class="form-group">
            <label for="address" class="form-label">Alamat Lengkap Perusahaan</label>
            <textarea id="address" name="address" class="form-control" placeholder="Jl. Raya No. ..., Kota ...">{{ old('address') }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="field_supervisor" class="form-label">Nama Pembimbing Lapangan</label>
                <input type="text" id="field_supervisor" name="field_supervisor" class="form-control" value="{{ old('field_supervisor') }}" placeholder="Contoh: Budi Santoso, S.Kom.">
            </div>
            <div class="form-group">
                <label for="field_supervisor_position" class="form-label">Jabatan Pembimbing Lapangan</label>
                <input type="text" id="field_supervisor_position" name="field_supervisor_position" class="form-control" value="{{ old('field_supervisor_position') }}" placeholder="Contoh: Senior Software Engineer">
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <button type="submit" class="btn btn-primary">Simpan Perusahaan</button>
        </div>
    </form>
</div>
@endsection
