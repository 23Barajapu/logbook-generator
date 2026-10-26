<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocxImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Student $student;
    protected Internship $internship;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create([
            'name' => 'Baraja Putra',
            'role' => 'mahasiswa',
        ]);

        $this->student = Student::create([
            'user_id' => $this->user->id,
            'nim' => '10601006',
            'study_program' => 'D4 TRPL',
            'batch_year' => 2026,
            'phone_number' => '081234567890',
        ]);

        $company = Company::create([
            'company_name' => 'PT Dwidasa Samsara Indonesia',
            'address' => 'Bandung',
            'field_supervisor' => 'Cecep Saefudin',
            'field_supervisor_position' => 'Leader QC',
        ]);

        $this->internship = Internship::create([
            'student_id' => $this->student->id,
            'company_id' => $company->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'aktif',
        ]);
    }

    public function test_student_can_view_import_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('mahasiswa.activities.import'));

        $response->assertStatus(200);
        $response->assertSee('Import Aktivitas dari File Word (.docx)');
        $response->assertSee('Ekstrak');
    }

    public function test_student_can_upload_and_parse_real_docx(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $this->assertFileExists($realDocxPath);

        $file = new UploadedFile(
            $realDocxPath,
            'BUKU AKTIVITAS HARIAN ALL DAY.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $response->assertRedirect(route('mahasiswa.activities.import.preview'));
        $this->assertTrue(session()->has('pending_docx_import'));

        $importData = session('pending_docx_import');
        $this->assertCount(13, $importData['activities']);
        $this->assertEquals(1, $importData['activities'][0]['day_number']);
        $this->assertEquals('2026-08-03', $importData['activities'][0]['activity_date']);
        $this->assertEquals('QC', $importData['activities'][0]['work_field']);
    }

    public function test_student_can_view_preview_page(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $file = new UploadedFile(
            $realDocxPath,
            'BUKU AKTIVITAS HARIAN ALL DAY.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $response = $this->actingAs($this->user)->get(route('mahasiswa.activities.import.preview'));

        $response->assertStatus(200);
        $response->assertSee('Pratinjau Hasil Ekstraksi Logbook');
        $response->assertSee('Simpan ke Logbook (Status Draft)');
        $response->assertSee('Hari Ke:');
    }

    public function test_student_can_confirm_import_and_save_to_database(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $file = new UploadedFile(
            $realDocxPath,
            'BUKU AKTIVITAS HARIAN ALL DAY.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $importData = session('pending_docx_import');
        $token = $importData['token'];
        $activities = $importData['activities'];

        $postPayload = [
            'token' => $token,
            'activities' => $activities,
        ];

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.confirm'), $postPayload);

        $response->assertRedirect(route('mahasiswa.activities'));
        $response->assertSessionHas('success');

        // Verify activities in database
        $this->assertEquals(13, Activity::where('internship_id', $this->internship->id)->count());

        $day1 = Activity::where('internship_id', $this->internship->id)->where('day_number', 1)->first();
        $this->assertNotNull($day1);
        $this->assertEquals('Draft', $day1->status);
        $this->assertEquals('2026-08-03', $day1->activity_date->format('Y-m-d'));
        $this->assertStringContainsString('setup dengan menginstall aplikasi', $day1->activities);

        // Day 1 has 1 image attachment extracted
        $day1Attachments = ActivityAttachment::where('activity_id', $day1->id)->get();
        $this->assertCount(1, $day1Attachments);
        $this->assertTrue(Storage::disk('public')->exists($day1Attachments->first()->file_path));

        // Session should be cleared
        $this->assertFalse(session()->has('pending_docx_import'));
    }

    public function test_conflict_handling_overwrite_and_skip(): void
    {
        // Pre-create existing activity for day 1
        $existingDay1 = Activity::create([
            'internship_id' => $this->internship->id,
            'day_number' => 1,
            'activity_date' => '2026-08-01',
            'work_field' => 'Old Field',
            'activities' => 'Old Activity Content',
            'tools_used' => 'Old Tools',
            'problems' => 'Old Problems',
            'solutions' => 'Old Solutions',
            'status' => 'Disetujui',
        ]);

        $token = 'test_token';
        Storage::disk('public')->makeDirectory('temp_imports/' . $token);

        $payload = [
            'token' => $token,
            'activities' => [
                [
                    'selected' => '1',
                    'day_number' => 1,
                    'activity_date' => '2026-08-03',
                    'work_field' => 'Updated Field',
                    'activities' => 'Updated Activities Content',
                    'tools_used' => 'Updated Tools',
                    'problems' => 'Updated Problems',
                    'solutions' => 'Updated Solutions',
                    'conflict_action' => 'overwrite',
                    'images' => [],
                ],
                [
                    'selected' => '1',
                    'day_number' => 2,
                    'activity_date' => '2026-08-04',
                    'work_field' => 'New Field Day 2',
                    'activities' => 'New Activities Day 2',
                    'tools_used' => 'New Tools',
                    'problems' => 'None',
                    'solutions' => 'None',
                    'conflict_action' => 'overwrite',
                    'images' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.confirm'), $payload);
        $response->assertRedirect(route('mahasiswa.activities'));

        // Existing day 1 should be updated and reset to Draft
        $existingDay1->refresh();
        $this->assertEquals('Updated Field', $existingDay1->work_field);
        $this->assertEquals('Updated Activities Content', $existingDay1->activities);
        $this->assertEquals('Draft', $existingDay1->status);

        // Day 2 should be created
        $this->assertDatabaseHas('activities', [
            'internship_id' => $this->internship->id,
            'day_number' => 2,
            'work_field' => 'New Field Day 2',
        ]);
    }

    public function test_cancel_import_cleans_up_session_and_directory(): void
    {
        $token = 'test_cancel_token';
        Storage::disk('public')->makeDirectory('temp_imports/' . $token);
        Storage::disk('public')->put('temp_imports/' . $token . '/dummy.png', 'fake image');

        session(['pending_docx_import' => ['token' => $token, 'activities' => []]]);

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.cancel'), [
            'token' => $token,
        ]);

        $response->assertRedirect(route('mahasiswa.activities'));
        $this->assertFalse(session()->has('pending_docx_import'));
        $this->assertFalse(Storage::disk('public')->exists('temp_imports/' . $token . '/dummy.png'));
    }
}
