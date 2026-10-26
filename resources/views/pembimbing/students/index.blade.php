@extends('layouts.app')

@section('title', 'Daftar Mahasiswa Bimbingan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Mahasiswa Bimbingan PKL</h1>
        <div class="page-subtitle">Monitoring perkembangan kegiatan dan logbook mahasiswa Praktik Kerja Lapangan.</div>
    </div>
</div>

<div class="card">
    @if($internships->isEmpty())
        <div class="empty-state">
            <h2 class="empty-state-title">Belum Ada Mahasiswa Bimbingan</h2>
            <p class="empty-state-desc">Anda belum memiliki mahasiswa bimbingan yang terdaftar. Administrator kampus akan menugaskan mahasiswa pada periode PKL yang aktif.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>NIM & Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Tempat PKL</th>
                        <th>Aktivitas Terinput</th>
                        <th>Nilai Akhir</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($internships as $intern)
                    <tr>
                        <td>
                            <strong style="font-size: 15px; color: var(--text-primary);">{{ $intern->student->user->name ?? '-' }}</strong>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                NIM: {{ $intern->student->nim ?? '-' }}
                            </div>
                        </td>
                        <td>{{ $intern->student->study_program ?? '-' }}</td>
                        <td>
                            <strong>{{ $intern->company->company_name ?? '-' }}</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                {{ $intern->start_date ? $intern->start_date->format('d/m/Y') : '-' }} s.d. {{ $intern->end_date ? $intern->end_date->format('d/m/Y') : '-' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-draft" style="font-size: 13px;">
                                {{ $intern->activities->count() }} Hari
                            </span>
                        </td>
                        <td>
                            @if($intern->evaluation)
                                <span class="badge badge-approved" style="font-size: 13px;">{{ $intern->evaluation->final_score }}</span>
                            @else
                                <span class="badge badge-draft">Belum Dinilai</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                <a href="{{ route('pembimbing.students.show', $intern) }}" class="btn btn-secondary btn-sm">
                                    Logbook Mahasiswa
                                </a>
                                <a href="{{ route('pembimbing.students.evaluate', $intern) }}" class="btn btn-primary btn-sm">
                                    {{ $intern->evaluation ? 'Edit Nilai' : 'Beri Nilai' }}
                                </a>
                                <a href="{{ route('documents.pdf', $intern) }}" class="btn btn-secondary btn-sm" title="Download PDF Logbook">
                                    PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
