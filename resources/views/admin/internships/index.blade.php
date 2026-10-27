@extends('layouts.app')

@section('title', 'Penugasan Praktik Kerja Lapangan (PKL)')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Penugasan PKL Mahasiswa</h1>
        <div class="page-subtitle">Hubungkan mahasiswa dengan perusahaan mitra dan dosen pembimbing akademik.</div>
    </div>
    <div>
        <a href="{{ route('admin.internships.create') }}" class="btn btn-primary">
            + Buat Penugasan Baru
        </a>
    </div>
</div>

<div class="card">
    @if($internships->isEmpty())
        <div class="empty-state">
            <h2 class="empty-state-title">Belum Ada Penugasan PKL</h2>
            <p class="empty-state-desc">Buat penugasan baru untuk menghubungkan mahasiswa ke tempat magang dan dosen pembimbing.</p>
            <a href="{{ route('admin.internships.create') }}" class="btn btn-primary">+ Buat Penugasan</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Perusahaan Mitra</th>
                        <th>Dosen Pembimbing</th>
                        <th>Periode Magang</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($internships as $intern)
                    <tr>
                        <td>
                            <strong style="display: block;">{{ $intern->student->user->name ?? '-' }}</strong>
                            <span style="font-size: 12px; color: var(--text-muted);">
                                NIM: {{ $intern->student->nim ?? '-' }} &bull; {{ $intern->student->study_program ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $intern->company->company_name ?? '-' }}</strong>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                Mentor: {{ $intern->company->field_supervisor ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <strong>{{ $intern->supervisor->user->name ?? 'Belum Ditentukan' }}</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                NIP: {{ $intern->supervisor->employee_number ?? '-' }}
                            </div>
                        </td>
                        <td>
                            {{ $intern->start_date ? $intern->start_date->format('d/m/Y') : '-' }} &mdash; {{ $intern->end_date ? $intern->end_date->format('d/m/Y') : '-' }}
                        </td>
                        <td>
                            <span class="badge {{ $intern->status === 'aktif' ? 'badge-approved' : ($intern->status === 'selesai' ? 'badge-draft' : 'badge-revision') }}">
                                {{ strtoupper($intern->status) }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                <a href="{{ route('documents.summary', $intern) }}" class="btn btn-secondary btn-sm" title="Lihat Rekap">
                                    Rekap
                                </a>
                                <a href="{{ route('admin.internships.edit', $intern) }}" class="btn btn-secondary btn-sm">
                                    Edit
                                </a>
                                <form action="{{ route('admin.internships.destroy', $intern) }}" method="POST" style="display:inline;" data-confirm="Hapus penugasan PKL untuk {{ $intern->student->user->name ?? 'mahasiswa ini' }} beserta seluruh data aktivitas logbook-nya? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus Penugasan PKL" data-confirm-btn="Ya, Hapus" data-confirm-variant="danger">
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
            {{ $internships->links() }}
        </div>
    @endif
</div>
@endsection
