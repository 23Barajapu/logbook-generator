<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAttachment;
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
     * @return array{token: string, activities: array, stats: array}
     */
    public function parseDocxFiles(array $files, int $internshipId): array
    {
        $importToken = 'imp_' . Str::random(24);
        $tempRelativeDir = 'temp_imports/' . $importToken;
        Storage::disk('public')->makeDirectory($tempRelativeDir);

        $extractedActivities = [];
        $totalImages = 0;
        $conflictCount = 0;

        foreach ($files as $file) {
            $filePath = $file instanceof UploadedFile ? $file->getRealPath() : (string) $file;
            $sourceFileName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($filePath);

            $parsed = $this->parseSingleDocx($filePath, $sourceFileName, $tempRelativeDir, $internshipId);

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
            'stats' => [
                'total_extracted' => count($extractedActivities),
                'conflict_count' => $conflictCount,
                'image_count' => $totalImages,
                'files_count' => count($files),
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
    public function saveImportedActivities(int $internshipId, array $activities, string $importToken): array
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

                    foreach ($item['images'] as $img) {
                        $tempPath = $img['temp_path'] ?? null;
                        if ($tempPath && Storage::disk('public')->exists($tempPath)) {
                            $permanentFilename = basename($tempPath);
                            $permanentPath = $targetDir . '/' . $permanentFilename;

                            // Move to permanent directory
                            Storage::disk('public')->move($tempPath, $permanentPath);

                            ActivityAttachment::create([
                                'activity_id' => $activity->id,
                                'file_name' => $img['file_name'] ?? $permanentFilename,
                                'file_path' => $permanentPath,
                                'file_type' => $img['file_type'] ?? 'png',
                                'file_size' => $img['file_size'] ?? Storage::disk('public')->size($permanentPath),
                            ]);
                        }
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
