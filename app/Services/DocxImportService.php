<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\ImportedDocument;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DocxImportService
{
    protected array $indonesianMonths = [
        'januari' => '01', 'jan' => '01',
        'februari' => '02', 'feb' => '02', 'pebruari' => '02',
        'maret' => '03', 'mar' => '03',
        'april' => '04', 'apr' => '04',
        'mei' => '05', 'may' => '05',
        'juni' => '06', 'jun' => '06',
        'juli' => '07', 'jul' => '07',
        'agustus' => '08', 'ags' => '08', 'agu' => '08', 'aug' => '08',
        'september' => '09', 'sep' => '09',
        'oktober' => '10', 'okt' => '10', 'oct' => '10',
        'november' => '11', 'nov' => '11', 'nopember' => '11', 'nop' => '11',
        'desember' => '12', 'des' => '12', 'dec' => '12',
    ];

    /**
     * Parse multiple DOCX files and extract activity records with images.
     *
     * @param  array<UploadedFile|string>  $files
     * @return array{token: string, activities: array, processed_files: array, stats: array}
     */
    public function parseDocxFiles(array $files, int $internshipId): array
    {
        $importToken = 'imp_' . Str::random(24);
        $tempRelativeDir = 'temp_imports/' . $importToken;
        Storage::disk('public')->makeDirectory($tempRelativeDir);

        $extractedActivities = [];
        $totalImages = 0;
        $conflictCount = 0;
        $seenHashesInBatch = [];
        $skippedDuplicates = [];
        $validFileCount = 0;
        $processedFiles = [];

        // Ambil riwayat hash file yang sudah pernah diimpor untuk penugasan PKL ini
        $historicalHashes = ImportedDocument::where('internship_id', $internshipId)
            ->pluck('file_hash')
            ->toArray();

        foreach ($files as $file) {
            $filePath = $file instanceof UploadedFile ? $file->getRealPath() : (string) $file;
            $sourceFileName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($filePath);
            $fileSize = $file instanceof UploadedFile ? $file->getSize() : (file_exists($filePath) ? filesize($filePath) : 0);

            $fileHash = file_exists($filePath) ? hash_file('sha256', $filePath) : null;

            if ($fileHash) {
                // 1. Validasi duplikat dalam satu sesi unggah (intra-batch)
                if (isset($seenHashesInBatch[$fileHash])) {
                    $skippedDuplicates[] = [
                        'file_name' => $sourceFileName,
                        'reason' => 'Duplikat dalam satu sesi upload (identik dengan ' . $seenHashesInBatch[$fileHash] . ')',
                    ];
                    continue;
                }

                // 2. Validasi duplikat terhadap riwayat impor sebelumnya (historical)
                if (in_array($fileHash, $historicalHashes, true)) {
                    $skippedDuplicates[] = [
                        'file_name' => $sourceFileName,
                        'reason' => 'Dokumen ini sudah pernah diimpor sebelumnya untuk periode PKL ini',
                    ];
                    continue;
                }

                $seenHashesInBatch[$fileHash] = $sourceFileName;
            }

            $validFileCount++;
            $processedFiles[] = [
                'file_name' => $sourceFileName,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
            ];

            $ext = strtolower(pathinfo($sourceFileName, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $parsed = $this->parseSinglePdf($filePath, $sourceFileName, $tempRelativeDir, $internshipId);
            } else {
                $parsed = $this->parseSingleDocx($filePath, $sourceFileName, $tempRelativeDir, $internshipId);
            }

            foreach ($parsed as $item) {
                if ($item['is_conflict']) {
                    $conflictCount++;
                }
                $totalImages += count($item['images']);
                $extractedActivities[] = $item;
            }
        }

        // Sort activities by day_number ascending
        usort($extractedActivities, fn ($a, $b) => $a['day_number'] <=> $b['day_number']);

        return [
            'token' => $importToken,
            'activities' => $extractedActivities,
            'processed_files' => $processedFiles,
            'stats' => [
                'total_extracted' => count($extractedActivities),
                'conflict_count' => $conflictCount,
                'image_count' => $totalImages,
                'files_count' => $validFileCount,
                'total_files_uploaded' => count($files),
                'duplicate_count' => count($skippedDuplicates),
                'skipped_duplicates' => $skippedDuplicates,
            ],
        ];
    }

    /**
     * Parse single DOCX file.
     */
    public function parseSingleDocx(string $filePath, string $sourceFileName, string $tempRelativeDir, int $internshipId): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            Log::warning("Gagal membuka arsip DOCX: {$sourceFileName}");
            return [];
        }

        $docXml = $zip->getFromName('word/document.xml');
        if (! $docXml) {
            $zip->close();
            return [];
        }

        // Parse relationships to map rId -> target media
        $rels = [];
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($relsXml) {
            $relsDoc = new DOMDocument();
            @$relsDoc->loadXML($relsXml);
            foreach ($relsDoc->getElementsByTagName('Relationship') as $rel) {
                if ($rel instanceof \DOMElement) {
                    $id = $rel->getAttribute('Id');
                    $target = $rel->getAttribute('Target');
                    $rels[$id] = $target;
                }
            }
        }

        $doc = new DOMDocument();
        @$doc->loadXML($docXml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $bodyNodes = $xpath->query('//w:body/*');
        if (! $bodyNodes) {
            $zip->close();
            return [];
        }

        $activities = [];
        $currentDayInfo = null;
        $docDefaultWorkField = 'Quality Control (QC)';

        // Scan document text to detect general work field / division if available
        if (preg_match('/(?:Divisi|Bidang|Pekerjaan)[:\s]+([A-Za-z0-9\s\(\)]+)/i', $doc->textContent, $wfMatch)) {
            $docDefaultWorkField = trim(explode("\n", $wfMatch[1])[0]);
        }

        foreach ($bodyNodes as $node) {
            $text = trim($node->textContent);

            // Detect day header, e.g., "Hari Ke-1, Tanggal 3 Agustus 2026" or "Hari Ke-44: Senin, 5 Oktober 2026"
            if (preg_match('/Hari\s*Ke[- ]*(\d+)/i', $text, $m)) {
                $currentDayInfo = [
                    'day_number' => (int) $m[1],
                    'raw_header' => $text,
                    'date' => $this->parseIndonesianDate($text),
                ];
            } elseif ($node->nodeName === 'w:tbl' && $currentDayInfo) {
                $rows = $xpath->query('.//w:tr', $node);
                if ($rows->length > 1) {
                    $dayNum = $currentDayInfo['day_number'];
                    $workFields = [];
                    $activityTexts = [];
                    $toolsTexts = [];
                    $extractedImages = [];

                    for ($i = 1; $i < $rows->length; $i++) {
                        $row = $rows->item($i);
                        $cells = $xpath->query('.//w:tc', $row);
                        if ($cells->length >= 3) {
                            $wf = trim($cells->item(0)->textContent);
                            if ($wf && ! in_array(strtolower($wf), ['bidang pekerjaan', 'divisi'])) {
                                $workFields[] = $wf;
                            }

                            // Extract activities text from cell 1
                            $pNodes = $xpath->query('.//w:p', $cells->item(1));
                            $pTexts = [];
                            foreach ($pNodes as $p) {
                                $pTrim = trim($p->textContent);
                                if ($pTrim) {
                                    $pTexts[] = $pTrim;
                                }
                            }
                            if ($pTexts) {
                                $activityTexts[] = implode("\n", $pTexts);
                            }

                            // Extract tools/problems/solutions text from cell 2
                            $tNodes = $xpath->query('.//w:p', $cells->item(2));
                            $tTexts = [];
                            foreach ($tNodes as $t) {
                                $tTrim = trim($t->textContent);
                                if ($tTrim) {
                                    $tTexts[] = $tTrim;
                                }
                            }
                            if ($tTexts) {
                                $toolsTexts[] = implode("\n", $tTexts);
                            }

                            // Extract images from cell 1
                            $blips = $xpath->query('.//a:blip', $cells->item(1));
                            foreach ($blips as $blip) {
                                if ($blip instanceof \DOMElement) {
                                    $embedId = $blip->getAttribute('r:embed');
                                    if (isset($rels[$embedId])) {
                                        $target = $rels[$embedId];
                                        $targetClean = ltrim(str_replace('\\', '/', $target), '/');
                                        $pathInZip = str_starts_with($targetClean, 'word/') ? $targetClean : 'word/' . $targetClean;

                                        $imgBinary = $zip->getFromName($pathInZip);
                                        if ($imgBinary !== false) {
                                            $ext = pathinfo($targetClean, PATHINFO_EXTENSION) ?: 'png';
                                            $tempFilename = 'day' . $dayNum . '_' . Str::random(8) . '.' . strtolower($ext);
                                            $relativeSavedPath = $tempRelativeDir . '/' . $tempFilename;

                                            Storage::disk('public')->put($relativeSavedPath, $imgBinary);

                                            $extractedImages[] = [
                                                'file_name' => basename($targetClean),
                                                'temp_path' => $relativeSavedPath,
                                                'preview_url' => asset('storage/' . $relativeSavedPath),
                                                'file_size' => strlen($imgBinary),
                                                'file_type' => strtolower($ext),
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Check conflict with database
                    $existing = Activity::where('internship_id', $internshipId)
                        ->where('day_number', $dayNum)
                        ->first();

                    $combinedToolsText = implode("\n\n", $toolsTexts);
                    $parsedTools = $this->parseToolsColumn($combinedToolsText);

                    $activities[] = [
                        'uuid' => (string) Str::uuid(),
                        'day_number' => $dayNum,
                        'activity_date' => $currentDayInfo['date'] ?? now()->toDateString(),
                        'raw_header' => $currentDayInfo['raw_header'],
                        'work_field' => ! empty($workFields) ? implode(' / ', array_unique($workFields)) : 'PKL',
                        'activities' => ! empty($activityTexts) ? implode("\n\n", $activityTexts) : 'Melaksanakan kegiatan PKL.',
                        'tools_used' => $parsedTools['tools_used'],
                        'problems' => $parsedTools['problems'],
                        'solutions' => $parsedTools['solutions'],
                        'images' => $extractedImages,
                        'source_file' => $sourceFileName,
                        'is_conflict' => $existing !== null,
                        'existing_id' => $existing?->id,
                        'selected' => true,
                        'conflict_action' => 'overwrite',
                    ];
                }
                $currentDayInfo = null;
            }
        }

        // Propagate common work field to any days where cell 0 was blank
        $commonWf = null;
        foreach ($activities as $act) {
            if (! empty($act['work_field']) && ! in_array($act['work_field'], ['PKL', ''])) {
                $commonWf = $act['work_field'];
                break;
            }
        }
        if ($commonWf) {
            foreach ($activities as &$act) {
                if (empty($act['work_field']) || $act['work_field'] === 'PKL') {
                    $act['work_field'] = $commonWf;
                }
            }
            unset($act);
        }

        $zip->close();

        return $activities;
    }

    /**
     * Parse single PDF file and extract activities and embedded images.
     */
    public function parseSinglePdf(string $filePath, string $sourceFileName, string $tempRelativeDir, int $internshipId): array
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
        } catch (\Throwable $e) {
            Log::warning("Gagal membuka file PDF: {$sourceFileName} - " . $e->getMessage());
            return [];
        }

        $activities = [];
        $pages = $pdf->getPages();

        foreach ($pages as $pIdx => $page) {
            $pageText = $page->getText();
            if (empty(trim($pageText))) {
                continue;
            }

            // Ekstrak gambar tersemat pada halaman ini
            $pageImages = [];
            try {
                $xobjects = $page->getXObjects();
                foreach ($xobjects as $xId => $xObj) {
                    if ($xObj instanceof \Smalot\PdfParser\XObject\Image) {
                        $details = $xObj->getDetails();
                        $width = (int) ($details['Width'] ?? 0);
                        $height = (int) ($details['Height'] ?? 0);

                        // Filter cerdas: abaikan logo/ikon kecil (< 100px)
                        if ($width > 0 && $height > 0 && ($width < 100 || $height < 100)) {
                            continue;
                        }

                        $rawContent = $xObj->getContent();
                        if (strlen($rawContent) < 1000) {
                            continue;
                        }

                        $imgData = null;
                        $ext = 'jpg';
                        $filter = $details['Filter'] ?? '';

                        if ($filter === 'DCTDecode' || str_starts_with($rawContent, "\xFF\xD8\xFF")) {
                            $imgData = $rawContent;
                            $ext = 'jpg';
                        } else {
                            $gd = @imagecreatefromstring($rawContent);
                            if ($gd !== false) {
                                ob_start();
                                imagepng($gd);
                                $imgData = ob_get_clean();
                                $ext = 'png';
                            }
                        }

                        if ($imgData !== null) {
                            $imgHash = hash('sha256', $imgData);
                            $imageFilename = 'img_pdf_' . substr($imgHash, 0, 16) . '.' . $ext;
                            $tempImagePath = $tempRelativeDir . '/' . $imageFilename;

                            if (! Storage::disk('public')->exists($tempImagePath)) {
                                Storage::disk('public')->put($tempImagePath, $imgData);
                            }

                            $pageImages[] = [
                                'file_name' => $imageFilename,
                                'temp_path' => $tempImagePath,
                                'preview_url' => asset('storage/' . $tempImagePath),
                                'file_type' => $ext,
                                'file_size' => strlen($imgData),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal mengekstrak gambar dari halaman PDF {$pIdx}: " . $e->getMessage());
            }

            // Deteksi penanda Hari Ke-X
            if (preg_match_all('/Hari\s*Ke[-–—\s]*(\d+)/i', $pageText, $dayMatches, PREG_OFFSET_CAPTURE)) {
                $matchesCount = count($dayMatches[0]);

                for ($i = 0; $i < $matchesCount; $i++) {
                    $dayNum = (int) $dayMatches[1][$i][0];
                    $startOffset = $dayMatches[0][$i][1];
                    $endOffset = ($i + 1 < $matchesCount) ? $dayMatches[0][$i + 1][1] : strlen($pageText);
                    $block = substr($pageText, $startOffset, $endOffset - $startOffset);

                    // Parse tanggal
                    $parsedDate = $this->parseIndonesianDate($block) ?? now()->toDateString();

                    // Parse Tools, Masalah, Solusi
                    $toolsData = $this->parseToolsColumn($block);

                    // Parse Bidang Kerja & Deskripsi Aktivitas
                    $body = preg_replace('/^.*?Hari\s*Ke[^\n]*\n?/im', '', $block);
                    $body = preg_replace('/(Bidang\s*Pekerjaan|Kegiatan\s*(&|dan)\s*Bukti|Tools\s*yang\s*digunakan[^\n]*|Masalah\s*dan\s*Solusi|Solusi)/im', '', $body);
                    $body = preg_replace('/\bTools\s*:.*$/is', '', $body);

                    $lines = array_values(array_filter(array_map('trim', explode("\n", $body))));
                    $workField = 'PKL';
                    if (! empty($lines)) {
                        if (count($lines) > 1 && strlen($lines[0]) <= 50 && ! preg_match('/^\d+[\.\)]/i', $lines[0])) {
                            $workField = array_shift($lines);
                            // Jika baris berikutnya juga pendek dan kelanjutan kata bidang (misal: 'Mobile' + 'Development' atau 'DevOps' + 'Support')
                            if (! empty($lines) && strlen($lines[0]) <= 30 && ! preg_match('/[\.\,\!\?]$/', $lines[0]) && ! preg_match('/^(membuat|mengerjakan|melakukan|setup|install|perbaikan|testing|deployment|analisis)/i', $lines[0])) {
                                $workField .= ' ' . array_shift($lines);
                            }
                        }
                        $activitiesText = implode("\n", $lines);
                    } else {
                        $activitiesText = 'Aktivitas PKL Hari Ke-' . $dayNum;
                    }

                    // Pengecekan konflik nomor hari di database
                    $conflict = Activity::where('internship_id', $internshipId)
                        ->where('day_number', $dayNum)
                        ->first();

                    $activities[] = [
                        'day_number' => $dayNum,
                        'activity_date' => $parsedDate,
                        'work_field' => $workField,
                        'activities' => $activitiesText,
                        'tools_used' => $toolsData['tools_used'],
                        'problems' => $toolsData['problems'],
                        'solutions' => $toolsData['solutions'],
                        'is_conflict' => (bool) $conflict,
                        'conflict_action' => $conflict ? 'overwrite' : 'skip',
                        'existing_activity' => $conflict ? [
                            'work_field' => $conflict->work_field,
                            'activities' => $conflict->activities,
                            'status' => $conflict->status,
                        ] : null,
                        'images' => $pageImages,
                        'source_file' => $sourceFileName,
                    ];
                }
            }
        }

        // Jika belum ada aktivitas terdeteksi per halaman, coba parsing dari teks gabungan seluruh dokumen
        if (empty($activities)) {
            $fullText = $pdf->getText();
            if (preg_match_all('/Hari\s*Ke[-–—\s]*(\d+)/i', $fullText, $dayMatches, PREG_OFFSET_CAPTURE)) {
                $matchesCount = count($dayMatches[0]);
                for ($i = 0; $i < $matchesCount; $i++) {
                    $dayNum = (int) $dayMatches[1][$i][0];
                    $startOffset = $dayMatches[0][$i][1];
                    $endOffset = ($i + 1 < $matchesCount) ? $dayMatches[0][$i + 1][1] : strlen($fullText);
                    $block = substr($fullText, $startOffset, $endOffset - $startOffset);

                    $parsedDate = $this->parseIndonesianDate($block) ?? now()->toDateString();
                    $toolsData = $this->parseToolsColumn($block);

                    $body = preg_replace('/^.*?Hari\s*Ke[^\n]*\n?/im', '', $block);
                    $body = preg_replace('/(Bidang\s*Pekerjaan|Kegiatan\s*(&|dan)\s*Bukti|Tools\s*yang\s*digunakan[^\n]*|Masalah\s*dan\s*Solusi|Solusi)/im', '', $body);
                    $body = preg_replace('/\bTools\s*:.*$/is', '', $body);

                    $lines = array_values(array_filter(array_map('trim', explode("\n", $body))));
                    $workField = 'PKL';
                    if (! empty($lines)) {
                        if (count($lines) > 1 && strlen($lines[0]) <= 50 && ! preg_match('/^\d+[\.\)]/i', $lines[0])) {
                            $workField = array_shift($lines);
                            // Jika baris berikutnya juga pendek dan kelanjutan kata bidang (misal: 'Mobile' + 'Development' atau 'DevOps' + 'Support')
                            if (! empty($lines) && strlen($lines[0]) <= 30 && ! preg_match('/[\.\,\!\?]$/', $lines[0]) && ! preg_match('/^(membuat|mengerjakan|melakukan|setup|install|perbaikan|testing|deployment|analisis)/i', $lines[0])) {
                                $workField .= ' ' . array_shift($lines);
                            }
                        }
                        $activitiesText = implode("\n", $lines);
                    } else {
                        $activitiesText = 'Aktivitas PKL Hari Ke-' . $dayNum;
                    }

                    $conflict = Activity::where('internship_id', $internshipId)
                        ->where('day_number', $dayNum)
                        ->first();

                    $activities[] = [
                        'day_number' => $dayNum,
                        'activity_date' => $parsedDate,
                        'work_field' => $workField,
                        'activities' => $activitiesText,
                        'tools_used' => $toolsData['tools_used'],
                        'problems' => $toolsData['problems'],
                        'solutions' => $toolsData['solutions'],
                        'is_conflict' => (bool) $conflict,
                        'conflict_action' => $conflict ? 'overwrite' : 'skip',
                        'existing_activity' => $conflict ? [
                            'work_field' => $conflict->work_field,
                            'activities' => $conflict->activities,
                            'status' => $conflict->status,
                        ] : null,
                        'images' => [],
                        'source_file' => $sourceFileName,
                    ];
                }
            }
        }

        // Normalisasi work_field umum jika ada
        $commonWf = null;
        foreach ($activities as $act) {
            if (! empty($act['work_field']) && $act['work_field'] !== 'PKL') {
                $commonWf = $act['work_field'];
                break;
            }
        }
        if ($commonWf) {
            foreach ($activities as &$act) {
                if (empty($act['work_field']) || $act['work_field'] === 'PKL') {
                    $act['work_field'] = $commonWf;
                }
            }
            unset($act);
        }

        return $activities;
    }

    /**
     * Parse Indonesian formatted date into Y-m-d.
     */
    public function parseIndonesianDate(string $text): ?string
    {
        // Format: "3 Agustus 2026" or "5 Oktober 2026"
        if (preg_match('/\b(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})\b/u', $text, $m)) {
            $mLower = strtolower($m[2]);
            if (isset($this->indonesianMonths[$mLower])) {
                return sprintf('%04d-%02d-%02d', $m[3], $this->indonesianMonths[$mLower], $m[1]);
            }
        }

        // Format: "2026-08-03"
        if (preg_match('/\b(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})\b/', $text, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        // Format: "03/08/2026" or "03-08-2026"
        if (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/', $text, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return null;
    }

    /**
     * Parse the 3rd column containing Tools, Problems, and Solutions.
     */
    public function parseToolsColumn(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $tools = null;
        $problems = null;
        $solutions = null;

        // Pattern for Tools section
        $patternTools = '/(?:^|\n)(?:•\s*)?(Tools(?: yang digunakan)?|Alat|Peralatan)\s*:\s*(.*?)(?=(?:\n(?:•\s*)?(?:Masalah|Kendala|Hambatan)\s*:)|\n(?:•\s*)?(?:Solusi|Penyelesaian)\s*:|$)/is';
        if (preg_match($patternTools, $text, $m)) {
            $tools = trim($m[2]);
        }

        // Pattern for Problems section
        $patternProblems = '/(?:^|\n)(?:•\s*)?(Masalah|Kendala|Hambatan)\s*:\s*(.*?)(?=(?:\n(?:•\s*)?(?:Solusi|Penyelesaian)\s*:)|\n(?:•\s*)?(?:Tools(?: yang digunakan)?|Alat)\s*:|$)/is';
        if (preg_match($patternProblems, $text, $m)) {
            $problems = trim($m[2]);
        }

        // Pattern for Solutions section
        $patternSolutions = '/(?:^|\n)(?:•\s*)?(Solusi|Penyelesaian)\s*:\s*(.*?)(?=(?:\n(?:•\s*)?(?:Masalah|Kendala|Hambatan)\s*:)|\n(?:•\s*)?(?:Tools(?: yang digunakan)?|Alat)\s*:|$)/is';
        if (preg_match($patternSolutions, $text, $m)) {
            $solutions = trim($m[2]);
        }

        // Fallback when no section prefixes are present
        if ($tools === null && $problems === null && $solutions === null) {
            $tools = trim($text);
            $problems = 'Tidak Ada';
            $solutions = 'Tidak Ada';
        }

        return [
            'tools_used' => $tools ?: 'Tidak Ada',
            'problems' => $problems ?: 'Tidak Ada',
            'solutions' => $solutions ?: 'Tidak Ada',
        ];
    }

    /**
     * Save the reviewed activities into database.
     *
     * @param  array<array>  $activities
     * @return array{saved: int, updated: int, skipped: int}
     */
    public function saveImportedActivities(int $internshipId, array $activities, string $importToken, array $processedFiles = []): array
    {
        $stats = ['saved' => 0, 'updated' => 0, 'skipped' => 0];

        DB::beginTransaction();
        try {
            foreach ($activities as $item) {
                // Check if user selected this row
                if (empty($item['selected'])) {
                    $stats['skipped']++;
                    continue;
                }

                $dayNum = (int) $item['day_number'];
                $existing = Activity::where('internship_id', $internshipId)
                    ->where('day_number', $dayNum)
                    ->first();

                $conflictAction = $item['conflict_action'] ?? 'overwrite';

                if ($existing && $conflictAction === 'skip') {
                    $stats['skipped']++;
                    continue;
                }

                // Keamanan: Aktivitas yang telah disetujui atau sedang ditinjau pembimbing tidak boleh ditimpa
                if ($existing && ! in_array($existing->status, ['Draft', 'Revisi'], true)) {
                    Log::info("Impor dilewati: Aktivitas hari ke-{$dayNum} berstatus {$existing->status} tidak dapat ditimpa sepihak.");
                    $stats['skipped']++;
                    continue;
                }

                if ($existing && $conflictAction === 'overwrite') {
                    $existing->update([
                        'activity_date' => $item['activity_date'],
                        'work_field' => $item['work_field'],
                        'activities' => $item['activities'],
                        'tools_used' => $item['tools_used'],
                        'problems' => $item['problems'],
                        'solutions' => $item['solutions'],
                        'status' => 'Draft',
                    ]);
                    $activity = $existing;
                    $stats['updated']++;
                } else {
                    $activity = Activity::create([
                        'internship_id' => $internshipId,
                        'day_number' => $dayNum,
                        'activity_date' => $item['activity_date'],
                        'work_field' => $item['work_field'],
                        'activities' => $item['activities'],
                        'tools_used' => $item['tools_used'],
                        'problems' => $item['problems'],
                        'solutions' => $item['solutions'],
                        'status' => 'Draft',
                    ]);
                    $stats['saved']++;
                }

                // Move attachments from temp to permanent
                if (! empty($item['images']) && is_array($item['images'])) {
                    $targetDir = 'attachments/' . $activity->id;
                    Storage::disk('public')->makeDirectory($targetDir);

                    $cleanToken = preg_replace('/[^a-zA-Z0-9_]/', '', $importToken);
                    $expectedPrefix = 'temp_imports/' . $cleanToken . '/';

                    foreach ($item['images'] as $img) {
                        $tempPath = $img['temp_path'] ?? null;
                        if (! $tempPath || ! is_string($tempPath)) {
                            continue;
                        }

                        // Keamanan: Validasi bahwa temp_path wajib berada strictly di direktori sesi impor saat ini
                        $normalizedPath = str_replace('\\', '/', $tempPath);
                        if (! str_starts_with($normalizedPath, $expectedPrefix) || str_contains($normalizedPath, '..')) {
                            Log::warning("Percobaan manipulasi temp_path terdeteksi: {$tempPath}");
                            continue;
                        }

                        if (Storage::disk('public')->exists($normalizedPath)) {
                            $fullTempPath = Storage::disk('public')->path($normalizedPath);
                            $imgHash = file_exists($fullTempPath) ? hash_file('sha256', $fullTempPath) : null;

                            // Lewati jika lampiran gambar dengan hash konten yang sama sudah ada pada aktivitas ini
                            if ($imgHash) {
                                $existingAtt = ActivityAttachment::where('activity_id', $activity->id)
                                    ->where('file_hash', $imgHash)
                                    ->first();
                                if ($existingAtt) {
                                    Storage::disk('public')->delete($normalizedPath);
                                    continue;
                                }
                            }

                            $permanentFilename = basename($normalizedPath);
                            $permanentPath = $targetDir . '/' . $permanentFilename;

                            // Move to permanent directory
                            Storage::disk('public')->move($normalizedPath, $permanentPath);

                            ActivityAttachment::create([
                                'activity_id' => $activity->id,
                                'file_name' => $img['file_name'] ?? $permanentFilename,
                                'file_path' => $permanentPath,
                                'file_type' => $img['file_type'] ?? 'png',
                                'file_size' => $img['file_size'] ?? Storage::disk('public')->size($permanentPath),
                                'file_hash' => $imgHash,
                            ]);
                        }
                    }
                }
            }

            // Catat dokumen DOCX yang berhasil diimpor agar tidak terduplikasi di masa mendatang
            if (! empty($processedFiles) && is_array($processedFiles)) {
                foreach ($processedFiles as $pf) {
                    if (! empty($pf['file_hash'])) {
                        ImportedDocument::firstOrCreate(
                            [
                                'internship_id' => $internshipId,
                                'file_hash' => $pf['file_hash'],
                            ],
                            [
                                'file_name' => $pf['file_name'],
                                'file_size' => $pf['file_size'] ?? 0,
                                'activities_count' => $stats['saved'] + $stats['updated'],
                            ]
                        );
                    }
                }
            }

            DB::commit();

            // Cleanup temp directory
            $this->cancelImport($importToken);

            return $stats;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan aktivitas impor DOCX: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Delete temporary import directory.
     */
    public function cancelImport(string $importToken): void
    {
        $cleanToken = preg_replace('/[^a-zA-Z0-9_]/', '', $importToken);
        if ($cleanToken) {
            Storage::disk('public')->deleteDirectory('temp_imports/' . $cleanToken);
        }
    }
}
