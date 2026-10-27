@extends('layouts.app')

@section('title', 'Master Data Mahasiswa')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Master Data Mahasiswa</h1>
        <div class="page-subtitle">Kelola akun dan profil mahasiswa peserta Praktik Kerja Lapangan.</div>
    </div>
    <div>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
            + Tambah Mahasiswa
        </a>
    </div>
</div>

<div class="card">
    @if($students->isEmpty())
        <div class="empty-state">
            <h2 class="empty-state-title">Belum Ada Data Mahasiswa</h2>
            <p class="empty-state-desc">Tambahkan mahasiswa untuk memulai proses penugasan PKL.</p>
            <a href="{{ route('admin.students.create') }}" class="btn btn-primary">+ Tambah Mahasiswa</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Angkatan</th>
                        <th>Email & Kontak</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $st)
                    <tr>
                        <td style="font-weight: 700;">{{ $st->nim }}</td>
                        <td>
                            <strong style="display: block;">{{ $st->user->name ?? '-' }}</strong>
                        </td>
                        <td>{{ $st->study_program }}</td>
                        <td>{{ $st->batch_year ?? '-' }}</td>
                        <td>
                            <div>{{ $st->user->email ?? '-' }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">{{ $st->phone_number ?? '-' }}</div>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                <a href="{{ route('admin.students.edit', $st) }}" class="btn btn-secondary btn-sm">
                                    Edit
                                </a>
                                <form action="{{ route('admin.students.destroy', $st) }}" method="POST" style="display:inline;" data-confirm="Hapus data mahasiswa {{ $st->user->name }} beserta akun loginnya? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus Mahasiswa" data-confirm-btn="Ya, Hapus" data-confirm-variant="danger">
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
            {{ $students->links() }}
        </div>
    @endif
</div>
@endsection
