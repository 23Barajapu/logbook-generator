@extends('layouts.app')

@section('title', 'Master Data Perusahaan Mitra')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Master Data Perusahaan Mitra PKL</h1>
        <div class="page-subtitle">Daftar instansi, perusahaan, dan pembimbing lapangan mitra kerja sama PKL.</div>
    </div>
    <div>
        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">
            + Tambah Perusahaan
        </a>
    </div>
</div>

<div class="card">
    @if($companies->isEmpty())
        <div class="empty-state">
            <h2 class="empty-state-title">Belum Ada Data Perusahaan Mitra</h2>
            <p class="empty-state-desc">Tambahkan perusahaan mitra industri untuk tempat pelaksanaan PKL mahasiswa.</p>
            <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">+ Tambah Perusahaan</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Perusahaan</th>
                        <th>Alamat</th>
                        <th>Pembimbing Lapangan</th>
                        <th>Jabatan Pembimbing</th>
                        <th>Mahasiswa Magang</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $comp)
                    <tr>
                        <td>
                            <strong>{{ $comp->company_name }}</strong>
                        </td>
                        <td style="max-width: 250px;">{{ $comp->address ?? '-' }}</td>
                        <td>{{ $comp->field_supervisor ?? '-' }}</td>
                        <td>{{ $comp->field_supervisor_position ?? '-' }}</td>
                        <td>
                            <span class="badge badge-draft">
                                {{ $comp->internships_count }} Mahasiswa
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                <a href="{{ route('admin.companies.edit', $comp) }}" class="btn btn-secondary btn-sm">
                                    Edit
                                </a>
                                <form action="{{ route('admin.companies.destroy', $comp) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus perusahaan mitra ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $companies->links() }}
        </div>
    @endif
</div>
@endsection
