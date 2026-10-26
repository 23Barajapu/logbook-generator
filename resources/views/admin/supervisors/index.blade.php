@extends('layouts.app')

@section('title', 'Master Data Dosen Pembimbing')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Master Data Dosen Pembimbing</h1>
        <div class="page-subtitle">Kelola akun dan profil dosen pembimbing akademik PKL.</div>
    </div>
    <div>
        <a href="{{ route('admin.supervisors.create') }}" class="btn btn-primary">
            + Tambah Pembimbing
        </a>
    </div>
</div>

<div class="card">
    @if($supervisors->isEmpty())
        <div class="empty-state">
            <h2 class="empty-state-title">Belum Ada Data Pembimbing</h2>
            <p class="empty-state-desc">Tambahkan dosen pembimbing akademik untuk membimbing mahasiswa PKL.</p>
            <a href="{{ route('admin.supervisors.create') }}" class="btn btn-primary">+ Tambah Pembimbing</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>NIP / NIDN</th>
                        <th>Nama Dosen Pembimbing</th>
                        <th>Jabatan / Fungsional</th>
                        <th>Email Login</th>
                        <th>Mahasiswa Bimbingan</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supervisors as $spv)
                    <tr>
                        <td style="font-weight: 700;">{{ $spv->employee_number ?? '—' }}</td>
                        <td>
                            <strong>{{ $spv->user->name ?? '-' }}</strong>
                        </td>
                        <td>{{ $spv->position ?? 'Dosen Pembimbing' }}</td>
                        <td>{{ $spv->user->email ?? '-' }}</td>
                        <td>
                            <span class="badge badge-draft">
                                {{ $spv->internships->count() }} Mahasiswa
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                <a href="{{ route('admin.supervisors.edit', $spv) }}" class="btn btn-secondary btn-sm">
                                    Edit
                                </a>
                                <form action="{{ route('admin.supervisors.destroy', $spv) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus data dosen pembimbing ini beserta akunnya?');">
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
            {{ $supervisors->links() }}
        </div>
    @endif
</div>
@endsection
