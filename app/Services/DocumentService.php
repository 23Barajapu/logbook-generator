<?php

namespace App\Services;

use App\Models\Internship;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;

class DocumentService
{
    public function generatePdf(Internship $internship, ?string $startDate = null, ?string $endDate = null)
    {
        \Carbon\Carbon::setLocale('id');

        $internship->load([
            'student.user',
            'company',
            'supervisor.user',
            'activities' => function ($query) use ($startDate, $endDate) {
                if ($startDate) {
                    $query->whereDate('activity_date', '>=', $startDate);
                }
                if ($endDate) {
                    $query->whereDate('activity_date', '<=', $endDate);
                }
                $query->orderBy('day_number', 'asc')->with(['attachments', 'approvals.supervisor.user']);
            },
            'evaluation',
        ]);

        $activities = $internship->activities;
        $student = $internship->student;
        $studentUser = $student?->user;
        $company = $internship->company;

        $studentName = $studentUser->name ?? 'Baraja Putra';
        $studentNim = $student->nim ?? '10601006';
        $studyProgram = $student->study_program ?? 'D4 TRPL';
        $companyName = $company->company_name ?? 'PT Dwidasa Samsara Indonesia';
        $fieldSupervisorName = $company->field_supervisor ?? 'Cecep Saefudin';
        $fieldSupervisorPos = $company->field_supervisor_position ?? 'Leader Quality Control';

        $firstActDate = $activities->first()?->activity_date;
        $lastActDate = $activities->last()?->activity_date;
        $startDateCarbon = $firstActDate ?? $internship->start_date ?? now();

        $refMonth = (int)$startDateCarbon->format('n');
        $refYear = (int)$startDateCarbon->format('Y');
        $academicYear = $refMonth >= 7
            ? ($refYear - 1) . ' / ' . $refYear
            : ($refYear - 1) . ' / ' . $refYear;
        if ($refMonth >= 7) {
            $academicYear = ($refYear - 1) . ' / ' . $refYear;
        }

        $city = 'Bandung';
        if (preg_match('/(Bandung|Jakarta|Surabaya|Semarang|Yogyakarta|Subang|Bogor|Bekasi|Tangerang|Depok)/i', $company->address ?? '', $matches)) {
            $city = ucfirst(strtolower($matches[1]));
        }

        $approvalDateText = $lastActDate
            ? $lastActDate->translatedFormat('j F Y')
            : ($internship->end_date ? $internship->end_date->translatedFormat('j F Y') : now()->translatedFormat('j F Y'));

        $logoPath = $internship->getEffectiveCampusLogoRealPath();
        $logoBase64 = null;
        if ($logoPath && file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };
            $logoData = file_get_contents($logoPath);
            if ($logoData !== false) {
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($logoData);
            }
        }

        foreach ($activities as $act) {
            if ($act->attachments) {
                foreach ($act->attachments as $att) {
                    if ($att->isImage()) {
                        $filePath = storage_path('app/public/' . $att->file_path);
                        if (file_exists($filePath)) {
                            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                            $mime = match ($ext) {
                                'png' => 'image/png',
                                'webp' => 'image/webp',
                                'jpg', 'jpeg' => 'image/jpeg',
                                default => 'image/jpeg',
                            };
                            $data = file_get_contents($filePath);
                            if ($data !== false) {
                                $att->base64_src = 'data:' . $mime . ';base64,' . base64_encode($data);
                            }
                        }
                    }
                }
            }
        }

        $studyProgClean = strtoupper($studyProgram);
        if (str_contains($studyProgClean, 'TRPL')) {
            $studyProgDisplay = 'D4 TRPL';
        } else {
            $studyProgDisplay = $studyProgClean;
        }

        $pdf = Pdf::loadView('documents.logbook-pdf', [
            'internship' => $internship,
            'student' => $student,
            'studentName' => $studentName,
            'studentNim' => $studentNim,
            'studyProgram' => $studyProgDisplay,
            'company' => $company,
            'companyName' => $companyName,
            'fieldSupervisorName' => $fieldSupervisorName,
            'fieldSupervisorPos' => $fieldSupervisorPos,
            'city' => $city,
            'approvalDateText' => $approvalDateText,
            'academicYear' => $academicYear,
            'batchYear' => $student->batch_year ?? $startDateCarbon->format('Y'),
            'supervisor' => $internship->supervisor,
            'activities' => $activities,
            'evaluation' => $internship->evaluation,
            'campusLogoBase64' => $logoBase64,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Dapatkan daftar minggu PKL yang memiliki aktivitas
     *
     * @return array<int, array{week_number: int, start_date: string, end_date: string, label: string, range_text: string, activities_count: int, day_range: string}>
     */
    public function getAvailableWeeks(Internship $internship): array
    {
        $activities = $internship->activities()->orderBy('activity_date', 'asc')->get();
        if ($activities->isEmpty()) {
            return [];
        }

        \Carbon\Carbon::setLocale('id');

        $grouped = [];
        foreach ($activities as $act) {
            if (! $act->activity_date) {
                continue;
            }

            $mon = $act->activity_date->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
            $fri = $mon->copy()->addDays(4);

            $key = $mon->format('Y-m-d');
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'monday' => $mon,
                    'friday' => $fri,
                    'activities_count' => 0,
                    'min_day' => $act->day_number,
                    'max_day' => $act->day_number,
                ];
            }
            $grouped[$key]['activities_count']++;
            $grouped[$key]['min_day'] = min($grouped[$key]['min_day'], $act->day_number);
            $grouped[$key]['max_day'] = max($grouped[$key]['max_day'], $act->day_number);
        }

        ksort($grouped);

        $weeks = [];
        $weekNumber = 1;
        foreach ($grouped as $info) {
            $mon = $info['monday'];
            $fri = $info['friday'];

            if ($mon->format('Y-m') === $fri->format('Y-m')) {
                $rangeStr = $mon->translatedFormat('j') . ' – ' . $fri->translatedFormat('j F Y');
            } elseif ($mon->format('Y') === $fri->format('Y')) {
                $rangeStr = $mon->translatedFormat('j F') . ' – ' . $fri->translatedFormat('j F Y');
            } else {
                $rangeStr = $mon->translatedFormat('j F Y') . ' – ' . $fri->translatedFormat('j F Y');
            }

            $weeks[] = [
                'week_number' => $weekNumber,
                'start_date' => $mon->format('Y-m-d'),
                'end_date' => $fri->format('Y-m-d'),
                'label' => "Minggu ke-{$weekNumber} ({$rangeStr})",
                'short_label' => "Minggu {$weekNumber}",
                'range_text' => $rangeStr,
                'activities_count' => $info['activities_count'],
                'day_range' => "Hari ke-{$info['min_day']} s.d. {$info['max_day']}",
            ];
            $weekNumber++;
        }

        return $weeks;
    }

    public function generateDocx(Internship $internship, ?string $startDate = null, ?string $endDate = null): string
    {
        \Carbon\Carbon::setLocale('id');

        $internship->load([
            'student.user',
            'company',
            'supervisor.user',
            'activities' => function ($query) use ($startDate, $endDate) {
                if ($startDate) {
                    $query->whereDate('activity_date', '>=', $startDate);
                }
                if ($endDate) {
                    $query->whereDate('activity_date', '<=', $endDate);
                }
                $query->orderBy('day_number', 'asc')->with(['attachments', 'approvals.supervisor.user']);
            },
            'evaluation',
        ]);

        $activities = $internship->activities;
        $student = $internship->student;
        $studentUser = $student?->user;
        $company = $internship->company;

        $studentName = $studentUser->name ?? 'Baraja Putra';
        $studentNim = $student->nim ?? '10601006';
        $studyProgram = $student->study_program ?? 'D4 TRPL';
        $companyName = $company->company_name ?? 'PT Dwidasa Samsara Indonesia';
        $fieldSupervisorName = $company->field_supervisor ?? 'Cecep Saefudin';
        $fieldSupervisorPos = $company->field_supervisor_position ?? 'Leader Quality Control';

        // Hitung Periode & Tanggal
        $firstActDate = $activities->first()?->activity_date;
        $lastActDate = $activities->last()?->activity_date;
        $startDateCarbon = $firstActDate ?? $internship->start_date ?? now();
        $endDateCarbon = $lastActDate ?? $internship->end_date ?? now();

        $refMonth = (int)$startDateCarbon->format('n');
        $refYear = (int)$startDateCarbon->format('Y');
        $academicYear = $refMonth >= 7
            ? ($refYear - 1) . ' / ' . $refYear
            : ($refYear - 1) . ' / ' . $refYear;
        if ($refMonth >= 7) {
            $academicYear = ($refYear - 1) . ' / ' . $refYear;
        }

        $city = 'Bandung';
        if (preg_match('/(Bandung|Jakarta|Surabaya|Semarang|Yogyakarta|Subang|Bogor|Bekasi|Tangerang|Depok)/i', $company->address ?? '', $matches)) {
            $city = ucfirst(strtolower($matches[1]));
        }

        $approvalDateText = $lastActDate
            ? $lastActDate->translatedFormat('j F Y')
            : ($internship->end_date ? $internship->end_date->translatedFormat('j F Y') : now()->translatedFormat('j F Y'));

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        // ==========================================
        // 1. HALAMAN COVER (LANDSCAPE)
        // ==========================================
        $sectionCover = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1701,
            'marginRight' => 1440,
        ]);

        $sectionCover->addText(
            $this->cleanText('BUKU AKTIVITAS HARIAN'),
            ['bold' => false, 'size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionCover->addText(
            $this->cleanText('PRAKTIK KERJA LAPANGAN'),
            ['bold' => false, 'size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionCover->addText(
            $this->cleanText('TA. ' . $academicYear),
            ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );

        $sectionCover->addTextBreak(2);

        // Logo Kampus
        $logoPath = $internship->getEffectiveCampusLogoRealPath();
        if ($logoPath && file_exists($logoPath)) {
            $imageInfo = @getimagesize($logoPath);
            if ($imageInfo && $imageInfo[0] > 0 && $imageInfo[1] > 0) {
                $maxDim = 101;
                $ratio = min($maxDim / $imageInfo[0], $maxDim / $imageInfo[1]);
                $renderWidth = (int) round($imageInfo[0] * $ratio);
                $renderHeight = (int) round($imageInfo[1] * $ratio);
            } else {
                $renderWidth = 101;
                $renderHeight = 101;
            }

            $sectionCover->addImage($logoPath, [
                'width' => $renderWidth,
                'height' => $renderHeight,
                'alignment' => Jc::CENTER,
            ]);
        }

        $sectionCover->addTextBreak(2);

        // Tabel Identitas Mahasiswa (Tanpa border)
        $identTable = $sectionCover->addTable([
            'alignment' => Jc::CENTER,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMarginLeft' => 60,
            'cellMarginRight' => 60,
        ]);

        $identRows = [
            ['Nama', $studentName],
            ['NIM', $studentNim],
            ['Tempat PKL', $companyName],
        ];

        foreach ($identRows as $row) {
            $identTable->addRow(215);
            $identTable->addCell(1747)->addText($this->cleanText($row[0]), ['bold' => true, 'size' => 12], ['alignment' => Jc::START]);
            $identTable->addCell(297)->addText(':', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
            $identTable->addCell(2211)->addText($this->cleanText($row[1]), ['bold' => true, 'size' => 12], ['alignment' => Jc::START]);
        }

        $sectionCover->addTextBreak(3);

        $studyProgClean = strtoupper($studyProgram);
        if (str_contains($studyProgClean, 'TRPL')) {
            $studyProgDisplay = 'D4 TRPL';
        } else {
            $studyProgDisplay = $studyProgClean;
        }

        $sectionCover->addText(
            $this->cleanText($studyProgDisplay),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionCover->addText(
            $this->cleanText('POLITEKNIK NEGERI SUBANG'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionCover->addText(
            $this->cleanText((string)($student->batch_year ?? $startDateCarbon->format('Y'))),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );

        // ==========================================
        // 2. HALAMAN LEMBAR PENGESAHAN (LANDSCAPE)
        // ==========================================
        $sectionPengesahan = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1701,
            'marginRight' => 1440,
        ]);

        $sectionPengesahan->addText(
            $this->cleanText('LEMBAR PENGESAHAN'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );
        $sectionPengesahan->addText(
            $this->cleanText('BUKU AKTIVITAS HARIAN'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionPengesahan->addText(
            $this->cleanText('DI'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionPengesahan->addText(
            $this->cleanText($companyName),
            ['bold' => true, 'size' => 9],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );

        $sectionPengesahan->addTextBreak(2);

        $sectionPengesahan->addText(
            $this->cleanText('Oleh :'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionPengesahan->addText(
            $this->cleanText($studentName),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionPengesahan->addText(
            $this->cleanText('NIM ' . $studentNim),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );

        $sectionPengesahan->addTextBreak(2);

        $sectionPengesahan->addText(
            $this->cleanText('Disetujui dan disahkan sebagai'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionPengesahan->addText(
            $this->cleanText('Buku Aktivitas Harian'),
            ['bold' => true, 'size' => 9],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );

        $sectionPengesahan->addTextBreak(2);

        $sectionPengesahan->addText(
            $this->cleanText($city . ', ' . $approvalDateText),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $sectionPengesahan->addText(
            $this->cleanText($fieldSupervisorPos . ','),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );

        $sectionPengesahan->addTextBreak(2);

        $sectionPengesahan->addText(
            $this->cleanText('Ttd + Stempel'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );

        $sectionPengesahan->addTextBreak(2);

        $sectionPengesahan->addText(
            $this->cleanText($fieldSupervisorName),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );

        // ==========================================
        // 3. HALAMAN LAPORAN AKTIVITAS HARIAN (LANDSCAPE)
        // ==========================================
        $sectionActivities = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1701,
            'marginRight' => 1134,
        ]);

        $sectionActivities->addText(
            $this->cleanText('LAPORAN AKTIVITAS HARIAN'),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 160]
        );

        $actTableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMarginTop' => 70,
            'cellMarginBottom' => 70,
            'cellMarginLeft' => 100,
            'cellMarginRight' => 100,
        ];

        foreach ($activities as $act) {
            $actDateCarbon = $act->activity_date;
            $dateStr = $actDateCarbon ? $actDateCarbon->translatedFormat('j F Y') : '-';

            // Header Hari: Hari Ke-44, Tanggal 5 Oktober 2026
            $sectionActivities->addText(
                $this->cleanText('Hari Ke-' . $act->day_number . ', Tanggal ' . $dateStr),
                ['size' => 12],
                ['spaceBefore' => 140, 'spaceAfter' => 60]
            );

            $table = $sectionActivities->addTable($actTableStyle);

            // Baris Header
            $table->addRow(440, ['cantSplit' => true]);
            $table->addCell(1271, ['valign' => 'center'])->addText(
                $this->cleanText('Bidang Pekerjaan'),
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
            $table->addCell(9496, ['valign' => 'center'])->addText(
                $this->cleanText('Kegiatan & Bukti'),
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
            $table->addCell(2920, ['valign' => 'center'])->addText(
                $this->cleanText('Tools yang digunakan , Masalah dan Solusi'),
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            // Baris Data
            $table->addRow(null, ['cantSplit' => true]);

            // Kolom 1: Bidang Pekerjaan (Center, 9pt)
            $workFieldText = $act->work_field;
            if (stripos($workFieldText, 'Quality Control') !== false) {
                $workFieldText = 'QC';
            }
            $table->addCell(1271)->addText(
                $this->cleanText($workFieldText),
                ['size' => 9],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            // Kolom 2: Kegiatan & Bukti (Left, 9pt)
            $cellKegiatan = $table->addCell(9496);
            $lines = preg_split('/\r\n|\r|\n/', trim($act->activities));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $cleanLine = preg_replace('/^\d+[\.\)]\s*/', '', $trimmed);
                    $cellKegiatan->addText(
                        $this->cleanText($cleanLine),
                        ['size' => 9],
                        ['spaceAfter' => 40]
                    );
                }
            }

            // Lampiran Bukti jika ada
            if ($act->attachments && $act->attachments->isNotEmpty()) {
                foreach ($act->attachments as $att) {
                    $filePath = storage_path('app/public/' . $att->file_path);
                    if ($att->isImage() && file_exists($filePath)) {
                        $maxW = 400; // max pt
                        $maxH = 250; // max pt
                        $imgInfo = @getimagesize($filePath);
                        if ($imgInfo && $imgInfo[0] > 0 && $imgInfo[1] > 0) {
                            $ratio = min($maxW / $imgInfo[0], $maxH / $imgInfo[1], 1.0);
                            $renderW = (int) round($imgInfo[0] * $ratio);
                            $renderH = (int) round($imgInfo[1] * $ratio);
                        } else {
                            $renderW = 380;
                            $renderH = 200;
                        }

                        $cellKegiatan->addImage($filePath, [
                            'width' => $renderW,
                            'height' => $renderH,
                            'alignment' => Jc::START,
                        ]);
                    } else {
                        $cellKegiatan->addText(
                            $this->cleanText('Bukti: ' . $att->file_name),
                            ['size' => 8, 'italic' => true],
                            ['spaceAfter' => 20]
                        );
                    }
                }
            }

            // Kolom 3: Tools, Masalah & Solusi (Left, 10pt)
            $cellTools = $table->addCell(2920);

            $cellTools->addText(
                $this->cleanText('Tools:'),
                ['bold' => true, 'size' => 10],
                ['spaceAfter' => 0]
            );
            $cellTools->addText(
                $this->cleanText(!empty($act->tools_used) ? $act->tools_used : 'Tidak ada'),
                ['size' => 10],
                ['spaceAfter' => 40]
            );

            $cellTools->addText(
                $this->cleanText('Masalah :'),
                ['bold' => true, 'size' => 10],
                ['spaceAfter' => 0]
            );
            $cellTools->addText(
                $this->cleanText(!empty($act->problems) ? $act->problems : 'Tidak ada'),
                ['size' => 10],
                ['spaceAfter' => 40]
            );

            $cellTools->addText(
                $this->cleanText('Solusi:'),
                ['bold' => true, 'size' => 10],
                ['spaceAfter' => 0]
            );
            $cellTools->addText(
                $this->cleanText(!empty($act->solutions) ? $act->solutions : 'Tidak ada'),
                ['size' => 10],
                ['spaceAfter' => 20]
            );

            $sectionActivities->addTextBreak(1);
        }

        // Simpan file ke storage
        $safeNim = preg_replace('/[^A-Za-z0-9]/', '', $studentNim);
        $fileName = 'logbook_' . $safeNim . '_' . date('Ymd_His') . '.docx';
        $tempPath = storage_path('app/temp_' . $fileName);

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }

    private function cleanText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
    }
}

