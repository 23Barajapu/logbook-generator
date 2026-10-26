<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private function createInternshipWithActivities()
    {
        $user = User::create([
            'name' => 'Test Mahasiswa',
            'email' => 'mhs_doc@test.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'nim' => '22010199',
            'study_program' => 'Teknik Informatika',
            'batch_year' => 2022,
        ]);

        $company = Company::create([
            'company_name' => 'PT Karya Cipta Digital',
            'address' => 'Jl. Sudirman Kav 20, Jakarta',
            'field_supervisor' => 'Ir. Hartono',
            'field_supervisor_position' => 'Direktur IT',
        ]);

        $spvUser = User::create([
            'name' => 'Dr. Pembimbing Akademik',
            'email' => 'spv_doc@test.com',
            'password' => Hash::make('password'),
            'role' => 'pembimbing',
        ]);

        $supervisor = Supervisor::create([
            'user_id' => $spvUser->id,
            'employee_number' => '198101012005011003',
        ]);

        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(80)->toDateString(),
            'status' => 'aktif',
        ]);

        Activity::create([
            'internship_id' => $internship->id,
            'day_number' => 1,
            'activity_date' => now()->subDays(5)->toDateString(),
            'work_field' => 'Software Engineering',
            'activities' => 'Konfigurasi server dan migrasi database aplikasi logbook.',
            'tools_used' => 'PHP, PostgreSQL, Git',
            'problems' => 'Tidak ada',
            'solutions' => 'Lancar',
            'status' => 'Disetujui',
        ]);

        return compact('user', 'student', 'internship');
    }

    public function test_can_download_pdf_logbook(): void
    {
        $setup = $this->createInternshipWithActivities();

        $response = $this->actingAs($setup['user'])
            ->get(route('documents.pdf', $setup['internship']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_download_docx_logbook(): void
    {
        $setup = $this->createInternshipWithActivities();

        $response = $this->actingAs($setup['user'])
            ->get(route('documents.docx', $setup['internship']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_can_download_docx_with_date_filter(): void
    {
        $setup = $this->createInternshipWithActivities();

        $response = $this->actingAs($setup['user'])
            ->get(route('documents.docx', [
                'internship' => $setup['internship'],
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'week' => 1,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_can_download_pdf_with_date_and_week_filter(): void
    {
        $setup = $this->createInternshipWithActivities();

        $response = $this->actingAs($setup['user'])
            ->get(route('documents.pdf', [
                'internship' => $setup['internship'],
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'week' => 1,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_get_available_weeks_from_document_service(): void
    {
        $setup = $this->createInternshipWithActivities();
        $service = app(\App\Services\DocumentService::class);

        $weeks = $service->getAvailableWeeks($setup['internship']);

        $this->assertIsArray($weeks);
        $this->assertNotEmpty($weeks);
        $this->assertArrayHasKey('week_number', $weeks[0]);
        $this->assertArrayHasKey('start_date', $weeks[0]);
        $this->assertArrayHasKey('end_date', $weeks[0]);
        $this->assertArrayHasKey('label', $weeks[0]);
    }

    public function test_can_view_summary_report(): void
    {
        $setup = $this->createInternshipWithActivities();

        $response = $this->actingAs($setup['user'])
            ->get(route('documents.summary', $setup['internship']));

        $response->assertStatus(200);
        $response->assertSee('Rekap Aktivitas & Laporan Akhir PKL', false);
        $response->assertSee('PT Karya Cipta Digital');
    }

    public function test_can_download_docx_and_pdf_with_custom_campus_logo(): void
    {
        Storage::fake('public');
        $setup = $this->createInternshipWithActivities();

        $file = UploadedFile::fake()->image('custom_campus_logo.png', 120, 120);
        $path = $file->store('logos', 'public');
        $setup['internship']->update(['campus_logo' => $path]);

        // Download DOCX dengan logo kustom
        $responseDocx = $this->actingAs($setup['user'])
            ->get(route('documents.docx', $setup['internship']));
        $responseDocx->assertStatus(200);
        $responseDocx->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // Download PDF dengan logo kustom
        $responsePdf = $this->actingAs($setup['user'])
            ->get(route('documents.pdf', $setup['internship']));
        $responsePdf->assertStatus(200);
        $responsePdf->assertHeader('content-type', 'application/pdf');
    }
}

