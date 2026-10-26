<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Services\DocumentService;
use Tests\TestCase;
use ZipArchive;

class DocxContentTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_docx_contains_exact_markdown_structure(): void
    {
        $user = \App\Models\User::factory()->create(['name' => 'Baraja Putra', 'role' => 'mahasiswa']);
        $nim = '1060' . rand(1000, 9999);
        $student = \App\Models\Student::create([
            'user_id' => $user->id,
            'nim' => $nim,
            'study_program' => 'D4 Teknologi Rekayasa Perangkat Lunak (TRPL)',
            'batch_year' => 2026,
            'phone_number' => '081234567890',
        ]);

        $company = \App\Models\Company::create([
            'company_name' => 'PT Dwidasa Samsara Indonesia',
            'address' => 'Bandung',
            'field_supervisor' => 'Cecep Saefudin',
            'field_supervisor_position' => 'Leader Quality Control',
        ]);

        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-09',
            'status' => 'aktif',
        ]);

        \App\Models\Activity::create([
            'internship_id' => $internship->id,
            'day_number' => 44,
            'activity_date' => '2026-10-05',
            'work_field' => 'Quality Control (QC)',
            'activities' => "1. Melakukan pengujian manual sistem perbankan IBB Nasabah BSSB.\n2. Melakukan dokumentasi hasil pengujian manual.",
            'tools_used' => 'Google Chrome, SharePoint, Google Drive, ShareX, FortiClient VPN',
            'problems' => 'Tidak ada.',
            'solutions' => 'Tidak ada.',
            'status' => 'Disetujui',
        ]);

        $service = app(DocumentService::class);
        $path = $service->generateDocx($internship);

        $this->assertFileExists($path);

        $zip = new ZipArchive();
        $opened = $zip->open($path);
        $this->assertTrue($opened);

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        @unlink($path);

        $this->assertStringContainsString('BUKU AKTIVITAS HARIAN', $xml);
        $this->assertStringContainsString('PRAKTIK KERJA LAPANGAN', $xml);
        $this->assertStringContainsString('TA.', $xml);
        $this->assertStringContainsString('LEMBAR PENGESAHAN', $xml);
        $this->assertStringContainsString('LAPORAN AKTIVITAS HARIAN', $xml);
        $this->assertStringContainsString('Bidang Pekerjaan', $xml);
        $this->assertStringContainsString('Kegiatan &amp; Bukti', $xml);
        $this->assertStringContainsString('Tools yang digunakan , Masalah dan Solusi', $xml);
        $this->assertStringContainsString('w:orient="landscape"', $xml);
        $this->assertStringContainsString('QC', $xml);
        $this->assertStringContainsString($nim, $xml);
        $this->assertStringContainsString('Cecep Saefudin', $xml);

        // Test PDF Output
        $pdf = $service->generatePdf($internship);
        $pdfRaw = $pdf->output();
        $this->assertNotEmpty($pdfRaw);
        $this->assertStringStartsWith('%PDF-', $pdfRaw);
    }
}
