<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Aktivitas Harian PKL - {{ $studentNim }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 18mm 15mm 15mm 20mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .text-center {
            text-align: center;
        }
        .text-justify {
            text-align: justify;
        }
        .bold {
            font-weight: bold;
        }

        /* Cover */
        .cover-container {
            text-align: center;
            padding-top: 10px;
        }
        .cover-title-1 {
            font-size: 14pt;
            margin: 0;
            line-height: 1.25;
        }
        .cover-title-2 {
            font-size: 14pt;
            margin: 0;
            line-height: 1.25;
        }
        .cover-title-3 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 20px 0;
            line-height: 1.25;
        }
        .cover-logo-box {
            margin: 15px auto 20px auto;
            text-align: center;
            height: 110px;
        }
        .cover-logo-box img {
            max-height: 110px;
            max-width: 150px;
        }
        .ident-table {
            margin: 0 auto;
            border-collapse: collapse;
            font-size: 12pt;
            font-weight: bold;
        }
        .ident-table td {
            padding: 2px 6px;
            vertical-align: top;
        }
        .cover-bottom {
            margin-top: 35px;
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.3;
        }

        /* Lembar Pengesahan */
        .pengesahan-container {
            text-align: center;
            padding-top: 10px;
        }
        .pengesahan-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        .pengesahan-sub {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
        }
        .pengesahan-company {
            font-size: 9.5pt;
            font-weight: bold;
            margin: 0 0 16px 0;
        }
        .pengesahan-student {
            font-size: 12pt;
            font-weight: bold;
            margin: 0 0 16px 0;
            line-height: 1.3;
        }
        .pengesahan-statement {
            font-size: 12pt;
            font-weight: bold;
            margin: 0 0 18px 0;
            line-height: 1.3;
        }
        .pengesahan-statement span {
            font-size: 9.5pt;
        }
        .pengesahan-sig-box {
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.3;
        }
        .sig-space {
            margin: 28px auto 26px auto;
            font-size: 12pt;
            font-weight: bold;
        }

        /* Laporan Aktivitas Harian */
        .logbook-header {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 12px;
        }
        .day-block {
            margin-bottom: 16px;
        }
        .day-title {
            font-size: 11pt;
            margin-bottom: 4px;
            color: #000000;
        }
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .activity-table th, .activity-table td {
            border: 1px solid #000000;
            padding: 5px 8px;
            vertical-align: top;
        }
        .activity-table th {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            background-color: #ffffff;
            padding: 6px 8px;
        }
        .activity-text p {
            margin: 0 0 4px 0;
            line-height: 1.3;
        }
        .activity-evidence {
            margin-top: 6px;
            text-align: left;
        }
        /* 1 gambar bukti */
        .activity-evidence.count-1 img {
            max-width: 95%;
            max-height: 220px;
            display: block;
            margin: 4px auto;
        }
        /* 2 gambar bukti bersebelahan */
        .activity-evidence.count-2 img {
            max-width: 48%;
            max-height: 170px;
            display: inline-block;
            vertical-align: top;
            margin: 2px 1%;
        }
        /* 3 s.d. 4 gambar bukti grid 2 kolom */
        .activity-evidence.count-medium img {
            max-width: 48%;
            max-height: 140px;
            display: inline-block;
            vertical-align: top;
            margin: 2px 1%;
        }
        /* 5 gambar bukti atau lebih (grid multi-kolom proporsional) */
        .activity-evidence.count-many img {
            max-width: 23.5%;
            max-height: 115px;
            display: inline-block;
            vertical-align: top;
            margin: 2px 0.4%;
        }
        .tools-box div {
            line-height: 1.3;
        }
        .tools-label {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 2px;
            margin-bottom: 1px;
        }
        .tools-val {
            font-size: 10pt;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

    <!-- 1. HALAMAN COVER (LANDSCAPE) -->
    <div class="cover-container">
        <div class="cover-title-1">BUKU AKTIVITAS HARIAN</div>
        <div class="cover-title-2">PRAKTIK KERJA LAPANGAN</div>
        <div class="cover-title-3">TA. {{ $academicYear }}</div>

        <div class="cover-logo-box">
            @if(!empty($campusLogoPath) && file_exists($campusLogoPath))
                <img src="{{ $campusLogoPath }}" alt="Logo Kampus">
            @elseif(!empty($campusLogoBase64))
                <img src="{{ $campusLogoBase64 }}" alt="Logo Kampus">
            @endif
        </div>

        <table class="ident-table">
            <tr>
                <td style="text-align: left; width: 130px;">Nama</td>
                <td style="text-align: center; width: 20px;">:</td>
                <td style="text-align: left; width: 300px;">{{ $studentName }}</td>
            </tr>
            <tr>
                <td style="text-align: left;">NIM</td>
                <td style="text-align: center;">:</td>
                <td style="text-align: left;">{{ $studentNim }}</td>
            </tr>
            <tr>
                <td style="text-align: left;">Tempat PKL</td>
                <td style="text-align: center;">:</td>
                <td style="text-align: left;">{{ $companyName }}</td>
            </tr>
        </table>

        <div class="cover-bottom">
            <div>{{ $studyProgram }}</div>
            <div>POLITEKNIK NEGERI SUBANG</div>
            <div>{{ $batchYear }}</div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- 2. HALAMAN LEMBAR PENGESAHAN (LANDSCAPE) -->
    <div class="pengesahan-container">
        <div class="pengesahan-title">LEMBAR PENGESAHAN</div>
        <div class="pengesahan-sub">BUKU AKTIVITAS HARIAN</div>
        <div class="pengesahan-sub">DI</div>
        <div class="pengesahan-company">{{ $companyName }}</div>

        <div class="pengesahan-student">
            <div>Oleh :</div>
            <div>{{ $studentName }}</div>
            <div>NIM {{ $studentNim }}</div>
        </div>

        <div class="pengesahan-statement">
            <div>Disetujui dan disahkan sebagai</div>
            <span>Buku Aktivitas Harian</span>
        </div>

        <div class="pengesahan-sig-box">
            <div>{{ $city }}, {{ $approvalDateText }}</div>
            <div>{{ $fieldSupervisorPos }},</div>
            <div class="sig-space">Ttd + Stempel</div>
            <div>{{ $fieldSupervisorName }}</div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- 3. HALAMAN LAPORAN AKTIVITAS HARIAN (LANDSCAPE) -->
    <div class="logbook-header">LAPORAN AKTIVITAS HARIAN</div>

    @forelse($activities as $act)
    <div class="day-block">
        <div class="day-title">Hari Ke-{{ $act->day_number }}, Tanggal {{ $act->activity_date ? $act->activity_date->translatedFormat('j F Y') : '-' }}</div>
        <table class="activity-table">
            <thead>
                <tr>
                    <th style="width: 9.5%;">Bidang Pekerjaan</th>
                    <th style="width: 68.5%;">Kegiatan &amp; Bukti</th>
                    <th style="width: 22%;">Tools yang digunakan , Masalah dan Solusi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <!-- Kolom 1: Bidang Pekerjaan -->
                    <td class="text-center" style="font-size: 9pt;">
                        {{ stripos($act->work_field, 'Quality Control') !== false ? 'QC' : ($act->work_field ?: '-') }}
                    </td>

                    <!-- Kolom 2: Kegiatan & Bukti -->
                    <td style="font-size: 9pt;">
                        <div class="activity-text">
                            @php
                                $lines = preg_split('/\r\n|\r|\n/', trim($act->activities ?? ''));
                            @endphp
                            @foreach($lines as $line)
                                @if(trim($line) !== '')
                                    <p>{{ preg_replace('/^\d+[\.\)]\s*/', '', trim($line)) }}</p>
                                @endif
                            @endforeach
                        </div>

                        @if($act->attachments && $act->attachments->isNotEmpty())
                            @php
                                $imgCount = $act->attachments->filter(fn($a) => $a->isImage())->count();
                                $countClass = $imgCount <= 1 ? 'count-1' : ($imgCount == 2 ? 'count-2' : ($imgCount <= 4 ? 'count-medium' : 'count-many'));
                            @endphp
                            <div class="activity-evidence {{ $countClass }}">
                                @foreach($act->attachments as $att)
                                    @if(!empty($att->pdf_image_path))
                                        <img src="{{ $att->pdf_image_path }}" alt="Bukti Kegiatan">
                                    @elseif(!empty($att->base64_src))
                                        <img src="{{ $att->base64_src }}" alt="Bukti Kegiatan">
                                    @elseif(!$att->isImage())
                                        <p style="font-size: 8pt; font-style: italic; margin-top: 4px;">[Lampiran Dokumen: {{ $att->file_name }}]</p>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </td>

                    <!-- Kolom 3: Tools, Masalah dan Solusi -->
                    <td class="tools-box">
                        <div class="tools-label">Tools:</div>
                        <div class="tools-val">{{ !empty($act->tools_used) ? $act->tools_used : 'Tidak ada' }}</div>

                        <div class="tools-label">Masalah :</div>
                        <div class="tools-val">{{ !empty($act->problems) ? $act->problems : 'Tidak ada' }}</div>

                        <div class="tools-label">Solusi:</div>
                        <div class="tools-val">{{ !empty($act->solutions) ? $act->solutions : 'Tidak ada' }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @empty
    <div class="text-center" style="padding: 40px; font-style: italic;">
        Belum ada catatan aktivitas harian yang tercatat.
    </div>
    @endforelse

</body>
</html>
