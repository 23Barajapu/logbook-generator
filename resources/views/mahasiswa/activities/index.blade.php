@extends('layouts.app')

@section('title', 'Daftar Aktivitas Harian')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Catatan Aktivitas Harian (Logbook)</h1>
        <div class="page-subtitle">Kelola seluruh riwayat aktivitas harian Anda selama Praktik Kerja Lapangan.</div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        @if($internship)
            <a href="{{ route('mahasiswa.activities.import') }}" class="btn btn-secondary" id="btn-import-docx" title="Import otomatis aktivitas harian dari file Word (.docx) atau PDF (.pdf)">
                Import DOCX / PDF
            </a>
            <button type="button" class="btn btn-secondary" onclick="openExportModal()" id="btn-open-export-modal" title="Unduh Logbook mingguan atau keseluruhan">
                Unduh Logbook (.docx / .pdf)
            </button>
            <a href="{{ route('documents.docx', $internship) }}" class="btn btn-secondary" id="btn-download-docx" style="display: none;">
                Download DOCX
            </a>
            <a href="{{ route('documents.pdf', $internship) }}" class="btn btn-secondary" id="btn-download-pdf" style="display: none;">
                Download PDF
            </a>
        @endif
        <a href="{{ route('mahasiswa.activities.create') }}" class="btn btn-primary" id="btn-tambah-aktivitas">
            + Tambah Aktivitas Baru
        </a>
    </div>
</div>

<!-- Filter & Pencarian -->
<div class="card" style="margin-bottom: 20px; padding: 16px;">
    <form action="{{ route('mahasiswa.activities') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label for="search" class="form-label">Cari Aktivitas / Tools / Bidang</label>
            <input type="text" id="search" name="search" class="form-control" value="{{ $currentSearch }}" placeholder="Ketik kata kunci...">
        </div>
        <div style="min-width: 180px;">
            <label for="status" class="form-label">Filter Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="Draft" {{ $currentStatus === 'Draft' ? 'selected' : '' }}>Draft</option>
                <option value="Menunggu Approval" {{ $currentStatus === 'Menunggu Approval' ? 'selected' : '' }}>Menunggu Approval</option>
                <option value="Disetujui" {{ $currentStatus === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                <option value="Revisi" {{ $currentStatus === 'Revisi' ? 'selected' : '' }}>Revisi</option>
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if($currentStatus || $currentSearch)
                <a href="{{ route('mahasiswa.activities') }}" class="btn btn-secondary">Reset</a>
            @endif
        </div>
    </form>
</div>

<!-- Tabel Aktivitas -->
<div class="card">
    @if($activities->isEmpty())
        <div class="empty-state">
            <h2 class="empty-state-title">Tidak Ada Aktivitas Ditemukan</h2>
            <p class="empty-state-desc">Belum ada data aktivitas harian yang sesuai dengan kriteria filter saat ini.</p>
            <a href="{{ route('mahasiswa.activities.create') }}" class="btn btn-primary">+ Catat Aktivitas</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 80px; text-align: center;">Hari Ke</th>
                        <th style="width: 120px;">Tanggal</th>
                        <th style="width: 160px;">Divisi/Bidang</th>
                        <th>Uraian Kegiatan & Tools</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 180px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $act)
                    <tr>
                        <td style="font-weight: 700; text-align: center; font-size: 16px;">
                            {{ $act->day_number }}
                        </td>
                        <td>{{ $act->activity_date ? $act->activity_date->format('d/m/Y') : '-' }}</td>
                        <td style="font-weight: 600;">{{ $act->work_field }}</td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-primary);">{{ Str::limit($act->activities, 120) }}</div>
                            @if($act->tools_used)
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    <strong>Tools:</strong> {{ $act->tools_used }}
                                </div>
                            @endif
                            @if($act->attachments->count() > 0)
                                <div style="font-size: 11px; color: var(--accent-blue); margin-top: 4px;">
                                    {{ $act->attachments->count() }} Lampiran Dokumentasi
                                </div>
                            @endif
                            @if($act->latestApproval && $act->latestApproval->notes)
                                <div style="font-size: 12px; color: var(--warning-text); background: var(--warning-dim); padding: 4px 8px; border-radius: var(--radius-sm); margin-top: 6px;">
                                    <strong>Catatan Pembimbing:</strong> {{ $act->latestApproval->notes }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($act->status === 'Disetujui')
                                <span class="badge badge-approved">Disetujui</span>
                            @elseif($act->status === 'Menunggu Approval')
                                <span class="badge badge-pending">Menunggu Approval</span>
                            @elseif($act->status === 'Revisi')
                                <span class="badge badge-revision">Perlu Revisi</span>
                            @else
                                <span class="badge badge-draft">Draft</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                                <a href="{{ route('mahasiswa.activities.show', $act) }}" class="btn btn-secondary btn-sm">
                                    Detail
                                </a>

                                @if(in_array($act->status, ['Draft', 'Revisi']))
                                    <a href="{{ route('mahasiswa.activities.edit', $act) }}" class="btn btn-secondary btn-sm">
                                        Edit
                                    </a>
                                    <form action="{{ route('mahasiswa.activities.submit', $act) }}" method="POST" style="display:inline;" data-confirm="Ajukan aktivitas hari ke-{{ $act->day_number }} ini ke pembimbing untuk proses approval?" data-confirm-title="Ajukan Aktivitas" data-confirm-btn="Ya, Ajukan" data-confirm-variant="primary">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            Ajukan
                                        </button>
                                    </form>
                                @endif

                                @if($act->status === 'Draft')
                                    <form action="{{ route('mahasiswa.activities.destroy', $act) }}" method="POST" style="display:inline;" data-confirm="Hapus aktivitas draft hari ke-{{ $act->day_number }} ini? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus Aktivitas Draft" data-confirm-btn="Ya, Hapus" data-confirm-variant="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $activities->links() }}
        </div>
    @endif
</div>

@if($internship)
    @include('documents.export-modal')
@endif
@endsection
